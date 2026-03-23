<?php declare(strict_types=1);

namespace Tests\Unit;

use DateTime;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use KsfOfxParser\Parser;
use KsfOfxParser\Entities\Transaction;
use Tests\Builders\EdgeCaseAmounts;
use Tests\Builders\EdgeCaseDates;
use Tests\Builders\OFXEnvelopeBuilder;

/**
 * Unit tests for edge case handling in individual components
 * 
 * These tests verify that critical components correctly handle:
 * - Amount parsing extremes
 * - Date parsing edge cases
 * - Field length boundaries
 * - Special characters
 * 
 * Organized by component being tested
 */
class EdgeCaseParsingTest extends TestCase
{
    private Parser $parser;
    
    protected function setUp(): void
    {
        $this->parser = new Parser();
    }
    
    // =================================================================
    // Amount Parsing Tests
    // =================================================================
    
    /**
     * Test: Parsing minimum positive amount
     */
    public function testParseMinimumPositiveAmount(): void
    {
        $ofx = OFXEnvelopeBuilder::ofxBankStatement()
            ->addTransaction([
                'id' => '1',
                'type' => 'CREDIT',
                'amount' => '0.01',
                'date' => new DateTime('2026-03-13', new DateTimeZone('UTC')),
                'memo' => 'Minimum',
            ])->build();
        
        $parsed = $this->parser->loadOFXString($ofx);
        $txn = $parsed->getTransactions()[0];
        
        $this->assertEquals(0.01, $txn->getAmount());
    }
    
    /**
     * Test: Parsing maximum positive amount
     */
    public function testParseMaximumPositiveAmount(): void
    {
        $ofx = OFXEnvelopeBuilder::ofxBankStatement()
            ->addTransaction([
                'id' => '1',
                'type' => 'CREDIT',
                'amount' => '999999999999.99',
                'date' => new DateTime('2026-03-13', new DateTimeZone('UTC')),
                'memo' => 'Maximum',
            ])->build();
        
        $parsed = $this->parser->loadOFXString($ofx);
        $txn = $parsed->getTransactions()[0];
        
        $this->assertEquals(999999999999.99, $txn->getAmount());
    }
    
    /**
     * Test: Parsing minimum negative amount
     */
    public function testParseMinimumNegativeAmount(): void
    {
        $ofx = OFXEnvelopeBuilder::ofxBankStatement()
            ->addTransaction([
                'id' => '1',
                'type' => 'DEBIT',
                'amount' => '-0.01',
                'date' => new DateTime('2026-03-13', new DateTimeZone('UTC')),
                'memo' => 'Minimum negative',
            ])->build();
        
        $parsed = $this->parser->loadOFXString($ofx);
        $txn = $parsed->getTransactions()[0];
        
        $this->assertEquals(-0.01, $txn->getAmount());
    }
    
    /**
     * Test: Parsing maximum negative amount
     */
    public function testParseMaximumNegativeAmount(): void
    {
        $ofx = OFXEnvelopeBuilder::ofxBankStatement()
            ->addTransaction([
                'id' => '1',
                'type' => 'DEBIT',
                'amount' => '-999999999999.99',
                'date' => new DateTime('2026-03-13', new DateTimeZone('UTC')),
                'memo' => 'Maximum negative',
            ])->build();
        
        $parsed = $this->parser->loadOFXString($ofx);
        $txn = $parsed->getTransactions()[0];
        
        $this->assertEquals(-999999999999.99, $txn->getAmount());
    }
    
    /**
     * Test: Parsing amount with many decimal places
     */
    public function testParseAmountWithExtraDecimals(): void
    {
        $ofx = OFXEnvelopeBuilder::ofxBankStatement()
            ->addTransaction([
                'id' => '1',
                'type' => 'CREDIT',
                'amount' => '100.123456',
                'date' => new DateTime('2026-03-13', new DateTimeZone('UTC')),
                'memo' => 'Extra decimals',
            ])->build();
        
        $parsed = $this->parser->loadOFXString($ofx);
        $txn = $parsed->getTransactions()[0];
        
        // Should handle precision appropriately
        $this->assertIsFloat($txn->getAmount());
    }
    
