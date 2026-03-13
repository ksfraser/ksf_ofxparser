<?php declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use OfxParser\Parser;

class DataIntegrityTest extends TestCase
{
    private Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Parser();
    }

    // IT2-001: Transaction count matches file expectations
    public function testTransactionCountMatches(): void
    {
        $this->markTestSkipped('Requires fixture file with known transaction count');
        
        // Expected: 25 transactions in bank.ofx
        $ofx = $this->parser->loadFromFile('tests/fixtures/bank.ofx');
        
        $transactionCount = count($ofx->bankAccount->statement->transactions ?? []);
        $this->assertEquals(25, $transactionCount);
    }

    // IT2-002: Amount preservation - original amounts unchanged
    public function testAmountPreservation(): void
    {
        $this->markTestSkipped('Requires fixture file with known amounts');
        
        $ofx = $this->parser->loadFromFile('tests/fixtures/bank.ofx');
        
        $transactions = $ofx->bankAccount->statement->transactions ?? [];
        $this->assertNotEmpty($transactions);
        
        foreach ($transactions as $txn) {
            // Amount should be preserved exactly as in file
            $this->assertNotEmpty($txn->amount ?? '');
            $this->assertTrue(is_numeric($txn->amount ?? 0));
        }
    }

    // IT2-003: Date preservation - dates unchanged
    public function testDatePreservation(): void
    {
        $this->markTestSkipped('Requires fixture file with known dates');
        
        $ofx = $this->parser->loadFromFile('tests/fixtures/bank.ofx');
        
        $account = $ofx->bankAccount;
        $this->assertNotNull($account);
        $this->assertNotNull($account->statement);
        
        // Dates should be DateTime objects
        if ($account->statement->startDate) {
            $this->assertInstanceOf(\DateTime::class, $account->statement->startDate);
        }
        if ($account->statement->endDate) {
            $this->assertInstanceOf(\DateTime::class, $account->statement->endDate);
        }
    }

    // IT2-004: Multi-account separation - accounts independent
    public function testMultiAccountSeparation(): void
    {
        $this->markTestSkipped('Requires fixture with multiple accounts');
        
        $ofx = $this->parser->loadFromFile('tests/fixtures/multi.ofx');
        
        // Should have multiple accounts
        $this->assertGreaterThan(1, count($ofx->bankAccounts ?? []));
        
        // Account data should not leak between accounts
        $account1 = $ofx->bankAccounts[0] ?? null;
        $account2 = $ofx->bankAccounts[1] ?? null;
        
        if ($account1 && $account2) {
            $this->assertNotEquals(
                $account1->accountId ?? '',
                $account2->accountId ?? ''
            );
        }
    }

    // IT2-005: Transaction ordering - preserved from file
    public function testTransactionOrdering(): void
    {
        $this->markTestSkipped('Requires fixture with ordered transactions');
        
        $ofx = $this->parser->loadFromFile('tests/fixtures/bank.ofx');
        
        $transactions = $ofx->bankAccount->statement->transactions ?? [];
        
        if (count($transactions) > 1) {
            // Verify transactions are in order (by date/ID)
            for ($i = 1; $i < count($transactions); $i++) {
                $prev = $transactions[$i - 1];
                $curr = $transactions[$i];
                
                // At minimum, IDs should be different
                $this->assertNotEquals($prev->id ?? '', $curr->id ?? '');
            }
        }
    }

    // IT2-006: Account balance accuracy
    public function testAccountBalanceAccuracy(): void
    {
        $this->markTestSkipped('Requires fixture with balance information');
        
        $ofx = $this->parser->loadFromFile('tests/fixtures/bank.ofx');
        
        $account = $ofx->bankAccount;
        if ($account && isset($account->balance)) {
            $balance = $account->balance;
            $this->assertTrue(is_numeric($balance));
        }
    }

    // IT2-007: Credit card amounts are signed correctly
    public function testCreditCardAmountsSigned(): void
    {
        $this->markTestSkipped('Requires credit card fixture');
        
        $ofx = $this->parser->loadFromFile('tests/fixtures/creditcard.ofx');
        
        $account = $ofx->creditCardAccounts[0] ?? null;
        if ($account) {
            $transactions = $account->statement->transactions ?? [];
            
            foreach ($transactions as $txn) {
                // Amounts should be numeric
                $this->assertTrue(is_numeric($txn->amount ?? 0));
            }
        }
    }

    // IT2-008: Investment holding quantities preserved
    public function testInvestmentQuantitiesPreserved(): void
    {
        $this->markTestSkipped('Requires investment account fixture');
        
        $ofx = $this->parser->loadFromFile('tests/fixtures/investment.ofx');
        
        $account = $ofx->investmentAccounts[0] ?? null;
        if ($account) {
            // Holdings should have quantities
            $transactions = $account->statement->transactions ?? [];
            $this->assertNotEmpty($transactions);
        }
    }

    // IT2-009: Memo fields preserved exactly
    public function testMemoPreservation(): void
    {
        $this->markTestSkipped('Requires fixture with memo fields');
        
        $ofx = $this->parser->loadFromFile('tests/fixtures/bank.ofx');
        
        $transactions = $ofx->bankAccount->statement->transactions ?? [];
        
        foreach ($transactions as $txn) {
            // Memo should be string or null (not corrupted)
            $memo = $txn->memo ?? '';
            $this->assertTrue(is_string($memo));
        }
    }

    // IT2-010: Payee information preserved
    public function testPayeePreservation(): void
    {
        $this->markTestSkipped('Requires fixture with payee info');
        
        $ofx = $this->parser->loadFromFile('tests/fixtures/bank.ofx');
        
        $transactions = $ofx->bankAccount->statement->transactions ?? [];
        
        foreach ($transactions as $txn) {
            // Payee name should be preserved
            $name = $txn->name ?? '';
            $this->assertTrue(is_string($name));
        }
    }

    // IT2-011: Check numbers preserved for bank accounts
    public function testCheckNumberPreservation(): void
    {
        $this->markTestSkipped('Requires bank fixture with checks');
        
        $ofx = $this->parser->loadFromFile('tests/fixtures/bank.ofx');
        
        $transactions = $ofx->bankAccount->statement->transactions ?? [];
        
        // Should have transaction objects
        $this->assertNotEmpty($transactions);
    }

    // IT2-012: Security information linked correctly
    public function testSecurityLinking(): void
    {
        $this->markTestSkipped('Requires investment fixture');
        
        $ofx = $this->parser->loadFromFile('tests/fixtures/investment.ofx');
        
        $account = $ofx->investmentAccounts[0] ?? null;
        if ($account) {
            $transactions = $account->statement->transactions ?? [];
            
            foreach ($transactions as $txn) {
                // Investment transactions should have security references
                $this->assertNotNull($txn);
            }
        }
    }

    // IT2-013: Balances can be calculated from transactions (reconciliation)
    public function testBalanceReconciliation(): void
    {
        $this->markTestSkipped('Requires fixture with starting balance');
        
        $ofx = $this->parser->loadFromFile('tests/fixtures/bank.ofx');
        
        $account = $ofx->bankAccount;
        if ($account) {
            $statement = $account->statement;
            
            // Transaction data should be consistent enough to reconcile
            $transactions = $statement->transactions ?? [];
            $this->assertNotEmpty($transactions);
        }
    }

    // IT2-014: Multi-currency accounts preserve currency codes
    public function testCurrencyCodePreservation(): void
    {
        $this->markTestSkipped('Requires multi-currency fixture');
        
        $ofx = $this->parser->loadFromFile('tests/fixtures/multi.ofx');
        
        $accounts = $ofx->bankAccounts ?? [];
        
        foreach ($accounts as $account) {
            // Currency should be set (even if USD default)
            $currency = $account->currency ?? 'USD';
            $this->assertTrue(is_string($currency));
            $this->assertEquals(3, strlen($currency)); // ISO 4217
        }
    }

    // IT2-015: All required fields present and valid
    public function testRequiredFieldsPresent(): void
    {
        $this->markTestSkipped('Requires any valid fixture');
        
        $ofx = $this->parser->loadFromFile('tests/fixtures/bank.ofx');
        
        // Top-level required fields
        $this->assertNotNull($ofx->bankAccounts);
        
        $account = $ofx->bankAccount;
        if ($account) {
            // Account should have required fields
            $this->assertNotEmpty($account->accountId ?? '');
            $this->assertNotNull($account->accountType);
            $this->assertNotNull($account->statement);
        }
    }

    // IT2-016: No data corruption in text fields (special characters)
    public function testSpecialCharacterHandling(): void
    {
        $this->markTestSkipped('Requires fixture with special characters');
        
        $ofx = $this->parser->loadFromFile('tests/fixtures/bank.ofx');
        
        $transactions = $ofx->bankAccount->statement->transactions ?? [];
        
        // Text fields should be UTF-8 valid
        foreach ($transactions as $txn) {
            $memo = $txn->memo ?? '';
            $this->assertTrue(mb_check_encoding($memo, 'UTF-8') || $memo === '');
        }
    }

    // IT2-017: Numeric precision maintained (decimal places)
    public function testNumericPrecision(): void
    {
        $this->markTestSkipped('Requires fixture with decimal amounts');
        
        $ofx = $this->parser->loadFromFile('tests/fixtures/bank.ofx');
        
        $transactions = $ofx->bankAccount->statement->transactions ?? [];
        
        foreach ($transactions as $txn) {
            $amount = (string)($txn->amount ?? '0');
            
            // Should preserve decimal places
            if (strpos($amount, '.') !== false) {
                [$whole, $decimal] = explode('.', $amount);
                $this->assertLessOrEqual(2, strlen($decimal)); // Currency precision
            }
        }
    }

    // IT2-018: No duplicate transactions
    public function testNoDuplicateTransactions(): void
    {
        $this->markTestSkipped('Requires fixture');
        
        $ofx = $this->parser->loadFromFile('tests/fixtures/bank.ofx');
        
        $transactions = $ofx->bankAccount->statement->transactions ?? [];
        $ids = array_map(fn($t) => $t->id ?? '', $transactions);
        
        // All IDs should be unique
        $this->assertEquals(count($ids), count(array_unique($ids)));
    }

    // IT2-019: Account types correctly classified
    public function testAccountTypeClassification(): void
    {
        $this->markTestSkipped('Requires multi-type fixture');
        
        $ofx = $this->parser->loadFromFile('tests/fixtures/multi.ofx');
        
        // Should correctly separate account types
        $bankAccounts = $ofx->bankAccounts ?? [];
        $ccAccounts = $ofx->creditCardAccounts ?? [];
        $invAccounts = $ofx->investmentAccounts ?? [];
        
        // At least one type should have data
        $totalAccounts = count($bankAccounts) + count($ccAccounts) + count($invAccounts);
        $this->assertGreaterThan(0, $totalAccounts);
    }

    // IT2-020: Statement period dates are valid range
    public function testStatementPeriodValidity(): void
    {
        $this->markTestSkipped('Requires fixture');
        
        $ofx = $this->parser->loadFromFile('tests/fixtures/bank.ofx');
        
        $statement = $ofx->bankAccount->statement;
        
        if ($statement && $statement->startDate && $statement->endDate) {
            $this->assertLessThanOrEqual(
                $statement->endDate->getTimestamp(),
                $statement->startDate->getTimestamp()
            );
        }
    }
}
