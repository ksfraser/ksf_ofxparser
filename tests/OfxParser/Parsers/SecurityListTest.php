<?php declare(strict_types=1);

namespace OfxParserTest\Parsers;

use PHPUnit\Framework\TestCase;
use OfxParser\Parser;
use OfxParser\Entities\Investment\Security;
use OfxParser\Entities\Investment\SecurityList;

/**
 * Test Security List (SECLISTMSGSRSV1) Parsing
 * 
 * TDD: Tests written first to define expected behavior
 */
class SecurityListTest extends TestCase
{
    /**
     * Test parsing stock security with basic fields
     */
    public function testParseStockSecurityBasicFields(): void
    {
        $ofxContent = <<<OFX
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
<DTSERVER>20240115120000
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
<SECLISTMSGSRSV1>
<SECLIST>
<STOCKINFO>
<SECINFO>
<SECID>
<UNIQUEID>037833100
<UNIQUEIDTYPE>CUSIP
</SECID>
<SECNAME>Apple Inc.
<TICKER>AAPL
<MEMO>Common Stock
</SECINFO>
</STOCKINFO>
</SECLIST>
</SECLISTMSGSRSV1>
</OFX>
OFX;

        $parser = new Parser();
        $ofx = $parser->loadFromString($ofxContent);
        
        $this->assertInstanceOf(\OfxParser\Ofx::class, $ofx);
        $this->assertNotNull($ofx->securityList);
        $this->assertInstanceOf(SecurityList::class, $ofx->securityList);
        $this->assertEquals(1, $ofx->securityList->count());
        
        $security = $ofx->securityList->getSecurities()[0];
        $this->assertEquals('037833100', $security->securityId);
        $this->assertEquals('CUSIP', $security->securityIdType);
        $this->assertEquals('Apple Inc.', $security->name);
        $this->assertEquals('AAPL', $security->ticker);
        $this->assertEquals('STOCK', $security->securityType);
        $this->assertEquals('Common Stock', $security->memo);
    }
    
    /**
     * Test parsing bond security with coupon and maturity
     */
    public function testParseBondSecurityWithCouponAndMaturity(): void
    {
        $ofxContent = <<<OFX
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
<DTSERVER>20240115120000
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
<SECLISTMSGSRSV1>
<SECLIST>
<DEBTINFO>
<SECINFO>
<SECID>
<UNIQUEID>912828YK6
<UNIQUEIDTYPE>CUSIP
</SECID>
<SECNAME>US Treasury Bond 2.875%
<TICKER>T
</SECINFO>
<DEBTTYPE>COUPON
<DEBTCLASS>TREASURY
<COUPONRT>2.875
<DTMAT>20320515
<PARVALUE>1000
</DEBTINFO>
</SECLIST>
</SECLISTMSGSRSV1>
</OFX>
OFX;

        $parser = new Parser();
        $ofx = $parser->loadFromString($ofxContent);
        
        $this->assertNotNull($ofx->securityList);
        $this->assertEquals(1, $ofx->securityList->count());
        
        $security = $ofx->securityList->getSecurities()[0];
        $this->assertEquals('912828YK6', $security->securityId);
        $this->assertEquals('CUSIP', $security->securityIdType);
        $this->assertEquals('US Treasury Bond 2.875%', $security->name);
        $this->assertEquals('BOND', $security->securityType);
        $this->assertEquals('COUPON', $security->debtType);
        $this->assertEquals('TREASURY', $security->debtClass);
        $this->assertEquals(2.875, $security->couponRate);
        $this->assertInstanceOf(\DateTimeInterface::class, $security->maturityDate);
        $this->assertEquals('2032-05-15', $security->maturityDate->format('Y-m-d'));
        $this->assertEquals(1000.0, $security->parValue);
    }
    
