<?php

namespace OfxParserTest;

use OfxParser\Parser;
use PHPUnit\Framework\TestCase;

class RealWorldBankFilesTest extends TestCase
{
    /** @var string */
    private $fixturesDir;

    protected function setUp(): void
    {
        $this->fixturesDir = __DIR__ . '/fixtures';
    }

    /**
     * Parse and report details from CIBC HISA fixture
     * Expected: routingNumber=600000100 (real CIBC), 6 transactions, HISA account
     */
    public function testParseCibcHisaFixture(): void
    {
        $file = $this->fixturesDir . '/ofxdata-cibc-hisa.ofx';
        $this->assertFileExists($file);

        $parser = new Parser();
        $ofx = $parser->loadFromFile($file);
        
        $this->assertNotNull($ofx);
        $this->assertNotEmpty($ofx->bankAccounts);
        
        $account = $ofx->bankAccounts[0];
        echo "\n✓ CIBC HISA (ofxdata-cibc-hisa.ofx)";
        echo "\n  BANK: CIBC";
        echo "\n  BANKID: " . $account->routingNumber;
        echo "\n  ACCOUNT: " . $account->accountNumber . " (Type: " . $account->accountType . ")";
        echo "\n  BALANCE: " . $account->balance . " CAD";
        echo "\n  TRANSACTIONS: " . count($account->statement->transactions);
        echo "\n  PERIOD: " . $account->statement->startDate->format('Y-m-d') . 
             " to " . $account->statement->endDate->format('Y-m-d');
        
        // Verify real CIBC bank ID
        $this->assertEquals('600000100', $account->routingNumber, 'Should have real CIBC bank ID');
        $this->assertCount(6, $account->statement->transactions);
        $this->assertEquals('SAVINGS', $account->accountType);
    }

    /**
     * Parse and report details from CIBC Visa fixture
     * Expected: Credit card data with multiple transactions
     */
    public function testParseCibcVisaFixture(): void
    {
        $file = $this->fixturesDir . '/ofxdata-cibc-visa.ofx';
        $this->assertFileExists($file);

        $parser = new Parser();
        $ofx = $parser->loadFromFile($file);
        
        $this->assertNotNull($ofx);
        $this->assertNotEmpty($ofx->bankAccounts);
        
        $account = $ofx->bankAccounts[0];
        echo "\n✓ CIBC VISA (ofxdata-cibc-visa.ofx)";
        echo "\n  BANK: CIBC";
        echo "\n  ACCOUNT: " . $account->accountNumber . " (Type: CREDITLINE)";
        echo "\n  BALANCE: " . $account->balance . " CAD";
        echo "\n  TRANSACTIONS: " . count($account->statement->transactions);
        if (count($account->statement->transactions) > 0) {
            echo "\n  PERIOD: " . $account->statement->startDate->format('Y-m-d') . 
                 " to " . $account->statement->endDate->format('Y-m-d');
        }
        
        $this->assertGreaterThan(0, count($account->statement->transactions));
    }

    /**
     * Parse and report details from Manulife checking fixture
     * Expected: routingNumber=054000240 (real Manulife), 17 transactions
     */
    public function testParseManulifeCheckingFixture(): void
    {
        $file = $this->fixturesDir . '/ofxdata-manulife-checking.ofx';
        $this->assertFileExists($file);

        $parser = new Parser();
        $ofx = $parser->loadFromFile($file);
        
        $this->assertNotNull($ofx);
        $this->assertNotEmpty($ofx->bankAccounts);
        
        $account = $ofx->bankAccounts[0];
        echo "\n✓ Manulife Checking (ofxdata-manulife-checking.ofx)";
        echo "\n  BANK: Manulife";
        echo "\n  BANKID: " . $account->routingNumber;
        echo "\n  ACCOUNT: " . $account->accountNumber . " (Type: " . $account->accountType . ")";
        echo "\n  BALANCE: " . $account->balance . " CAD";
        echo "\n  TRANSACTIONS: " . count($account->statement->transactions);
        if (count($account->statement->transactions) > 0) {
            echo "\n  PERIOD: " . $account->statement->startDate->format('Y-m-d') . 
                 " to " . $account->statement->endDate->format('Y-m-d');
        }
        
        // Verify real Manulife bank ID
        $this->assertEquals('054000240', $account->routingNumber, 'Should have real Manulife bank ID');
        $this->assertCount(17, $account->statement->transactions);
        $this->assertEquals('CHECKING', $account->accountType);
    }

    /**
     * Parse and report details from RBC savings fixture
     * Expected: routingNumber=900000100 (real RBC), minimal transactions
     */
    public function testParseRbcSavingsFixture(): void
    {
        $file = $this->fixturesDir . '/ofxdata-rbc-savings.ofx';
        $this->assertFileExists($file);

        $parser = new Parser();
        $ofx = $parser->loadFromFile($file);
        
        $this->assertNotNull($ofx);
        $this->assertNotEmpty($ofx->bankAccounts);
        
        $account = $ofx->bankAccounts[0];
        echo "\n✓ RBC Savings (ofxdata-rbc-savings.ofx)";
        echo "\n  BANK: RBC";
        echo "\n  BANKID: " . $account->routingNumber;
        echo "\n  ACCOUNT: " . $account->accountNumber . " (Type: " . $account->accountType . ")";
        echo "\n  BALANCE: " . $account->balance . " CAD";
        echo "\n  TRANSACTIONS: " . count($account->statement->transactions);
        
        // Verify real RBC bank ID
        $this->assertEquals('900000100', $account->routingNumber, 'Should have real RBC bank ID');
        $this->assertEquals('SAVINGS', $account->accountType);
    }