    /**
     * Test: Parsing amount without decimal places
     */
    public function testParseAmountWithoutDecimal(): void
    {
        $ofx = OFXEnvelopeBuilder::ofxBankStatement()
            ->addTransaction([
                'id' => '1',
                'type' => 'CREDIT',
                'amount' => '100',
                'date' => new DateTime('2026-03-13', new DateTimeZone('UTC')),
                'memo' => 'No decimal',
            ])->build();
        
        $parsed = $this->parser->loadOFXString($ofx);
        $txn = $parsed->getTransactions()[0];
        
        $this->assertEquals(100.0, $txn->getAmount());
    }
    
    /**
     * Test: Parsing zero amount
     */
    public function testParseZeroAmount(): void
    {
        $amounts = ['0', '0.00', '-0.00'];
        
        foreach ($amounts as $amount) {
            $ofx = OFXEnvelopeBuilder::ofxBankStatement()
                ->addTransaction([
                    'id' => '1',
                    'type' => 'CREDIT',
                    'amount' => $amount,
                    'date' => new DateTime('2026-03-13', new DateTimeZone('UTC')),
                    'memo' => "Zero as: {$amount}",
                ])->build();
            
            $parsed = $this->parser->loadOFXString($ofx);
            $txn = $parsed->getTransactions()[0];
            
            $this->assertEquals(0.0, $txn->getAmount());
        }
    }
    
    /**
     * Test: Parsing amount with leading zeros
     */
    public function testParseAmountWithLeadingZeros(): void
    {
        $ofx = OFXEnvelopeBuilder::ofxBankStatement()
            ->addTransaction([
                'id' => '1',
                'type' => 'CREDIT',
                'amount' => '00100.50',
                'date' => new DateTime('2026-03-13', new DateTimeZone('UTC')),
                'memo' => 'Leading zeros',
            ])->build();
        
        $parsed = $this->parser->loadOFXString($ofx);
        $txn = $parsed->getTransactions()[0];
        
        $this->assertEquals(100.50, $txn->getAmount());
    }
    
    // =================================================================
    // Date Parsing Tests
    // =================================================================
    
    /**
     * Test: Parsing Unix epoch date
     */
    public function testParseUnixEpoch(): void
    {
        $ofx = OFXEnvelopeBuilder::ofxBankStatement()
            ->addTransaction([
                'id' => '1',
                'type' => 'CREDIT',
                'amount' => '100.00',
                'date' => new DateTime('1970-01-01', new DateTimeZone('UTC')),
                'memo' => 'Epoch',
            ])->build();
        
        $parsed = $this->parser->loadOFXString($ofx);
        $txn = $parsed->getTransactions()[0];
        
        $this->assertEquals('1970-01-01', $txn->getDate()->format('Y-m-d'));
    }
    
    /**
     * Test: Parsing Y2K date
     */
    public function testParseY2KDate(): void
    {
        $ofx = OFXEnvelopeBuilder::ofxBankStatement()
            ->addTransaction([
                'id' => '1',
                'type' => 'CREDIT',
                'amount' => '100.00',
                'date' => new DateTime('2000-01-01', new DateTimeZone('UTC')),
                'memo' => 'Y2K',
            ])->build();
        
        $parsed = $this->parser->loadOFXString($ofx);
        $txn = $parsed->getTransactions()[0];
        
        $this->assertEquals('2000-01-01', $txn->getDate()->format('Y-m-d'));
    }
    
    /**
     * Test: Parsing leap year date (Feb 29)
     */
    public function testParseLeapYearDate(): void
    {
        $ofx = OFXEnvelopeBuilder::ofxBankStatement()
            ->addTransaction([
                'id' => '1',
                'type' => 'CREDIT',
                'amount' => '100.00',
                'date' => new DateTime('2024-02-29', new DateTimeZone('UTC')),
                'memo' => 'Leap day',
            ])->build();
        
        $parsed = $this->parser->loadOFXString($ofx);
        $txn = $parsed->getTransactions()[0];
        
        $this->assertEquals('2024-02-29', $txn->getDate()->format('Y-m-d'));
    }
    
