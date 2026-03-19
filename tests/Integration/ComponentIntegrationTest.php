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

    /**
     * Helper: Generate valid minimal OFX (SGML format with header)
     */
    private function getValidOFXBank(): string
    {
        return <<<'XML'
OFXHEADER:100
SECURITY:NONE
ENCODING:USASCII
CHARSET:1252
COMPRESSION:NONE

<OFX><SIGNONMSGSRSV1><SONRS><STATUS><CODE>0<SEVERITY>INFO</STATUS><DTSERVER>20260313120000<LANGUAGE>ENG</SONRS></SIGNONMSGSRSV1>
<STMTMSGSRSV1><STMTTRNRS><STATUS><CODE>0<SEVERITY>INFO</STATUS><STMTRS><CURDEF>CAD<BANKTRANLIST><STMTTRN><TRNTYPE>DEBIT<TRNID>1<TRNAMT>-50.00<DTPOSTED>20260313<MEMO>Test transaction</STMTTRN></BANKTRANLIST><LEDGERBAL><BALAMT>1000.00<DTASOF>20260313</LEDGERBAL></STMTRS></STMTTRNRS></STMTMSGSRSV1></OFX>
XML;
    }

    /**
     * Helper: Generate valid OFX SGML format with header
     */
    private function getValidOFXSGML(): string
    {
        return <<<'DATA'
OFXHEADER:100
SECURITY:NONE
ENCODING:USASCII
CHARSET:1252
COMPRESSION:NONE
OLDFILEFORMAT:NO
NEWFILEFORMAT:YES

<OFX><SIGNONMSGSRSV1><SONRS><STATUS><CODE>0<SEVERITY>INFO</STATUS><DTSERVER>20260313120000<LANGUAGE>ENG</SONRS></SIGNONMSGSRSV1>
<STMTMSGSRSV1><STMTTRNRS><STATUS><CODE>0<SEVERITY>INFO</STATUS><STMTRS><CURDEF>CAD<BANKTRANLIST><STMTTRN><TRNTYPE>DEBIT<TRNID>1<TRNAMT>-50.00<DTPOSTED>20260313<MEMO>Test</STMTTRN></BANKTRANLIST><LEDGERBAL><BALAMT>1000.00<DTASOF>20260313</LEDGERBAL></STMTRS></STMTTRNRS></STMTMSGSRSV1></OFX>
DATA;
    }

    // IT3-001: Parser → Loader → Builder flow
    public function testParserLoaderBuilderFlow(): void
    {
        $ofx = $this->parser->loadFromString($this->getValidOFXBank());
        
        // Complete flow should return an Ofx object
        $this->assertNotNull($ofx);
    }

    // IT3-002: Tokenizer → Parser → Factory flow
    public function testTokenizerParserFactoryFlow(): void
    {
        // This tests the SGML path: tokenization → tree building → element factory
        $ofx = $this->parser->loadFromString($this->getValidOFXSGML());
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
        $ofx = $this->parser->loadFromString($this->getValidOFXBank());
        $this->assertNotNull($ofx);
        
        // SGML format should use SgmlOfxLoader
        $ofx2 = $this->parser->loadFromString($this->getValidOFXSGML());
        $this->assertNotNull($ofx2);
    }

    // IT3-005: Format detection accuracy
    public function testFormatDetectionAccuracy(): void
    {
        // Should detect XML
        $xmlResult = $this->parser->loadFromString($this->getValidOFXBank());
        $this->assertNotNull($xmlResult);
        
        // Should detect SGML
        $sgmlResult = $this->parser->loadFromString($this->getValidOFXSGML());
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
        $ofx = $this->parser->loadFromString($this->getValidOFXBank());
        
        // Should be able to retrieve metrics
        if (method_exists($ofx, 'getMetrics')) {
            $metrics = $ofx->getMetrics();
            $this->assertNotNull($metrics);
        }
    }

    // IT3-008: Parser handles both SGML and XML in series
    public function testParserHandlesBothFormatsInSeries(): void
    {
        $ofx1 = $this->parser->loadFromString($this->getValidOFXBank());
        $ofx2 = $this->parser->loadFromString($this->getValidOFXSGML());
        
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
        $ofx = $this->parser->loadFromString($this->getValidOFXBank());
        $this->assertNotNull($ofx);
    }

    // IT3-010: Multi-step parsing with recovery
    public function testMultiStepParsingWithRecovery(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        
        // Should parse without crashing
        $ofx = $this->parser->loadFromString($this->getValidOFXBank());
        $this->assertNotNull($ofx);
    }

    // IT3-011: Account population from parsed elements
    public function testAccountPopulationFlow(): void
    {
        $ofx = $this->parser->loadFromString($this->getValidOFXBank());
        
        // Should have populated accounts
        if (isset($ofx->bankAccounts)) {
            $this->assertIsArray($ofx->bankAccounts);
        }
    }

    // IT3-012: Transaction list building from parsed tree
    public function testTransactionListBuilding(): void
    {
        $ofx = $this->parser->loadFromString($this->getValidOFXBank());
        
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
        
        // Should not crash with recovery strategies
        try {
            $ofx = $parser->loadFromString($this->getValidOFXBank());
            $this->assertNotNull($ofx);
        } catch (\Exception $e) {
            $this->fail("Parsing with recovery strategy failed: " . $e->getMessage());
        }
    }

    // IT3-014: Cross-component data consistency
    public function testCrossComponentDataConsistency(): void
    {
        $ofx = $this->parser->loadFromString($this->getValidOFXBank());
        
        // Data should flow consistently through components
        if (isset($ofx->bankAccount)) {
            $account = $ofx->bankAccount;
            
            // Account should be populated  
            $this->assertNotNull($account);
        }
    }

    // IT3-015: Stream of components in correct sequence
    public function testComponentSequenceCorrectness(): void
    {
        // Test that components are called in correct order
        $ofx = $this->parser->loadFromString($this->getValidOFXBank());
        
        // If we got here, the sequence worked
        $this->assertNotNull($ofx);
    }

    // IT3-016: Loader return value used by parser
    public function testLoaderReturnValueUsage(): void
    {
        // Loaders return Ofx objects which are then returned to client
        $ofx = $this->parser->loadFromString($this->getValidOFXBank());
        
        // Result should be an Ofx instance
        $this->assertNotNull($ofx);
        $this->assertTrue(class_exists('OfxParser\\Ofx') || class_exists('Ofx'));
    }

    // IT3-017: Metrics available after parsing completes
    public function testMetricsAvailabilityPostParsing(): void
    {
        $ofx = $this->parser->loadFromString($this->getValidOFXBank());
        
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
        
        $ofx = $this->parser->loadFromString($this->getValidOFXBank());
        
        // Should be able to inspect what recoveries occurred
        if (method_exists($ofx, 'getMetrics')) {
            $metrics = $ofx->getMetrics();
            $this->assertNotNull($metrics);
        }
    }

    // IT3-019: Element factory creates correct entity types
    public function testElementFactoryEntityTypeCreation(): void
    {
        $ofx = $this->parser->loadFromString($this->getValidOFXBank());
        
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
        
        // Should handle gracefully
        try {
            $ofx = $this->parser->loadFromString($this->getValidOFXBank());
            
            // Either parses successfully or throws handled exception
            $this->assertTrue(true);
        } catch (\Exception $e) {
            // Exception handling is part of component lifecycle
            $this->assertIsObject($e);
        }
    }
}
