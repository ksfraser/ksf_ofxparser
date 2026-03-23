<?php

namespace OfxParserTest;

use PHPUnit\Framework\TestCase;
use OfxParser\Ofx;
use SimpleXMLElement;

/**
 * Test XML OFX Parser (Ofx.php)
 * 
 * What: Tests the XML-based OFX parser which uses SimpleXMLElement
 * instead of the SGML parser path.
 * 
 * Why: The Ofx.php class has its own parsing logic that differs from
 * SgmlOfxBuilder, including XML-specific handling, defensive parsing,
 * and legacy compatibility features. Testing these paths ensures both
 * XML and SGML parsing work correctly.
 */
class XmlOfxParserTest extends TestCase
{
    /**
     * Test XML OFX with currency in transactions
     */
    public function testXmlOfxWithCurrency()
    {
        $xml = <<<XML
<?xml version="1.0"?>
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS>
                <CODE>0</CODE>
                <SEVERITY>INFO</SEVERITY>
            </STATUS>
            <DTSERVER>20260114120000</DTSERVER>
            <LANGUAGE>ENG</LANGUAGE>
            <FI>
                <ORG>Test Bank</ORG>
                <FID>12345</FID>
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
                    <BANKID>123456</BANKID>
                    <ACCTID>9876543210</ACCTID>
                    <ACCTTYPE>CHECKING</ACCTTYPE>
                </BANKACCTFROM>
                <BANKTRANLIST>
                    <DTSTART>20260101</DTSTART>
                    <DTEND>20260114</DTEND>
                    <STMTTRN>
                        <TRNTYPE>DEBIT</TRNTYPE>
                        <DTPOSTED>20260105</DTPOSTED>
                        <TRNAMT>-100.00</TRNAMT>
                        <FITID>TXN001</FITID>
                        <NAME>Test Transaction</NAME>
                        <CURRENCY>
                            <CURSYM>EUR</CURSYM>
                            <CURRATE>1.18</CURRATE>
                        </CURRENCY>
                    </STMTTRN>
                </BANKTRANLIST>
                <LEDGERBAL>
                    <BALAMT>5000.00</BALAMT>
                    <DTASOF>20260114</DTASOF>
                </LEDGERBAL>
            </STMTRS>
        </STMTTRNRS>
    </BANKMSGSRSV1>
</OFX>
XML;
        
        $xmlElement = new SimpleXMLElement($xml);
        $ofx = new Ofx($xmlElement);
        
        $this->assertNotNull($ofx->signOn);
        $this->assertEquals('Test Bank', $ofx->signOn->institute->name);
        $this->assertEquals('12345', $ofx->signOn->institute->id);
        
        $this->assertCount(1, $ofx->bankAccounts);
        $account = $ofx->bankAccounts[0];
        $this->assertEquals('9876543210', $account->accountNumber);
        
        $transaction = $account->statement->transactions[0];
        $this->assertEquals('TXN001', $transaction->uniqueId);
        $this->assertNotNull($transaction->currency);
        $this->assertEquals('EUR', $transaction->currency['code']);
        $this->assertEquals(1.18, $transaction->currency['rate']);
    }
    
    /**
     * Test XML OFX without FID (uses INTU.BID fallback)
     */
    public function testXmlOfxWithIntuBid()
    {
        $xml = <<<XML
<?xml version="1.0"?>
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS>
                <CODE>0</CODE>
                <SEVERITY>INFO</SEVERITY>
            </STATUS>
            <DTSERVER>20260114120000</DTSERVER>
            <LANGUAGE>ENG</LANGUAGE>
            <FI>
                <ORG>Intuit Bank</ORG>
            </FI>
            <INTU.BID>INTUIT123</INTU.BID>
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
                    <DTEND>20260114</DTEND>
                </BANKTRANLIST>
                <LEDGERBAL>
                    <BALAMT>1000.00</BALAMT>
                    <DTASOF>20260114</DTASOF>
                </LEDGERBAL>
            </STMTRS>
        </STMTTRNRS>
    </BANKMSGSRSV1>
</OFX>
XML;
        
        $xmlElement = new SimpleXMLElement($xml);
        $ofx = new Ofx($xmlElement);
        
        // Should use INTU.BID as fallback when FID is missing
        $this->assertEquals('INTUIT123', $ofx->signOn->institute->id);
        $this->assertEquals('Intuit Bank', $ofx->signOn->institute->name);
    }
    
