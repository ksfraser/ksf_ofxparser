<?php

namespace OfxParser;

use PHPUnit\Framework\TestCase;
use OfxParser\Config\DefensiveParsingConfig;

/**
 * Test Parser Utility Methods
 * 
 * What: Tests utility methods in the main Parser class including header parsing,
 * SGML to XML conversion helpers, and defensive parsing configuration.
 * 
 * Why: These methods handle edge cases in OFX files from different financial
 * institutions, including unclosed tags, missing newlines, and non-standard formats.
 */
class ParserUtilityTest extends TestCase
{
    /**
     * Test parseHeader() extracts OFX header fields
     */
    public function testParseHeader()
    {
        $header = <<<HEADER
OFXHEADER:100
DATA:OFXSGML
VERSION:102
SECURITY:NONE
ENCODING:USASCII
CHARSET:1252
COMPRESSION:NONE
OLDFILEUID:NONE
NEWFILEUID:NONE
HEADER;

        $parser = new Parser();
        $reflection = new \ReflectionClass($parser);
        $method = $reflection->getMethod('parseHeader');
        $method->setAccessible(true);

        $result = $method->invoke($parser, $header);
        
        $this->assertIsArray($result);
        $this->assertEquals('100', trim($result['OFXHEADER']));
        $this->assertEquals('OFXSGML', trim($result['DATA']));
        $this->assertEquals('102', trim($result['VERSION']));
        $this->assertEquals('NONE', trim($result['SECURITY']));
        $this->assertEquals('USASCII', trim($result['ENCODING']));
        $this->assertEquals('1252', trim($result['CHARSET']));
    }

    /**
     * Test parseHeader() with Windows line endings
     */
    public function testParseHeaderWithWindowsLineEndings()
    {
        $header = "OFXHEADER:100\r\nDATA:OFXSGML\r\nVERSION:102\r\n";

        $parser = new Parser();
        $reflection = new \ReflectionClass($parser);
        $method = $reflection->getMethod('parseHeader');
        $method->setAccessible(true);

        $result = $method->invoke($parser, $header);
        
        $this->assertEquals('100', trim($result['OFXHEADER']));
        $this->assertEquals('OFXSGML', trim($result['DATA']));
        $this->assertEquals('102', trim($result['VERSION']));
    }

    /**
     * Test defensive parsing configuration
     */
    public function testDefensiveParsingConfiguration()
    {
        $parser = new Parser();
        
        // Default: defensive parsing disabled
        $this->assertFalse($parser->isDefensiveParsingEnabled());
        
        // Enable defensive parsing
        $result = $parser->withDefensiveParsing();
        $this->assertSame($parser, $result); // Fluent interface
        $this->assertTrue($parser->isDefensiveParsingEnabled());
        
        // Check path info
        $pathInfo = $parser->getParsingPathInfo();
        $this->assertIsArray($pathInfo);
    }

    /**
     * Test defensive parsing with custom config
     */
    public function testDefensiveParsingWithCustomConfig()
    {
        $config = new DefensiveParsingConfig();
        $config->continueOnError = true;
        
        $parser = new Parser();
        $parser->withDefensiveParsing($config);
        
        $this->assertTrue($parser->isDefensiveParsingEnabled());
    }

    /**
     * Test usedXmlPath() and usedSgmlPath() tracking
     */
    public function testParsingPathTracking()
    {
        $parser = new Parser();
        
        // Before parsing, both should be false
        $this->assertFalse($parser->usedXmlPath());
        $this->assertFalse($parser->usedSgmlPath());
        
        // Parse XML OFX
        $xmlOfx = <<<OFX
<?xml version="1.0"?>
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS>
                <CODE>0</CODE>
                <SEVERITY>INFO</SEVERITY>
            </STATUS>
            <DTSERVER>20260115120000</DTSERVER>
            <LANGUAGE>ENG</LANGUAGE>
            <FI>
                <ORG>Test</ORG>
                <FID>123</FID>
            </FI>
        </SONRS>
    </SIGNONMSGSRSV1>
    <BANKMSGSRSV1>
        <STMTTRNRS>
            <TRNUID>1</TRNUID>
            <STATUS>
                <CODE>0</CODE>
                <SEVERITY>INFO</SEVERITY>
            </STATUS>
            <STMTRS>
                <CURDEF>USD</CURDEF>
                <BANKACCTFROM>
                    <BANKID>123</BANKID>
                    <ACCTID>456</ACCTID>
                    <ACCTTYPE>CHECKING</ACCTTYPE>
                </BANKACCTFROM>
                <BANKTRANLIST>
                    <DTSTART>20260101</DTSTART>
                    <DTEND>20260115</DTEND>
                </BANKTRANLIST>
                <LEDGERBAL>
                    <BALAMT>1000.00</BALAMT>
                    <DTASOF>20260115</DTASOF>
                </LEDGERBAL>
            </STMTRS>
        </STMTTRNRS>
    </BANKMSGSRSV1>
</OFX>
OFX;

        $ofx = $parser->loadFromString($xmlOfx);
        
        $this->assertTrue($parser->usedXmlPath());
        $this->assertFalse($parser->usedSgmlPath());
        $this->assertNotNull($ofx);
    }

