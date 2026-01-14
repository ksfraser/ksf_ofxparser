# Defensive OFX Parsing Architecture - Design Document
**Date:** January 13, 2026
**Purpose:** Implement defensive parsing with SRP exception handlers

---

## OFX Specification - Required vs Optional Fields

### Required Fields (per OFX 2.2 spec):
**Transaction (STMTTRN):**
- TRNTYPE (required) - Transaction type
- DTPOSTED (required) - Post date
- TRNAMT (required) - Amount
- FITID (required) - Unique transaction ID

**BankAccount (STMTRS):**
- CURDEF (required) - Currency
- BANKACCTFROM (required) - Bank account from
  - BANKID (required)
  - ACCTID (required)
  - ACCTTYPE (required)
- BANKTRANLIST (required)
  - DTSTART (required)
  - DTEND (required)

### Optional Fields:
**Transaction:**
- DTUSER, NAME, MEMO, SIC, CHECKNUM, REFNUM, EXTDNAME, PAYEEID, PAYEE, BANKACCTTO, CCACCTTO

**BankAccount:**
- LEDGERBAL (often present but technically optional)
- AVAILBAL, BRANCHID

---

## Current Issues in KSF Parser

### 1. Fatal Errors on Missing Optional Fields:
```php
// Line 299-300: No null check
$transaction->name = (string)$t->NAME;
$transaction->memo = (string)$t->MEMO;
// SimpleXML returns empty string for missing nodes, but could error on malformed XML
```

### 2. Mixed Defensive Patterns:
```php
// Good: Defensive (lines 230-231)
$bankAccount->balance = isset($statementResponse->LEDGERBAL->BALAMT) 
    ? (string)$statementResponse->LEDGERBAL->BALAMT : '';

// Bad: Not defensive (line 238)
$bankAccount->statement->currency = (string)$statementResponse->CURDEF;
```

### 3. No Transaction-Level Error Recovery:
```php
// buildTransactions() - if ONE transaction fails, entire file fails
foreach ($transactions as $t) {
    $transaction = new Transaction();
    // ... any exception here kills entire import
    $return[] = $transaction;
}
```

### 4. No Metrics/Counters:
- No tracking of successful transactions
- No tracking of incomplete transactions
- No tracking of corrupt transactions
- No visibility into parsing health

---

## Proposed Architecture

### 1. Exception Handler Hierarchy

```
OfxParser\
├── Exceptions\
│   ├── OfxParsingException.php (base)
│   ├── Field\
│   │   ├── RequiredFieldMissingException.php
│   │   ├── OptionalFieldMissingException.php
│   │   ├── InvalidFieldFormatException.php
│   │   └── InvalidFieldValueException.php
│   ├── Transaction\
│   │   ├── TransactionParsingException.php
│   │   ├── CorruptTransactionException.php
│   │   └── IncompleteTransactionException.php
│   └── Document\
│       ├── InvalidOfxStructureException.php
│       └── MalformedXmlException.php
├── Recovery\
│   ├── RecoveryStrategyInterface.php
│   ├── FieldRecovery\
│   │   ├── EmptyStringStrategy.php (SRP: return '')
│   │   ├── NullStrategy.php (SRP: return null)
│   │   ├── DefaultValueStrategy.php (SRP: return predefined default)
│   │   ├── ZeroAmountStrategy.php (SRP: return 0.00)
│   │   └── CurrentDateStrategy.php (SRP: return new DateTime())
│   ├── TransactionRecovery\
│   │   ├── SkipTransactionStrategy.php (SRP: skip and continue)
│   │   ├── PartialTransactionStrategy.php (SRP: save with nulls)
│   │   └── LogAndContinueStrategy.php (SRP: log error + continue)
│   └── RecoveryContext.php (orchestrates recovery)
└── Metrics\
    ├── ParsingMetrics.php (counters and stats)
    └── ParsingResult.php (encapsulates result + metrics)
```

### 2. Recovery Strategy Interface

```php
<?php
namespace OfxParser\Recovery;

interface RecoveryStrategyInterface
{
    /**
     * Attempt to recover from an exception
     * @param \Exception $exception The exception to recover from
     * @param array $context Additional context (field name, xml node, etc)
     * @return mixed Recovered value or null if unrecoverable
     */
    public function recover(\Exception $exception, array $context);
    
    /**
     * Can this strategy handle this exception type?
     * @param \Exception $exception
     * @return bool
     */
    public function canHandle(\Exception $exception): bool;
}
```

