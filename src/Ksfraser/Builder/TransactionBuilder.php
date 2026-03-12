<?php declare(strict_types=1);

namespace OfxParser\Builder;

use OfxParser\Entities\Transaction;
use OfxParser\Extraction\FieldExtractor;
use OfxParser\Recovery\RecoveryContext;
use OfxParser\Metrics\ParsingMetrics;
use OfxParser\Exceptions\Transaction\CorruptTransactionException;
use OfxParser\Exceptions\Transaction\IncompleteTransactionException;
use OfxParser\Exceptions\Field\RequiredFieldMissingException;

/**
 * Defensive transaction builder with error recovery
 */
class TransactionBuilder
{
    private FieldExtractor $fieldExtractor;
    private RecoveryContext $recoveryContext;
    private ParsingMetrics $metrics;
    
    public function __construct(
        FieldExtractor $fieldExtractor,
        RecoveryContext $recoveryContext,
        ParsingMetrics $metrics
    ) {
        $this->fieldExtractor = $fieldExtractor;
        $this->recoveryContext = $recoveryContext;
        $this->metrics = $metrics;
    }
    
    /**
     * Build transaction array from XML elements
     *
     * @param \SimpleXMLElement $transactions
     * @return Transaction[]
     */
    public function buildTransactions(\SimpleXMLElement $transactions): array
    {
        $result = [];
        $transactionNumber = 0;
        
        foreach ($transactions as $t) {
            $transactionNumber++;
            
            try {
                $transaction = $this->buildSingleTransaction($t, $transactionNumber);
                
                if ($transaction !== null) {
                    $result[] = $transaction;
                    $this->metrics->incrementSuccessfulTransaction();
                }
            } catch (CorruptTransactionException $e) {
                // Transaction is corrupt - use recovery strategy
                $this->metrics->incrementCorruptTransaction();
                $this->metrics->logCorruptTransaction(
                    $transactionNumber,
                    $e->getMessage(),
                    $this->getXmlSnippet($t)
                );
                
                $recoveredTransaction = $this->recoveryContext->recover($e, $t, $transactionNumber);
                
                // null means skip transaction
                if ($recoveredTransaction !== null) {
                    $result[] = $recoveredTransaction;
                }
                
            } catch (IncompleteTransactionException $e) {
                // Transaction is incomplete but usable
                $this->metrics->incrementIncompleteTransaction();
                $this->metrics->logIncompleteTransaction(
                    $transactionNumber,
                    $e->getContext()['missing_fields'] ?? []
                );
                
                $recoveredTransaction = $this->recoveryContext->recover($e, $t, $transactionNumber);
                
                if ($recoveredTransaction !== null) {
                    $result[] = $recoveredTransaction;
                }
                
            } catch (\Throwable $e) {
                // Unexpected error
                $this->metrics->incrementUnexpectedError();
                $this->metrics->logUnexpectedError(
                    $transactionNumber,
                    $e->getMessage(),
                    $e->getTraceAsString()
                );
                
                // Try to continue parsing
                continue;
            }
        }
        
        return $result;
    }
    
