<?php

namespace OfxParserTest\Parsers;

use OfxParser\Parser;
use PHPUnit\Framework\TestCase;

/**
 * Test parsing of INTERXFERMSGSRSV1 (Interbank Transfer) messages
 * 
 * TDD RED Phase: Define expected behavior for interbank transfer parsing
 */
class InterXferTest extends TestCase
{
    /**
     * Test parsing basic interbank transfer information
     * 
     * Verifies:
     * - Transfer server transaction ID
     * - Transfer ID
     * - Transfer amount
     * - From and to account information
     * - Transfer date posted
     */
    public function testParseBasicInterXferInformation()
    {
        $ofxContent = "OFXHEADER:100
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
<DTSERVER>20231115120000
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
<INTERXFERMSGSRSV1>
<INTERXFERTRNRS>
<TRNUID>1234567890
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<INTERXFERRS>
<SRVRTID>SERVER-TXN-123
<XFERINFO>
<XFERID>XFER-456
<TRNAMT>500.00
<DTPOSTED>20231110000000
<FROMACCTINFO>
<BANKACCTFROM>
<BANKID>111000025
<ACCTID>123456789
<ACCTTYPE>CHECKING
</BANKACCTFROM>
</FROMACCTINFO>
<TOACCTINFO>
<BANKACCTTO>
<BANKID>222000025
<ACCTID>987654321
<ACCTTYPE>SAVINGS
</BANKACCTTO>
</TOACCTINFO>
</XFERINFO>
</INTERXFERRS>
</INTERXFERTRNRS>
</INTERXFERMSGSRSV1>
</OFX>";

        $parser = new Parser();
        $ofx = $parser->loadFromString($ofxContent);

        $this->assertNotNull($ofx->interXfers);
        $this->assertCount(1, $ofx->interXfers);

        $transfer = $ofx->interXfers[0];
        $this->assertEquals('SERVER-TXN-123', $transfer->serverTransactionId);
        $this->assertEquals('XFER-456', $transfer->transferId);
        $this->assertEquals(500.00, $transfer->amount);
        $this->assertInstanceOf(\DateTimeInterface::class, $transfer->datePosted);
        $this->assertEquals('2023-11-10', $transfer->datePosted->format('Y-m-d'));

        // From account
        $this->assertEquals('111000025', $transfer->fromBankId);
        $this->assertEquals('123456789', $transfer->fromAccountId);
        $this->assertEquals('CHECKING', $transfer->fromAccountType);

        // To account
        $this->assertEquals('222000025', $transfer->toBankId);
        $this->assertEquals('987654321', $transfer->toAccountId);
        $this->assertEquals('SAVINGS', $transfer->toAccountType);
    }

    /**
     * Test parsing transfer with processing dates and status
     * 
     * Verifies:
     * - Due date (when transfer will process)
     * - Date available (when funds available)
     * - Date processed
     */
    public function testParseInterXferWithProcessingDates()
    {
        $ofxContent = "OFXHEADER:100
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
<DTSERVER>20231115120000
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
<INTERXFERMSGSRSV1>
<INTERXFERTRNRS>
<TRNUID>1234567890
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<INTERXFERRS>
<SRVRTID>SERVER-TXN-124
<XFERINFO>
<XFERID>XFER-457
<TRNAMT>1000.00
<DTDUE>20231120000000
<DTXFERPRJ>20231121000000
<DTPOSTED>20231120000000
<FROMACCTINFO>
<BANKACCTFROM>
<BANKID>111000025
<ACCTID>123456789
<ACCTTYPE>CHECKING
</BANKACCTFROM>
</FROMACCTINFO>
<TOACCTINFO>
<BANKACCTTO>
<BANKID>222000025
<ACCTID>987654321
<ACCTTYPE>SAVINGS
</BANKACCTTO>
</TOACCTINFO>
</XFERINFO>
</INTERXFERRS>
</INTERXFERTRNRS>
</INTERXFERMSGSRSV1>
</OFX>";

        $parser = new Parser();
        $ofx = $parser->loadFromString($ofxContent);

        $this->assertCount(1, $ofx->interXfers);
        $transfer = $ofx->interXfers[0];

        $this->assertEquals('SERVER-TXN-124', $transfer->serverTransactionId);
        $this->assertEquals(1000.00, $transfer->amount);

        // Processing dates
        $this->assertInstanceOf(\DateTimeInterface::class, $transfer->dateDue);
        $this->assertEquals('2023-11-20', $transfer->dateDue->format('Y-m-d'));
        
        $this->assertInstanceOf(\DateTimeInterface::class, $transfer->dateAvailable);
        $this->assertEquals('2023-11-21', $transfer->dateAvailable->format('Y-m-d'));
        
        $this->assertInstanceOf(\DateTimeInterface::class, $transfer->datePosted);
        $this->assertEquals('2023-11-20', $transfer->datePosted->format('Y-m-d'));
    }