    /**
     * Test parsing mutual fund security
     */
    public function testParseMutualFundSecurity(): void
    {
        $ofxContent = <<<OFX
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
<DTSERVER>20240115120000
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
<SECLISTMSGSRSV1>
<SECLIST>
<MFINFO>
<SECINFO>
<SECID>
<UNIQUEID>922908769
<UNIQUEIDTYPE>CUSIP
</SECID>
<SECNAME>Vanguard 500 Index Fund
<TICKER>VFINX
</SECINFO>
<MFASSETCLASS>LARGESTOCK
<FIMFASSETCLASS>Domestic Equity
</MFINFO>
</SECLIST>
</SECLISTMSGSRSV1>
</OFX>
OFX;

        $parser = new Parser();
        $ofx = $parser->loadFromString($ofxContent);
        
        $this->assertNotNull($ofx->securityList);
        $this->assertEquals(1, $ofx->securityList->count());
        
        $security = $ofx->securityList->getSecurities()[0];
        $this->assertEquals('922908769', $security->securityId);
        $this->assertEquals('Vanguard 500 Index Fund', $security->name);
        $this->assertEquals('VFINX', $security->ticker);
        $this->assertEquals('MUTUALFUND', $security->securityType);
        $this->assertEquals('LARGESTOCK', $security->assetClass);
        $this->assertEquals('Domestic Equity', $security->fiAssetClass);
    }
    
    /**
     * Test parsing multiple securities in one list
     */
    public function testParseMultipleSecurities(): void
    {
        $ofxContent = <<<OFX
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
<DTSERVER>20240115120000
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
<SECLISTMSGSRSV1>
<SECLIST>
<STOCKINFO>
<SECINFO>
<SECID>
<UNIQUEID>037833100
<UNIQUEIDTYPE>CUSIP
</SECID>
<SECNAME>Apple Inc.
<TICKER>AAPL
</SECINFO>
</STOCKINFO>
<STOCKINFO>
<SECINFO>
<SECID>
<UNIQUEID>594918104
<UNIQUEIDTYPE>CUSIP
</SECID>
<SECNAME>Microsoft Corporation
<TICKER>MSFT
</SECINFO>
</STOCKINFO>
<MFINFO>
<SECINFO>
<SECID>
<UNIQUEID>922908769
<UNIQUEIDTYPE>CUSIP
</SECID>
<SECNAME>Vanguard 500 Index Fund
<TICKER>VFINX
</SECINFO>
</MFINFO>
</SECLIST>
</SECLISTMSGSRSV1>
</OFX>
OFX;

        $parser = new Parser();
        $ofx = $parser->loadFromString($ofxContent);
        
        $this->assertNotNull($ofx->securityList);
        $this->assertEquals(3, $ofx->securityList->count());
        
        $securities = $ofx->securityList->getSecurities();
        $this->assertEquals('Apple Inc.', $securities[0]->name);
        $this->assertEquals('Microsoft Corporation', $securities[1]->name);
        $this->assertEquals('Vanguard 500 Index Fund', $securities[2]->name);
    }
    
    /**
     * Test findById method for security lookup
     */
    public function testFindSecurityById(): void
    {
        $ofxContent = <<<OFX
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
<DTSERVER>20240115120000
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
<SECLISTMSGSRSV1>
<SECLIST>
<STOCKINFO>
<SECINFO>
<SECID>
<UNIQUEID>037833100
<UNIQUEIDTYPE>CUSIP
</SECID>
<SECNAME>Apple Inc.
<TICKER>AAPL
</SECINFO>
</STOCKINFO>
<STOCKINFO>
<SECINFO>
<SECID>
<UNIQUEID>594918104
<UNIQUEIDTYPE>CUSIP
</SECID>
<SECNAME>Microsoft Corporation
<TICKER>MSFT
</SECINFO>
</STOCKINFO>
</SECLIST>
</SECLISTMSGSRSV1>
</OFX>
OFX;

        $parser = new Parser();
        $ofx = $parser->loadFromString($ofxContent);
        
        $apple = $ofx->securityList->findById('037833100');
        $this->assertNotNull($apple);
        $this->assertEquals('Apple Inc.', $apple->name);
        $this->assertEquals('AAPL', $apple->ticker);
        
        $msft = $ofx->securityList->findById('594918104');
        $this->assertNotNull($msft);
        $this->assertEquals('Microsoft Corporation', $msft->name);
        
        $notFound = $ofx->securityList->findById('NOTFOUND');
        $this->assertNull($notFound);
    }
    
