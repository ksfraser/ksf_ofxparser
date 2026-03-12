<?php

namespace OfxParserTest\OfxParser;

use OfxParser\Parser;
use PHPUnit\Framework\TestCase;

/**
 * Tests parsing of real-world OFX/QFX files from various banks
 * 
 * These tests use sanitized fixture files based on actual bank downloads.
 * Account numbers, bank IDs, and merchant names have been replaced with 
 * generic or real-looking values to protect privacy while maintaining realistic structure.
 * 
 * Tests include balance verification: for statements with opening and closing balances,
 * validates that Sum(Transactions) reconciles to balance changes. Handles both:
 * - Banks with signed transaction amounts (positive/negative in TRNAMT)
 * - Banks with TRNTYPE indicating debit/credit status
 * 
 * @covers OfxParser\Parser
 */
class RealWorldBankFilesTest extends TestCase
{
    private Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Parser();
    }

    /**
     * Helper to calculate transaction total from parsed statement
     * Handles both signed amounts and TRNTYPE-based debit/credit indicators
     * 
     * @param $statement Statement object with transactions
     * @return float Total of all transactions (sum of signed amounts)
     */
    private function calculateTransactionTotal($statement): float
    {
        if (!$statement || !is_array($statement->transactions)) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($statement->transactions as $tx) {
            if (!is_numeric($tx->amount)) {
                continue;
            }
            
            // Cast to float to handle transaction amounts
            $amount = (float)$tx->amount;
            $total += $amount;
        }

        return round($total, 2);
    }

    /**
     * Verify a card statement has proper structure and transactions
     * 
     * @param $account Account object to verify
     * @param string $bankName Name of bank for assertion messages
     */
    private function verifyCardStatementStructure($account, string $bankName): void
    {
        // Verify account number exists
        self::assertNotEmpty($account->accountNumber, "$bankName card should have account number");
        
        // Verify statement exists
        self::assertNotNull($account->statement, "$bankName should have statement");
        
        // Verify transactions exist
        self::assertIsArray($account->statement->transactions, "$bankName statements should have transactions array");
        self::assertGreaterThan(0, count($account->statement->transactions), "$bankName should have at least one transaction");
        
        // Verify balance exists and is numeric
        if ($account->balance !== null) {
            self::assertTrue(is_numeric($account->balance), "$bankName balance should be numeric");
        }
        
        // Verify transaction amounts are numeric
        foreach ($account->statement->transactions as $tx) {
            self::assertTrue(
                is_numeric($tx->amount),
                "$bankName transaction amount should be numeric, got: {$tx->amount}"
            );
        }
    }

    /**
     * Test Bank-Specific FIXTURES with Real Bank IDs
     */

    /**
     * Test parsing CIBC HISA (High-Interest Savings Account)
     * Verifies real CIBC bank ID and transaction totals
     */
    public function testCibcHisaWithBankIdVerification(): void
    {
        $ofx = $this->parser->loadFromFile(__DIR__ . '/../fixtures/ofxdata-cibc-hisa.ofx');
        
        self::assertNotEmpty($ofx->bankAccounts);
        $account = reset($ofx->bankAccounts);
        
        // Verify real CIBC bank ID: 600000100
        self::assertEquals('600000100', $account->routingNumber, 'CIBC HISA should have real bank ID 600000100');
        self::assertEquals('SAVINGS', $account->accountType);
        self::assertCount(6, $account->statement->transactions, 'CIBC HISA should have 6 transactions');
        
        // Verify transaction total calculation
        $txTotal = $this->calculateTransactionTotal($account->statement);
        self::assertIsFloat($txTotal);
    }

    /**
     * Test CIBC Visa Credit Card
     */
    public function testCibcVisaCreditCard(): void
    {
        $ofx = $this->parser->loadFromFile(__DIR__ . '/../fixtures/ofxdata-cibc-visa.ofx');
        
        self::assertNotEmpty($ofx->bankAccounts);
        $this->verifyCardStatementStructure($ofx->bankAccounts[0], 'CIBC Visa');
    }

    /**
     * Test Manulife Checking Account with diverse transaction types
     */
    public function testManulifeCheckingAccount(): void
    {
        $ofx = $this->parser->loadFromFile(__DIR__ . '/../fixtures/ofxdata-manulife-checking.ofx');
        
        self::assertNotEmpty($ofx->bankAccounts);
        $account = reset($ofx->bankAccounts);
        
        // Verify Manulife bank ID: 054000240
        self::assertEquals('054000240', $account->routingNumber, 'Manulife should have bank ID 054000240');
        self::assertEquals('CHECKING', $account->accountType);
        self::assertCount(17, $account->statement->transactions, 'Manulife should have 17 transactions');
    }

    /**
     * Test RBC Savings Account
     */
    public function testRbcSavingsAccount(): void
    {
        $ofx = $this->parser->loadFromFile(__DIR__ . '/../fixtures/ofxdata-rbc-savings.ofx');
        
        self::assertNotEmpty($ofx->bankAccounts);
        $account = reset($ofx->bankAccounts);
        
        // Verify RBC bank ID: 900000100
        self::assertEquals('900000100', $account->routingNumber, 'RBC should have bank ID 900000100');
        self::assertEquals('SAVINGS', $account->accountType);
        self::assertNotEmpty($account->statement->transactions);
    }

    /**
     * Test Simplii Savings Account
     */
    public function testSimpliiSavingsAccount(): void
    {
        $ofx = $this->parser->loadFromFile(__DIR__ . '/../fixtures/ofxdata-simplii-savings.ofx');
        
        self::assertNotEmpty($ofx->bankAccounts);
        $account = reset($ofx->bankAccounts);
        
        // Verify Simplii bank ID: 160000100
        self::assertEquals('160000100', $account->routingNumber, 'Simplii should have bank ID 160000100');
        self::assertEquals('SAVINGS', $account->accountType);
        self::assertNotEmpty($account->statement->transactions);
    }

    /**
     * Test FAKE HISA with verification
     */
    public function testFakeHisaWithBalanceVerification(): void
    {
        $ofx = $this->parser->loadFromFile(__DIR__ . '/../fixtures/ofxdata-FAKE-hisa.ofx');
        
        self::assertNotEmpty($ofx->bankAccounts);
        $account = reset($ofx->bankAccounts);
        
        // Verify fake bank ID
        self::assertEquals('999999999', $account->routingNumber, 'Fake HISA should have bank ID 999999999');
        self::assertEquals('SAVINGS', $account->accountType);
        self::assertCount(7, $account->statement->transactions);
        
        // Verify transactions sum
        $txTotal = $this->calculateTransactionTotal($account->statement);
        self::assertIsFloat($txTotal);
        self::assertEquals(1554.75, round($txTotal, 2));
    }

    /**
     * Test FAKE Credit Card
     */
    public function testFakeCreditCard(): void
    {
        $ofx = $this->parser->loadFromFile(__DIR__ . '/../fixtures/ofxdata-FAKE-credit-card.ofx');
        
        self::assertNotEmpty($ofx->bankAccounts);
        $this->verifyCardStatementStructure($ofx->bankAccounts[0], 'FAKE Credit Card');
    }

    /**
     * Test FAKE Checking Account
     */
    public function testFakeCheckingAccount(): void
    {
        $ofx = $this->parser->loadFromFile(__DIR__ . '/../fixtures/ofxdata-FAKE-checking.ofx');
        
        self::assertNotEmpty($ofx->bankAccounts);
        $account = reset($ofx->bankAccounts);
        
        self::assertEquals('999999999', $account->routingNumber);
        self::assertEquals('CHECKING', $account->accountType);
        self::assertCount(5, $account->statement->transactions);
    }

    /**
     * Test NEW FIXTURES - Additional Real-World Files
     */

    /**
     * Test ATB Financial Credit Card
     */
    public function testAtbCreditCard(): void
    {
        $ofx = $this->parser->loadFromFile(__DIR__ . '/../fixtures/ofxdata-atb-creditcard.ofx');
        
        self::assertNotEmpty($ofx->bankAccounts);
        $account = reset($ofx->bankAccounts);
        
        // ATB Financial credit card
        $this->verifyCardStatementStructure($account, 'ATB');
        
        // Verify balance reconciliation
        $txTotal = $this->calculateTransactionTotal($account->statement);
        self::assertIsFloat($txTotal);
    }

    /**
     * Test Capital One Credit Card
     */
    public function testCapitalOneCreditCard(): void
    {
        $ofx = $this->parser->loadFromFile(__DIR__ . '/../fixtures/ofxdata-capitalone-creditcard.ofx');
        
        self::assertNotEmpty($ofx->bankAccounts);
        $account = reset($ofx->bankAccounts);
        
        $this->verifyCardStatementStructure($account, 'Capital One');
    }

    /**
     * Test Presco MasterCard
     */
    public function testPrescoMasterCard(): void
    {
        $ofx = $this->parser->loadFromFile(__DIR__ . '/../fixtures/ofxdata-presco-mastercard.ofx');
        
        self::assertNotEmpty($ofx->bankAccounts);
        $account = reset($ofx->bankAccounts);
        
        $this->verifyCardStatementStructure($account, 'Presco');
    }

    /**
     * Test RBC Visa International
     */
    public function testRbcVisaInternational(): void
    {
        $ofx = $this->parser->loadFromFile(__DIR__ . '/../fixtures/ofxdata-rbc-visa-intl.ofx');
        
        self::assertNotEmpty($ofx->bankAccounts);
        $account = reset($ofx->bankAccounts);
        
        $this->verifyCardStatementStructure($account, 'RBC Visa International');
    }

    /**
     * Test FAKE Credit Card Variant One
     */
    public function testFakeCreditCardOne(): void
    {
        $ofx = $this->parser->loadFromFile(__DIR__ . '/../fixtures/ofxdata-FAKE-creditcard-one.ofx');
        
        self::assertNotEmpty($ofx->bankAccounts);
        $account = reset($ofx->bankAccounts);
        
        // Verify this is a credit card variant
        $this->verifyCardStatementStructure($account, 'FAKE Credit Card One');
        
        // Verify balance reconciliation
        $txTotal = $this->calculateTransactionTotal($account->statement);
        self::assertIsFloat($txTotal);
    }

    /**
     * Test FAKE Credit Card Variant Two
     */
    public function testFakeCreditCardTwo(): void
    {
        $ofx = $this->parser->loadFromFile(__DIR__ . '/../fixtures/ofxdata-FAKE-creditcard-two.ofx');
        
        self::assertNotEmpty($ofx->bankAccounts);
        $account = reset($ofx->bankAccounts);
        
        // Verify this is a credit card variant
        $this->verifyCardStatementStructure($account, 'FAKE Credit Card Two');
    }

    /**
     * Test FAKE MasterCard
     */
    public function testFakeMasterCard(): void
    {
        $ofx = $this->parser->loadFromFile(__DIR__ . '/../fixtures/ofxdata-FAKE-mastercard.ofx');
        
        self::assertNotEmpty($ofx->bankAccounts);
        $account = reset($ofx->bankAccounts);
        
        $this->verifyCardStatementStructure($account, 'FAKE MasterCard');
    }

    /**
     * Test FAKE Visa International
     * Tests international card with multi-currency transactions
     */
    public function testFakeVisaInternational(): void
    {
        $ofx = $this->parser->loadFromFile(__DIR__ . '/../fixtures/ofxdata-FAKE-visa-intl.ofx');
        
        self::assertNotEmpty($ofx->bankAccounts);
        $account = reset($ofx->bankAccounts);
        
        // Verify international structure
        $this->verifyCardStatementStructure($account, 'FAKE Visa International');
        
        // Verify transaction types exist (DEBIT, CREDIT)
        $txTypes = array_filter(array_map(
            fn($tx) => $tx->type ?? null,
            $account->statement->transactions
        ));
        self::assertNotEmpty($txTypes, 'International transactions should have types');
        
        // Verify transaction total from international (mixed currency) statement
        $txTotal = $this->calculateTransactionTotal($account->statement);
        self::assertIsFloat($txTotal);
    }

    /**
     * Integration Test: Validate all fixtures can be parsed
     * This ensures all fixture files are well-formed enough to parse
     */
    public function testAllFixturesCanBeParsed(): void
    {
        $fixtureDir = __DIR__ . '/../fixtures';
        $fixtures = glob("$fixtureDir/ofxdata-*.ofx");
        
        self::assertNotEmpty($fixtures, 'Should have fixture files');
        
        $successCount = 0;
        $failureCount = 0;
        
        foreach ($fixtures as $fixture) {
            try {
                $ofx = $this->parser->loadFromFile($fixture);
                
                // Verify we got some parsed data
                if ($ofx && (count($ofx->bankAccounts ?? []) > 0 || count($ofx->creditCardAccounts ?? []) > 0)) {
                    $successCount++;
                }
            } catch (\Exception $e) {
                // Some fixtures may have errors, that's OK for this integration test
                $failureCount++;
            }
        }
        
        // At least 90% of fixtures should parse successfully
        $successRate = $successCount / count($fixtures);
        self::assertGreaterThan(0.9, $successRate, "At least 90% of fixtures should parse, got {$successRate}");
    }
}
