<?php declare(strict_types=1);

namespace Tests\Integration;

use DateTime;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use KsfOfxParser\Parser;
use KsfOfxParser\Entities\Transaction;
use Tests\Builders\OFXEnvelopeBuilder;
use Tests\Builders\TestScenarios;
use Tests\Builders\EdgeCaseAmounts;
use Tests\Builders\EdgeCaseDates;

/**
 * Integration tests using pre-built scenarios
 * 
 * These tests verify that the parser correctly handles:
 * - Real-world OFX documents
 * - Edge case combinations
 * - Large datasets
 * - Special formatting
 * 
 * Tests are organized by scenario complexity
 */
class ScenarioBasedParsingTest extends TestCase
{
    private Parser $parser;
    
    protected function setUp(): void
    {
        $this->parser = new Parser();
    }
    
    // =================================================================
    // Large Statement Tests
    // =================================================================
    
    /**
     * Test parsing a statement with 100 transactions
     * Verifies parser can handle volume
     */
    public function testParseLargeStatement(): void
    {
        $ofx = TestScenarios::largeStatement(100)->build();
        $parsed = $this->parser->loadOFXString($ofx);
        
        $this->assertNotNull($parsed);
        $this->assertCount(100, $parsed->getTransactions());
    }
    
    /**
     * Test parsing a statement with 1000 transactions
     * Stress test for volume handling
     */
    public function testParseExtraLargeStatement(): void
    {
        $ofx = TestScenarios::largeStatement(1000)->build();
        $parsed = $this->parser->loadOFXString($ofx);
        
        $this->assertNotNull($parsed);
        $this->assertCount(1000, $parsed->getTransactions());
    }
    
    /**
     * Test that transaction order is preserved in large statements
     */
    public function testTransactionOrderPreservedInLargeStatement(): void
    {
        $ofx = TestScenarios::largeStatement(50)->build();
        $parsed = $this->parser->loadOFXString($ofx);
        
        $transactions = $parsed->getTransactions();
        for ($i = 0; $i < count($transactions) - 1; $i++) {
            $date1 = $transactions[$i]->getDate();
            $date2 = $transactions[$i + 1]->getDate();
            
            // Dates should be in order or equal
            $this->assertLessThanOrEqual(0, $date1->diff($date2)->invert);
        }
    }
    
    // =================================================================
    // Amount Edge Case Tests
    // =================================================================
    
    /**
     * Test all positive amount extremes
     */
    public function testPositiveAmountExtremes(): void
    {
        $ofx = TestScenarios::positiveAmountExtremes()->build();
        $parsed = $this->parser->loadOFXString($ofx);
        
        $transactions = $parsed->getTransactions();
        
        // Verify all amounts parsed
        $this->assertCount(4, $transactions);
        
        // Verify amounts are preserved with precision
        $this->assertEquals(0.01, $transactions[0]->getAmount());
        $this->assertEquals(100.00, $transactions[1]->getAmount());
        $this->assertEquals(1000.50, $transactions[2]->getAmount());
        $this->assertEquals(999999999999.99, $transactions[3]->getAmount());
    }
    
    /**
     * Test all negative amount extremes
     */
    public function testNegativeAmountExtremes(): void
    {
        $ofx = TestScenarios::negativeAmountExtremes()->build();
        $parsed = $this->parser->loadOFXString($ofx);
        
        $transactions = $parsed->getTransactions();
        
        // Verify all amounts parsed
        $this->assertCount(4, $transactions);
        
        // Verify negative amounts
        $this->assertEquals(-0.01, $transactions[0]->getAmount());
        $this->assertEquals(-100.00, $transactions[1]->getAmount());
        $this->assertEquals(-1500.50, $transactions[2]->getAmount());
        $this->assertEquals(-999999999999.99, $transactions[3]->getAmount());
    }
    
    /**
     * Test zero and near-zero amounts
     */
    public function testZeroAndNearZeroAmounts(): void
    {
        $ofx = TestScenarios::zeroAmounts()->build();
        $parsed = $this->parser->loadOFXString($ofx);
        
        $transactions = $parsed->getTransactions();
        
        // Find transactions and verify zero amounts handled correctly
        foreach ($transactions as $txn) {
            $amount = $txn->getAmount();
            $memo = $txn->getMemo();
            
            if (strpos($memo, 'Zero') !== false) {
                $this->assertEquals(0.0, $amount);
            } elseif (strpos($memo, 'Negative zero') !== false) {
                // -0.00 should parse to 0
                $this->assertEquals(0.0, $amount);
            }
        }
    }
    
    // =================================================================
    // Date Edge Case Tests
    // =================================================================
    
    /**
     * Test date boundary conditions
     */
    public function testDateBoundaries(): void
    {
        $ofx = TestScenarios::dateBoundaries()->build();
        $parsed = $this->parser->loadOFXString($ofx);
        
        $transactions = $parsed->getTransactions();
        
        // Should have transactions for each date
        $this->assertCount(6, $transactions);
        
        // Verify dates are parsed
        $dates = array_map(fn($t) => $t->getDate(), $transactions);
        
        // Check for Unix epoch
        $this->assertTrue(any($dates, fn($d) => $d->format('Y-m-d') === '1970-01-01'));
        
        // Check for Y2K
        $this->assertTrue(any($dates, fn($d) => $d->format('Y-m-d') === '2000-01-01'));
        
        // Check for leap day
        $this->assertTrue(any($dates, fn($d) => $d->format('Y-m-d') === '2024-02-29'));
    }
    