    /**
     * Test XML OFX with SIGNUPMSGSRSV1 (account info)
     * 
     * Note: The parser expects ACCTID directly under ACCTINFO, not under BANKACCTFROM
     */
    public function testXmlOfxWithSignupAccountInfo()
    {
        $xml = <<<XML
<?xml version="1.0"?>
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS>
                <CODE>0</CODE>
                <SEVERITY>INFO</SEVERITY>
            </STATUS>
            <DTSERVER>20260114120000</DTSERVER>
            <LANGUAGE>ENG</LANGUAGE>
            <FI>
                <ORG>Test Bank</ORG>
                <FID>123</FID>
            </FI>
        </SONRS>
    </SIGNONMSGSRSV1>
    <SIGNUPMSGSRSV1>
        <ACCTINFOTRNRS>
            <TRNUID>1</TRNUID>
            <STATUS>
                <CODE>0</CODE>
                <SEVERITY>INFO</SEVERITY>
            </STATUS>
            <ACCTINFO>
                <DESC>Checking Account</DESC>
                <ACCTID>111222333</ACCTID>
            </ACCTINFO>
        </ACCTINFOTRNRS>
    </SIGNUPMSGSRSV1>
    <BANKMSGSRSV1>
        <STMTTRNRS>
            <TRNUID>2</TRNUID>
            <STATUS>
                <CODE>0</CODE>
                <SEVERITY>INFO</SEVERITY>
            </STATUS>
            <STMTRS>
                <CURDEF>USD</CURDEF>
                <BANKACCTFROM>
                    <BANKID>123456</BANKID>
                    <ACCTID>111222333</ACCTID>
                    <ACCTTYPE>CHECKING</ACCTTYPE>
                </BANKACCTFROM>
                <BANKTRANLIST>
                    <DTSTART>20260101</DTSTART>
                    <DTEND>20260114</DTEND>
                </BANKTRANLIST>
                <LEDGERBAL>
                    <BALAMT>5000.00</BALAMT>
                    <DTASOF>20260114</DTASOF>
                </LEDGERBAL>
            </STMTRS>
        </STMTTRNRS>
    </BANKMSGSRSV1>
</OFX>
XML;
        
        $xmlElement = new SimpleXMLElement($xml);
        $ofx = new Ofx($xmlElement);
        
        $this->assertNotEmpty($ofx->signupAccountInfo);
        $this->assertCount(1, $ofx->signupAccountInfo);
        
        $accountInfo = $ofx->signupAccountInfo[0];
        $this->assertEquals('Checking Account', $accountInfo->desc);
        $this->assertEquals('111222333', $accountInfo->number);
    }
    
    /**
     * Test XML credit card account
     */
    public function testXmlCreditCardAccount()
    {
        $xml = <<<XML
<?xml version="1.0"?>
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS>
                <CODE>0</CODE>
                <SEVERITY>INFO</SEVERITY>
            </STATUS>
            <DTSERVER>20260114120000</DTSERVER>
            <LANGUAGE>ENG</LANGUAGE>
            <FI>
                <ORG>Credit Card Co</ORG>
                <FID>999</FID>
            </FI>
        </SONRS>
    </SIGNONMSGSRSV1>
    <CREDITCARDMSGSRSV1>
        <CCSTMTTRNRS>
            <TRNUID>1</TRNUID>
            <STATUS>
                <CODE>0</CODE>
                <SEVERITY>INFO</SEVERITY>
            </STATUS>
            <CCSTMTRS>
                <CURDEF>USD</CURDEF>
                <CCACCTFROM>
                    <ACCTID>4111111111111111</ACCTID>
                </CCACCTFROM>
                <BANKTRANLIST>
                    <DTSTART>20260101</DTSTART>
                    <DTEND>20260114</DTEND>
                    <STMTTRN>
                        <TRNTYPE>DEBIT</TRNTYPE>
                        <DTPOSTED>20260105</DTPOSTED>
                        <TRNAMT>-50.00</TRNAMT>
                        <FITID>CC001</FITID>
                        <NAME>Purchase</NAME>
                    </STMTTRN>
                </BANKTRANLIST>
                <LEDGERBAL>
                    <BALAMT>-150.00</BALAMT>
                    <DTASOF>20260114</DTASOF>
                </LEDGERBAL>
            </CCSTMTRS>
        </CCSTMTTRNRS>
    </CREDITCARDMSGSRSV1>
</OFX>
XML;
        
        $xmlElement = new SimpleXMLElement($xml);
        $ofx = new Ofx($xmlElement);
        
        $this->assertCount(1, $ofx->bankAccounts);
        $account = $ofx->bankAccounts[0];
        $this->assertEquals('4111111111111111', $account->accountNumber);
        $this->assertEquals(-150.00, $account->balance);
        
        $transaction = $account->statement->transactions[0];
        $this->assertEquals('CC001', $transaction->uniqueId);
        $this->assertEquals(-50.00, $transaction->amount);
    }
    