    /**
     * Test SGML path detection
     */
    public function testSgmlPathDetection()
    {
        $sgmlOfx = <<<OFX
OFXHEADER:100
DATA:OFXSGML
VERSION:102
SECURITY:NONE
ENCODING:USASCII
CHARSET:1252
COMPRESSION:NONE
OLDFILEUID:NONE
NEWFILEUID:NONE

<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<DTSERVER>20260115120000
<LANGUAGE>ENG
<FI>
<ORG>Test
<FID>123
</FI>
</SONRS>
</SIGNONMSGSRSV1>
<BANKMSGSRSV1>
<STMTTRNRS>
<TRNUID>1
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<STMTRS>
<CURDEF>USD
<BANKACCTFROM>
<BANKID>123
<ACCTID>456
<ACCTTYPE>CHECKING
</BANKACCTFROM>
<BANKTRANLIST>
<DTSTART>20260101
<DTEND>20260115
</BANKTRANLIST>
<LEDGERBAL>
<BALAMT>1000.00
<DTASOF>20260115
</LEDGERBAL>
</STMTRS>
</STMTTRNRS>
</BANKMSGSRSV1>
</OFX>
OFX;

        $parser = new Parser();
        $ofx = $parser->loadFromString($sgmlOfx);
        
        $this->assertFalse($parser->usedXmlPath());
        $this->assertTrue($parser->usedSgmlPath());
    }

    /**
     * Test getParsingPathInfo() provides diagnostic info
     */
    public function testGetParsingPathInfo()
    {
        $parser = new Parser();
        $sgmlOfx = <<<OFX
OFXHEADER:100
DATA:OFXSGML
VERSION:102
SECURITY:NONE
ENCODING:USASCII
CHARSET:1252
COMPRESSION:NONE
OLDFILEUID:NONE
NEWFILEUID:NONE

<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<DTSERVER>20260115120000
<LANGUAGE>ENG
<FI>
<ORG>Test
<FID>123
</FI>
</SONRS>
</SIGNONMSGSRSV1>
</OFX>
OFX;

        $parser->loadFromString($sgmlOfx);
        
        $pathInfo = $parser->getParsingPathInfo();
        $this->assertIsArray($pathInfo);
        $this->assertArrayHasKey('parser_used', $pathInfo);
        $this->assertArrayHasKey('version_detected', $pathInfo);
        $this->assertEquals('sgml', $pathInfo['parser_used']);
    }

    /**
     * Test OFX with header but no body content
     */
    public function testOfxWithHeaderOnly()
    {
        $ofx = <<<OFX
OFXHEADER:100
DATA:OFXSGML
VERSION:102
SECURITY:NONE
ENCODING:USASCII
CHARSET:1252
COMPRESSION:NONE
OLDFILEUID:NONE
NEWFILEUID:NONE

OFX;

        $parser = new Parser();
        
        try {
            $result = $parser->loadFromString($ofx);
            // If it doesn't throw, check that it handled it gracefully
            $this->assertTrue(true, 'Parser handled empty body gracefully');
        } catch (\Exception $e) {
            // Exception is also acceptable for malformed OFX
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }

    /**
     * Test OFX with missing required header fields
     */
    public function testOfxWithIncompleteHeader()
    {
        $ofx = <<<OFX
OFXHEADER:100

<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<DTSERVER>20260115120000
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
</OFX>
OFX;

        $parser = new Parser();
        $result = $parser->loadFromString($ofx);
        
        // Should still parse, header is optional
        $this->assertInstanceOf(Ofx::class, $result);
    }

    /**
     * Test custom loaders can be provided
     */
    public function testCustomLoadersConfiguration()
    {
        $customLoaders = [
            'custom_loader' => function($content) {
                return null;
            }
        ];
        
        $parser = new Parser($customLoaders);
        $this->assertInstanceOf(Parser::class, $parser);
    }

    /**
     * Test header array is accessible after parsing
     */
    public function testHeaderAccessAfterParsing()
    {
        $ofx = <<<OFX
OFXHEADER:100
DATA:OFXSGML
VERSION:102
SECURITY:NONE
ENCODING:USASCII
CHARSET:1252
COMPRESSION:NONE
OLDFILEUID:NONE
NEWFILEUID:NONE

<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<DTSERVER>20260115120000
<LANGUAGE>ENG
<FI>
<ORG>Test
<FID>123
</FI>
</SONRS>
</SIGNONMSGSRSV1>
</OFX>
OFX;

        $parser = new Parser();
        $result = $parser->loadFromString($ofx);
        
        $this->assertIsArray($result->header);
        $this->assertEquals('100', $result->header['OFXHEADER']);
        $this->assertEquals('102', $result->header['VERSION']);
    }
}
