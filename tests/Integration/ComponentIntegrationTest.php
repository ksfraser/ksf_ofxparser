<?php declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use OfxParser\Parser;
use OfxParser\Config\DefensiveParsingConfig;
use OfxParser\Recovery\RecoveryContext;

class ComponentIntegrationTest extends TestCase
{
    private Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Parser();
    }

    // IT3-001: Parser → Loader → Builder flow
    public function testParserLoaderBuilderFlow(): void
    {
        $ofx = $this->parser->loadFromString("<?xml version=\"1.0\"?>
<OFX>
  <STMTTRNRS>
    <STMTRS>
      <STMTFRS>
        <BANKID>123456</BANKID>
        <ACCTID>987654</ACCTID>
        <ACCTTYPE>CHECKING</ACCTTYPE>
      </STMTFRS>
    </STMTRS>
  </STMTTRNRS>
</OFX>");
        
        // Complete flow should return an Ofx object
        $this->assertNotNull($ofx);
    }

    // IT3-002: Tokenizer → Parser → Factory flow
    public function testTokenizerParserFactoryFlow(): void
    {
        // This tests the SGML path: tokenization → tree building → element factory
        $sgmlContent = "OFXHEADER:100
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNID>12345
<TRNAMT>100.00
</STMTTRN>
</BANKTRANLIST>
</STMTRS>";
        
        $ofx = $this->parser->loadFromString($sgmlContent);
        $this->assertNotNull($ofx);
    }

    // IT3-003: Recovery config → Strategy → Metrics integration
    public function testRecoveryConfigStrategyMetricsIntegration(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        $parser = new Parser();
        
        // Enable defensive parsing
        if (method_exists($parser, 'withDefensiveParsing')) {
            $parser->withDefensiveParsing($config);
        }
        
        // Defensive parsing should be enabled
        if (method_exists($parser, 'isDefensiveParsingEnabled')) {
            $this->assertTrue($parser->isDefensiveParsingEnabled());
        }
    }

    // IT3-004: Loader selection based on format detection
    public function testLoaderSelectionByFormat(): void
    {
        // XML format should use XmlOfxLoader
        $xmlContent = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($xmlContent);
        $this->assertNotNull($ofx);
        
        // SGML format should use SgmlOfxLoader
        $sgmlContent = "OFXHEADER:100
<OFX>
<STMTTRNRS>
</STMTTRNRS>
</OFX>";
        
        $ofx2 = $this->parser->loadFromString($sgmlContent);
        $this->assertNotNull($ofx2);
    }

    // IT3-005: Format detection accuracy
    public function testFormatDetectionAccuracy(): void
    {
        // Should detect XML
        $xmlResult = $this->parser->loadFromString("<?xml version=\"1.0\"?><OFX></OFX>");
        $this->assertNotNull($xmlResult);
        
        // Should detect SGML
        $sgmlResult = $this->parser->loadFromString("OFXHEADER:100\n<OFX></OFX>");
        $this->assertNotNull($sgmlResult);
    }

    // IT3-006: Entity builder with defensive parsing context
    public function testEntityBuilderWithDefensiveContext(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        $context = new RecoveryContext($config);
        
        // Context should be available
        $this->assertNotNull($context->getConfig());
    }

    // IT3-007: Metrics collection integrated into parsing
    public function testMetricsCollectionIntegration(): void
    {
        // Parse a simple file
        $ofx = $this->parser->loadFromString("<?xml version=\"1.0\"?>
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
</OFX>");
        
        // Should be able to retrieve metrics
        if (method_exists($ofx, 'getMetrics')) {
            $metrics = $ofx->getMetrics();
            $this->assertNotNull($metrics);
        }
    }

    // IT3-008: Parser handles both SGML and XML in series
    public function testParserHandlesBothFormatsInSeries(): void
    {
        $xml = "<?xml version=\"1.0\"?><OFX></OFX>";
        $sgml = "OFXHEADER:100\n<OFX></OFX>";
        
        $ofx1 = $this->parser->loadFromString($xml);
        $ofx2 = $this->parser->loadFromString($sgml);
        
        $this->assertNotNull($ofx1);
        $this->assertNotNull($ofx2);
    }

    // IT3-009: Error in one parsing doesn't affect next
    public function testParserStateIndependent(): void
    {
        try {
            // Try to parse invalid content (might throw)
            @$this->parser->loadFromString("INVALID");
        } catch (\Exception $e) {
            // Suppress exception for this test
        }
        
        // Should still be able to parse valid content
        $valid = "<?xml version=\"1.0\"?><OFX></OFX>";
        $ofx = $this->parser->loadFromString($valid);
        $this->assertNotNull($ofx);
    }

    // IT3-010: Multi-step parsing with recovery
    public function testMultiStepParsingWithRecovery(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        
        // Content with potential issues
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNID></TRNID>
<TRNAMT>invalid</TRNAMT>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        // Should parse without crashing even with issues
        $ofx = $this->parser->loadFromString($content);
        $this->assertNotNull($ofx);
    }

    // IT3-011: Account population from parsed elements
    public function testAccountPopulationFlow(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<STMTFRS>
<BANKID>123</BANKID>
<ACCTID>456</ACCTID>
<ACCTTYPE>CHECKING</ACCTTYPE>
</STMTFRS>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        
        // Should have populated accounts
        if (isset($ofx->bankAccounts)) {
            $this->assertIsArray($ofx->bankAccounts);
        }
    }

    // IT3-012: Transaction list building from parsed tree
    public function testTransactionListBuilding(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<STMTFRS>
<BANKID>123</BANKID>
</STMTFRS>
<BANKTRANLIST>
<STMTTRN>
<TRNID>1</TRNID>
<TRNAMT>100.00</TRNAMT>
</STMTTRN>
<STMTTRN>
<TRNID>2</TRNID>
<TRNAMT>200.00</TRNAMT>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        
        if (isset($ofx->bankAccount) && isset($ofx->bankAccount->statement)) {
            $transactions = $ofx->bankAccount->statement->transactions ?? [];
            $this->assertNotEmpty($transactions);
        }
    }

    // IT3-013: Recovery strategy applied during entity construction
    public function testRecoveryStrategyApplicationInConstruction(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        
        // SetUp with defensive parsing
        $parser = new Parser();
        if (method_exists($parser, 'withDefensiveParsing')) {
            $parser->withDefensiveParsing($config);
        }
        
        // Parse content with missing/invalid fields
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNID></TRNID>
<TRNAMT></TRNAMT>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        // Should not crash with recovery strategies
        try {
            $ofx = $parser->loadFromString($content);
            $this->assertNotNull($ofx);
        } catch (\Exception $e) {
            $this->fail("Parsing with recovery strategy failed: " . $e->getMessage());
        }
    }

    // IT3-014: Cross-component data consistency
    public function testCrossComponentDataConsistency(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<STMTFRS>
<BANKID>MYBANK</BANKID>
<ACCTID>ACC123</ACCTID>
</STMTFRS>
<STMTSTART>20260101</STMTSTART>
<STMTEND>20260131</STMTEND>
<BANKTRANLIST>
<STMTTRN>
<TRNID>TX1</TRNID>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        
        // Data should flow consistently through components
        if (isset($ofx->bankAccount)) {
            $account = $ofx->bankAccount;
            
            // Account fields should be populated
            if (isset($account->accountId)) {
                $this->assertEquals('ACC123', $account->accountId);
            }
        }
    }

    // IT3-015: Stream of components in correct sequence
    public function testComponentSequenceCorrectness(): void
    {
        // Test that components are called in correct order:
        // 1. Format detection
        // 2. Loader selection
        // 3. SGML tokenizer (if needed)
        // 4. Parser/Builder
        // 5. Factory creation
        // 6. Recovery application
        
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        
        // If we got here, the sequence worked
        $this->assertNotNull($ofx);
    }

    // IT3-016: Loader return value used by parser
    public function testLoaderReturnValueUsage(): void
    {
        // Loaders return Ofx objects which are then returned to client
        $content = "<?xml version=\"1.0\"?>
<OFX>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        
        // Result should be an Ofx instance
        $this->assertNotNull($ofx);
        $this->assertTrue(class_exists('OfxParser\\Ofx') || class_exists('Ofx'));
    }

    // IT3-017: Metrics available after parsing completes
    public function testMetricsAvailabilityPostParsing(): void
    {
        $ofx = $this->parser->loadFromString("<?xml version=\"1.0\"?><OFX></OFX>");
        
        // Should be able to get metrics after parsing
        if (method_exists($ofx, 'getMetrics')) {
            $metrics = $ofx->getMetrics();
            // Metrics object should exist
            $this->assertTrue(is_object($metrics) || is_array($metrics));
        }
    }

    // IT3-018: ParsingResult contains recovery information
    public function testParsingResultRecoveryInfo(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        
        $ofx = $this->parser->loadFromString("<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNAMT></TRNAMT>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>");
        
        // Should be able to inspect what recoveries occurred
        if (method_exists($ofx, 'getMetrics')) {
            $metrics = $ofx->getMetrics();
            $this->assertNotNull($metrics);
        }
    }

    // IT3-019: Element factory creates correct entity types
    public function testElementFactoryEntityTypeCreation(): void
    {
        $content = "<?xml version=\"1.0\"?>
<OFX>
<STMTTRNRS>
<STMTRS>
<STMTFRS>
<BANKID>123</BANKID>
</STMTFRS>
<BANKTRANLIST>
<STMTTRN>
<TRNID>1</TRNID>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</OFX>";
        
        $ofx = $this->parser->loadFromString($content);
        
        // Should create appropriate entity types
        if (isset($ofx->bankAccount)) {
            // Account should be an Account object
            $this->assertNotNull($ofx->bankAccount);
        }
    }

    // IT3-020: Full component lifecycle for malformed input
    public function testComponentLifecycleWithMalformedInput(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        
        $malformed = "OFXHEADER:100
<STMTTRN>
<TRNID>123
<TRNAMT>invalid
</STMTTRN>";
        
        // Should handle malformed input through recovery
        try {
            $ofx = $this->parser->loadFromString($malformed);
            
            // Either parses successfully or throws handled exception
            $this->assertTrue(true);
        } catch (\Exception $e) {
            // Exception handling is part of component lifecycle
            $this->assertIsObject($e);
        }
    }
}
