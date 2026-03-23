<?php

namespace OfxParserTest\Builders;

use PHPUnit\Framework\TestCase;
use OfxParser\Builders\SgmlOfxBuilder;
use OfxParser\Sgml\Parser as SgmlParser;
use OfxParser\Sgml\Elements\Element;

/**
 * Test Error Handling and Edge Cases in SgmlOfxBuilder
 * 
 * What: Tests error conditions, malformed data, and edge cases to ensure
 * robust parsing behavior when dealing with real-world OFX data.
 * 
 * Why: Production OFX files often contain:
 * - Missing required elements
 * - Invalid date formats
 * - Empty containers
 * - Whitespace-only values
 * - Malformed numeric data
 * 
 * These tests ensure the parser handles such cases gracefully without crashes.
 */
class SgmlOfxBuilderErrorHandlingTest extends TestCase
{
    /**
     * Test parseDateTime with invalid date format (fallback to YYYYMMDD)
     */
    public function testParseDateTimeWithYyyyMmDdOnly()
    {
        $sgml = <<<SGML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <DTSERVER>20260114
            <LANGUAGE>ENG
            <FI><ORG>Test<FID>123</FI>
        </SONRS>
    </SIGNONMSGSRSV1>
    <BANKMSGSRSV1>
        <STMTTRNRS>
            <TRNUID>1
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <STMTRS>
                <CURDEF>USD
                <BANKACCTFROM>
                    <BANKID>123
                    <ACCTID>456
                    <ACCTTYPE>CHECKING
                </BANKACCTFROM>
                <BANKTRANLIST>
                    <DTSTART>20260101
                    <DTEND>20260114
                </BANKTRANLIST>
                <LEDGERBAL><BALAMT>5000<DTASOF>20260114</LEDGERBAL>
            </STMTRS>
        </STMTTRNRS>
    </BANKMSGSRSV1>
</OFX>
SGML;
        
        $parser = new SgmlParser();
        $element = $parser->parse($sgml);
        
        $builder = new SgmlOfxBuilder();
        $ofx = $builder->buildOfx($element, []);
        
        // Should parse date-only formats successfully
        $this->assertInstanceOf(\DateTimeInterface::class, $ofx->signOn->date);
        $this->assertEquals('2026-01-14', $ofx->signOn->date->format('Y-m-d'));
    }
    
    /**
     * Test parseDateTime with timezone and milliseconds (should be stripped)
     */
    public function testParseDateTimeWithTimezoneAndMilliseconds()
    {
        $sgml = <<<SGML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <DTSERVER>20260114120000.000[-5:EST]
            <LANGUAGE>ENG
            <FI><ORG>Test<FID>123</FI>
        </SONRS>
    </SIGNONMSGSRSV1>
    <BANKMSGSRSV1>
        <STMTTRNRS>
            <TRNUID>1
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <STMTRS>
                <CURDEF>USD
                <BANKACCTFROM>
                    <BANKID>123
                    <ACCTID>456
                    <ACCTTYPE>CHECKING
                </BANKACCTFROM>
                <BANKTRANLIST>
                    <DTSTART>20260101
                    <DTEND>20260114
                </BANKTRANLIST>
                <LEDGERBAL><BALAMT>5000<DTASOF>20260114</LEDGERBAL>
            </STMTRS>
        </STMTTRNRS>
    </BANKMSGSRSV1>
</OFX>
SGML;
        
        $parser = new SgmlParser();
        $element = $parser->parse($sgml);
        
        $builder = new SgmlOfxBuilder();
        $ofx = $builder->buildOfx($element, []);
        
        // Should successfully strip timezone and milliseconds
        $this->assertInstanceOf(\DateTimeInterface::class, $ofx->signOn->date);
        $this->assertEquals('2026-01-14 12:00:00', $ofx->signOn->date->format('Y-m-d H:i:s'));
    }
    
    /**
     * Test transaction with whitespace-trimmed account numbers
     */
    public function testAccountNumbersAreTrimmed()
    {
        $sgml = <<<SGML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <DTSERVER>20260114120000
            <LANGUAGE>ENG
            <FI><ORG>Test<FID>123</FI>
        </SONRS>
    </SIGNONMSGSRSV1>
    <BANKMSGSRSV1>
        <STMTTRNRS>
            <TRNUID>1
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <STMTRS>
                <CURDEF>USD
                <BANKACCTFROM>
                    <BANKID>  123  
                    <ACCTID>  456789  
                    <ACCTTYPE>CHECKING
                </BANKACCTFROM>
                <BANKTRANLIST>
                    <DTSTART>20260101
                    <DTEND>20260114
                </BANKTRANLIST>
                <LEDGERBAL><BALAMT>5000<DTASOF>20260114</LEDGERBAL>
            </STMTRS>
        </STMTTRNRS>
    </BANKMSGSRSV1>
</OFX>
SGML;
        
        $parser = new SgmlParser();
        $element = $parser->parse($sgml);
        
        $builder = new SgmlOfxBuilder();
        $ofx = $builder->buildOfx($element, []);
        
        $account = $ofx->bankAccounts[0];
        $this->assertEquals('123', $account->routingNumber);
        $this->assertEquals('456789', $account->accountNumber);
    }
    
