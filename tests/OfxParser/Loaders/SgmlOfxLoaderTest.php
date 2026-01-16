<?php declare(strict_types=1);

namespace OfxParser\Loaders;

use PHPUnit\Framework\TestCase;

/**
 * Test SgmlOfxLoader SGML parsing and header handling
 */
class SgmlOfxLoaderTest extends TestCase
{
    private SgmlOfxLoader $loader;
    
    protected function setUp(): void
    {
        $this->loader = new SgmlOfxLoader();
    }
    
    /**
     * Test canHandle with SGML header (OFXHEADER)
     */
    public function testCanHandleWithSgmlHeader(): void
    {
        $header = "OFXHEADER:100\nDATA:OFXSGML\nVERSION:102";
        $body = "<OFX><SIGNONMSGSRSV1></SIGNONMSGSRSV1></OFX>";
        
        $canHandle = $this->loader->canHandle($header, $body);
        
        $this->assertTrue($canHandle);
    }
    
    /**
     * Test canHandle with DATA:OFXSGML header
     */
    public function testCanHandleWithDataOfxsgml(): void
    {
        $header = "DATA:OFXSGML\nVERSION:102\nSECURITY:NONE";
        $body = "<OFX></OFX>";
        
        $canHandle = $this->loader->canHandle($header, $body);
        
        $this->assertTrue($canHandle);
    }
    
    /**
     * Test canHandle rejects XML headers
     */
    public function testCanHandleRejectsXmlHeader(): void
    {
        $header = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
        $body = "<OFX></OFX>";
        
        $canHandle = $this->loader->canHandle($header, $body);
        
        $this->assertFalse($canHandle);
    }
    
    /**
     * Test canHandle rejects headers without OFXHEADER or DATA:OFXSGML
     */
    public function testCanHandleRejectsInvalidHeader(): void
    {
        $header = "UNKNOWN:100\nINVALID:HEADER";
        $body = "<OFX></OFX>";
        
        $canHandle = $this->loader->canHandle($header, $body);
        
        $this->assertFalse($canHandle);
    }
    
    /**
     * Test load with valid SGML SignOn message
     */
    public function testLoadWithValidSignOnMessage(): void
    {
        $header = "OFXHEADER:100\nDATA:OFXSGML\nVERSION:102";
        $body = <<<SGML
<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<DTSERVER>20240115120000
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
</OFX>
SGML;
        
        $result = $this->loader->load($header, $body);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('element', $result);
        $this->assertArrayHasKey('header', $result);
        $this->assertInstanceOf(\OfxParser\Sgml\Elements\Element::class, $result['element']);
    }
    
    /**
     * Test load with bank statement message
     */
    public function testLoadWithBankStatement(): void
    {
        $header = "OFXHEADER:100\nDATA:OFXSGML\nVERSION:102";
        $body = <<<SGML
<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS><CODE>0<SEVERITY>INFO</STATUS>
<DTSERVER>20240115120000
</SONRS>
</SIGNONMSGSRSV1>
<BANKMSGSRSV1>
<STMTTRNRS>
<TRNUID>1001
<STATUS><CODE>0<SEVERITY>INFO</STATUS>
<STMTRS>
<CURDEF>USD
<BANKACCTFROM>
<BANKID>123456
<ACCTID>987654
<ACCTTYPE>CHECKING
</BANKACCTFROM>
<BANKTRANLIST>
<DTSTART>20240101
<DTEND>20240131
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</BANKMSGSRSV1>
</OFX>
SGML;
        
        $result = $this->loader->load($header, $body);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('element', $result);
    }
    
    /**
     * Test load with credit card message
     */
    public function testLoadWithCreditCardMessage(): void
    {
        $header = "OFXHEADER:100\nDATA:OFXSGML";
        $body = <<<SGML
<OFX>
<SIGNONMSGSRSV1>
<SONRS><STATUS><CODE>0<SEVERITY>INFO</STATUS><DTSERVER>20240115</SONRS>
</SIGNONMSGSRSV1>
<CREDITCARDMSGSRSV1>
<CCSTMTTRNRS>
<TRNUID>2001
<CCSTMTRS>
<CURDEF>USD
<CCACCTFROM>
<ACCTID>4111111111111111
</CCACCTFROM>
</CCSTMTRS>
</CCSTMTTRNRS>
</CREDITCARDMSGSRSV1>
</OFX>
SGML;
        
        $result = $this->loader->load($header, $body);
        
        $this->assertIsArray($result);
    }
    