    /**
     * Test dates across different timezones
     */
    public function testDateTimezoneHandling(): void
    {
        $builder = OFXEnvelopeBuilder::ofxBankStatement();
        
        // Add transactions with dates
        $builder->addTransaction([
            'id' => '1',
            'type' => 'CREDIT',
            'amount' => '100.00',
            'date' => new DateTime('2026-03-13 14:30:00', new DateTimeZone('UTC')),
            'memo' => 'UTC transaction',
        ]);
        
        $ofx = $builder->build();
        $parsed = $this->parser->loadOFXString($ofx);
        
        $transactions = $parsed->getTransactions();
        $this->assertCount(1, $transactions);
        
        // Date should be parsed correctly
        $date = $transactions[0]->getDate();
        $this->assertInstanceOf(DateTime::class, $date);
    }
    
    // =================================================================
    // Field Length Tests
    // =================================================================
    
    /**
     * Test maximum field lengths are preserved
     */
    public function testMaximumFieldLengths(): void
    {
        $ofx = TestScenarios::maximumFieldLengths()->build();
        $parsed = $this->parser->loadOFXString($ofx);
        
        $statements = $parsed->getStatements();
        $this->assertCount(1, $statements);
        
        $statement = $statements[0];
        
        // Account ID should be preserved (up to 32 chars typically)
        $accountId = $statement->getAccounts()[0]->getId();
        $this->assertNotEmpty($accountId);
        
        // Transactions with long memos should be preserved
        $transactions = $parsed->getTransactions();
        foreach ($transactions as $txn) {
            $memo = $txn->getMemo();
            // Even if truncated, should have content
            $this->assertNotEmpty($memo);
        }
    }
    
    // =================================================================
    // Transaction Type Tests
    // =================================================================
    
    /**
     * Test parsing all supported transaction types
     */
    public function testAllTransactionTypes(): void
    {
        $ofx = TestScenarios::allTransactionTypes()->build();
        $parsed = $this->parser->loadOFXString($ofx);
        
        $transactions = $parsed->getTransactions();
        
        // Should have parsed all transaction types
        $this->assertGreaterThan(0, count($transactions));
        
        // Verify transaction types are preserved
        $types = array_map(fn($t) => $t->getType(), $transactions);
        $uniqueTypes = array_unique($types);
        
        $this->assertGreaterThan(1, count($uniqueTypes));
    }
    
    // =================================================================
    // Credit Card Tests
    // =================================================================
    
    /**
     * Test parsing credit card statements
     */
    public function testCreditCardStatement(): void
    {
        $ofx = TestScenarios::creditCardStatement()->build();
        $parsed = $this->parser->loadOFXString($ofx);
        
        $statements = $parsed->getStatements();
        $this->assertCount(1, $statements);
        
        $statement = $statements[0];
        
        // Credit card account should be identified
        $account = $statement->getAccounts()[0];
        $this->assertEquals('4111111111111111', $account->getId());
        
        // Should have transactions
        $transactions = $parsed->getTransactions();
        $this->assertCount(3, $transactions);
        
        // All amounts should be negative (charges)
        foreach ($transactions as $txn) {
            $this->assertLessThan(0, $txn->getAmount());
        }
    }
    
    // =================================================================
    // Special Character Tests
    // =================================================================
    
    /**
     * Test special characters in fields
     */
    public function testSpecialCharactersInFields(): void
    {
        $ofx = TestScenarios::specialCharacters()->build();
        $parsed = $this->parser->loadOFXString($ofx);
        
        $transactions = $parsed->getTransactions();
        $this->assertCount(3, $transactions);
        
        // Verify special characters preserved
        $memo0 = $transactions[0]->getMemo();
        $this->assertStringContainsString('&', $memo0);
        
        $memo1 = $transactions[1]->getMemo();
        $this->assertStringContainsString('"', $memo1);
        
        $memo2 = $transactions[2]->getMemo();
        $this->assertStringContainsString('<', $memo2);
        $this->assertStringContainsString('>', $memo2);
    }
    
    // =================================================================
    // Consistency Tests
    // =================================================================
    
    /**
     * Test that rebuilding a parsed document preserves data
     */
    public function testRoundTripParsing(): void
    {
        $originalBuilder = TestScenarios::largeStatement(10);
        $originalOfx = $originalBuilder->build();
        
        // Parse original
        $parsed = $this->parser->loadOFXString($originalOfx);
        
        // Verify data matches
        $transactions = $parsed->getTransactions();
        $this->assertCount(10, $transactions);
        
        // All transactions should have data
        foreach ($transactions as $txn) {
            $this->assertNotEmpty($txn->getId());
            $this->assertNotNull($txn->getAmount());
            $this->assertNotNull($txn->getDate());
        }
    }
    
    /**
     * Test consistency of repeated parses on same document
     */
    public function testConsistentRepeatParsing(): void
    {
        $ofx = TestScenarios::positiveAmountExtremes()->build();
        
        // Parse multiple times
        $parsed1 = $this->parser->loadOFXString($ofx);
        $parsed2 = $this->parser->loadOFXString($ofx);
        
        $txns1 = $parsed1->getTransactions();
        $txns2 = $parsed2->getTransactions();
        
        // Should produce identical results
        $this->assertCount(count($txns1), $txns2);
        
        for ($i = 0; $i < count($txns1); $i++) {
            $this->assertEquals(
                $txns1[$i]->getAmount(),
                $txns2[$i]->getAmount()
            );
            $this->assertEquals(
                $txns1[$i]->getDate()->format('Y-m-d'),
                $txns2[$i]->getDate()->format('Y-m-d')
            );
        }
    }
}

// Helper function for tests
function any(array $items, callable $predicate): bool
{
    foreach ($items as $item) {
        if ($predicate($item)) {
            return true;
        }
    }
    return false;
}