    /**
     * Build a single transaction with defensive parsing
     *
     * @param \SimpleXMLElement $element
     * @param int $transactionNumber
     * @return Transaction|null
     * @throws CorruptTransactionException
     * @throws IncompleteTransactionException
     */
    private function buildSingleTransaction(\SimpleXMLElement $element, int $transactionNumber): ?Transaction
    {
        $transaction = new Transaction();
        $missingOptionalFields = [];
        
        try {
            // OFX 2.2 Required Fields per spec
            $transaction->type = $this->fieldExtractor->extractRequired($element, 'TRNTYPE');
            $transaction->date = $this->fieldExtractor->extractRequiredDate($element, 'DTPOSTED');
            $transaction->amount = $this->fieldExtractor->extractRequiredAmount($element, 'TRNAMT');
            $transaction->uniqueId = $this->fieldExtractor->extractRequired($element, 'FITID');
            
        } catch (RequiredFieldMissingException $e) {
            // Missing required field = corrupt transaction
            throw new CorruptTransactionException(
                "Transaction #{$transactionNumber} missing required field: {$e->getMessage()}",
                [
                    'transaction_number' => $transactionNumber,
                    'field_name' => $e->getContext()['field_name'] ?? 'unknown',
                    'original_exception' => $e
                ]
            );
        }
        
        // OFX 2.2 Optional Fields
        try {
            $transaction->name = $this->fieldExtractor->extractOptional($element, 'NAME', '');
            if ($transaction->name === '') {
                $missingOptionalFields[] = 'NAME';
            }
        } catch (\Exception $e) {
            $missingOptionalFields[] = 'NAME';
            $transaction->name = '';
        }
        
        try {
            // Handle multiple MEMO tags
            $memo = [];
            if (isset($element->MEMO)) {
                foreach ($element->MEMO as $m) {
                    $memo[] = (string)$m;
                }
            }
            $transaction->memo = implode(' ', $memo);
            if ($transaction->memo === '') {
                $missingOptionalFields[] = 'MEMO';
            }
        } catch (\Exception $e) {
            $missingOptionalFields[] = 'MEMO';
            $transaction->memo = '';
        }
        
        try {
            $transaction->sic = $this->fieldExtractor->extractOptional($element, 'SIC', null);
            if ($transaction->sic === null) {
                $missingOptionalFields[] = 'SIC';
            }
        } catch (\Exception $e) {
            $missingOptionalFields[] = 'SIC';
            $transaction->sic = null;
        }
        
        try {
            $transaction->checkNumber = $this->fieldExtractor->extractOptional($element, 'CHECKNUM', null);
            if ($transaction->checkNumber === null) {
                $missingOptionalFields[] = 'CHECKNUM';
            }
        } catch (\Exception $e) {
            $missingOptionalFields[] = 'CHECKNUM';
            $transaction->checkNumber = null;
        }
        
        try {
            $transaction->refNumber = $this->fieldExtractor->extractOptional($element, 'REFNUM', null);
            if ($transaction->refNumber === null) {
                $missingOptionalFields[] = 'REFNUM';
            }
        } catch (\Exception $e) {
            $missingOptionalFields[] = 'REFNUM';
            $transaction->refNumber = null;
        }
        
        // DTUSER - User initiated date
        try {
            $dateStr = $this->fieldExtractor->extractOptional($element, 'DTUSER', null);
            if ($dateStr !== null && $dateStr !== '') {
                $transaction->userInitiatedDate = $this->fieldExtractor->extractOptionalDate($element, 'DTUSER', null);
            } else {
                $missingOptionalFields[] = 'DTUSER';
            }
        } catch (\Exception $e) {
            $missingOptionalFields[] = 'DTUSER';
            $transaction->userInitiatedDate = null;
        }
        
        // EXTDNAME - Extended name
        try {
            $transaction->nameExtended = $this->fieldExtractor->extractOptional($element, 'EXTDNAME', null);
            if ($transaction->nameExtended === null) {
                $missingOptionalFields[] = 'EXTDNAME';
            }
        } catch (\Exception $e) {
            $missingOptionalFields[] = 'EXTDNAME';
            $transaction->nameExtended = null;
        }
        
        // PAYEEID - Payee ID
        try {
            $transaction->payeeId = $this->fieldExtractor->extractOptional($element, 'PAYEEID', null);
            if ($transaction->payeeId === null) {
                $missingOptionalFields[] = 'PAYEEID';
            }
        } catch (\Exception $e) {
            $missingOptionalFields[] = 'PAYEEID';
            $transaction->payeeId = null;
        }
        
        // PAYEE (optional but complex)
        if (isset($element->PAYEE)) {
            try {
                $transaction->payee = $this->buildPayee($element->PAYEE);
            } catch (\Exception $e) {
                $missingOptionalFields[] = 'PAYEE';
                $transaction->payee = null;
            }
        } else {
            $missingOptionalFields[] = 'PAYEE';
        }
        
        // BANKACCTTO (optional but complex)
        if (isset($element->BANKACCTTO)) {
            try {
                $transaction->bankAccountTo = $this->buildBankAccountTo($element->BANKACCTTO);
            } catch (\Exception $e) {
                $missingOptionalFields[] = 'BANKACCTTO';
                $transaction->bankAccountTo = null;
            }
        } else {
            $missingOptionalFields[] = 'BANKACCTTO';
        }
        
        // CCACCTTO (optional but complex)
        if (isset($element->CCACCTTO)) {
            try {
                $transaction->cardAccountTo = $this->buildCardAccountTo($element->CCACCTTO);
            } catch (\Exception $e) {
                $missingOptionalFields[] = 'CCACCTTO';
                $transaction->cardAccountTo = null;
            }
        } else {
            $missingOptionalFields[] = 'CCACCTTO';
        }
        
        // Track missing optional fields for metrics (but don't fail - they're OPTIONAL!)
        foreach ($missingOptionalFields as $fieldName) {
            $this->metrics->incrementMissingOptionalField($fieldName);
        }
        
        return $transaction;
    }
    
