<?php

namespace OfxParser\Parsers;

use PHPUnit\Framework\TestCase;
use OfxParser\Parser;
use OfxParser\Entities\Payee;

/**
 * Test SGML Payee Parsing
 * 
 * Why: PAYEE elements contain structured information about payment recipients
 * including name, address, and contact details. This is part of the OFX spec
 * for transaction detail and is already supported in XML format. SGML support
 * ensures feature parity across both parsing paths.
 * 
 * What: Validates that PAYEE container elements in SGML transactions are correctly
 * parsed into Payee entity objects with all fields (name, address lines, city,
 * state, postal code, country, phone).
 * 
 * @covers OfxParser\Builders\SgmlOfxBuilder::buildPayee
 */
class SgmlPayeeTest extends TestCase
{
    /**
     * Test parsing complete payee information from SGML format
     * 
     * Why: Complete payee data includes multiple address lines and all contact fields.
     * This tests the full parsing capability against the OFX spec.
     */
    public function testSgmlTransactionWithCompletePayee()
    {
        $parser = new Parser();
        $ofx = $parser->loadFromFile(__DIR__ . '/../../fixtures/ofxdata-sgml-with-payee.ofx');
        
        $account = reset($ofx->bankAccounts);
        self::assertNotNull($account);
        
        $transactions = $account->statement->transactions;
        self::assertCount(3, $transactions);
        
        // First transaction has complete payee data
        $transaction1 = $transactions[0];
        self::assertEquals('TXN001', $transaction1->uniqueId);
        self::assertEquals('PAYEE123', $transaction1->payeeId);
        
        self::assertInstanceOf(Payee::class, $transaction1->payee);
        self::assertEquals('ABC Company Payment Processing', $transaction1->payee->name);
        
        self::assertIsArray($transaction1->payee->address);
        self::assertCount(3, $transaction1->payee->address);
        self::assertEquals('123 Business Ave', $transaction1->payee->address[0]);
        self::assertEquals('Suite 200', $transaction1->payee->address[1]);
        self::assertEquals('Building B', $transaction1->payee->address[2]);
        
        self::assertEquals('Metropolis', $transaction1->payee->city);
        self::assertEquals('NY', $transaction1->payee->state);
        self::assertEquals('10001', $transaction1->payee->postalCode);
        self::assertEquals('USA', $transaction1->payee->country);
        self::assertEquals('555-123-4567', $transaction1->payee->phone);
    }
    
    /**
     * Test parsing partial payee information
     * 
     * Why: Not all payees have complete address data. The parser must handle
     * optional fields gracefully per OFX spec.
     */
    public function testSgmlTransactionWithPartialPayee()
    {
        $parser = new Parser();
        $ofx = $parser->loadFromFile(__DIR__ . '/../../fixtures/ofxdata-sgml-with-payee.ofx');
        
        $account = reset($ofx->bankAccounts);
        $transactions = $account->statement->transactions;
        
        // Second transaction has partial payee data
        $transaction2 = $transactions[1];
        self::assertEquals('TXN002', $transaction2->uniqueId);
        
        self::assertInstanceOf(Payee::class, $transaction2->payee);
        self::assertEquals('Electric Company', $transaction2->payee->name);
        self::assertEquals('Springfield', $transaction2->payee->city);
        self::assertEquals('IL', $transaction2->payee->state);
        self::assertEquals('62701', $transaction2->payee->postalCode);
        
        // These fields should be null or empty when not provided
        self::assertEmpty($transaction2->payee->address ?? []);
        self::assertNull($transaction2->payee->country);
        self::assertNull($transaction2->payee->phone);
    }
    
    /**
     * Test transaction without payee element
     * 
     * Why: PAYEE is optional in OFX spec. Transactions without payee data
     * should parse successfully with payee field as null.
     */
    public function testSgmlTransactionWithoutPayee()
    {
        $parser = new Parser();
        $ofx = $parser->loadFromFile(__DIR__ . '/../../fixtures/ofxdata-sgml-with-payee.ofx');
        
        $account = reset($ofx->bankAccounts);
        $transactions = $account->statement->transactions;
        
        // Third transaction has no payee element
        $transaction3 = $transactions[2];
        self::assertEquals('TXN003', $transaction3->uniqueId);
        self::assertNull($transaction3->payee);
        self::assertNull($transaction3->payeeId);
    }
    
    /**
     * Test SGML payee matches XML payee parsing behavior
     * 
     * Why: Both parsing paths should produce identical results for the same
     * logical data. This ensures architectural consistency.
     */
    public function testSgmlPayeeMatchesXmlBehavior()
    {
        // This test will compare SGML and XML parsing once both are implemented
        // For now, it validates the SGML structure matches entity expectations
        
        $parser = new Parser();
        $ofx = $parser->loadFromFile(__DIR__ . '/../../fixtures/ofxdata-sgml-with-payee.ofx');
        
        $account = reset($ofx->bankAccounts);
        $transaction = $account->statement->transactions[0];
        
        // Validate Payee object structure
        $payee = $transaction->payee;
        self::assertInstanceOf(Payee::class, $payee);
        
        // All properties should exist (even if null)
        self::assertObjectHasProperty('name', $payee);
        self::assertObjectHasProperty('address', $payee);
        self::assertObjectHasProperty('city', $payee);
        self::assertObjectHasProperty('state', $payee);
        self::assertObjectHasProperty('postalCode', $payee);
        self::assertObjectHasProperty('country', $payee);
        self::assertObjectHasProperty('phone', $payee);
    }
}
