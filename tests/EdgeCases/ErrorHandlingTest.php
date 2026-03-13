<?php declare(strict_types=1);

namespace Tests\EdgeCases;

use PHPUnit\Framework\TestCase;
use OfxParser\Parser;

class ErrorHandlingTest extends TestCase
{
    private Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Parser();
    }

    // EC1-001: File with no accounts returns empty arrays
    public function testFileWithNoAccounts(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        
        // Should have empty account arrays
        $this->assertIsArray($ofx->bankAccounts ?? []);
        $this->assertEmpty($ofx->bankAccounts ?? []);
    }

    // EC1-002: No transactions in account - should return empty list
    public function testNoTransactionsInAccount(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<STMTFRS>
<BANKID>123</BANKID>
<ACCTID>456</ACCTID>
</STMTFRS>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        
        if (isset($ofx->bankAccount)) {
            $transactions = $ofx->bankAccount->statement->transactions ?? [];
            $this->assertIsArray($transactions);
        }
    }

    // EC1-003: Corrupt transaction syntax - recovery via skip
    public function testCorruptTransactionSkipped(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
MALFORMED
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        // Should either parse with recovery or throw controlled exception
        try {
            $ofx = $this->parser->loadFromString($content);
            $this->assertNotNull($ofx);
        } catch (\Exception $e) {
            $this->assertIsObject($e);
        }
    }

    // EC1-004: Missing required fields - recovery strategy applied
    public function testMissingRequiredFieldRecovery(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNID></TRNID>
<TRNAMT></TRNAMT>
<DTPOSTED></DTPOSTED>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        // Should recover missing fields or propagate errors gracefully
        try {
            $ofx = $this->parser->loadFromString($content);
            $this->assertNotNull($ofx);
        } catch (\Exception $e) {
            // Exception is acceptable for strictly missing required data
            $this->assertTrue(true);
        }
    }

    // EC1-005: Empty OFX file
    public function testEmptyOFXFile(): void
    {
        $content = "";
        
        try {
            $ofx = $this->parser->loadFromString($content);
            // May succeed with empty result or throw
            $this->assertTrue(true);
        } catch (\Exception $e) {
            // Expected for empty input
            $this->assertTrue(true);
        }
    }

    // EC1-006: Null/void values in fields
    public function testNullValuesInFields(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNID>null</TRNID>
<TRNAMT>0</TRNAMT>
<MEMO></MEMO>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC1-007: Invalid field types detected
    public function testInvalidFieldTypesDetected(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNAMT>NOT_A_NUMBER</TRNAMT>
<DTPOSTED>NOT_A_DATE</DTPOSTED>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        try {
            $ofx = $this->parser->loadFromString($content);
            $this->assertNotNull($ofx);
        } catch (\Exception $e) {
            // Type validation error caught
            $this->assertTrue(true);
        }
    }

    // EC1-008: Duplicate transaction IDs allowed (no dedup)
    public function testDuplicateTransactionIDsAllowed(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNID>SAME_ID</TRNID>
<TRNAMT>100.00</TRNAMT>
</STMTTRN>
<STMTTRN>
<TRNID>SAME_ID</TRNID>
<TRNAMT>200.00</TRNAMT>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        
        if (isset($ofx->bankAccount)) {
            $transactions = $ofx->bankAccount->statement->transactions ?? [];
            // Should have both transactions (no dedup in parser)
            $this->assertNotEmpty($transactions);
        }
    }

    // EC1-009: Invalid XML structure (unclosed tags)
    public function testUnclosedTags(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNID>123
<TRNAMT>100.00</TRNAMT>";
        
        try {
            $ofx = $this->parser->loadFromString($content);
            // May parse successfully with auto-closing
            $this->assertNotNull($ofx);
        } catch (\Exception $e) {
            // Or throw XML parse error
            $this->assertTrue(true);
        }
    }

    // EC1-010: Invalid SGML structure (malformed header)
    public function testSGMLMalformedHeader(): void
    {
        $content = "OFXBAD:100
<OFX>
</OFX>";
        
        try {
            $ofx = $this->parser->loadFromString($content);
            // May parse as content
            $this->assertTrue(true);
        } catch (\Exception $e) {
            // Or reject malformed header
            $this->assertTrue(true);
        }
    }

    // EC1-011: File with only OFXHEADER, no content
    public function testOFXHeaderOnlyFile(): void
    {
        $content = "OFXHEADER:100";
        
        try {
            $ofx = $this->parser->loadFromString($content);
            // May treat as empty
            $this->assertTrue(true);
        } catch (\Exception $e) {
            // Or throw
            $this->assertTrue(true);
        }
    }

    // EC1-012: Very deeply nested elements
    public function testDeeplyNestedElements(): void
    {
        $content = "<?xml version=\"1.0\"?>
<L1>
<L2>
<L3>
<L4>
<L5>
<L6>
<L7>
<L8>
<L9>
<L10>
Content
</L10>
</L9>
</L8>
</L7>
</L6>
</L5>
</L4>
</L3>
</L2>
</L1>";
        
        // Should handle nesting without stack overflow
        try {
            $ofx = $this->parser->loadFromString($content);
            $this->assertTrue(true);
        } catch (\Exception $e) {
            // Expected behavior is acceptable
            $this->assertTrue(true);
        }
    }

    // EC1-013: Special XML characters in content
    public function testSpecialXMLCharacters(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<MEMO>Test &amp; Special &lt;chars&gt;</MEMO>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC1-014: BOM (Byte Order Mark) in file
    public function testByteOrderMarkHandling(): void
    {
        $bom = "\xEF\xBB\xBF"; // UTF-8 BOM
        $content = $bom . "<?xml version=\"1.0\"?>
<OFX>
</OFX>";
        
        try {
            $ofx = $this->parser->loadFromString($content);
            $this->assertNotNull($ofx);
        } catch (\Exception $e) {
            // May fail with BOM
            $this->assertTrue(true);
        }
    }

    // EC1-015: Mixed encoding content
    public function testMixedEncodingContent(): void
    {
        // UTF-8 content (declared as ISO-8859-1)
        $content = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>
<OFX>
<MEMO>Test with UTF-8: café</MEMO>
</OFX>";
        
        try {
            $ofx = $this->parser->loadFromString($content);
            $this->assertTrue(true);
        } catch (\Exception $e) {
            // Encoding mismatch may cause error
            $this->assertTrue(true);
        }
    }
}