    /**
     * Test: Parsing year-end date
     */
    public function testParseYearEnd(): void
    {
        $ofx = OFXEnvelopeBuilder::ofxBankStatement()
            ->addTransaction([
                'id' => '1',
                'type' => 'CREDIT',
                'amount' => '100.00',
                'date' => new DateTime('2026-12-31', new DateTimeZone('UTC')),
                'memo' => 'Year end',
            ])->build();
        
        $parsed = $this->parser->loadOFXString($ofx);
        $txn = $parsed->getTransactions()[0];
        
        $this->assertEquals('2026-12-31', $txn->getDate()->format('Y-m-d'));
    }
    
    /**
     * Test: Parsing far-future date
     */
    public function testParseFarFutureDate(): void
    {
        $ofx = OFXEnvelopeBuilder::ofxBankStatement()
            ->addTransaction([
                'id' => '1',
                'type' => 'CREDIT',
                'amount' => '100.00',
                'date' => new DateTime('2099-12-31', new DateTimeZone('UTC')),
                'memo' => 'Far future',
            ])->build();
        
        $parsed = $this->parser->loadOFXString($ofx);
        $txn = $parsed->getTransactions()[0];
        
        $this->assertEquals('2099-12-31', $txn->getDate()->format('Y-m-d'));
    }
    
    /**
     * Test: Date with time component
     */
    public function testParseDateWithTime(): void
    {
        $ofx = OFXEnvelopeBuilder::ofxBankStatement()
            ->addTransaction([
                'id' => '1',
                'type' => 'CREDIT',
                'amount' => '100.00',
                'date' => new DateTime('2026-03-13 14:30:00', new DateTimeZone('UTC')),
                'memo' => 'With time',
            ])->build();
        
        $parsed = $this->parser->loadOFXString($ofx);
        $txn = $parsed->getTransactions()[0];
        
        $date = $txn->getDate();
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertEquals('2026-03-13', $date->format('Y-m-d'));
    }
    
    // =================================================================
    // Field Length Tests
    // =================================================================
    
    /**
     * Test: Transaction ID at maximum length
     */
    public function testMaximumTransactionIdLength(): void
    {
        $longId = str_repeat('X', 255);
        
        $ofx = OFXEnvelopeBuilder::ofxBankStatement()
            ->addTransaction([
                'id' => $longId,
                'type' => 'CREDIT',
                'amount' => '100.00',
                'date' => new DateTime('2026-03-13', new DateTimeZone('UTC')),
                'memo' => 'Long ID',
            ])->build();
        
        $parsed = $this->parser->loadOFXString($ofx);
        $txn = $parsed->getTransactions()[0];
        
        // Should preserve or handle gracefully
        $this->assertNotNull($txn->getId());
    }
    
    /**
     * Test: Memo at maximum length
     */
    public function testMaximumMemoLength(): void
    {
        $longMemo = str_repeat('M', 10000);
        
        $ofx = OFXEnvelopeBuilder::ofxBankStatement()
            ->addTransaction([
                'id' => '1',
                'type' => 'CREDIT',
                'amount' => '100.00',
                'date' => new DateTime('2026-03-13', new DateTimeZone('UTC')),
                'memo' => $longMemo,
            ])->build();
        
        $parsed = $this->parser->loadOFXString($ofx);
        $txn = $parsed->getTransactions()[0];
        
        // Should parse without error
        $this->assertNotNull($txn->getMemo());
    }
    
    /**
     * Test: Payee name at maximum length
     */
    public function testMaximumPayeeLength(): void
    {
        $longPayee = str_repeat('P', 32);
        
        $ofx = OFXEnvelopeBuilder::ofxBankStatement()
            ->addTransaction([
                'id' => '1',
                'type' => 'CREDIT',
                'amount' => '100.00',
                'date' => new DateTime('2026-03-13', new DateTimeZone('UTC')),
                'memo' => 'Test',
                'payee' => $longPayee,
            ])->build();
        
        $parsed = $this->parser->loadOFXString($ofx);
        $txn = $parsed->getTransactions()[0];
        
        $this->assertNotNull($txn->getPayee());
    }
    