    /**
     * Build BANKACCTTO entity
     *
     * @param \SimpleXMLElement $element
     * @return \OfxParser\Entities\BankAccount
     */
    private function buildBankAccountTo(\SimpleXMLElement $element): \OfxParser\Entities\BankAccount
    {
        $bankAccount = new \OfxParser\Entities\BankAccount();
        
        $bankAccount->routingNumber = (string) $element->BANKID;
        $bankAccount->agencyNumber = (string) $element->BRANCHID;
        $bankAccount->accountNumber = (string) $element->ACCTID;
        $bankAccount->accountType = (string) $element->ACCTTYPE;
        
        // Remove other attrs as they don't apply to BANKACCTTO
        unset($bankAccount->balance, $bankAccount->balanceDate, $bankAccount->statement, $bankAccount->transactionUid);
        
        return $bankAccount;
    }
    
    /**
     * Build PAYEE entity
     *
     * @param \SimpleXMLElement $element
     * @return \OfxParser\Entities\Payee
     */
    private function buildPayee(\SimpleXMLElement $element): \OfxParser\Entities\Payee
    {
        $payee = new \OfxParser\Entities\Payee();
        
        $payee->name = (string) $element->NAME;
        
        // Build address array
        $address = [];
        if ((string) $element->ADDR1) $address[] = (string) $element->ADDR1;
        if ((string) $element->ADDR2) $address[] = (string) $element->ADDR2;
        if ((string) $element->ADDR3) $address[] = (string) $element->ADDR3;
        if (count($address) > 0) $payee->address = $address;
        
        $payee->city = (string) $element->CITY;
        $payee->state = (string) $element->STATE;
        $payee->postalCode = (string) $element->POSTALCODE;
        $payee->country = (string) $element->COUNTRY;
        $payee->phone = (string) $element->PHONE;
        
        return $payee;
    }
    
    /**
     * Build CCACCTTO entity
     *
     * @param \SimpleXMLElement $element
     * @return \OfxParser\Entities\BankAccount
     */
    private function buildCardAccountTo(\SimpleXMLElement $element): \OfxParser\Entities\BankAccount
    {
        $cardAccount = new \OfxParser\Entities\BankAccount();
        
        $cardAccount->accountNumber = (string) $element->ACCTID;
        
        // Remove other attrs as they don't apply to CCACCTTO
        unset($cardAccount->routingNumber, $cardAccount->agencyNumber, $cardAccount->accountType, 
              $cardAccount->balance, $cardAccount->balanceDate, $cardAccount->statement, $cardAccount->transactionUid);
        
        return $cardAccount;
    }
    
    /**
     * Get XML snippet for logging
     *
     * @param \SimpleXMLElement $element
     * @return string
     */
    private function getXmlSnippet(\SimpleXMLElement $element): string
    {
        try {
            $xml = $element->asXML();
            return $xml !== false ? substr($xml, 0, 500) : 'Unable to get XML';
        } catch (\Exception $e) {
            return 'Unable to get XML: ' . $e->getMessage();
        }
    }
}
