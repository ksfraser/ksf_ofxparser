<?php

namespace OfxParserTest\OfxParser;

use OfxParser\Parser;
use PHPUnit\Framework\TestCase;

/**
 * Tests parsing of real-world OFX/QFX files from major Canadian banks
 * 
 * These tests use sanitized fixture files based on actual bank downloads.
 * Account numbers, bank IDs, and merchant names have been replaced with 
 * generic values to protect privacy while maintaining realistic structure.
 * 
 * @covers OfxParser\Parser
 */
class RealWorldBankFilesTest extends TestCase
{
    /**
     * Test parsing CIBC HISA (High-Interest Savings Account)
     * Tests handle of multiple transaction types with simple SGML structure
     */
    public function testParseCibcHisaAccount()
    {
        $parser = new Parser();
        $ofx = $parser->loadFromFile(__DIR__ . '/../fixtures/ofxdata-cibc-hisa.ofx');

        // Verify we have bank accounts
        self::assertIsArray($ofx->bankAccounts);
        self::assertNotEmpty($ofx->bankAccounts);
        
        $account = reset($ofx->bankAccounts);
        
        // Verify account details
        self::assertSame('1000 00-12345', $account->accountNumber);
        self::assertSame('111111111', $account->institution->bankId);
        self::assertSame('CAD', $ofx->header['CURDEF']);
        
        // Verify transactions loaded
        self::assertIsArray($account->statement->transactions);
        self::assertCount(6, $account->statement->transactions);
        
        // Verify first transaction (CREDIT)
        $firstTx = reset($account->statement->transactions);
        self::assertSame('CREDIT', $firstTx->type);
        self::assertSame('1400.00', (string)$firstTx->amount);
    }

    /**
     * Test parsing CIBC Visa credit card
     * Tests credit card statement parsing and transaction details
     */
    public function testParseCibcVisaCreditCard()
    {
        $parser = new Parser();
        $ofx = $parser->loadFromFile(__DIR__ . '/../fixtures/ofxdata-cibc-visa.ofx');

        // Credit cards are stored separately
        self::assertIsArray($ofx->creditCardAccounts);
        self::assertNotEmpty($ofx->creditCardAccounts);
        
        $ccAccount = reset($ofx->creditCardAccounts);
        
        // Verify credit card account
        self::assertSame('4111111111111111', $ccAccount->accountNumber);
        
        // Verify transactions
        self::assertIsArray($ccAccount->statement->transactions);
        self::assertCount(5, $ccAccount->statement->transactions);
        
        // Verify transaction has merchant name
        $firstTx = reset($ccAccount->statement->transactions);
        self::assertNotEmpty($firstTx->payee);
    }

    /**
     * Test parsing Manulife checking account
     * Tests diverse transaction types including transfers and direct debits
     */
    public function testParseManulifeCheckingAccount()
    {
        $parser = new Parser();
        $ofx = $parser->loadFromFile(__DIR__ . '/../fixtures/ofxdata-manulife-checking.ofx');

        self::assertIsArray($ofx->bankAccounts);
        self::assertNotEmpty($ofx->bankAccounts);
        
        $account = reset($ofx->bankAccounts);
        
        // Verify account
        self::assertSame('1000000', $account->accountNumber);
        self::assertSame('111111111', $account->institution->bankId);
        
        // Verify diverse transactions
        self::assertIsArray($account->statement->transactions);
        self::assertGreaterThan(10, count($account->statement->transactions));
        
        // Find specific transaction types
        $transactionTypes = array_map(
            fn($tx) => $tx->type,
            $account->statement->transactions
        );
        
        self::assertContains('DEBIT', $transactionTypes);
        self::assertContains('XFER', $transactionTypes);
        self::assertContains('DIRECTDEBIT', $transactionTypes);
        self::assertContains('INT', $transactionTypes);
    }