    // =================================================================
    // Special Character Tests
    // =================================================================
    
    /**
     * Test: Ampersand in field
     */
    public function testAmpersandInField(): void
    {
        $ofx = OFXEnvelopeBuilder::ofxBankStatement()
            ->addTransaction([
                'id' => '1',
                'type' => 'CREDIT',
                'amount' => '100.00',
                'date' => new DateTime('2026-03-13', new DateTimeZone('UTC')),
                'memo' => 'AT&T purchase',
                'payee' => 'Smith & Sons',
            ])->build();
        
        $parsed = $this->parser->loadOFXString($ofx);
        $txn = $parsed->getTransactions()[0];
        
        $this->assertStringContainsString('&', $txn->getMemo());
    }
    
    /**
     * Test: Quotes in field
     */
    public function testQuotesInField(): void
    {
        $ofx = OFXEnvelopeBuilder::ofxBankStatement()
            ->addTransaction([
                'id' => '1',
                'type' => 'CREDIT',
                'amount' => '100.00',
                'date' => new DateTime('2026-03-13', new DateTimeZone('UTC')),
                'memo' => 'Said "hello" to customer',
            ])->build();
        
        $parsed = $this->parser->loadOFXString($ofx);
        $txn = $parsed->getTransactions()[0];
        
        $this->assertStringContainsString('"', $txn->getMemo());
    }
    
    /**
     * Test: XML entities in field
     */
    public function testXmlEntitiesInField(): void
    {
        $ofx = OFXEnvelopeBuilder::ofxBankStatement()
            ->addTransaction([
                'id' => '1',
                'type' => 'CREDIT',
                'amount' => '100.00',
                'date' => new DateTime('2026-03-13', new DateTimeZone('UTC')),
                'memo' => 'Amount < 100 & > 50',
            ])->build();
        
        $parsed = $this->parser->loadOFXString($ofx);
        $txn = $parsed->getTransactions()[0];
        
        $this->assertStringContainsString('<', $txn->getMemo());
        $this->assertStringContainsString('>', $txn->getMemo());
        $this->assertStringContainsString('&', $txn->getMemo());
    }
    
    /**
     * Test: Newlines in field
     */
    public function testNewlinesInField(): void
    {
        $ofx = OFXEnvelopeBuilder::ofxBankStatement()
            ->addTransaction([
                'id' => '1',
                'type' => 'CREDIT',
                'amount' => '100.00',
                'date' => new DateTime('2026-03-13', new DateTimeZone('UTC')),
                'memo' => "Line 1\nLine 2\nLine 3",
            ])->build();
        
        $parsed = $this->parser->loadOFXString($ofx);
        $txn = $parsed->getTransactions()[0];
        
        // Should handle newlines
        $this->assertNotNull($txn->getMemo());
    }
    
    // =================================================================
    // Transaction Type Tests
    // =================================================================
    
    /**
     * Test: Each transaction type is recognized
     */
    public function testTransactionTypeRecognition(): void
    {
        $types = ['DEBIT', 'CREDIT', 'INT', 'DIV', 'FEE', 'SRVCHG', 'DEP', 'ATM', 'POS', 'XFER', 'CHECK'];
        
        foreach ($types as $type) {
            $ofx = OFXEnvelopeBuilder::ofxBankStatement()
                ->addTransaction([
                    'id' => '1',
                    'type' => $type,
                    'amount' => '100.00',
                    'date' => new DateTime('2026-03-13', new DateTimeZone('UTC')),
                    'memo' => "Type: {$type}",
                ])->build();
            
            $parsed = $this->parser->loadOFXString($ofx);
            $txn = $parsed->getTransactions()[0];
            
            $this->assertNotNull($txn->getType());
        }
    }
}