    /**
     * Test parsing multiple interbank transfers
     * 
     * Verifies:
     * - Multiple transfers in one response
     * - Each transfer has correct data
     */
    public function testParseMultipleInterXfers()
    {
        $ofxContent = "OFXHEADER:100
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
<DTSERVER>20231115120000
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
<INTERXFERMSGSRSV1>
<INTERXFERTRNRS>
<TRNUID>1234567890
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<INTERXFERRS>
<SRVRTID>SERVER-TXN-125
<XFERINFO>
<XFERID>XFER-458
<TRNAMT>250.00
<DTPOSTED>20231110000000
<FROMACCTINFO>
<BANKACCTFROM>
<BANKID>111000025
<ACCTID>123456789
<ACCTTYPE>CHECKING
</BANKACCTFROM>
</FROMACCTINFO>
<TOACCTINFO>
<BANKACCTTO>
<BANKID>222000025
<ACCTID>987654321
<ACCTTYPE>SAVINGS
</BANKACCTTO>
</TOACCTINFO>
</XFERINFO>
</INTERXFERRS>
</INTERXFERTRNRS>
<INTERXFERTRNRS>
<TRNUID>1234567891
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<INTERXFERRS>
<SRVRTID>SERVER-TXN-126
<XFERINFO>
<XFERID>XFER-459
<TRNAMT>750.00
<DTPOSTED>20231112000000
<FROMACCTINFO>
<BANKACCTFROM>
<BANKID>333000025
<ACCTID>111222333
<ACCTTYPE>CHECKING
</BANKACCTFROM>
</FROMACCTINFO>
<TOACCTINFO>
<BANKACCTTO>
<BANKID>444000025
<ACCTID>444555666
<ACCTTYPE>CHECKING
</BANKACCTTO>
</TOACCTINFO>
</XFERINFO>
</INTERXFERRS>
</INTERXFERTRNRS>
</INTERXFERMSGSRSV1>
</OFX>";

        $parser = new Parser();
        $ofx = $parser->loadFromString($ofxContent);

        $this->assertCount(2, $ofx->interXfers);

        // First transfer
        $transfer1 = $ofx->interXfers[0];
        $this->assertEquals('SERVER-TXN-125', $transfer1->serverTransactionId);
        $this->assertEquals('XFER-458', $transfer1->transferId);
        $this->assertEquals(250.00, $transfer1->amount);
        $this->assertEquals('111000025', $transfer1->fromBankId);
        $this->assertEquals('222000025', $transfer1->toBankId);

        // Second transfer
        $transfer2 = $ofx->interXfers[1];
        $this->assertEquals('SERVER-TXN-126', $transfer2->serverTransactionId);
        $this->assertEquals('XFER-459', $transfer2->transferId);
        $this->assertEquals(750.00, $transfer2->amount);
        $this->assertEquals('333000025', $transfer2->fromBankId);
        $this->assertEquals('444000025', $transfer2->toBankId);
    }

    /**
     * Test parsing transfer in XML format
     * 
     * Verifies:
     * - XML format compatibility
     */
    public function testParseXmlFormatInterXfer()
    {
        $ofxContent = '<?xml version="1.0" encoding="utf-8"?>
<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS>
<CODE>0</CODE>
<SEVERITY>INFO</SEVERITY>
</STATUS>
<DTSERVER>20231115120000</DTSERVER>
<LANGUAGE>ENG</LANGUAGE>
</SONRS>
</SIGNONMSGSRSV1>
<INTERXFERMSGSRSV1>
<INTERXFERTRNRS>
<TRNUID>1234567890</TRNUID>
<STATUS>
<CODE>0</CODE>
<SEVERITY>INFO</SEVERITY>
</STATUS>
<INTERXFERRS>
<SRVRTID>SERVER-TXN-127</SRVRTID>
<XFERINFO>
<XFERID>XFER-460</XFERID>
<TRNAMT>300.00</TRNAMT>
<DTPOSTED>20231110000000</DTPOSTED>
<FROMACCTINFO>
<BANKACCTFROM>
<BANKID>111000025</BANKID>
<ACCTID>123456789</ACCTID>
<ACCTTYPE>CHECKING</ACCTTYPE>
</BANKACCTFROM>
</FROMACCTINFO>
<TOACCTINFO>
<BANKACCTTO>
<BANKID>222000025</BANKID>
<ACCTID>987654321</ACCTID>
<ACCTTYPE>SAVINGS</ACCTTYPE>
</BANKACCTTO>
</TOACCTINFO>
</XFERINFO>
</INTERXFERRS>
</INTERXFERTRNRS>
</INTERXFERMSGSRSV1>
</OFX>';

        $parser = new Parser();
        $ofx = $parser->loadFromString($ofxContent);

        $this->assertNotNull($ofx->interXfers);
        $this->assertCount(1, $ofx->interXfers);

        $transfer = $ofx->interXfers[0];
        $this->assertEquals('SERVER-TXN-127', $transfer->serverTransactionId);
        $this->assertEquals('XFER-460', $transfer->transferId);
        $this->assertEquals(300.00, $transfer->amount);
        $this->assertEquals('111000025', $transfer->fromBankId);
        $this->assertEquals('222000025', $transfer->toBankId);
    }

    /**
     * Test OFX without interbank transfers returns empty array
     * 
     * Verifies:
     * - Graceful handling when INTERXFERMSGSRSV1 not present
     */
    public function testOfxWithoutInterXfersReturnsEmptyArray()
    {
        $ofxContent = "OFXHEADER:100
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
<DTSERVER>20231115120000
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
<BANKMSGSRSV1>
<STMTTRNRS>
<TRNUID>1234567890
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
</STMTTRNRS>
</BANKMSGSRSV1>
</OFX>";

        $parser = new Parser();
        $ofx = $parser->loadFromString($ofxContent);

        $this->assertIsArray($ofx->interXfers);
        $this->assertEmpty($ofx->interXfers);
    }
}
