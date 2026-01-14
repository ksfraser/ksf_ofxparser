<?php

namespace OfxParserTest;

use OfxParser\Parser;
use PHPUnit\Framework\TestCase;

/**
 * @covers OfxParser\Parser
 */
class ParserTest extends TestCase
{
    public function testCreditCardStatementTransactionsAreLoaded()
    {
        $parser = new Parser();
        $ofx = $parser->loadFromFile(__DIR__ . '/../fixtures/ofxdata-credit-card.ofx');

        $account = reset($ofx->bankAccounts);
        self::assertSame('1234567891234567', (string)$account->accountNumber);
    }

    public function testXmlLoadStringThrowsExceptionWithInvalidXml()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to parse OFX');
        
        $invalidXml = '<invalid xml>';

        $method = new \ReflectionMethod(Parser::class, 'xmlLoadString');
        $method->setAccessible(true);
        $method->invoke(new Parser(), $invalidXml);
    }

    public function testXmlLoadStringLoadsValidXml()
    {
        $validXml = '<fooRoot><foo>bar</foo></fooRoot>';

        $method = new \ReflectionMethod(Parser::class, 'xmlLoadString');
        $method->setAccessible(true);

        $xml = $method->invoke(new Parser(), $validXml);

        self::assertInstanceOf('SimpleXMLElement', $xml);
        self::assertEquals('bar', (string)$xml->foo);
    }

    /**
     * @return array
     */
    public function closeUnclosedXmlTagsProvider()
    {
        return [
            ['<SOMETHING>', '<SOMETHING>'],
            ['<SOMETHING>foo</SOMETHING>', '<SOMETHING>foo'],
            ['<SOMETHING>foo</SOMETHING>', '<SOMETHING>foo</SOMETHING>'],
            ['<BANKID>XXXXX</BANKID>', '<BANKID>XXXXX</BANKID>'],
            ['<ACCTID>XXXXXXXXXXX</ACCTID>', '<ACCTID>XXXXXXXXXXX</ACCTID>'],
            ['<ACCTID>-198.98</ACCTID>', '<ACCTID>-198.98</ACCTID>'],
            ['<ACCTID>-198.98</ACCTID>', '<ACCTID>-198.98'],
        ];
    }

    /**
     * @dataProvider closeUnclosedXmlTagsProvider
     * @param $expected
     * @param $input
     */
    public function testCloseUnclosedXmlTags($expected, $input)
    {
        $method = new \ReflectionMethod(Parser::class, 'closeUnclosedXmlTags');
        $method->setAccessible(true);

        $parser = new Parser();

        self::assertEquals($expected, $method->invoke($parser, $input));
    }

    public function convertSgmlToXmlProvider()
    {
        return [
            [<<<HERE
<SOMETHING>
    <FOO>bar
    <BAZ>bat</BAZ>
</SOMETHING>
HERE
        , <<<HERE
<SOMETHING>
<FOO>bar</FOO>
<BAZ>bat</BAZ>
</SOMETHING>
HERE
            ], [<<<HERE
<BANKACCTFROM>
<BANKID>XXXXX</BANKID>
<BRANCHID>XXXXX</BRANCHID>
<ACCTID>XXXXXXXXXXX</ACCTID>
<ACCTTYPE>CHECKING</ACCTTYPE>
</BANKACCTFROM>
HERE
                ,<<<HERE
<BANKACCTFROM>
<BANKID>XXXXX</BANKID>
<BRANCHID>XXXXX</BRANCHID>
<ACCTID>XXXXXXXXXXX</ACCTID>
<ACCTTYPE>CHECKING</ACCTTYPE>
</BANKACCTFROM>
HERE
            ],
        ];
    }

    /**
     * @dataProvider convertSgmlToXmlProvider
     */
    public function testConvertSgmlToXml($sgml, $expected)
    {
        $method = new \ReflectionMethod(Parser::class, 'convertSgmlToXml');
        $method->setAccessible(true);

        $actual = $method->invoke(new Parser, $sgml);
        // Normalize line endings for cross-platform compatibility
        $actual = str_replace("\r\n", "\n", $actual);
        $expected = str_replace("\r\n", "\n", $expected);
        
        self::assertEquals($expected, $actual);
    }

    public function testLoadFromFileWhenFileDoesNotExist()
    {
        $this->expectException(\InvalidArgumentException::class);

        $parser = new Parser();
        $parser->loadFromFile('a non-existent file');
    }

    /**
     * @dataProvider loadFromStringProvider
     */
    public function testLoadFromFileWhenFileDoesExist($filename)
    {
        if (!file_exists($filename)) {
            self::markTestSkipped('Could not find data file, cannot test loadFromFile method fully');
        }

        /** @var Parser|\PHPUnit_Framework_MockObject_MockObject $parser */
        $parser = $this->getMockBuilder(Parser::class)
                         ->setMethods(['loadFromString'])
                         ->getMock();
        $parser->expects(self::once())->method('loadFromString');
        $parser->loadFromFile($filename);
    }

    /**
     * @return array
     */
    public function loadFromStringProvider()
    {
        return [
            'ofxdata.ofx' => [dirname(__DIR__).'/fixtures/ofxdata.ofx'],
            'ofxdata-oneline.ofx' => [dirname(__DIR__).'/fixtures/ofxdata-oneline.ofx'],
            'ofxdata-cmfr.ofx' => [dirname(__DIR__).'/fixtures/ofxdata-cmfr.ofx'],
            'ofxdata-bb.ofx' => [dirname(__DIR__).'/fixtures/ofxdata-bb.ofx'],
            'ofxdata-bb-two-stmtrs.ofx' => [dirname(__DIR__).'/fixtures/ofxdata-bb-two-stmtrs.ofx'],
            'ofxdata-credit-card.ofx' => [dirname(__DIR__).'/fixtures/ofxdata-credit-card.ofx'],
            'ofxdata-bpbfc.ofx' => [dirname(__DIR__).'/fixtures/ofxdata-bpbfc.ofx'],
            'ofxdata-memoWithQuotes.ofx' => [dirname(__DIR__).'/fixtures/ofxdata-memoWithQuotes.ofx'],
        ];
    }

    /**
     * @param string $filename
     * @throws \Exception
     * @dataProvider loadFromStringProvider
     */
    public function testLoadFromString($filename)
    {
        if (!file_exists($filename)) {
            self::markTestSkipped('Could not find data file, cannot test loadFromString method fully');
        }

        $content = file_get_contents($filename);

        $parser = new Parser();
        $ofx = $parser->loadFromString($content);
        
        self::assertInstanceOf(\OfxParser\Ofx::class, $ofx);
        self::assertNotNull($ofx->signOn);
    }

    public function testLoadFromStringWithEmptyContent()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('OFX tag not found');
        
        $parser = new Parser();
        $parser->loadFromString('');
    }

    public function testLoadFromStringWithNoOFXTag()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('OFX tag not found');
        
        $parser = new Parser();
        $parser->loadFromString('SOME HEADER\nBUT NO OFX TAG');
    }

    public function testLoadFromStringWithInvalidOfxSchema()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Content is not valid ofx schema');
        
        $content = "OFXHEADER:100\nDATA:OFXSGML\n<OFX><INVALID>data</INVALID></OFX>";
        
        $parser = new Parser();
        $parser->loadFromString($content);
    }

    public function testParseHeaderWithXmlStyle()
    {
        $header = '<?xml version="1.0" encoding="UTF-8"?>';
        
        $method = new \ReflectionMethod(Parser::class, 'parseHeader');
        $method->setAccessible(true);
        
        $result = $method->invoke(new Parser(), $header);
        
        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    public function testParseHeaderWithSGMLStyleColonSeparated()
    {
        $header = "OFXHEADER:100\nDATA:OFXSGML\nVERSION:102\nSECURITY:NONE\nENCODING:USASCII\nCHARSET:1252\nCOMPRESSION:NONE\nOLDFILEUID:NONE\nNEWFILEUID:NONE";
        
        $method = new \ReflectionMethod(Parser::class, 'parseHeader');
        $method->setAccessible(true);
        
        $result = $method->invoke(new Parser(), $header);
        
        self::assertIsArray($result);
        self::assertEquals('100', $result['OFXHEADER']);
        self::assertEquals('OFXSGML', $result['DATA']);
        self::assertEquals('102', $result['VERSION']);
        self::assertEquals('NONE', $result['SECURITY']);
    }

    public function testParseHeaderWithSGMLStyleEqualsSeparated()
    {
        $header = '<?OFX OFXHEADER="200" VERSION="220" SECURITY="NONE" OLDFILEUID="NONE" NEWFILEUID="NONE"?>';
        
        $method = new \ReflectionMethod(Parser::class, 'parseHeader');
        $method->setAccessible(true);
        
        $result = $method->invoke(new Parser(), $header);
        
        self::assertIsArray($result);
        self::assertEquals('200', $result['OFXHEADER']);
        self::assertEquals('220', $result['VERSION']);
        self::assertEquals('NONE', $result['SECURITY']);
    }

    public function testParseHeaderWithMalformedData()
    {
        $header = "MALFORMED\nNO_SEPARATOR\nVALID:DATA";
        
        $method = new \ReflectionMethod(Parser::class, 'parseHeader');
        $method->setAccessible(true);
        
        $result = $method->invoke(new Parser(), $header);
        
        self::assertIsArray($result);
        self::assertEquals('DATA', $result['VALID']);
        self::assertArrayNotHasKey('MALFORMED', $result);
        self::assertArrayNotHasKey('NO_SEPARATOR', $result);
    }

    public function testConvertSgmlToXmlHandlesMultipleLines()
    {
        $sgml = "<OFX>\n<SIGNONMSGSRSV1>\n<SONRS>\n<STATUS>\n<CODE>0\n<SEVERITY>INFO\n</STATUS>\n</SONRS>\n</SIGNONMSGSRSV1>\n</OFX>";
        
        $method = new \ReflectionMethod(Parser::class, 'convertSgmlToXml');
        $method->setAccessible(true);
        
        $result = $method->invoke(new Parser(), $sgml);
        
        self::assertStringContainsString('<CODE>0</CODE>', $result);
        self::assertStringContainsString('<SEVERITY>INFO</SEVERITY>', $result);
    }

    public function testConvertSgmlToXmlHandlesNestedUnclosedTags()
    {
        $sgml = "<ROOT>\n<PARENT>\n<CHILD>value\n</PARENT>\n</ROOT>";
        
        $method = new \ReflectionMethod(Parser::class, 'convertSgmlToXml');
        $method->setAccessible(true);
        
        $result = $method->invoke(new Parser(), $sgml);
        
        self::assertStringContainsString('<CHILD>value</CHILD>', $result);
    }

    public function testLoadFromFilePreservesHeader()
    {
        $parser = new Parser();
        $ofx = $parser->loadFromFile(__DIR__ . '/../fixtures/ofxdata.ofx');
        
        self::assertNotEmpty($ofx->header);
        self::assertIsArray($ofx->header);
        self::assertArrayHasKey('OFXHEADER', $ofx->header);
    }

    public function testLoadFromFileHandlesMultipleBankAccounts()
    {
        $parser = new Parser();
        $ofx = $parser->loadFromFile(__DIR__ . '/../fixtures/ofxdata-bb-two-stmtrs.ofx');
        
        self::assertIsArray($ofx->bankAccounts);
        self::assertGreaterThanOrEqual(2, count($ofx->bankAccounts));
    }

    public function testCreateOfxCallsCorrectParser()
    {
        $xmlWithInvestment = simplexml_load_string('<OFX><INVSTMTMSGSRSV1><INVSTMTTRNRS><TRNUID>1</TRNUID></INVSTMTTRNRS></INVSTMTMSGSRSV1></OFX>');
        
        $method = new \ReflectionMethod(Parser::class, 'createOfx');
        $method->setAccessible(true);
        
        $ofx = $method->invoke(new Parser(), $xmlWithInvestment);
        
        self::assertInstanceOf(\OfxParser\Ofx::class, $ofx);
    }}