    /**
     * Parse and report details from Simplii savings fixture
     * Expected: routingNumber=160000100 (real Simplii)
     */
    public function testParseSimpliiSavingsFixture(): void
    {
        $file = $this->fixturesDir . '/ofxdata-simplii-savings.ofx';
        $this->assertFileExists($file);

        $parser = new Parser();
        $ofx = $parser->loadFromFile($file);
        
        $this->assertNotNull($ofx);
        $this->assertNotEmpty($ofx->bankAccounts);
        
        $account = $ofx->bankAccounts[0];
        echo "\n✓ Simplii Savings (ofxdata-simplii-savings.ofx)";
        echo "\n  BANK: Simplii";
        echo "\n  BANKID: " . $account->routingNumber;
        echo "\n  ACCOUNT: " . $account->accountNumber . " (Type: " . $account->accountType . ")";
        echo "\n  BALANCE: " . $account->balance . " CAD";
        echo "\n  TRANSACTIONS: " . count($account->statement->transactions);
        
        // Verify real Simplii bank ID
        $this->assertEquals('160000100', $account->routingNumber, 'Should have real Simplii bank ID');
        $this->assertEquals('SAVINGS', $account->accountType);
    }

    /**
     * Parse and report details from FAKE HISA fixture
     * Expected: routingNumber=999999999 (fake/generic)
     */
    public function testParseFakeHisaFixture(): void
    {
        $file = $this->fixturesDir . '/ofxdata-FAKE-hisa.ofx';
        $this->assertFileExists($file);

        $parser = new Parser();
        $ofx = $parser->loadFromFile($file);
        
        $this->assertNotNull($ofx);
        $this->assertNotEmpty($ofx->bankAccounts);
        
        $account = $ofx->bankAccounts[0];
        echo "\n✓ FAKE HISA (ofxdata-FAKE-hisa.ofx)";
        echo "\n  BANK: Generic/Fake";
        echo "\n  BANKID: " . $account->routingNumber;
        echo "\n  ACCOUNT: " . $account->accountNumber . " (Type: " . $account->accountType . ")";
        echo "\n  BALANCE: " . $account->balance . " CAD";
        echo "\n  TRANSACTIONS: " . count($account->statement->transactions);
        if (count($account->statement->transactions) > 0) {
            echo "\n  PERIOD: " . $account->statement->startDate->format('Y-m-d') . 
                 " to " . $account->statement->endDate->format('Y-m-d');
        }
        
        // Verify fake bank ID
        $this->assertEquals('999999999', $account->routingNumber, 'Should have fake bank ID');
        $this->assertEquals('SAVINGS', $account->accountType);
        $this->assertCount(7, $account->statement->transactions);
    }

    /**
     * Parse and report details from FAKE credit card fixture
     * Expected: routingNumber=999999999 (fake/generic), credit card account
     */
    public function testParseFakeCreditCardFixture(): void
    {
        $file = $this->fixturesDir . '/ofxdata-FAKE-credit-card.ofx';
        $this->assertFileExists($file);

        $parser = new Parser();
        $ofx = $parser->loadFromFile($file);
        
        $this->assertNotNull($ofx);
        $this->assertNotEmpty($ofx->bankAccounts);
        
        $account = $ofx->bankAccounts[0];
        echo "\n✓ FAKE CREDIT CARD (ofxdata-FAKE-credit-card.ofx)";
        echo "\n  BANK: Generic/Fake";
        echo "\n  ACCOUNT: " . $account->accountNumber . " (Type: CREDITLINE)";
        echo "\n  BALANCE: " . $account->balance . " CAD";
        echo "\n  TRANSACTIONS: " . count($account->statement->transactions);
        
        $this->assertGreaterThan(0, count($account->statement->transactions));
    }

    /**
     * Parse and report details from FAKE checking fixture
     * Expected: routingNumber=999999999 (fake/generic), checking account
     */
    public function testParseFakeCheckingFixture(): void
    {
        $file = $this->fixturesDir . '/ofxdata-FAKE-checking.ofx';
        $this->assertFileExists($file);

        $parser = new Parser();
        $ofx = $parser->loadFromFile($file);
        
        $this->assertNotNull($ofx);
        $this->assertNotEmpty($ofx->bankAccounts);
        
        $account = $ofx->bankAccounts[0];
        echo "\n✓ FAKE CHECKING (ofxdata-FAKE-checking.ofx)";
        echo "\n  BANK: Generic/Fake";
        echo "\n  BANKID: " . $account->routingNumber;
        echo "\n  ACCOUNT: " . $account->accountNumber . " (Type: " . $account->accountType . ")";
        echo "\n  BALANCE: " . $account->balance . " CAD";
        echo "\n  TRANSACTIONS: " . count($account->statement->transactions);
        if (count($account->statement->transactions) > 0) {
            echo "\n  PERIOD: " . $account->statement->startDate->format('Y-m-d') . 
                 " to " . $account->statement->endDate->format('Y-m-d');
        }
        
        // Verify fake bank ID
        $this->assertEquals('999999999', $account->routingNumber, 'Should have fake bank ID');
        $this->assertEquals('CHECKING', $account->accountType);
        $this->assertCount(5, $account->statement->transactions);
    }
}
