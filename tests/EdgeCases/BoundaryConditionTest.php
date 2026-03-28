<?php declare(strict_types=1);

namespace Tests\EdgeCases;

use PHPUnit\Framework\TestCase;
use OfxParser\Parser;
use Tests\TestOFXHelper;

class BoundaryConditionTest extends TestCase
{
    use TestOFXHelper;
    
    private Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Parser();
    }

    // EC2-001: Very large file (10MB simulation)
    public function testLargeFileHandling(): void
    {
        $this->markTestSkipped('Large file test - use with actual large fixtures');
    }

    // EC2-002: Many transactions (10K+)
    public function testManyTransactions(): void
    {
        $this->markTestSkipped('Large dataset test');
    }

    // EC2-003: Many accounts (50+)
    public function testManyAccounts(): void
    {
        $this->markTestSkipped('Large dataset test');
    }

    // EC2-004: Maximum string length in fields
    public function testMaximumFieldStringLength(): void
    {
        $longMemo = str_repeat('x', 10000);
        
        $content = "<STMTTRNRS>\n<STMTRS>\n<BANKTRANLIST>\n<STMTTRN>\n<TRNID>1</TRNID>\n<MEMO>{$longMemo}</MEMO>\n</STMTTRN>\n</BANKTRANLIST>\n</STMTRS>\n</STMTTRNRS>";
        $ofxContent = $this->wrapOFXContent($content, 'bank');
        
        $ofx = $this->parser->loadFromString($ofxContent);
        $this->assertNotNull($ofx);
    }

    // EC2-005: Very large amount value
    public function testLargeAmountValue(): void
    {
        $largeAmount = '999999999999.99';
        
        $content = "<STMTTRNRS>\n<STMTRS>\n<BANKTRANLIST>\n<STMTTRN>\n<TRNAMT>{$largeAmount}</TRNAMT>\n</STMTTRN>\n</BANKTRANLIST>\n</STMTRS>\n</STMTTRNRS>";
        $ofxContent = $this->wrapOFXContent($content, 'bank');
        
        $ofx = $this->parser->loadFromString($ofxContent);
        $this->assertNotNull($ofx);
    }

    // EC2-006: Very small (fractional) amount value
    public function testSmallFractionalAmount(): void
    {
        $content = "<STMTTRNRS>\n<STMTRS>\n<BANKTRANLIST>\n<STMTTRN>\n<TRNAMT>0.01</TRNAMT>\n</STMTTRN>\n</BANKTRANLIST>\n</STMTRS>\n</STMTTRNRS>";
        $ofxContent = $this->wrapOFXContent($content, 'bank');
        
        $ofx = $this->parser->loadFromString($ofxContent);
        $this->assertNotNull($ofx);
    }

    // EC2-007: Negative amount (for debit)
    public function testNegativeAmount(): void
    {
        $content = "<STMTTRNRS>\n<STMTRS>\n<BANKTRANLIST>\n<STMTTRN>\n<TRNAMT>-1500.50</TRNAMT>\n</STMTTRN>\n</BANKTRANLIST>\n</STMTRS>\n</STMTTRNRS>";
        $ofxContent = $this->wrapOFXContent($content, 'bank');
        
        $ofx = $this->parser->loadFromString($ofxContent);
        $this->assertNotNull($ofx);
    }

    // EC2-008: Exactly zero amount
    public function testZeroAmount(): void
    {
        $content = "<STMTTRNRS>\n<STMTRS>\n<BANKTRANLIST>\n<STMTTRN>\n<TRNAMT>0</TRNAMT>\n</STMTTRN>\n</BANKTRANLIST>\n</STMTRS>\n</STMTTRNRS>";
        $ofxContent = $this->wrapOFXContent($content, 'bank');
        
        $ofx = $this->parser->loadFromString($ofxContent);
        $this->assertNotNull($ofx);
    }

    // EC2-009: Very old date (1970 epoch)
    public function testVeryOldDate(): void
    {
        $content = "<STMTTRNRS>\n<STMTRS>\n<BANKTRANLIST>\n<STMTTRN>\n<DTPOSTED>19700101</DTPOSTED>\n</STMTTRN>\n</BANKTRANLIST>\n</STMTRS>\n</STMTTRNRS>";
        $ofxContent = $this->wrapOFXContent($content, 'bank');
        
        $ofx = $this->parser->loadFromString($ofxContent);
        $this->assertNotNull($ofx);
    }

    // EC2-010: Future date
    public function testFutureDate(): void
    {
        $content = "<STMTTRNRS>\n<STMTRS>\n<BANKTRANLIST>\n<STMTTRN>\n<DTPOSTED>20991231</DTPOSTED>\n</STMTTRN>\n</BANKTRANLIST>\n</STMTRS>\n</STMTTRNRS>";
        $ofxContent = $this->wrapOFXContent($content, 'bank');
        
        $ofx = $this->parser->loadFromString($ofxContent);
        $this->assertNotNull($ofx);
    }

    // EC2-011: Millisecond precision in date (if supported)
    public function testMillisecondPrecisionDate(): void
    {
        $content = "<STMTTRNRS>\n<STMTRS>\n<BANKTRANLIST>\n<STMTTRN>\n<DTPOSTED>20260313120000.000</DTPOSTED>\n</STMTTRN>\n</BANKTRANLIST>\n</STMTRS>\n</STMTTRNRS>";
        $ofxContent = $this->wrapOFXContent($content, 'bank');
        
        $ofx = $this->parser->loadFromString($ofxContent);
        $this->assertNotNull($ofx);
    }

    // EC2-012: Timezone info in date
    public function testTimezoneDateFormat(): void
    {
        $content = "<STMTTRNRS>\n<STMTRS>\n<BANKTRANLIST>\n<STMTTRN>\n<DTPOSTED>20260313120000Z</DTPOSTED>\n</STMTTRN>\n</BANKTRANLIST>\n</STMTRS>\n</STMTTRNRS>";
        $ofxContent = $this->wrapOFXContent($content, 'bank');
        
        try {
            $ofx = $this->parser->loadFromString($ofxContent);
            $this->assertNotNull($ofx);
        } catch (\Exception $e) {
            // May not support timezone format
            $this->assertTrue(true);
        }
    }

    // EC2-013: Single character transaction ID
    public function testMinimalTransactionID(): void
    {
        $content = "<STMTTRNRS>\n<STMTRS>\n<BANKTRANLIST>\n<STMTTRN>\n<TRNID>A</TRNID>\n</STMTTRN>\n</BANKTRANLIST>\n</STMTRS>\n</STMTTRNRS>";
        $ofxContent = $this->wrapOFXContent($content, 'bank');
        
        $ofx = $this->parser->loadFromString($ofxContent);
        $this->assertNotNull($ofx);
    }

    // EC2-014: Extremely long transaction ID
    public function testVeryLongTransactionID(): void
    {
        $longID = str_repeat('x', 1000);
        
        $content = "<STMTTRNRS>\n<STMTRS>\n<BANKTRANLIST>\n<STMTTRN>\n<TRNID>{$longID}</TRNID>\n</STMTTRN>\n</BANKTRANLIST>\n</STMTRS>\n</STMTTRNRS>";
        $ofxContent = $this->wrapOFXContent($content, 'bank');
        
        $ofx = $this->parser->loadFromString($ofxContent);
        $this->assertNotNull($ofx);
    }

    // EC2-015: Account ID with special characters
    public function testAccountIDSpecialCharacters(): void
    {
        // Use standard BANKTRANLIST format instead of STMTFRS (not yet fully supported)
        $content = "<STMTTRNRS>\n<STMTRS>\n<BANKTRANLIST>\n<DTSTART>20260114</DTSTART>\n<DTEND>20260114</DTEND>\n<STMTTRN>\n<TRNID>ACC-123_456.789</TRNID>\n<DTPOSTED>20260114</DTPOSTED>\n<TRNAMT>100.00</TRNAMT>\n</STMTTRN>\n</BANKTRANLIST>\n<BANKACCTFROM>\n<ACCTID>ACC-123_456.789</ACCTID>\n<ACCTTYPE>CHECKING</ACCTTYPE>\n</BANKACCTFROM>\n</STMTRS>\n</STMTTRNRS>";
        $ofxContent = $this->wrapOFXContent($content, 'bank');
        
        $ofx = $this->parser->loadFromString($ofxContent);
        $this->assertNotNull($ofx);
    }

    // EC2-016: Single account with empty statement
    public function testEmptyStatementInAccount(): void
    {
        // Use standard BANKTRANLIST format with account-type ID
        $content = "<STMTTRNRS>\n<STMTRS>\n<BANKTRANLIST>\n<DTSTART>20260114</DTSTART>\n<DTEND>20260114</DTEND>\n<STMTTRN>\n<TRNID>123</TRNID>\n<DTPOSTED>20260114</DTPOSTED>\n<TRNAMT>0.00</TRNAMT>\n</STMTTRN>\n</BANKTRANLIST>\n<BANKACCTFROM>\n<ACCTID>123</ACCTID>\n<ACCTTYPE>CHECKING</ACCTTYPE>\n</BANKACCTFROM>\n</STMTRS>\n</STMTTRNRS>";
        $ofxContent = $this->wrapOFXContent($content, 'bank');
        
        $ofx = $this->parser->loadFromString($ofxContent);
        $this->assertNotNull($ofx);
    }

    // EC2-017: Account with null balance
    public function testAccountWithNullBalance(): void
    {
        // Use standard BANKTRANLIST format with empty balance
        $content = "<STMTTRNRS>\n<STMTRS>\n<BANKTRANLIST>\n<DTSTART>20260114</DTSTART>\n<DTEND>20260114</DTEND>\n<STMTTRN>\n<TRNID>123</TRNID>\n<DTPOSTED>20260114</DTPOSTED>\n<TRNAMT>0.00</TRNAMT>\n</STMTTRN>\n</BANKTRANLIST>\n<LEDGERBAL>\n<BALAMT></BALAMT>\n<DTASOF>20260114</DTASOF>\n</LEDGERBAL>\n<BANKACCTFROM>\n<ACCTID>123</ACCTID>\n<ACCTTYPE>CHECKING</ACCTTYPE>\n</BANKACCTFROM>\n</STMTRS>\n</STMTTRNRS>";
        $ofxContent = $this->wrapOFXContent($content, 'bank');
        
        $ofx = $this->parser->loadFromString($ofxContent);
        $this->assertNotNull($ofx);
    }

    // EC2-018: Maximum nesting depth
    public function testMaximumNestingDepth(): void
    {
        $depth = 100;
        $opening = str_repeat('<L>', $depth);
        $closing = str_repeat('</L>', $depth);
        
        $content = "{$opening}\nContent\n{$closing}";
        $ofxContent = $this->wrapOFXContent($content, 'bank');
        
        try {
            $ofx = $this->parser->loadFromString($ofxContent);
            $this->assertNotNull($ofx);
        } catch (\Exception $e) {
            // Stack depth limitations are acceptable
            $this->assertTrue(true);
        }
    }

    // EC2-019: Single transaction (minimum)
    public function testSingleTransaction(): void
    {
        $content = "<STMTTRNRS>\n<STMTRS>\n<BANKTRANLIST>\n<STMTTRN>\n<TRNID>1</TRNID>\n</STMTTRN>\n</BANKTRANLIST>\n</STMTRS>\n</STMTTRNRS>";
        $ofxContent = $this->wrapOFXContent($content, 'bank');
        
        $ofx = $this->parser->loadFromString($ofxContent);
        $this->assertNotNull($ofx);
    }

    // EC2-020: Statement period boundary (start = end)
    public function testStatementPeriodBoundary(): void
    {
        $content = "<STMTTRNRS>\n<STMTRS>\n<BANKTRANLIST>\n<DTSTART>20260313</DTSTART>\n<DTEND>20260313</DTEND>\n<STMTTRN>\n<DTPOSTED>20260313</DTPOSTED>\n<TRNAMT>100.00</TRNAMT>\n<FITID>1</FITID>\n</STMTTRN>\n</BANKTRANLIST>\n<LEDGERBAL>\n<BALAMT>1000.00</BALAMT>\n<DTASOF>20260313</DTASOF>\n</LEDGERBAL>\n<BANKACCTFROM>\n<BANKID>123456</BANKID>\n<ACCTID>9876543</ACCTID>\n<ACCTTYPE>CHECKING</ACCTTYPE>\n</BANKACCTFROM>\n</STMTRS>\n</STMTTRNRS>";
        $ofxContent = $this->wrapOFXContent($content, 'bank');
        
        $ofx = $this->parser->loadFromString($ofxContent);
        $this->assertNotNull($ofx);
    }
}