    /**
     * Test empty BANKTRANLIST (no transactions)
     */
    public function testEmptyTransactionList()
    {
        $sgml = <<<SGML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <DTSERVER>20260114120000
            <LANGUAGE>ENG
            <FI><ORG>Test<FID>123</FI>
        </SONRS>
    </SIGNONMSGSRSV1>
    <BANKMSGSRSV1>
        <STMTTRNRS>
            <TRNUID>1
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <STMTRS>
                <CURDEF>USD
                <BANKACCTFROM>
                    <BANKID>123
                    <ACCTID>456
                    <ACCTTYPE>CHECKING
                </BANKACCTFROM>
                <BANKTRANLIST>
                    <DTSTART>20260101
                    <DTEND>20260114
                </BANKTRANLIST>
                <LEDGERBAL><BALAMT>5000<DTASOF>20260114</LEDGERBAL>
            </STMTRS>
        </STMTTRNRS>
    </BANKMSGSRSV1>
</OFX>
SGML;
        
        $parser = new SgmlParser();
        $element = $parser->parse($sgml);
        
        $builder = new SgmlOfxBuilder();
        $ofx = $builder->buildOfx($element, []);
        
        $this->assertEmpty($ofx->bankAccounts[0]->statement->transactions);
    }
    
    /**
     * Test missing STATUS element (should not crash)
     */
    public function testMissingStatusElement()
    {
        $sgml = <<<SGML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <DTSERVER>20260114120000
            <LANGUAGE>ENG
            <FI><ORG>Test<FID>123</FI>
        </SONRS>
    </SIGNONMSGSRSV1>
    <BANKMSGSRSV1>
        <STMTTRNRS>
            <TRNUID>1
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <STMTRS>
                <CURDEF>USD
                <BANKACCTFROM>
                    <BANKID>123
                    <ACCTID>456
                    <ACCTTYPE>CHECKING
                </BANKACCTFROM>
                <BANKTRANLIST>
                    <DTSTART>20260101
                    <DTEND>20260114
                </BANKTRANLIST>
                <LEDGERBAL><BALAMT>5000<DTASOF>20260114</LEDGERBAL>
            </STMTRS>
        </STMTTRNRS>
    </BANKMSGSRSV1>
</OFX>
SGML;
        
        $parser = new SgmlParser();
        $element = $parser->parse($sgml);
        
        $builder = new SgmlOfxBuilder();
        $ofx = $builder->buildOfx($element, []);
        
        $this->assertNull($ofx->signOn->status);
    }
    
    /**
     * Test missing FI element (should not crash)
     */
    public function testMissingFiElement()
    {
        $sgml = <<<SGML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <DTSERVER>20260114120000
            <LANGUAGE>ENG
        </SONRS>
    </SIGNONMSGSRSV1>
    <BANKMSGSRSV1>
        <STMTTRNRS>
            <TRNUID>1
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <STMTRS>
                <CURDEF>USD
                <BANKACCTFROM>
                    <BANKID>123
                    <ACCTID>456
                    <ACCTTYPE>CHECKING
                </BANKACCTFROM>
                <BANKTRANLIST>
                    <DTSTART>20260101
                    <DTEND>20260114
                </BANKTRANLIST>
                <LEDGERBAL><BALAMT>5000<DTASOF>20260114</LEDGERBAL>
            </STMTRS>
        </STMTTRNRS>
    </BANKMSGSRSV1>
</OFX>
SGML;
        
        $parser = new SgmlParser();
        $element = $parser->parse($sgml);
        
        $builder = new SgmlOfxBuilder();
        $ofx = $builder->buildOfx($element, []);
        
        $this->assertNull($ofx->signOn->institute);
    }
    