    /**
     * Test getTransactions() deprecated helper method
     */
    public function testGetTransactionsHelper()
    {
        $xml = <<<XML
<?xml version="1.0"?>
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS>
                <CODE>0</CODE>
                <SEVERITY>INFO</SEVERITY>
            </STATUS>
            <DTSERVER>20260114120000</DTSERVER>
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
                    <DTEND>20260114</DTEND>
                    <STMTTRN>
                        <TRNTYPE>DEBIT</TRNTYPE>
                        <DTPOSTED>20260105</DTPOSTED>
                        <TRNAMT>-100.00</TRNAMT>
                        <FITID>TXN001</FITID>
                        <NAME>Test</NAME>
                    </STMTTRN>
                    <STMTTRN>
                        <TRNTYPE>CREDIT</TRNTYPE>
                        <DTPOSTED>20260106</DTPOSTED>
                        <TRNAMT>200.00</TRNAMT>
                        <FITID>TXN002</FITID>
                        <NAME>Deposit</NAME>
                    </STMTTRN>
                </BANKTRANLIST>
                <LEDGERBAL>
                    <BALAMT>1000.00</BALAMT>
                    <DTASOF>20260114</DTASOF>
                </LEDGERBAL>
            </STMTRS>
        </STMTTRNRS>
    </BANKMSGSRSV1>
</OFX>
XML;
        
        $xmlElement = new SimpleXMLElement($xml);
        $ofx = new Ofx($xmlElement);
        
        // Test deprecated helper method
        $transactions = $ofx->getTransactions();
        $this->assertCount(2, $transactions);
        $this->assertEquals('TXN001', $transactions[0]->uniqueId);
        $this->assertEquals('TXN002', $transactions[1]->uniqueId);
    }
    
    /**
     * Test buildHeader() method
     */
    public function testBuildHeader()
    {
        $xml = <<<XML
<?xml version="1.0"?>
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS>
                <CODE>0</CODE>
                <SEVERITY>INFO</SEVERITY>
            </STATUS>
            <DTSERVER>20260114120000</DTSERVER>
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
                    <DTEND>20260114</DTEND>
                </BANKTRANLIST>
                <LEDGERBAL>
                    <BALAMT>1000.00</BALAMT>
                    <DTASOF>20260114</DTASOF>
                </LEDGERBAL>
            </STMTRS>
        </STMTTRNRS>
    </BANKMSGSRSV1>
</OFX>
XML;
        
        $xmlElement = new SimpleXMLElement($xml);
        $ofx = new Ofx($xmlElement);
        
        $header = [
            'OFXHEADER' => '100',
            'VERSION' => '102',
            'DATA' => 'OFXSGML',
            'ENCODING' => 'USASCII',
            'CHARSET' => '1252'
        ];
        
        $result = $ofx->buildHeader($header);
        
        // Should return self for method chaining
        $this->assertSame($ofx, $result);
        $this->assertEquals($header, $ofx->header);
        $this->assertEquals('100', $ofx->header['OFXHEADER']);
        $this->assertEquals('102', $ofx->header['VERSION']);
    }
    
    /**
     * Test XML with available balance (AVAILBAL)
     * 
     * Note: Current entity structure doesn't have availableBalance property
     * This test verifies parsing doesn't break when AVAILBAL is present
     */
    public function testXmlWithAvailableBalance()
    {
        $xml = <<<XML
<?xml version="1.0"?>
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS>
                <CODE>0</CODE>
                <SEVERITY>INFO</SEVERITY>
            </STATUS>
            <DTSERVER>20260114120000</DTSERVER>
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
                    <ACCTTYPE>SAVINGS</ACCTTYPE>
                </BANKACCTFROM>
                <BANKTRANLIST>
                    <DTSTART>20260101</DTSTART>
                    <DTEND>20260114</DTEND>
                </BANKTRANLIST>
                <LEDGERBAL>
                    <BALAMT>5000.00</BALAMT>
                    <DTASOF>20260114120000</DTASOF>
                </LEDGERBAL>
                <AVAILBAL>
                    <BALAMT>4500.00</BALAMT>
                    <DTASOF>20260114120000</DTASOF>
                </AVAILBAL>
            </STMTRS>
        </STMTTRNRS>
    </BANKMSGSRSV1>
</OFX>
XML;
        
        $xmlElement = new SimpleXMLElement($xml);
        $ofx = new Ofx($xmlElement);
        
        $account = $ofx->bankAccounts[0];
        $this->assertEquals('5000.00', $account->balance);
        $this->assertEquals('SAVINGS', $account->accountType);
        $this->assertInstanceOf(\DateTimeInterface::class, $account->balanceDate);
    }
    
    /**
     * Test XML with null constructor (for SGML builder path)
     */
    public function testNullConstructor()
    {
        $ofx = new Ofx(null);
        
        // Should allow direct population
        $this->assertEmpty($ofx->bankAccounts);
        $this->assertNull($ofx->bankAccount);
        
        // Can populate manually
        $ofx->signOn = new \OfxParser\Entities\SignOn();
        $ofx->signOn->language = 'ENG';
        
        $this->assertEquals('ENG', $ofx->signOn->language);
    }
}