    /**
     * Test parsing RBC HISA savings account
     * Tests minimal transaction single-transaction SGML file
     */
    public function testParseRbcSavingsAccount()
    {
        $parser = new Parser();
        $ofx = $parser->loadFromFile(__DIR__ . '/../fixtures/ofxdata-rbc-savings.ofx');

        self::assertIsArray($ofx->bankAccounts);
        self::assertNotEmpty($ofx->bankAccounts);
        
        $account = reset($ofx->bankAccounts);
        
        // Verify account
        self::assertSame('3000 0000123456', $account->accountNumber);
        self::assertSame('111111111', $account->institution->bankId);
        self::assertSame('SAVINGS', $account->type);
        
        // Verify transaction
        self::assertIsArray($account->statement->transactions);
        self::assertCount(1, $account->statement->transactions);
        
        $tx = reset($account->statement->transactions);
        self::assertSame('CREDIT', $tx->type);
        self::assertSame('0.08', (string)$tx->amount);
    }

    /**
     * Test parsing SIMPLII HISA account
     * Tests online bank with simple transaction structure
     */
    public function testSimpliiSavingsAccount()
    {
        $parser = new Parser();
        $ofx = $parser->loadFromFile(__DIR__ . '/../fixtures/ofxdata-simplii-savings.ofx');

        self::assertIsArray($ofx->bankAccounts);
        self::assertNotEmpty($ofx->bankAccounts);
        
        $account = reset($ofx->bankAccounts);
        
        // Verify account
        self::assertSame('3000 0000123456', $account->accountNumber);
        self::assertSame('111111111', $account->institution->bankId);
        
        // Verify transactions
        self::assertIsArray($account->statement->transactions);
        self::assertCount(2, $account->statement->transactions);
    }

    /**
     * Test parsing generic real-world HISA account
     * Tests typical high-interest savings with regular deposits and interest
     */
    public function testRealWorldHisaAccount()
    {
        $parser = new Parser();
        $ofx = $parser->loadFromFile(__DIR__ . '/../fixtures/ofxdata-real-world-hisa.ofx');

        self::assertIsArray($ofx->bankAccounts);
        $account = reset($ofx->bankAccounts);

        // Should have multiple transactions
        self::assertNotEmpty($account->statement->transactions);
        
        // Verify we have both credits and debits
        $txTypes = array_map(fn($tx) => $tx->type, $account->statement->transactions);
        self::assertContains('CREDIT', $txTypes);
        self::assertContains('DEBIT', $txTypes);
    }

    /**
     * Test parsing real-world credit card statement
     * Tests typical credit card with purchases and payment
     */
    public function testRealWorldCreditCard()
    {
        $parser = new Parser();
        $ofx = $parser->loadFromFile(__DIR__ . '/../fixtures/ofxdata-real-world-credit-card.ofx');

        self::assertIsArray($ofx->creditCardAccounts);
        $ccAccount = reset($ofx->creditCardAccounts);

        // Should have multiple transactions
        self::assertNotEmpty($ccAccount->statement->transactions);
        
        // All transactions should have payee names
        foreach ($ccAccount->statement->transactions as $tx) {
            self::assertNotEmpty($tx->payee, 'Credit card transactions should have payee/merchant name');
        }
    }

    /**
     * Test parsing real-world checking account
     * Tests diverse transaction types typical of primary checking account
     */
    public function testRealWorldCheckingAccount()
    {
        $parser = new Parser();
        $ofx = $parser->loadFromFile(__DIR__ . '/../fixtures/ofxdata-real-world-checking.ofx');

        self::assertIsArray($ofx->bankAccounts);
        $account = reset($ofx->bankAccounts);

        // Should have many transactions
        self::assertGreaterThan(10, count($account->statement->transactions));
        
        // Verify common transaction types in checking account
        $txTypes = array_map(fn($tx) => $tx->type, $account->statement->transactions);
        self::assertContains('CREDIT', $txTypes); // Regular deposits
        self::assertContains('DEBIT', $txTypes);  // Check payments
        
        // Verify statement has balance information
        self::assertNotNull($account->statement->balance);
        self::assertGreater($account->statement->balance, 0);
    }
}
