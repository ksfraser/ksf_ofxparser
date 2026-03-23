<?php

namespace OfxParserTest\Builders;

use PHPUnit\Framework\TestCase;
use OfxParser\Builders\SgmlOfxBuilder;
use OfxParser\Sgml\Parser as SgmlParser;
use OfxParser\Sgml\Elements\Element;

/**
 * Test Edge Cases and Branch Coverage for SgmlOfxBuilder
 * 
 * What: Tests all conditional branches in SgmlOfxBuilder to achieve 100% code coverage,
 * including edge cases, null handling, empty values, and error conditions.
 * 
 * Why: Comprehensive branch testing ensures:
 * - All error conditions are handled properly
 * - Null/empty value handling is correct
 * - Optional fields work as expected
 * - Type checking logic is sound
 * - No untested code paths exist
 */
class SgmlOfxBuilderCoverageTest extends TestCase
{
    /**
     * Test currency with null values (both fields required)
     */
    public function testBuildCurrencyWithNullCode()
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
                        <TRNAMT>-100.00
                        <FITID>TXN001
                        <NAME>Test
                        <CURRENCY>
                            <CURRATE>1.18
                        </CURRENCY>
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
        // Currency should be null because CURSYM is missing
        $this->assertNull($transaction->currency);
    }
    
    /**
     * Test currency with null rate
     */
    public function testBuildCurrencyWithNullRate()
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
                        <TRNAMT>-100.00
                        <FITID>TXN001
                        <NAME>Test
                        <ORIGCURRENCY>
                            <CURSYM>EUR
                        </ORIGCURRENCY>
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
        // Original currency should be null because CURRATE is missing
        $this->assertNull($transaction->originalCurrency);
    }
    
    /**
     * Test payee with empty address lines (not adding to array)
     */
    public function testBuildPayeeWithEmptyAddressLines()
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
                        <TRNAMT>-100.00
                        <FITID>TXN001
                        <NAME>Test
                        <PAYEE>
                            <NAME>Test Payee
                            <ADDR1>
                            <ADDR2>
                            <ADDR3>
                            <CITY>TestCity
                        </PAYEE>
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
        $this->assertInstanceOf(\OfxParser\Entities\Payee::class, $transaction->payee);
        $this->assertEquals('Test Payee', $transaction->payee->name);
        // Address should be null when all lines are empty
        $this->assertNull($transaction->payee->address);
        $this->assertEquals('TestCity', $transaction->payee->city);
    }
    
    /**
     * Test payee with only some address lines populated
     */
    public function testBuildPayeeWithOnlyAddr2()
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
                        <TRNAMT>-100.00
                        <FITID>TXN001
                        <NAME>Test
                        <PAYEE>
                            <NAME>Test Payee
                            <ADDR2>Suite 200
                            <CITY>TestCity
                        </PAYEE>
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
        $this->assertNotNull($transaction->payee->address);
        $this->assertCount(1, $transaction->payee->address);
        $this->assertEquals('Suite 200', $transaction->payee->address[0]);
    }
    
    /**
     * Test transaction with empty string in optional fields
     */
    public function testTransactionWithEmptyStringOptionalFields()
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
                        <TRNAMT>-100.00
                        <FITID>TXN001
                        <NAME>
                        <MEMO>
                        <SIC>
                        <CHECKNUM>
                        <REFNUM>
                        <PAYEEID>
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
        $this->assertEquals('', $transaction->name);
        $this->assertEquals('', $transaction->memo);
        $this->assertEquals('', $transaction->sic);
        $this->assertEquals('', $transaction->checkNumber);
        $this->assertEquals('', $transaction->refNumber);
        $this->assertNull($transaction->payeeId);
        $this->assertNull($transaction->payee);
    }
    
    /**
     * Test OFX with no bank accounts (only sign-on)
     */
    public function testOfxWithOnlySignOn()
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
        
        $this->assertNotNull($ofx->signOn);
        $this->assertEmpty($ofx->bankAccounts);
        $this->assertNull($ofx->bankAccount);
    }
    
    /**
     * Test with multiple bank accounts (should not set single bankAccount)
     */
    public function testOfxWithMultipleBankAccounts()
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
        <STMTTRNRS>
            <TRNUID>2
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <STMTRS>
                <CURDEF>USD
                <BANKACCTFROM>
                    <BANKID>123
                    <ACCTID>789
                    <ACCTTYPE>SAVINGS
                </BANKACCTFROM>
                <BANKTRANLIST>
                    <DTSTART>20260101
                    <DTEND>20260114
                </BANKTRANLIST>
                <LEDGERBAL><BALAMT>10000<DTASOF>20260114</LEDGERBAL>
            </STMTRS>
        </STMTTRNRS>
    </BANKMSGSRSV1>
</OFX>
SGML;
        
        $parser = new SgmlParser();
        $element = $parser->parse($sgml);
        
        $builder = new SgmlOfxBuilder();
        $ofx = $builder->buildOfx($element, []);
        
        $this->assertCount(2, $ofx->bankAccounts);
        $this->assertNull($ofx->bankAccount); // Should NOT be set when > 1 account
    }
    
    /**
     * Test datetime fields that return DateTime objects from parser
     * (tests instanceof DateTimeInterface branches)
     */
    public function testDateTimeFieldsAlreadyDateTime()
    {
        // This test verifies the instanceof \DateTimeInterface branches work
        // The SGML parser returns DateTime objects, so this path is already tested
        // but we verify the behavior explicitly
        
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
                    <DTSTART>20260101120000
                    <DTEND>20260114120000
                    <STMTTRN>
                        <TRNTYPE>DEBIT
                        <DTPOSTED>20260105120000
                        <DTUSER>20260104120000
                        <TRNAMT>-100.00
                        <FITID>TXN001
                        <NAME>Test
                    </STMTTRN>
                </BANKTRANLIST>
                <LEDGERBAL><BALAMT>5000<DTASOF>20260114120000</LEDGERBAL>
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
        $this->assertInstanceOf(\DateTimeInterface::class, $account->balanceDate);
        
        $transaction = $account->statement->transactions[0];
        $this->assertInstanceOf(\DateTimeInterface::class, $transaction->date);
        $this->assertInstanceOf(\DateTimeInterface::class, $transaction->userInitiatedDate);
        
        $statement = $account->statement;
        $this->assertInstanceOf(\DateTimeInterface::class, $statement->startDate);
        $this->assertInstanceOf(\DateTimeInterface::class, $statement->endDate);
    }
}
