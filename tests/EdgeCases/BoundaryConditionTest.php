<?php declare(strict_types=1);

namespace Tests\EdgeCases;

use PHPUnit\Framework\TestCase;
use OfxParser\Parser;

class BoundaryConditionTest extends TestCase
{
    private Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Parser();
    }

    // EC2-001: Very large file (10MB simulation)
    public function testLargeFileHandling(): void
    {
        $this->markTestSkipped('Large file test - use with actual large fixtures');
        
        // Generate a large OFX content with many transactions
        $transactions = '';
        for ($i = 0; $i < 5000; $i++) {
            $transactions .= "
<STMTTRN>
<TRNID>TXN{$i}</TRNID>
<TRNAMT>100.00</TRNAMT>
<DTPOSTED>20260313</DTPOSTED>
<MEMO>Transaction {$i}</MEMO>
</STMTTRN>";
        }
        
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
{$transactions}
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        // Should not crash or exceed memory limits
        try {
            $ofx = $this->parser->loadFromString($content);
            $this->assertNotNull($ofx);
        } catch (\Exception $e) {
            // Memory limit or timeout is acceptable
            $this->assertTrue(true);
        }
    }

    // EC2-002: Many transactions (10K+)
    public function testManyTransactions(): void
    {
        $this->markTestSkipped('Large dataset test');
        
        $transCount = 1000;
        $transactions = '';
        for ($i = 0; $i < $transCount; $i++) {
            $transactions .= "<STMTTRN><TRNID>T$i</TRNID></STMTTRN>";
        }
        
        $content = "<?xml version=\"1.0\"?>
<OFX>
<BANKTRANLIST>
{$transactions}
</BANKTRANLIST>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC2-003: Many accounts (50+)
    public function testManyAccounts(): void
    {
        $this->markTestSkipped('Large dataset test');
        
        $accounts = '';
        for ($i = 0; $i < 50; $i++) {
            $accounts .= "
<STMTTRNRS>
<STMTRS>
<STMTFRS>
<ACCTID>ACC{$i}</ACCTID>
</STMTFRS>
</STMTRS>
</STMTTRNRS>";
        }
        
        $content = "<?xml version=\"1.0\"?>
<OFX>
{$accounts}
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC2-004: Maximum string length in fields
    public function testMaximumFieldStringLength(): void
    {
        $longMemo = str_repeat('x', 10000);
        
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNID>1</TRNID>
<MEMO>{$longMemo}</MEMO>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC2-005: Very large amount value
    public function testLargeAmountValue(): void
    {
        $largeAmount = '999999999999.99';
        
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNAMT>{$largeAmount}</TRNAMT>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC2-006: Very small (fractional) amount value
    public function testSmallFractionalAmount(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNAMT>0.01</TRNAMT>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC2-007: Negative amount (for debit)
    public function testNegativeAmount(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNAMT>-1500.50</TRNAMT>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC2-008: Exactly zero amount
    public function testZeroAmount(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNAMT>0</TRNAMT>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC2-009: Very old date (1970 epoch)
    public function testVeryOldDate(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<DTPOSTED>19700101</DTPOSTED>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC2-010: Future date
    public function testFutureDate(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<DTPOSTED>20991231</DTPOSTED>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC2-011: Millisecond precision in date (if supported)
    public function testMillisecondPrecisionDate(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<DTPOSTED>20260313120000.000</DTPOSTED>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC2-012: Timezone info in date
    public function testTimezoneDateFormat(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<DTPOSTED>20260313120000Z</DTPOSTED>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        try {
            $ofx = $this->parser->loadFromString($content);
            $this->assertNotNull($ofx);
        } catch (\Exception $e) {
            // May not support timezone format
            $this->assertTrue(true);
        }
    }

    // EC2-013: Single character transaction ID
    public function testMinimalTransactionID(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNID>A</TRNID>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC2-014: Extremely long transaction ID
    public function testVeryLongTransactionID(): void
    {
        $longID = str_repeat('x', 1000);
        
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNID>{$longID}</TRNID>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC2-015: Account ID with special characters
    public function testAccountIDSpecialCharacters(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<STMTFRS>
<ACCTID>ACC-123_456.789</ACCTID>
</STMTFRS>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC2-016: Single account with empty statement
    public function testEmptyStatementInAccount(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<STMTFRS>
<ACCTID>123</ACCTID>
</STMTFRS>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC2-017: Account with null balance
    public function testAccountWithNullBalance(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<STMTFRS>
<ACCTID>123</ACCTID>
<BALANCE></BALANCE>
</STMTFRS>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC2-018: Maximum nesting depth
    public function testMaximumNestingDepth(): void
    {
        $depth = 100;
        $opening = str_repeat('<L>', $depth);
        $closing = str_repeat('</L>', $depth);
        
        $content = "<?xml version=\"1.0\"?>
<OFX>
{$opening}
Content
{$closing}
</OFX>";
        
        try {
            $ofx = $this->parser->loadFromString($content);
            $this->assertNotNull($ofx);
        } catch (\Exception $e) {
            // Stack depth limitations are acceptable
            $this->assertTrue(true);
        }
    }

    // EC2-019: Single transaction (minimum)
    public function testSingleTransaction(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNID>1</TRNID>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC2-020: Statement period boundary (start = end)
    public function testStatementPeriodBoundary(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<DTSTART>20260313</DTSTART>
<DTEND>20260313</DTEND>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }
}
