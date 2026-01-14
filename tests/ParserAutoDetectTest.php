<?php declare(strict_types=1);

namespace OfxParser\Tests;

use PHPUnit\Framework\TestCase;
use OfxParser\Parser;
use OfxParser\Ofx;

/**
 * Test auto-detection and routing between XML and SGML parsers
 */
class ParserAutoDetectTest extends TestCase
{
    private string $fixturesPath;
    
    protected function setUp(): void
    {
        $this->fixturesPath = __DIR__ . '/../examples/qfx_files/';
    }
    
    /**
     * Test that XML files (OFX v2+) are detected and parsed via XML path
     */
    public function testDetectsAndParsesXmlFiles(): void
    {
        $parser = new Parser();
        
        // Create a sample OFX v2 (XML) file content
        $xmlContent = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS>
<CODE>0</CODE>
<SEVERITY>INFO</SEVERITY>
</STATUS>
<DTSERVER>20250114120000</DTSERVER>
<LANGUAGE>ENG</LANGUAGE>
</SONRS>
</SIGNONMSGSRSV1>
<BANKMSGSRSV1>
<STMTRNRS>
<TRNUID>1</TRNUID>
<STATUS>
<CODE>0</CODE>
<SEVERITY>INFO</SEVERITY>
</STATUS>
<STMTRS>
<CURDEF>USD</CURDEF>
<BANKACCTFROM>
<BANKID>123456</BANKID>
<ACCTID>987654321</ACCTID>
<ACCTTYPE>CHECKING</ACCTTYPE>
</BANKACCTFROM>
<BANKTRANLIST>
<DTSTART>20250101</DTSTART>
<DTEND>20250114</DTEND>
<STMTTRN>
<TRNTYPE>DEBIT</TRNTYPE>
<DTPOSTED>20250110</DTPOSTED>
<TRNAMT>-100.00</TRNAMT>
<FITID>TXN001</FITID>
<NAME>Test Transaction</NAME>
</STMTTRN>
</BANKTRANLIST>
<LEDGERBAL>
<BALAMT>1000.00</BALAMT>
<DTASOF>20250114</DTASOF>
</LEDGERBAL>
</STMTRS>
</STMTRNRS>
</BANKMSGSRSV1>
</OFX>
XML;
        
        $ofx = $parser->loadFromString($xmlContent);
        
        $this->assertInstanceOf(Ofx::class, $ofx);
        $this->assertTrue($parser->usedXmlPath(), 'Should use XML path for XML files');
        $this->assertFalse($parser->usedSgmlPath(), 'Should not use SGML path for XML files');
        
        $accounts = $ofx->bankAccounts;
        $this->assertCount(1, $accounts);
        
        $transactions = $accounts[0]->statement->transactions;
        $this->assertCount(1, $transactions);
        $this->assertEquals('TXN001', $transactions[0]->uniqueId);
    }
    
    /**
     * Test that SGML files (OFX v1) are detected and parsed via SGML path
     */
    public function testDetectsAndParsesSgmlFiles(): void
    {
        $parser = new Parser();
        
        // Create a sample OFX v1 (SGML) file content
        $sgmlContent = <<<SGML
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
<DTSERVER>20250114120000
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
<BANKMSGSRSV1>
<STMTRNRS>
<TRNUID>1
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<STMTRS>
<CURDEF>USD
<BANKACCTFROM>
<BANKID>123456
<ACCTID>987654321
<ACCTTYPE>CHECKING
</BANKACCTFROM>
<BANKTRANLIST>
<DTSTART>20250101
<DTEND>20250114
<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>20250110
<TRNAMT>-100.00
<FITID>TXN001
<NAME>Test Transaction
</STMTTRN>
</BANKTRANLIST>
<LEDGERBAL>
<BALAMT>1000.00
<DTASOF>20250114
</LEDGERBAL>
</STMTRS>
</STMTRNRS>
</BANKMSGSRSV1>
</OFX>
SGML;
        
        $ofx = $parser->loadFromString($sgmlContent);
        
        $this->assertInstanceOf(Ofx::class, $ofx);
        $this->assertTrue($parser->usedSgmlPath(), 'Should use SGML path for SGML files');
        $this->assertFalse($parser->usedXmlPath(), 'Should not use XML path for SGML files');
        
        $accounts = $ofx->bankAccounts;
        $this->assertCount(1, $accounts);
        
        $transactions = $accounts[0]->statement->transactions;
        $this->assertCount(1, $transactions);
        $this->assertEquals('TXN001', $transactions[0]->uniqueId);
    }
    
