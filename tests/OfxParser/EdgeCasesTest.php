<?php

namespace OfxParser;

use PHPUnit\Framework\TestCase;

/**
 * Test Edge Cases and Malformed Data Handling
 * 
 * What: Tests parser robustness with malformed OFX data, missing fields,
 * unusual formats, and edge cases from real-world financial institutions.
 * 
 * Why: Financial institutions produce OFX files with variations and errors.
 * The parser must handle these gracefully without crashes or data loss.
 */
class EdgeCasesTest extends TestCase
{
    /**
     * Test OFX with BOM (Byte Order Mark)
     */
    public function testOfxWithBom()
    {
        $bom = "\xEF\xBB\xBF"; // UTF-8 BOM
        $ofx = $bom . <<<OFX
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
</SONRS>
</SIGNONMSGSRSV1>
</OFX>
OFX;

        $parser = new Parser();
        $result = $parser->loadFromString($ofx);
        
        $this->assertInstanceOf(Ofx::class, $result);
        $this->assertNotNull($result->signOn);
    }

    /**
     * Test transaction with negative amount zero (-0.00)
     */
    public function testTransactionWithNegativeZero()
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
<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>20260105
<TRNAMT>-0.00
<FITID>TXN001
<NAME>Zero Amount
</STMTTRN>
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
        $result = $parser->loadFromString($ofx);
        
        $this->assertCount(1, $result->bankAccounts);
        $transactions = $result->bankAccounts[0]->statement->transactions;
        $this->assertCount(1, $transactions);
        $this->assertEquals(0.0, $transactions[0]->amount);
    }

    /**
     * Test transaction with very large amount
     */
    public function testTransactionWithLargeAmount()
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
<STMTTRN>
<TRNTYPE>CREDIT
<DTPOSTED>20260105
<TRNAMT>999999999.99
<FITID>TXN001
<NAME>Large Amount
</STMTTRN>
</BANKTRANLIST>
<LEDGERBAL>
<BALAMT>999999999.99
<DTASOF>20260115
</LEDGERBAL>
</STMTRS>
</STMTTRNRS>
</BANKMSGSRSV1>
</OFX>
OFX;

        $parser = new Parser();
        $result = $parser->loadFromString($ofx);
        
        $transactions = $result->bankAccounts[0]->statement->transactions;
        $this->assertEquals(999999999.99, $transactions[0]->amount);
    }

    /**
     * Test date at Y2K boundary
     */
    public function testDateAtY2kBoundary()
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
<DTSERVER>19991231235959
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
<DTSTART>19991231
<DTEND>20000101
<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>20000101000000
<TRNAMT>-100.00
<FITID>TXN001
<NAME>Y2K Transaction
</STMTTRN>
</BANKTRANLIST>
<LEDGERBAL>
<BALAMT>1000.00
<DTASOF>20000101
</LEDGERBAL>
</STMTRS>
</STMTTRNRS>
</BANKMSGSRSV1>
</OFX>
OFX;

        $parser = new Parser();
        $result = $parser->loadFromString($ofx);
        
        $this->assertInstanceOf(Ofx::class, $result);
        $transaction = $result->bankAccounts[0]->statement->transactions[0];
        $this->assertEquals('2000-01-01', $transaction->date->format('Y-m-d'));
    }

    /**
     * Test very long transaction name/memo
     */
    public function testVeryLongTransactionName()
    {
        $longName = str_repeat('A', 500);
        
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
<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>20260105
<TRNAMT>-100.00
<FITID>TXN001
<NAME>$longName
</STMTTRN>
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
        $result = $parser->loadFromString($ofx);
        
        $transaction = $result->bankAccounts[0]->statement->transactions[0];
        $this->assertEquals(500, strlen($transaction->name));
    }

    /**
     * Test account number with leading zeros
     */
    public function testAccountNumberWithLeadingZeros()
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
<BANKID>000123
<ACCTID>000000456
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
        $result = $parser->loadFromString($ofx);
        
        $account = $result->bankAccounts[0];
        // Leading zeros should be preserved
        $this->assertStringContainsString('000', $account->accountNumber);
    }

    /**
     * Test empty statement (no transactions)
     */
    public function testEmptyStatement()
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
        $result = $parser->loadFromString($ofx);
        
        $this->assertCount(1, $result->bankAccounts);
        $this->assertEmpty($result->bankAccounts[0]->statement->transactions);
    }

    /**
     * Test status code with leading zeros
     */
    public function testStatusCodeWithLeadingZeros()
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
<CODE>0000
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
        
        $this->assertInstanceOf(Ofx::class, $result);
        $this->assertEquals(0, $result->signOn->status->code);
    }

    /**
     * Test currency rate of 1.0 (no conversion)
     */
    public function testCurrencyRateOfOne()
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
<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>20260105
<TRNAMT>-100.00
<FITID>TXN001
<NAME>Test
<CURRENCY>
<CURSYM>USD
<CURRATE>1.0
</CURRENCY>
</STMTTRN>
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
        $result = $parser->loadFromString($ofx);
        
        $transaction = $result->bankAccounts[0]->statement->transactions[0];
        $this->assertNotNull($transaction->currency);
        $this->assertEquals(1.0, $transaction->currency['rate']);
    }

    /**
     * Test multiple bank accounts in one OFX file
     */
    public function testMultipleBankAccounts()
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
<ACCTID>111
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
<STMTTRNRS>
<TRNUID>2
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<STMTRS>
<CURDEF>USD
<BANKACCTFROM>
<BANKID>123
<ACCTID>222
<ACCTTYPE>SAVINGS
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
OFX;

        $parser = new Parser();
        $result = $parser->loadFromString($ofx);
        
        $this->assertCount(2, $result->bankAccounts);
        $this->assertEquals('111', $result->bankAccounts[0]->accountNumber);
        $this->assertEquals('222', $result->bankAccounts[1]->accountNumber);
        $this->assertEquals('CHECKING', $result->bankAccounts[0]->accountType);
        $this->assertEquals('SAVINGS', $result->bankAccounts[1]->accountType);
    }
}