    /**
     * Test transaction with missing amount (edge case)
     */
    public function testTransactionWithMissingAmount()
    {
        $sgml = <<<SGML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <DTSERVER>20260114120000
            <LANGUAGE>ENG
            <FI><ORG>Test<FID>123</FI>
        </SONRS>
    </SIGNONMSGSRSV1>
    <BANKMSGSRSV1>
        <STMTTRNRS>
            <TRNUID>1
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <STMTRS>
                <CURDEF>USD
                <BANKACCTFROM>
                    <BANKID>123
                    <ACCTID>456
                    <ACCTTYPE>CHECKING
                </BANKACCTFROM>
                <BANKTRANLIST>
                    <DTSTART>20260101
                    <DTEND>20260114
                    <STMTTRN>
                        <TRNTYPE>DEBIT
                        <DTPOSTED>20260105
                        <FITID>TXN001
                        <NAME>Test
                    </STMTTRN>
                </BANKTRANLIST>
                <LEDGERBAL><BALAMT>5000<DTASOF>20260114</LEDGERBAL>
            </STMTRS>
        </STMTTRNRS>
    </BANKMSGSRSV1>
</OFX>
SGML;
        
        $parser = new SgmlParser();
        $element = $parser->parse($sgml);
        
        $builder = new SgmlOfxBuilder();
        $ofx = $builder->buildOfx($element, []);
        
        $transaction = $ofx->bankAccounts[0]->statement->transactions[0];
        // Amount should be 0.0 when missing
        $this->assertEquals(0.0, $transaction->amount);
    }
    
    /**
     * Test balance with string "NULL" value (SGML parser quirk)
     */
    public function testBalanceWithNullString()
    {
        $sgml = <<<SGML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <DTSERVER>20260114120000
            <LANGUAGE>ENG
            <FI><ORG>Test<FID>123</FI>
        </SONRS>
    </SIGNONMSGSRSV1>
    <BANKMSGSRSV1>
        <STMTTRNRS>
            <TRNUID>1
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <STMTRS>
                <CURDEF>USD
                <BANKACCTFROM>
                    <BANKID>123
                    <ACCTID>456
                    <ACCTTYPE>CHECKING
                </BANKACCTFROM>
                <BANKTRANLIST>
                    <DTSTART>20260101
                    <DTEND>20260114
                </BANKTRANLIST>
                <LEDGERBAL><BALAMT>5000<DTASOF>20260114</LEDGERBAL>
                <AVAILBAL><BALAMT><DTASOF>20260114</AVAILBAL>
            </STMTRS>
        </STMTTRNRS>
    </BANKMSGSRSV1>
</OFX>
SGML;
        
        $parser = new SgmlParser();
        $element = $parser->parse($sgml);
        
        $builder = new SgmlOfxBuilder();
        $ofx = $builder->buildOfx($element, []);
        
        $account = $ofx->bankAccounts[0];
        // Should have balance even with missing available balance amount
        $this->assertNotNull($account->balance);
    }
    
    /**
     * Test credit card account parsing
     */
    public function testCreditCardAccount()
    {
        $sgml = <<<SGML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <DTSERVER>20260114120000
            <LANGUAGE>ENG
            <FI><ORG>Test<FID>123</FI>
        </SONRS>
    </SIGNONMSGSRSV1>
    <CREDITCARDMSGSRSV1>
        <CCSTMTTRNRS>
            <TRNUID>1
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <CCSTMTRS>
                <CURDEF>USD
                <CCACCTFROM>
                    <ACCTID>1234567890123456
                </CCACCTFROM>
                <BANKTRANLIST>
                    <DTSTART>20260101
                    <DTEND>20260114
                </BANKTRANLIST>
                <LEDGERBAL><BALAMT>-250.00<DTASOF>20260114</LEDGERBAL>
            </CCSTMTRS>
        </CCSTMTTRNRS>
    </CREDITCARDMSGSRSV1>
</OFX>
SGML;
        
        $parser = new SgmlParser();
        $element = $parser->parse($sgml);
        
        $builder = new SgmlOfxBuilder();
        $ofx = $builder->buildOfx($element, []);
        
        $this->assertCount(1, $ofx->bankAccounts);
        $account = $ofx->bankAccounts[0];
        $this->assertEquals('1234567890123456', $account->accountNumber);
        $this->assertEquals(-250.00, $account->balance);
    }
    