### 3. Field Extractor with Defensive Parsing

```php
<?php
namespace OfxParser\Extractors;

use OfxParser\Recovery\RecoveryContext;
use OfxParser\Metrics\ParsingMetrics;
use OfxParser\Exceptions\Field\RequiredFieldMissingException;

class FieldExtractor
{
    private RecoveryContext $recoveryContext;
    private ParsingMetrics $metrics;
    
    /**
     * Extract required field - throw if missing
     */
    public function extractRequired(\SimpleXMLElement $xml, string $fieldName, string $type = 'string')
    {
        if (!isset($xml->$fieldName)) {
            $this->metrics->incrementMissingRequiredField($fieldName);
            throw new RequiredFieldMissingException("Required field {$fieldName} is missing");
        }
        
        return $this->castValue($xml->$fieldName, $type);
    }
    
    /**
     * Extract optional field - use recovery strategy if missing
     */
    public function extractOptional(\SimpleXMLElement $xml, string $fieldName, string $type = 'string', $default = null)
    {
        try {
            if (!isset($xml->$fieldName)) {
                $this->metrics->incrementMissingOptionalField($fieldName);
                return $this->recoveryContext->recoverField($fieldName, $default);
            }
            
            return $this->castValue($xml->$fieldName, $type);
            
        } catch (\Exception $e) {
            $this->metrics->incrementFieldRecovery($fieldName);
            return $this->recoveryContext->recoverField($fieldName, $default, $e);
        }
    }
    
    private function castValue($value, string $type)
    {
        switch ($type) {
            case 'string': return (string)$value;
            case 'int': return (int)$value;
            case 'float': return (float)$value;
            case 'bool': return (bool)$value;
            default: return $value;
        }
    }
}
```

### 4. Transaction Builder with Recovery

```php
<?php
namespace OfxParser\Builders;

use OfxParser\Extractors\FieldExtractor;
use OfxParser\Metrics\ParsingMetrics;
use OfxParser\Entities\Transaction;
use OfxParser\Exceptions\Transaction\CorruptTransactionException;

class TransactionBuilder
{
    private FieldExtractor $fieldExtractor;
    private ParsingMetrics $metrics;
    
    /**
     * Build transactions with recovery for corrupt/incomplete entries
     */
    public function buildTransactions(\SimpleXMLElement $transactions): array
    {
        $results = [];
        $transactionNumber = 0;
        
        foreach ($transactions as $xmlTransaction) {
            $transactionNumber++;
            
            try {
                $transaction = $this->buildSingleTransaction($xmlTransaction, $transactionNumber);
                
                if ($transaction !== null) {
                    $results[] = $transaction;
                    $this->metrics->incrementSuccessfulTransaction();
                }
                
            } catch (CorruptTransactionException $e) {
                // Transaction is corrupt - log and continue
                $this->metrics->incrementCorruptTransaction();
                $this->metrics->logCorruptTransaction($transactionNumber, $e->getMessage());
                // Continue to next transaction
                continue;
                
            } catch (\Exception $e) {
                // Unexpected error - decide whether to continue or fail
                $this->metrics->incrementUnexpectedError();
                $this->metrics->logUnexpectedError($transactionNumber, $e->getMessage());
                
                // Option 1: Continue (most resilient)
                continue;
                
                // Option 2: Fail (if you want strict parsing)
                // throw $e;
            }
        }
        
        return $results;
    }
    
    private function buildSingleTransaction(\SimpleXMLElement $xml, int $number): ?Transaction
    {
        $transaction = new Transaction();
        
        try {
            // REQUIRED fields - must exist
            $transaction->type = $this->fieldExtractor->extractRequired($xml, 'TRNTYPE');
            $transaction->uniqueId = $this->fieldExtractor->extractRequired($xml, 'FITID');
            $transaction->amount = $this->fieldExtractor->extractRequired($xml, 'TRNAMT', 'float');
            $transaction->date = $this->fieldExtractor->extractRequired($xml, 'DTPOSTED', 'datetime');
            
        } catch (\Exception $e) {
            // Missing required field = corrupt transaction
            throw new CorruptTransactionException(
                "Transaction #{$number} missing required field: " . $e->getMessage(),
                $number,
                $e
            );
        }
        
        // OPTIONAL fields - use recovery strategies
        $transaction->name = $this->fieldExtractor->extractOptional($xml, 'NAME', 'string', '');
        $transaction->memo = $this->fieldExtractor->extractOptional($xml, 'MEMO', 'string', '');
        $transaction->userInitiatedDate = $this->fieldExtractor->extractOptional($xml, 'DTUSER', 'datetime', null);
        $transaction->sic = $this->fieldExtractor->extractOptional($xml, 'SIC', 'string', '');
        $transaction->checkNumber = $this->fieldExtractor->extractOptional($xml, 'CHECKNUM', 'string', '');
        $transaction->refNumber = $this->fieldExtractor->extractOptional($xml, 'REFNUM', 'string', '');
        $transaction->nameExtended = $this->fieldExtractor->extractOptional($xml, 'EXTDNAME', 'string', '');
        $transaction->payeeId = $this->fieldExtractor->extractOptional($xml, 'PAYEEID', 'string', '');
        
        // Complex optional fields
        if (isset($xml->PAYEE)) {
            try {
                $transaction->payee = $this->buildPayee($xml->PAYEE);
            } catch (\Exception $e) {
                $this->metrics->incrementIncompleteTransaction();
                $transaction->payee = null;
            }
        }
        
        return $transaction;
    }
}
```