    /**
     * Test security with unit price and price date
     */
    public function testSecurityWithPriceInformation(): void
    {
        $ofxContent = <<<OFX
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
<DTSERVER>20240115120000
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
<SECLISTMSGSRSV1>
<SECLIST>
<STOCKINFO>
<SECINFO>
<SECID>
<UNIQUEID>037833100
<UNIQUEIDTYPE>CUSIP
</SECID>
<SECNAME>Apple Inc.
<TICKER>AAPL
<UNITPRICE>150.25
<DTPRICEASOF>20240115120000
<CURRENCY>USD
</SECINFO>
</STOCKINFO>
</SECLIST>
</SECLISTMSGSRSV1>
</OFX>
OFX;

        $parser = new Parser();
        $ofx = $parser->loadFromString($ofxContent);
        
        $security = $ofx->securityList->getSecurities()[0];
        $this->assertEquals(150.25, $security->unitPrice);
        $this->assertInstanceOf(\DateTimeInterface::class, $security->priceDateOf);
        $this->assertEquals('2024-01-15', $security->priceDateOf->format('Y-m-d'));
        $this->assertEquals('USD', $security->currency);
    }
    
    /**
     * Test parsing OTHER security type
     */
    public function testParseOtherSecurityType(): void
    {
        $ofxContent = <<<OFX
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
<DTSERVER>20240115120000
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
<SECLISTMSGSRSV1>
<SECLIST>
<OTHERINFO>
<SECINFO>
<SECID>
<UNIQUEID>CUSTOM123
<UNIQUEIDTYPE>OTHER
</SECID>
<SECNAME>Custom Security
</SECINFO>
<TYPEDESC>Real Estate Investment
</OTHERINFO>
</SECLIST>
</SECLISTMSGSRSV1>
</OFX>
OFX;

        $parser = new Parser();
        $ofx = $parser->loadFromString($ofxContent);
        
        $security = $ofx->securityList->getSecurities()[0];
        $this->assertEquals('CUSTOM123', $security->securityId);
        $this->assertEquals('Custom Security', $security->name);
        $this->assertEquals('OTHER', $security->securityType);
    }
    
    /**
     * Test XML format security list parsing
     */
    public function testParseXmlFormatSecurityList(): void
    {
        $ofxContent = <<<OFX
<?xml version="1.0" encoding="UTF-8"?>
<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS>
<CODE>0</CODE>
<SEVERITY>INFO</SEVERITY>
</STATUS>
<DTSERVER>20240115120000</DTSERVER>
<LANGUAGE>ENG</LANGUAGE>
</SONRS>
</SIGNONMSGSRSV1>
<SECLISTMSGSRSV1>
<SECLIST>
<STOCKINFO>
<SECINFO>
<SECID>
<UNIQUEID>037833100</UNIQUEID>
<UNIQUEIDTYPE>CUSIP</UNIQUEIDTYPE>
</SECID>
<SECNAME>Apple Inc.</SECNAME>
<TICKER>AAPL</TICKER>
</SECINFO>
</STOCKINFO>
</SECLIST>
</SECLISTMSGSRSV1>
</OFX>
OFX;

        $parser = new Parser();
        $ofx = $parser->loadFromString($ofxContent);
        
        $this->assertNotNull($ofx->securityList);
        $this->assertEquals(1, $ofx->securityList->count());
        
        $security = $ofx->securityList->getSecurities()[0];
        $this->assertEquals('037833100', $security->securityId);
        $this->assertEquals('Apple Inc.', $security->name);
        $this->assertEquals('AAPL', $security->ticker);
        $this->assertEquals('STOCK', $security->securityType);
    }
    
    /**
     * Test OFX without security list returns null
     */
    public function testOfxWithoutSecurityListReturnsNull(): void
    {
        $ofxContent = <<<OFX
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
<DTSERVER>20240115120000
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
<BANKMSGSRSV1>
<STMTTRNRS>
<TRNUID>1001
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<STMTRS>
<CURDEF>USD
<BANKACCTFROM>
<BANKID>123456
<ACCTID>098765
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
OFX;

        $parser = new Parser();
        $ofx = $parser->loadFromString($ofxContent);
        
        $this->assertNull($ofx->securityList);
    }
}
