<?php

namespace OfxParser\Sgml\Tests;

use PHPUnit\Framework\TestCase;
use OfxParser\Sgml\Parser;
use OfxParser\Sgml\Elements\ValueElement;
use OfxParser\Sgml\Elements\ContainerElement;

class ParserTest extends TestCase
{
    public function testParsesSimpleElement()
    {
        $sgml = '<OFX><BANKMSGSRSV1><CODE>0</CODE></BANKMSGSRSV1></OFX>';
        
        $parser = new Parser();
        $root = $parser->parse($sgml);
        
        $this->assertEquals('OFX', $root->getTagName());
        $this->assertInstanceOf(ContainerElement::class, $root);
        
        $bankMsg = $root->getFirstChild('BANKMSGSRSV1');
        $this->assertNotNull($bankMsg);
        
        $code = $bankMsg->getFirstChild('CODE');
        $this->assertNotNull($code);
        $this->assertInstanceOf(ValueElement::class, $code);
        $this->assertEquals('0', $code->getTextValue());
    }

    public function testHandlesUnclosedTags()
    {
        // SGML with unclosed tags (common in OFX v1)
        $sgml = '<OFX><SIGNONMSGSRSV1><SONRS><DTSERVER>20151209<LANGUAGE>POR</SONRS></SIGNONMSGSRSV1></OFX>';
        
        $parser = new Parser();
        $root = $parser->parse($sgml);
        
        $this->assertEquals('OFX', $root->getTagName());
        
        $sonrs = $root->getFirstChild('SIGNONMSGSRSV1')->getFirstChild('SONRS');
        $this->assertNotNull($sonrs);
        
        $dtserver = $sonrs->getFirstChild('DTSERVER');
        $this->assertEquals('20151209', $dtserver->getTextValue());
        
        $language = $sonrs->getFirstChild('LANGUAGE');
        $this->assertEquals('POR', $language->getTextValue());
    }

    public function testHandlesNestedContainers()
    {
        $sgml = '<OFX><BANKMSGSRSV1><STMTTRNRS><STMTRS><BANKTRANLIST><STMTTRN><TRNTYPE>CREDIT</TRNTYPE><TRNAMT>100.00</TRNAMT></STMTTRN></BANKTRANLIST></STMTRS></STMTTRNRS></BANKMSGSRSV1></OFX>';
        
        $parser = new Parser();
        $root = $parser->parse($sgml);
        
        // Navigate to transaction
        $tranList = $root
            ->getFirstChild('BANKMSGSRSV1')
            ->getFirstChild('STMTTRNRS')
            ->getFirstChild('STMTRS')
            ->getFirstChild('BANKTRANLIST');
        
        $this->assertNotNull($tranList);
        
        $stmttrn = $tranList->getFirstChild('STMTTRN');
        $this->assertNotNull($stmttrn);
        
        $trntype = $stmttrn->getFirstChild('TRNTYPE');
        $this->assertEquals('CREDIT', $trntype->getTextValue());
        
        $amount = $stmttrn->getFirstChild('TRNAMT');
        $this->assertEquals('100.00', $amount->getTextValue());
    }

    public function testMagicGetterAccess()
    {
        $sgml = '<OFX><BANKMSGSRSV1><CODE>0</CODE></BANKMSGSRSV1></OFX>';
        
        $parser = new Parser();
        $root = $parser->parse($sgml);
        
        // Test magic getter access (SimpleXML-like)
        $code = $root->BANKMSGSRSV1->CODE;
        $this->assertEquals('0', (string)$code);
    }

    public function testValueElementValidation()
    {
        $sgml = '<OFX><STMTTRN><DTPOSTED>20200101</DTPOSTED><TRNAMT>invalid</TRNAMT></STMTTRN></OFX>';
        
        $parser = new Parser();
        $root = $parser->parse($sgml);
        
        $stmttrn = $root->getFirstChild('STMTTRN');
        
        // Validate DTPOSTED - should be valid
        $dtposted = $stmttrn->getFirstChild('DTPOSTED');
        $errors = $dtposted->validate();
        $this->assertEmpty($errors);
        
        // Validate TRNAMT - should have error (invalid numeric)
        $trnamt = $stmttrn->getFirstChild('TRNAMT');
        $errors = $trnamt->validate();
        $this->assertNotEmpty($errors);
    }

    public function testHandlesMultipleSiblings()
    {
        $sgml = '<OFX><BANKTRANLIST><STMTTRN><FITID>1</FITID></STMTTRN><STMTTRN><FITID>2</FITID></STMTTRN><STMTTRN><FITID>3</FITID></STMTTRN></BANKTRANLIST></OFX>';
        
        $parser = new Parser();
        $root = $parser->parse($sgml);
        
        $tranList = $root->getFirstChild('BANKTRANLIST');
        $transactions = $tranList->getChildrenByTag('STMTTRN');
        
        $this->assertCount(3, $transactions);
        $this->assertEquals('1', $transactions[0]->getFirstChild('FITID')->getTextValue());
        $this->assertEquals('2', $transactions[1]->getFirstChild('FITID')->getTextValue());
        $this->assertEquals('3', $transactions[2]->getFirstChild('FITID')->getTextValue());
    }
}
