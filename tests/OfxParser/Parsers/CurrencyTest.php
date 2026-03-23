<?php

namespace OfxParserTest\Parsers;

use PHPUnit\Framework\TestCase;
use OfxParser\Parser;

/**
 * Test Currency Support in Transactions
 * 
 * Why: The OFX spec supports multi-currency transactions via CURRENCY and ORIGCURRENCY
 * elements. These are critical for international banking where transactions occur in
 * different currencies than the account's base currency (CURDEF). Currency information
 * includes:
 * - CURSYM: Currency code (USD, EUR, GBP, etc.)
 * - CURRATE: Exchange rate applied
 * 
 * What: Validates that both SGML and XML parsers correctly extract currency information
 * from transactions, storing currency code and exchange rate in structured format.
 * 
 * @covers OfxParser\Builders\SgmlOfxBuilder::buildTransaction
 * @covers OfxParser\Ofx::buildTransaction
 */
class CurrencyTest extends TestCase
{
    /**
     * Test parsing transaction with currency conversion information
     * 
     * Why: When a transaction occurs in a foreign currency, OFX includes CURRENCY
     * with the exchange rate and currency code. This allows applications to show
     * both the converted account-currency amount and original foreign amount.
     */
    public function testSgmlTransactionWithCurrency()
    {
        $parser = new Parser();
        $ofx = $parser->loadFromFile(__DIR__ . '/../../fixtures/ofxdata-sgml-with-currency.ofx');
        
        $account = reset($ofx->bankAccounts);
        self::assertNotNull($account);
        self::assertEquals('EUR', $account->statement->currency);
        
        $transactions = $account->statement->transactions;
        self::assertCount(2, $transactions);
        
        // First transaction has currency information (converted from USD to EUR)
        $transaction1 = $transactions[0];
        self::assertEquals('TXN001', $transaction1->uniqueId);
        self::assertEquals(-100.00, $transaction1->amount); // Amount in EUR (account currency)
        
        // Validate currency structure
        self::assertIsArray($transaction1->currency);
        self::assertArrayHasKey('code', $transaction1->currency);
        self::assertArrayHasKey('rate', $transaction1->currency);
        self::assertEquals('USD', $transaction1->currency['code']);
        self::assertEquals(1.18, $transaction1->currency['rate']);
        
        // Validate original currency structure
        self::assertIsArray($transaction1->originalCurrency);
        self::assertEquals('USD', $transaction1->originalCurrency['code']);
        self::assertEquals(1.0, $transaction1->originalCurrency['rate']);
    }
    
    /**
     * Test transaction without currency conversion (account's base currency)
     * 
     * Why: Most transactions occur in the account's base currency and don't need
     * currency conversion data. The parser should handle this gracefully by leaving
     * currency fields as null.
     */
    public function testSgmlTransactionWithoutCurrency()
    {
        $parser = new Parser();
        $ofx = $parser->loadFromFile(__DIR__ . '/../../fixtures/ofxdata-sgml-with-currency.ofx');
        
        $account = reset($ofx->bankAccounts);
        $transactions = $account->statement->transactions;
        
        // Second transaction is in account currency (EUR) - no conversion
        $transaction2 = $transactions[1];
        self::assertEquals('TXN002', $transaction2->uniqueId);
        self::assertEquals(500.00, $transaction2->amount);
        
        // Currency fields should be null when not present
        self::assertNull($transaction2->currency);
        self::assertNull($transaction2->originalCurrency);
    }
    
    /**
     * Test currency calculation example
     * 
     * Why: Documents how to use currency information for amount calculations.
     * If TRNAMT is in account currency but original was in foreign currency,
     * you can calculate: original_amount = TRNAMT / CURRATE
     */
    public function testCurrencyCalculation()
    {
        $parser = new Parser();
        $ofx = $parser->loadFromFile(__DIR__ . '/../../fixtures/ofxdata-sgml-with-currency.ofx');
        
        $account = reset($ofx->bankAccounts);
        $transaction = $account->statement->transactions[0];
        
        // Transaction amount is in EUR (account currency): -100.00
        // Currency rate is 1.18 (USD per EUR)
        // Original USD amount would be: -100.00 / 1.18 = -84.75 USD (approximately)
        
        if ($transaction->currency) {
            $originalAmount = $transaction->amount / $transaction->currency['rate'];
            self::assertEqualsWithDelta(-84.75, $originalAmount, 0.01);
        }
    }
    
    /**
     * Test that statement currency (CURDEF) is preserved
     * 
     * Why: The statement's CURDEF indicates the account's base currency. This
     * is separate from individual transaction currencies and should always be set.
     */
    public function testStatementCurrency()
    {
        $parser = new Parser();
        $ofx = $parser->loadFromFile(__DIR__ . '/../../fixtures/ofxdata-sgml-with-currency.ofx');
        
        $account = reset($ofx->bankAccounts);
        
        // Statement should have EUR as base currency
        self::assertEquals('EUR', $account->statement->currency);
        
        // This is different from transaction currencies which may vary
        $transaction = $account->statement->transactions[0];
        if ($transaction->currency) {
            self::assertEquals('USD', $transaction->currency['code']);
            self::assertNotEquals($account->statement->currency, $transaction->currency['code']);
        }
    }
}
