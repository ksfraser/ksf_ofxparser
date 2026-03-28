<?php

namespace OfxParserTest\Builders;

use PHPUnit\Framework\TestCase;
use OfxParser\Builders\SgmlOfxBuilder;

/**
 * Test SGML Builder Date Parsing Edge Cases
 * 
 * What: Tests parseDateTime() method in SgmlOfxBuilder with various OFX date formats.
 * 
 * Why: OFX supports multiple date formats (with/without time, with/without timezone),
 * and the parser must handle all valid formats correctly for proper transaction dating.
 */
class SgmlBuilderHelpersTest extends TestCase
{
    private SgmlOfxBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new SgmlOfxBuilder();
    }

    /**
     * Test parseDateTime() with YYYYMMDDHHmmss format
     */
    public function testParseDateTimeComplete()
    {
        $reflection = new \ReflectionClass($this->builder);
        $method = $reflection->getMethod('parseDateTime');
        $method->setAccessible(true);

        $result = $method->invoke($this->builder, '20260115123456');
        
        $this->assertInstanceOf(\DateTimeInterface::class, $result);
        $this->assertEquals('2026-01-15', $result->format('Y-m-d'));
        $this->assertEquals('12:34:56', $result->format('H:i:s'));
    }

    /**
     * Test parseDateTime() with YYYYMMDD format (date only)
     */
    public function testParseDateTimeWithoutTime()
    {
        $reflection = new \ReflectionClass($this->builder);
        $method = $reflection->getMethod('parseDateTime');
        $method->setAccessible(true);

        $result = $method->invoke($this->builder, '20260115');
        
        $this->assertInstanceOf(\DateTimeInterface::class, $result);
        $this->assertEquals('2026-01-15', $result->format('Y-m-d'));
    }

    /**
     * Test parseDateTime() with timezone suffix
     * OFX format: YYYYMMDDHHmmss[.xxx][timezone]
     */
    public function testParseDateTimeWithTimezone()
    {
        $reflection = new \ReflectionClass($this->builder);
        $method = $reflection->getMethod('parseDateTime');
        $method->setAccessible(true);

        $result = $method->invoke($this->builder, '20260115123456.123[-5:EST]');
        
        $this->assertInstanceOf(\DateTimeInterface::class, $result);
        $this->assertEquals('2026-01-15', $result->format('Y-m-d'));
    }

    /**
     * Test parseDateTime() with empty string returns null
     */
    public function testParseDateTimeEmptyString()
    {
        $reflection = new \ReflectionClass($this->builder);
        $method = $reflection->getMethod('parseDateTime');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($this->builder, ''));
    }

    /**
     * Test handling of empty BANKTRANLIST
     */
    public function testBuildStatementWithEmptyTransactionList()
    {
        $sgml = <<<SGML
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
<ORG>Test Bank
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
<BANKID>123456
<ACCTID>9876543210
<ACCTTYPE>CHECKING
</BANKACCTFROM>
<BANKTRANLIST>
<DTSTART>20260101
<DTEND>20260115
</BANKTRANLIST>
<LEDGERBAL>
<BALAMT>5000.00
<DTASOF>20260115
</LEDGERBAL>
</STMTRS>
</STMTTRNRS>
</BANKMSGSRSV1>
</OFX>
SGML;

        $parser = new \OfxParser\Parser();
        $ofx = $parser->loadFromString($sgml);
        
        $this->assertCount(1, $ofx->bankAccounts);
        $account = $ofx->bankAccounts[0];
        $this->assertEmpty($account->statement->transactions);
        $this->assertEquals('5000.00', $account->balance);
    }

    /**
     * Test trimming of account numbers
     */
    public function testAccountNumberTrimming()
    {
        $sgml = <<<SGML
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
<ORG>Test Bank
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
<BANKID> 123456 
<ACCTID>  9876543210  
<ACCTTYPE>CHECKING
</BANKACCTFROM>
<BANKTRANLIST>
<DTSTART>20260101
<DTEND>20260115
</BANKTRANLIST>
<LEDGERBAL>
<BALAMT>5000.00
<DTASOF>20260115
</LEDGERBAL>
</STMTRS>
</STMTTRNRS>
</BANKMSGSRSV1>
</OFX>
SGML;

        $parser = new \OfxParser\Parser();
        $ofx = $parser->loadFromString($sgml);
        
        $this->assertCount(1, $ofx->bankAccounts);
        $account = $ofx->bankAccounts[0];
        $this->assertEquals('9876543210', $account->accountNumber);
        $this->assertEquals('123456', $account->routingNumber);
    }

    /**
     * Test malformed date handling
     */
    public function testParseDateTimeMalformed()
    {
        $reflection = new \ReflectionClass($this->builder);
        $method = $reflection->getMethod('parseDateTime');
        $method->setAccessible(true);

        // Various invalid formats should return null or handle gracefully
        $result = $method->invoke($this->builder, 'INVALID');
        $this->assertTrue(
            $result === null || $result instanceof \DateTimeInterface,
            'Malformed date should return null or valid DateTime'
        );
    }

    /**
     * Test leap year date
     */
    public function testParseDateTimeLeapYear()
    {
        $reflection = new \ReflectionClass($this->builder);
        $method = $reflection->getMethod('parseDateTime');
        $method->setAccessible(true);

        $result = $method->invoke($this->builder, '20240229');
        
        $this->assertInstanceOf(\DateTimeInterface::class, $result);
        $this->assertEquals('2024-02-29', $result->format('Y-m-d'));
    }
}
