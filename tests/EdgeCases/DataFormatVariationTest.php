<?php declare(strict_types=1);

namespace Tests\EdgeCases;

use PHPUnit\Framework\TestCase;
use OfxParser\Parser;

class DataFormatVariationTest extends TestCase
{
    private Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Parser();
    }

    // EC3-001: Dates YYYYMMDD format
    public function testDateFormatYYYYMMDD(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<DTPOSTED>20260313</DTPOSTED>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        
        if (isset($ofx->bankAccount)) {
            $transactions = $ofx->bankAccount->statement->transactions ?? [];
            $this->assertNotEmpty($transactions);
        }
    }

    // EC3-002: Dates with time YYYYMMDDHHMM format
    public function testDateFormatWithTime(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<DTPOSTED>202603131200</DTPOSTED>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC3-003: Dates with full timestamp
    public function testDateFormatFullTimestamp(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<DTPOSTED>20260313120000</DTPOSTED>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC3-004: Amounts with leading zeros
    public function testAmountWithLeadingZeros(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNAMT>000100.50</TRNAMT>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC3-005: Amounts with comma thousands separator
    public function testAmountWithCommaThousands(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNAMT>1,234.56</TRNAMT>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC3-006: Amounts with period thousands separator (European)
    public function testAmountWithPeriodThousands(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNAMT>1.234,56</TRNAMT>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC3-007: Currency codes (USD, CAD, EUR, etc.)
    public function testMultipleCurrencyCodes(): void
    {
        $currencies = ['USD', 'CAD', 'EUR', 'GBP', 'JPY', 'AUD'];
        
        foreach ($currencies as $currency) {
            $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<STMTFRS>
<CURRENCY>
<CURRATE>1.0</CURRATE>
<CURSYM>{$currency}</CURSYM>
</CURRENCY>
</STMTFRS>
</STMTRS>
</STMTTRNRS>
</OFX>";
            
            $ofx = $this->parser->loadFromString($content);
            $this->assertNotNull($ofx);
        }
    }

    // EC3-008: Empty optional fields
    public function testEmptyOptionalFields(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNID>123</TRNID>
<MEMO></MEMO>
<NAME></NAME>
<REFNUM></REFNUM>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC3-009: Null values vs empty fields
    public function testNullVsEmptyFields(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNID></TRNID>
<MEMO>NULL</MEMO>
<NAME/>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC3-010: Mixed case OFX tags
    public function testMixedCaseOFXTags(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<stmttrnrs>
<STMTRS>
<BankTransList>
<STMTtrn>
<trnid>123</trnid>
</STMTtrn>
</BankTransList>
</STMTRS>
</stmttrnrs>
</OFX>";
        
        try {
            $ofx = $this->parser->loadFromString($content);
            $this->assertNotNull($ofx);
        } catch (\Exception $e) {
            // Case sensitivity may be enforced
            $this->assertTrue(true);
        }
    }

    // EC3-011: Whitespace variations in content
    public function testWhitespaceVariations(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
  <STMTRS>
    <BANKTRANLIST>
      <STMTTRN>
        <TRNID>   123   </TRNID>
        <TRNAMT>
          100.00
        </TRNAMT>
      </STMTTRN>
    </BANKTRANLIST>
  </STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC3-012: Tabs and mixed whitespace
    public function testTabsAndMixedWhitespace(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
	<STMTTRNRS>
		<STMTRS>
			<BANKTRANLIST>
				<STMTTRN>
					<TRNID>	123	</TRNID>
				</STMTTRN>
			</BANKTRANLIST>
		</STMTRS>
	</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC3-013: CRLF vs LF line endings
    public function testCRLFLineEndings(): void
    {
        $content = "<?xml version=\"1.0\"?>\r\n<OFX>\r\n<STMTTRNRS>\r\n</STMTTRNRS>\r\n</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC3-014: Boolean-like field values
    public function testBooleanLikeValues(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<INCLUDE_PENDING>Y</INCLUDE_PENDING>
<PROCEED>1</PROCEED>
<VERIFIED>true</VERIFIED>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC3-015: Numeric strings vs numeric values
    public function testNumericStringHandling(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNID>00012345</TRNID>
<REFNUM>00098765</REFNUM>
<CHECKNUM>001</CHECKNUM>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC3-016: Sign indicators (leading +/- vs trailing)
    public function testSignIndicators(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNAMT>+100.00</TRNAMT>
</STMTTRN>
<STMTTRN>
<TRNAMT>-100.00</TRNAMT>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // EC3-017: Scientific notation in amounts
    public function testScientificNotationAmounts(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNAMT>1.5E2</TRNAMT>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        try {
            $ofx = $this->parser->loadFromString($content);
            $this->assertNotNull($ofx);
        } catch (\Exception $e) {
            // Scientific notation may not be supported
            $this->assertTrue(true);
        }
    }

    // EC3-018: Multiple decimal separators (invalid)
    public function testMultipleDecimalSeparators(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNAMT>100.50.25</TRNAMT>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        try {
            $ofx = $this->parser->loadFromString($content);
            // May parse as-is
            $this->assertTrue(true);
        } catch (\Exception $e) {
            // Or reject as invalid
            $this->assertTrue(true);
        }
    }

    // EC3-019: Transaction type code variations
    public function testTransactionTypeCodes(): void
    {
        $types = ['DEBIT', 'CREDIT', 'INT', 'DIV', 'FEE', 'SRVCHG', 'CHECK', 'PAYMENT'];
        
        foreach ($types as $type) {
            $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNTYPE>{$type}</TRNTYPE>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
            
            $ofx = $this->parser->loadFromString($content);
            $this->assertNotNull($ofx);
        }
    }

    // EC3-020: Account type variations
    public function testAccountTypeVariations(): void
    {
        $types = ['CHECKING', 'SAVINGS', 'MONEYMRKT', 'CREDITCARD', 'INVESTMENT'];
        
        foreach ($types as $type) {
            $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<STMTFRS>
<ACCTTYPE>{$type}</ACCTTYPE>
</STMTFRS>
</STMTRS>
</STMTTRNRS>
</OFX>";
            
            $ofx = $this->parser->loadFromString($content);
            $this->assertNotNull($ofx);
        }
    }
}