### 5. Parsing Metrics Class

```php
<?php
namespace OfxParser\Metrics;

class ParsingMetrics
{
    private int $successfulTransactions = 0;
    private int $incompleteTransactions = 0;
    private int $corruptTransactions = 0;
    private int $unexpectedErrors = 0;
    
    private array $missingRequiredFields = [];
    private array $missingOptionalFields = [];
    private array $fieldRecoveries = [];
    
    private array $corruptTransactionLogs = [];
    private array $unexpectedErrorLogs = [];
    
    public function incrementSuccessfulTransaction(): void
    {
        $this->successfulTransactions++;
    }
    
    public function incrementIncompleteTransaction(): void
    {
        $this->incompleteTransactions++;
    }
    
    public function incrementCorruptTransaction(): void
    {
        $this->corruptTransactions++;
    }
    
    public function incrementUnexpectedError(): void
    {
        $this->unexpectedErrors++;
    }
    
    public function incrementMissingRequiredField(string $fieldName): void
    {
        if (!isset($this->missingRequiredFields[$fieldName])) {
            $this->missingRequiredFields[$fieldName] = 0;
        }
        $this->missingRequiredFields[$fieldName]++;
    }
    
    public function incrementMissingOptionalField(string $fieldName): void
    {
        if (!isset($this->missingOptionalFields[$fieldName])) {
            $this->missingOptionalFields[$fieldName] = 0;
        }
        $this->missingOptionalFields[$fieldName]++;
    }
    
    public function incrementFieldRecovery(string $fieldName): void
    {
        if (!isset($this->fieldRecoveries[$fieldName])) {
            $this->fieldRecoveries[$fieldName] = 0;
        }
        $this->fieldRecoveries[$fieldName]++;
    }
    
    public function logCorruptTransaction(int $number, string $reason): void
    {
        $this->corruptTransactionLogs[] = [
            'transaction_number' => $number,
            'reason' => $reason,
            'timestamp' => new \DateTime()
        ];
    }
    
    public function logUnexpectedError(int $number, string $error): void
    {
        $this->unexpectedErrorLogs[] = [
            'transaction_number' => $number,
            'error' => $error,
            'timestamp' => new \DateTime()
        ];
    }
    
    public function getTotalTransactions(): int
    {
        return $this->successfulTransactions + $this->incompleteTransactions + $this->corruptTransactions;
    }
    
    public function getSuccessRate(): float
    {
        $total = $this->getTotalTransactions();
        return $total > 0 ? ($this->successfulTransactions / $total) * 100 : 0.0;
    }
    
    public function toArray(): array
    {
        return [
            'summary' => [
                'total' => $this->getTotalTransactions(),
                'successful' => $this->successfulTransactions,
                'incomplete' => $this->incompleteTransactions,
                'corrupt' => $this->corruptTransactions,
                'unexpected_errors' => $this->unexpectedErrors,
                'success_rate' => round($this->getSuccessRate(), 2) . '%'
            ],
            'field_issues' => [
                'missing_required' => $this->missingRequiredFields,
                'missing_optional' => $this->missingOptionalFields,
                'recoveries' => $this->fieldRecoveries
            ],
            'logs' => [
                'corrupt_transactions' => $this->corruptTransactionLogs,
                'unexpected_errors' => $this->unexpectedErrorLogs
            ]
        ];
    }
}
```