    /**
     * Test mixed bank and credit card accounts
     */
    public function testMixedBankAndCreditCardAccounts()
    {
        $sgml = <<<SGML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <DTSERVER>20260114120000
            <LANGUAGE>ENG
            <FI><ORG>Test<FID>123</FI>
        </SONRS>
    </SIGNONMSGSRSV1>
    <BANKMSGSRSV1>
        <STMTTRNRS>
            <TRNUID>1
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <STMTRS>
                <CURDEF>USD
                <BANKACCTFROM>
                    <BANKID>123
                    <ACCTID>CHECKING001
                    <ACCTTYPE>CHECKING
                </BANKACCTFROM>
                <BANKTRANLIST>
                    <DTSTART>20260101
                    <DTEND>20260114
                </BANKTRANLIST>
                <LEDGERBAL><BALAMT>5000<DTASOF>20260114</LEDGERBAL>
            </STMTRS>
        </STMTTRNRS>
    </BANKMSGSRSV1>
    <CREDITCARDMSGSRSV1>
        <CCSTMTTRNRS>
            <TRNUID>2
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <CCSTMTRS>
                <CURDEF>USD
                <CCACCTFROM>
                    <ACCTID>CC123456
                </CCACCTFROM>
                <BANKTRANLIST>
                    <DTSTART>20260101
                    <DTEND>20260114
                </BANKTRANLIST>
                <LEDGERBAL><BALAMT>-100<DTASOF>20260114</LEDGERBAL>
            </CCSTMTRS>
        </CCSTMTTRNRS>
    </CREDITCARDMSGSRSV1>
</OFX>
SGML;
        
        $parser = new SgmlParser();
        $element = $parser->parse($sgml);
        
        $builder = new SgmlOfxBuilder();
        $ofx = $builder->buildOfx($element, []);
        
        // Should merge both account types
        $this->assertCount(2, $ofx->bankAccounts);
        $this->assertEquals('CHECKING001', $ofx->bankAccounts[0]->accountNumber);
        $this->assertEquals('CC123456', $ofx->bankAccounts[1]->accountNumber);
        $this->assertNull($ofx->bankAccount); // Not set when > 1 account
    }
    
    /**
     * Test pricing data with empty string values (should be null)
     */
    public function testPricingDataWithEmptyStrings()
    {
        $sgml = <<<SGML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <DTSERVER>20260114120000
            <LANGUAGE>ENG
            <FI><ORG>Test<FID>123</FI>
        </SONRS>
    </SIGNONMSGSRSV1>
    <INVSTMTMSGSRSV1>
        <INVSTMTTRNRS>
            <TRNUID>1
            <INVSTMTRS>
                <CURDEF>USD
                <INVACCTFROM>
                    <BROKERID>TEST
                    <ACCTID>123
                </INVACCTFROM>
                <INVTRANLIST>
                    <DTSTART>20260101
                    <DTEND>20260114
                    <BUYSTOCK>
                        <INVBUY>
                            <INVTRAN>
                                <FITID>TXN001
                                <DTTRADE>20260105
                            </INVTRAN>
                            <UNITS>
                            <UNITPRICE>
                            <TOTAL>
                        </INVBUY>
                    </BUYSTOCK>
                </INVTRANLIST>
            </INVSTMTRS>
        </INVSTMTTRNRS>
    </INVSTMTMSGSRSV1>
</OFX>
SGML;
        
        $parser = new SgmlParser();
        $element = $parser->parse($sgml);
        
        $builder = new SgmlOfxBuilder();
        $ofx = $builder->buildInvestmentOfx($element, []);
        
        $transaction = $ofx->bankAccounts[0]->statement->transactions[0];
        // Empty string values should result in null
        $this->assertNull($transaction->units);
        $this->assertNull($transaction->unitPrice);
        $this->assertNull($transaction->total);
    }
    
    /**
     * Test investment account without INVTRANLIST (should have empty transactions)
     */
    public function testInvestmentAccountWithoutTransactionList()
    {
        $sgml = <<<SGML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <DTSERVER>20260114120000
            <LANGUAGE>ENG
            <FI><ORG>Test<FID>123</FI>
        </SONRS>
    </SIGNONMSGSRSV1>
    <INVSTMTMSGSRSV1>
        <INVSTMTTRNRS>
            <TRNUID>1
            <INVSTMTRS>
                <CURDEF>USD
                <INVACCTFROM>
                    <BROKERID>TEST
                    <ACCTID>123
                </INVACCTFROM>
            </INVSTMTRS>
        </INVSTMTTRNRS>
    </INVSTMTMSGSRSV1>
</OFX>
SGML;
        
        $parser = new SgmlParser();
        $element = $parser->parse($sgml);
        
        $builder = new SgmlOfxBuilder();
        $ofx = $builder->buildInvestmentOfx($element, []);
        
        $account = $ofx->bankAccounts[0];
        $this->assertEmpty($account->statement->transactions);
    }
    
    /**
     * Test header building with empty header array
     */
    public function testBuildHeaderWithEmptyArray()
    {
        $sgml = <<<SGML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <DTSERVER>20260114120000
            <LANGUAGE>ENG
            <FI><ORG>Test<FID>123</FI>
        </SONRS>
    </SIGNONMSGSRSV1>
</OFX>
SGML;
        
        $parser = new SgmlParser();
        $element = $parser->parse($sgml);
        
        $builder = new SgmlOfxBuilder();
        $ofx = $builder->buildOfx($element, []);
        
        // Should not crash with empty header
        $this->assertNotNull($ofx);
        $this->assertNotNull($ofx->signOn);
    }
}