    /**
     * Test load with investment message
     */
    public function testLoadWithInvestmentMessage(): void
    {
        $header = "OFXHEADER:100\nDATA:OFXSGML";
        $body = <<<SGML
<OFX>
<SIGNONMSGSRSV1>
<SONRS><STATUS><CODE>0<SEVERITY>INFO</STATUS><DTSERVER>20240115</SONRS>
</SIGNONMSGSRSV1>
<INVSTMTMSGSRSV1>
<INVSTMTTRNRS>
<TRNUID>3001
<INVSTMTRS>
<DTASOF>20240115
<CURDEF>USD
<INVACCTFROM>
<BROKERID>BROKER123
<ACCTID>INV-987654
</INVACCTFROM>
</INVSTMTRS>
</INVSTMTTRNRS>
</INVSTMTMSGSRSV1>
</OFX>
SGML;
        
        $result = $this->loader->load($header, $body);
        
        $this->assertIsArray($result);
    }
    
    /**
     * Test load with missing required message sets throws exception
     */
    public function testLoadWithMissingMessageSetsThrows(): void
    {
        $header = "OFXHEADER:100\nDATA:OFXSGML";
        $body = "<OFX><UNKNOWN></UNKNOWN></OFX>";
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Content is not valid OFX schema - missing required message sets');
        
        $this->loader->load($header, $body);
    }
    
    /**
     * Test load with only bank message (no SignOn) is valid
     */
    public function testLoadWithOnlyBankMessage(): void
    {
        $header = "OFXHEADER:100\nDATA:OFXSGML";
        $body = <<<SGML
<OFX>
<BANKMSGSRSV1>
<STMTTRNRS>
<STMTRS>
<CURDEF>USD
</STMTRS>
</STMTTRNRS>
</BANKMSGSRSV1>
</OFX>
SGML;
        
        $result = $this->loader->load($header, $body);
        
        $this->assertIsArray($result);
    }
    
    /**
     * Test getFormatName returns sgml
     */
    public function testGetFormatName(): void
    {
        $this->assertEquals('sgml', $this->loader->getFormatName());
    }
    
    /**
     * Test getVersion returns v1
     */
    public function testGetVersion(): void
    {
        $this->assertEquals('v1', $this->loader->getVersion());
    }
    
    /**
     * Test header parsing with multiple fields
     */
    public function testHeaderParsingWithMultipleFields(): void
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
        $body = "<OFX><SIGNONMSGSRSV1><SONRS><STATUS><CODE>0<SEVERITY>INFO</STATUS><DTSERVER>20240115</SONRS></SIGNONMSGSRSV1></OFX>";
        
        $result = $this->loader->load($header, $body);
        
        $this->assertIsArray($result['header']);
        $this->assertArrayHasKey('OFXHEADER', $result['header']);
        $this->assertEquals('100', $result['header']['OFXHEADER']);
        $this->assertArrayHasKey('VERSION', $result['header']);
        $this->assertEquals('102', $result['header']['VERSION']);
    }
    
    /**
     * Test header parsing with Windows line endings
     */
    public function testHeaderParsingWithWindowsLineEndings(): void
    {
        $header = "OFXHEADER:100\r\nDATA:OFXSGML\r\nVERSION:102";
        $body = "<OFX><SIGNONMSGSRSV1><SONRS><STATUS><CODE>0<SEVERITY>INFO</STATUS><DTSERVER>20240115</SONRS></SIGNONMSGSRSV1></OFX>";
        
        $result = $this->loader->load($header, $body);
        
        $this->assertIsArray($result['header']);
        $this->assertEquals('100', $result['header']['OFXHEADER']);
    }
    
    /**
     * Test load with empty lines in header
     */
    public function testLoadWithEmptyLinesInHeader(): void
    {
        $header = <<<HEADER

OFXHEADER:100

DATA:OFXSGML

VERSION:102

HEADER;
        $body = "<OFX><SIGNONMSGSRSV1><SONRS><STATUS><CODE>0<SEVERITY>INFO</STATUS><DTSERVER>20240115</SONRS></SIGNONMSGSRSV1></OFX>";
        
        $result = $this->loader->load($header, $body);
        
        $this->assertIsArray($result['header']);
        $this->assertEquals('100', $result['header']['OFXHEADER']);
    }
    
    /**
     * Test load with constructor dependencies (parser and metrics)
     */
    public function testLoadWithConstructorDependencies(): void
    {
        $metrics = new \OfxParser\Metrics\ParsingMetrics();
        $loader = new SgmlOfxLoader(null, null, $metrics);
        
        $header = "OFXHEADER:100\nDATA:OFXSGML";
        $body = "<OFX><SIGNONMSGSRSV1><SONRS><STATUS><CODE>0<SEVERITY>INFO</STATUS><DTSERVER>20240115</SONRS></SIGNONMSGSRSV1></OFX>";
        
        $result = $loader->load($header, $body);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('header', $result);
        $this->assertArrayHasKey('element', $result);
    }
}