    /**
     * Test that both parsers produce compatible output
     */
    public function testBothPathsProduceCompatibleOutput(): void
    {
        $parserXml = new Parser();
        $parserSgml = new Parser();
        
        // Same transaction data in both formats
        $xmlContent = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS><CODE>0</CODE><SEVERITY>INFO</SEVERITY></STATUS>
<DTSERVER>20250114120000</DTSERVER>
<LANGUAGE>ENG</LANGUAGE>
</SONRS>
</SIGNONMSGSRSV1>
<BANKMSGSRSV1>
<STMTRNRS>
<TRNUID>1</TRNUID>
<STATUS><CODE>0</CODE><SEVERITY>INFO</SEVERITY></STATUS>
<STMTRS>
<CURDEF>USD</CURDEF>
<BANKACCTFROM>
<BANKID>123456</BANKID>
<ACCTID>987654321</ACCTID>
<ACCTTYPE>CHECKING</ACCTTYPE>
</BANKACCTFROM>
<BANKTRANLIST>
<DTSTART>20250101</DTSTART>
<DTEND>20250114</DTEND>
<STMTTRN>
<TRNTYPE>DEBIT</TRNTYPE>
<DTPOSTED>20250110120000</DTPOSTED>
<TRNAMT>-100.00</TRNAMT>
<FITID>TXN001</FITID>
<NAME>Test Merchant</NAME>
<MEMO>Purchase</MEMO>
</STMTTRN>
</BANKTRANLIST>
<LEDGERBAL>
<BALAMT>1000.00</BALAMT>
<DTASOF>20250114</DTASOF>
</LEDGERBAL>
</STMTRS>
</STMTRNRS>
</BANKMSGSRSV1>
</OFX>
XML;
        
        $sgmlContent = <<<SGML
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
<DTSERVER>20250114120000
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
<BANKMSGSRSV1>
<STMTRNRS>
<TRNUID>1
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<STMTRS>
<CURDEF>USD
<BANKACCTFROM>
<BANKID>123456
<ACCTID>987654321
<ACCTTYPE>CHECKING
</BANKACCTFROM>
<BANKTRANLIST>
<DTSTART>20250101
<DTEND>20250114
<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>20250110120000
<TRNAMT>-100.00
<FITID>TXN001
<NAME>Test Merchant
<MEMO>Purchase
</STMTTRN>
</BANKTRANLIST>
<LEDGERBAL>
<BALAMT>1000.00
<DTASOF>20250114
</LEDGERBAL>
</STMTRS>
</STMTRNRS>
</BANKMSGSRSV1>
</OFX>
SGML;
        
        $ofxXml = $parserXml->loadFromString($xmlContent);
        $ofxSgml = $parserSgml->loadFromString($sgmlContent);
        
        // Compare key data points
        $xmlTxn = $ofxXml->bankAccounts[0]->statement->transactions[0];
        $sgmlTxn = $ofxSgml->bankAccounts[0]->statement->transactions[0];
        
        $this->assertEquals($xmlTxn->uniqueId, $sgmlTxn->uniqueId);
        $this->assertEquals($xmlTxn->amount, $sgmlTxn->amount);
        $this->assertEquals($xmlTxn->type, $sgmlTxn->type);
        $this->assertEquals($xmlTxn->name, $sgmlTxn->name);
        $this->assertEquals($xmlTxn->memo, $sgmlTxn->memo);
        $this->assertEquals($xmlTxn->date->format('Y-m-d'), $sgmlTxn->date->format('Y-m-d'));
    }
    
    /**
     * Test parsing metrics are available from both paths
     */
    public function testParsingMetricsAvailable(): void
    {
        $parser = new Parser();
        
        $sgmlContent = <<<SGML
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
<DTSERVER>20250114120000
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
<BANKMSGSRSV1>
<STMTRNRS>
<TRNUID>1
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<STMTRS>
<CURDEF>USD
<BANKACCTFROM>
<BANKID>123456
<ACCTID>987654321
<ACCTTYPE>CHECKING
</BANKACCTFROM>
<BANKTRANLIST>
<DTSTART>20250101
<DTEND>20250114
<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>20250110
<TRNAMT>-100.00
<FITID>TXN001
<NAME>Test Transaction
</STMTTRN>
</BANKTRANLIST>
<LEDGERBAL>
<BALAMT>1000.00
<DTASOF>20250114
</LEDGERBAL>
</STMTRS>
</STMTRNRS>
</BANKMSGSRSV1>
</OFX>
SGML;
        
        $ofx = $parser->loadFromString($sgmlContent);
        
        $this->assertInstanceOf(Ofx::class, $ofx);
        
        // Get parsing path info
        $pathInfo = $parser->getParsingPathInfo();
        $this->assertArrayHasKey('parser_used', $pathInfo);
        $this->assertArrayHasKey('version_detected', $pathInfo);
        $this->assertEquals('sgml', $pathInfo['parser_used']);
    }
}