### 6. Parsing Result Container

```php
<?php
namespace OfxParser\Metrics;

use OfxParser\Ofx;

class ParsingResult
{
    private Ofx $ofx;
    private ParsingMetrics $metrics;
    private bool $hasErrors;
    
    public function __construct(Ofx $ofx, ParsingMetrics $metrics)
    {
        $this->ofx = $ofx;
        $this->metrics = $metrics;
        $this->hasErrors = $metrics->getCorruptTransactions() > 0 || $metrics->getUnexpectedErrors() > 0;
    }
    
    public function getOfx(): Ofx
    {
        return $this->ofx;
    }
    
    public function getMetrics(): ParsingMetrics
    {
        return $this->metrics;
    }
    
    public function hasErrors(): bool
    {
        return $this->hasErrors;
    }
    
    public function isComplete(): bool
    {
        return $this->metrics->getCorruptTransactions() === 0 
            && $this->metrics->getIncompleteTransactions() === 0;
    }
    
    public function getSuccessRate(): float
    {
        return $this->metrics->getSuccessRate();
    }
}
```

---

## Implementation Plan

### Phase 1: Exception Infrastructure
1. Create exception hierarchy in `src/Ksfraser/Exceptions/`
2. Define custom exceptions for required vs optional fields
3. Define transaction-level exceptions

### Phase 2: Recovery Strategies
1. Create recovery strategy interface
2. Implement basic strategies (EmptyString, Null, Default)
3. Create RecoveryContext to orchestrate strategies

### Phase 3: Field Extraction
1. Create FieldExtractor class
2. Refactor existing field access to use extractor
3. Add required vs optional field distinction

### Phase 4: Transaction Builder
1. Create TransactionBuilder with recovery
2. Wrap each transaction in try-catch
3. Continue on corrupt transactions

### Phase 5: Metrics & Monitoring
1. Create ParsingMetrics class
2. Inject metrics into all builders/extractors
3. Create ParsingResult wrapper

### Phase 6: Integration
1. Update Parser::loadFromString to return ParsingResult
2. Update Ofx constructor to accept metrics
3. Add configuration for recovery strategies

---

## Usage Example

```php
$parser = new Parser();

// Configure recovery strategies
$parser->setFieldRecovery('NAME', new EmptyStringStrategy());
$parser->setFieldRecovery('MEMO', new EmptyStringStrategy());
$parser->setFieldRecovery('DTUSER', new NullStrategy());
$parser->setTransactionRecovery(new LogAndContinueStrategy());

// Parse with defensive handling
$result = $parser->loadFromFile('bank_statement.ofx');

// Access data
$ofx = $result->getOfx();
$transactions = $ofx->bankAccounts[0]->statement->transactions;

// Check metrics
$metrics = $result->getMetrics();
echo "Success rate: " . $metrics->getSuccessRate() . "%\n";
echo "Successful: " . $metrics->getSuccessfulTransactions() . "\n";
echo "Corrupt: " . $metrics->getCorruptTransactions() . "\n";
echo "Incomplete: " . $metrics->getIncompleteTransactions() . "\n";

// Get detailed logs
$logs = $metrics->toArray();
foreach ($logs['logs']['corrupt_transactions'] as $corrupt) {
    echo "Transaction #{$corrupt['transaction_number']}: {$corrupt['reason']}\n";
}
```

---

## Benefits

1. **Resilience**: One corrupt transaction doesn't kill entire import
2. **Visibility**: Know exactly what succeeded/failed
3. **Flexibility**: Configure recovery strategies per field
4. **SRP**: Each strategy handles one specific exception type
5. **Compliance**: Enforces OFX spec (required vs optional)
6. **Debugging**: Detailed logs of what went wrong
7. **Metrics**: Track parser health over time

---

## Next Steps

1. Review this architecture
2. Prioritize which phases to implement first
3. Create tests for exception scenarios
4. Implement Phase 1 (exceptions)
5. Gradually refactor existing code
