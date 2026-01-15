<?php

namespace OfxParser\Builders;

use PHPUnit\Framework\TestCase;
use OfxParser\Sgml\Parser as SgmlParser;
use OfxParser\Sgml\Elements\Element;

/**
 * Test Investment Transaction Building
 * 
 * What: Tests all investment transaction types (buy, sell, reinvest, income, banking)
 * to achieve comprehensive branch coverage for investment parsing in SgmlOfxBuilder.
 * 
 * Why: Investment transactions are currently at 0% coverage and represent a significant
 * portion of the OFX specification. These tests ensure:
 * - All investment transaction types parse correctly
 * - Optional fields are handled properly
 * - Missing elements return null or empty values
 * - DateTime parsing works for trade/settlement dates
 * - SECID, pricing, and transaction data load correctly
 */
class InvestmentTransactionTest extends TestCase
{
    /**
     * Test building a buy mutual fund transaction with all fields
     */
    public function testBuildBuyMutualFundComplete()
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
                <DTASOF>20260114120000
                <CURDEF>USD
                <INVACCTFROM>
                    <BROKERID>TEST123
                    <ACCTID>123456789
                </INVACCTFROM>
                <INVTRANLIST>
                    <DTSTART>20260101
                    <DTEND>20260114
                    <BUYMF>
                        <INVBUY>
                            <INVTRAN>
                                <FITID>BUY001
                                <DTTRADE>20260105
                                <DTSETTLE>20260108
                                <MEMO>Purchase mutual fund
                            </INVTRAN>
                            <SECID>
                                <UNIQUEID>9876543210
                                <UNIQUEIDTYPE>CUSIP
                            </SECID>
                            <UNITS>100.50
                            <UNITPRICE>25.75
                            <TOTAL>-2587.88
                            <SUBACCTSEC>OTHER
                            <SUBACCTFUND>OTHER
                        </INVBUY>
                        <BUYTYPE>BUY
                    </BUYMF>
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
        
        $this->assertCount(1, $ofx->bankAccounts);
        $account = $ofx->bankAccounts[0];
        $this->assertEquals('TEST123', $account->brokerId);
        $this->assertEquals('123456789', $account->accountNumber);
        $this->assertEquals('1', $account->transactionUid);
        
        $this->assertCount(1, $account->statement->transactions);
        $transaction = $account->statement->transactions[0];
        
        $this->assertInstanceOf(\OfxParser\Entities\Investment\Transaction\BuyMutualFund::class, $transaction);
        $this->assertEquals('BUY001', $transaction->uniqueId);
        $this->assertEquals('Purchase mutual fund', $transaction->memo);
        $this->assertEquals('9876543210', $transaction->securityId);
        $this->assertEquals('CUSIP', $transaction->securityIdType);
        $this->assertEquals('100.5', $transaction->units);
        $this->assertEquals('25.75', $transaction->unitPrice);
        $this->assertEquals('-2587.88', $transaction->total);
        $this->assertEquals('OTHER', $transaction->subAccountSec);
        $this->assertEquals('OTHER', $transaction->subAccountFund);
        $this->assertEquals('BUY', $transaction->buyType);
        
        $this->assertInstanceOf(\DateTimeInterface::class, $transaction->tradeDate);
        $this->assertInstanceOf(\DateTimeInterface::class, $transaction->settlementDate);
    }
    
    /**
     * Test buy mutual fund with missing optional fields
     */
    public function testBuildBuyMutualFundMinimal()
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
                    <BUYMF>
                        <INVBUY>
                            <INVTRAN>
                                <FITID>BUY001
                                <DTTRADE>20260105
                            </INVTRAN>
                        </INVBUY>
                    </BUYMF>
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
        $this->assertInstanceOf(\OfxParser\Entities\Investment\Transaction\BuyMutualFund::class, $transaction);
        $this->assertEquals('BUY001', $transaction->uniqueId);
        $this->assertNull($transaction->units);
        $this->assertNull($transaction->unitPrice);
        $this->assertNull($transaction->total);
        $this->assertNull($transaction->subAccountSec);
        $this->assertNull($transaction->subAccountFund);
        $this->assertEquals('', $transaction->buyType);
    }
    
    /**
     * Test buy stock transaction
     */
    public function testBuildBuyStock()
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
                                <FITID>STOCK001
                                <DTTRADE>20260105
                            </INVTRAN>
                            <SECID>
                                <UNIQUEID>AAPL
                                <UNIQUEIDTYPE>TICKER
                            </SECID>
                            <UNITS>50
                            <UNITPRICE>150.25
                            <TOTAL>-7512.50
                        </INVBUY>
                        <BUYTYPE>BUY
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
        $this->assertInstanceOf(\OfxParser\Entities\Investment\Transaction\BuyStock::class, $transaction);
        $this->assertEquals('STOCK001', $transaction->uniqueId);
        $this->assertEquals('AAPL', $transaction->securityId);
        $this->assertEquals('TICKER', $transaction->securityIdType);
        $this->assertEquals('50', $transaction->units);
        $this->assertEquals('150.25', $transaction->unitPrice);
        $this->assertEquals('-7512.5', $transaction->total);
        $this->assertEquals('BUY', $transaction->buyType);
    }
    
    /**
     * Test buy other security (generic)
     */
    public function testBuildBuyOther()
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
                    <BUYOTHER>
                        <INVBUY>
                            <INVTRAN>
                                <FITID>OTHER001
                                <DTTRADE>20260105
                            </INVTRAN>
                            <UNITS>25
                            <UNITPRICE>100.00
                        </INVBUY>
                    </BUYOTHER>
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
        $this->assertInstanceOf(\OfxParser\Entities\Investment\Transaction\BuySecurity::class, $transaction);
        $this->assertEquals('OTHER001', $transaction->uniqueId);
    }
    
    /**
     * Test sell mutual fund transaction
     */
    public function testBuildSellMutualFund()
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
                    <SELLMF>
                        <INVSELL>
                            <INVTRAN>
                                <FITID>SELL001
                                <DTTRADE>20260106
                                <DTSETTLE>20260109
                                <MEMO>Redeem mutual fund shares
                            </INVTRAN>
                            <SECID>
                                <UNIQUEID>9876543210
                                <UNIQUEIDTYPE>CUSIP
                            </SECID>
                            <UNITS>-50.25
                            <UNITPRICE>26.50
                            <TOTAL>1331.63
                        </INVSELL>
                        <SELLTYPE>SELL
                    </SELLMF>
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
        $this->assertInstanceOf(\OfxParser\Entities\Investment\Transaction\SellMutualFund::class, $transaction);
        $this->assertEquals('SELL001', $transaction->uniqueId);
        $this->assertEquals('Redeem mutual fund shares', $transaction->memo);
        $this->assertEquals('-50.25', $transaction->units);
        $this->assertEquals('26.5', $transaction->unitPrice);
        $this->assertEquals('1331.63', $transaction->total);
        $this->assertEquals('SELL', $transaction->sellType);
    }
    
    /**
     * Test sell stock transaction
     */
    public function testBuildSellStock()
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
                    <SELLSTOCK>
                        <INVSELL>
                            <INVTRAN>
                                <FITID>SELLSTK001
                                <DTTRADE>20260107
                            </INVTRAN>
                            <SECID>
                                <UNIQUEID>MSFT
                                <UNIQUEIDTYPE>TICKER
                            </SECID>
                            <UNITS>-25
                            <UNITPRICE>200.00
                            <TOTAL>5000.00
                        </INVSELL>
                        <SELLTYPE>SELL
                    </SELLSTOCK>
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
        $this->assertInstanceOf(\OfxParser\Entities\Investment\Transaction\SellStock::class, $transaction);
        $this->assertEquals('SELLSTK001', $transaction->uniqueId);
        $this->assertEquals('MSFT', $transaction->securityId);
        $this->assertEquals('-25', $transaction->units);
        $this->assertEquals('SELL', $transaction->sellType);
    }
    
    /**
     * Test sell other security (generic)
     */
    public function testBuildSellOther()
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
                    <SELLOTHER>
                        <INVSELL>
                            <INVTRAN>
                                <FITID>SELLOTH001
                                <DTTRADE>20260108
                            </INVTRAN>
                        </INVSELL>
                    </SELLOTHER>
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
        $this->assertInstanceOf(\OfxParser\Entities\Investment\Transaction\SellSecurity::class, $transaction);
        $this->assertEquals('SELLOTH001', $transaction->uniqueId);
    }
    
    /**
     * Test reinvest transaction (dividend reinvestment)
     */
    public function testBuildReinvest()
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
                    <REINVEST>
                        <INVTRAN>
                            <FITID>REINV001
                            <DTTRADE>20260110
                            <MEMO>Dividend reinvestment
                        </INVTRAN>
                        <SECID>
                            <UNIQUEID>VTI
                            <UNIQUEIDTYPE>TICKER
                        </SECID>
                        <INCOMETYPE>DIV
                        <TOTAL>100.00
                        <UNITS>0.85
                        <UNITPRICE>117.65
                        <SUBACCTSEC>CASH
                        <SUBACCTFUND>OTHER
                    </REINVEST>
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
        $this->assertInstanceOf(\OfxParser\Entities\Investment\Transaction\Reinvest::class, $transaction);
        $this->assertEquals('REINV001', $transaction->uniqueId);
        $this->assertEquals('Dividend reinvestment', $transaction->memo);
        $this->assertEquals('VTI', $transaction->securityId);
        $this->assertEquals('DIV', $transaction->incomeType);
        $this->assertEquals('100', $transaction->total);
        $this->assertEquals('0.85', $transaction->units);
        $this->assertEquals('117.65', $transaction->unitPrice);
    }
    
    /**
     * Test income transaction (dividend, interest, capital gains)
     */
    public function testBuildIncome()
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
                    <INCOME>
                        <INVTRAN>
                            <FITID>INC001
                            <DTTRADE>20260111
                            <MEMO>Quarterly dividend
                        </INVTRAN>
                        <SECID>
                            <UNIQUEID>IBM
                            <UNIQUEIDTYPE>TICKER
                        </SECID>
                        <INCOMETYPE>DIV
                        <TOTAL>250.00
                        <SUBACCTSEC>CASH
                        <SUBACCTFUND>CASH
                    </INCOME>
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
        $this->assertInstanceOf(\OfxParser\Entities\Investment\Transaction\Income::class, $transaction);
        $this->assertEquals('INC001', $transaction->uniqueId);
        $this->assertEquals('Quarterly dividend', $transaction->memo);
        $this->assertEquals('IBM', $transaction->securityId);
        $this->assertEquals('DIV', $transaction->incomeType);
        $this->assertEquals('250.00', $transaction->total);
        $this->assertEquals('CASH', $transaction->subAccountSec);
        $this->assertEquals('CASH', $transaction->subAccountFund);
    }
    
    /**
     * Test investment banking transaction (cash transfer, fee, etc.)
     */
    public function testBuildInvestmentBanking()
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
                    <INVBANKTRAN>
                        <STMTTRN>
                            <TRNTYPE>DEBIT
                            <DTPOSTED>20260112
                            <TRNAMT>-15.00
                            <FITID>FEE001
                        </STMTTRN>
                        <SUBACCTFUND>OTHER
                    </INVBANKTRAN>
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
        $this->assertInstanceOf(\OfxParser\Entities\Investment\Transaction\Banking::class, $transaction);
        $this->assertEquals('DEBIT', $transaction->type);
        $this->assertEquals(-15.00, $transaction->amount);
        $this->assertEquals('FEE001', $transaction->uniqueId);
        $this->assertEquals('OTHER', $transaction->subAccountFund);
        $this->assertInstanceOf(\DateTimeInterface::class, $transaction->date);
    }
    
    /**
     * Test multiple investment transactions in one statement
     */
    public function testMultipleInvestmentTransactions()
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
                        </INVBUY>
                    </BUYSTOCK>
                    <INCOME>
                        <INVTRAN>
                            <FITID>TXN002
                            <DTTRADE>20260110
                        </INVTRAN>
                        <INCOMETYPE>DIV
                        <TOTAL>50.00
                    </INCOME>
                    <SELLSTOCK>
                        <INVSELL>
                            <INVTRAN>
                                <FITID>TXN003
                                <DTTRADE>20260112
                            </INVTRAN>
                        </INVSELL>
                    </SELLSTOCK>
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
        
        $transactions = $ofx->bankAccounts[0]->statement->transactions;
        $this->assertCount(3, $transactions);
        
        $this->assertInstanceOf(\OfxParser\Entities\Investment\Transaction\BuyStock::class, $transactions[0]);
        $this->assertInstanceOf(\OfxParser\Entities\Investment\Transaction\Income::class, $transactions[1]);
        $this->assertInstanceOf(\OfxParser\Entities\Investment\Transaction\SellStock::class, $transactions[2]);
    }
    
    /**
     * Test investment account with single account helper
     */
    public function testInvestmentSingleAccountHelper()
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
        
        $this->assertCount(1, $ofx->bankAccounts);
        $this->assertNotNull($ofx->bankAccount); // Should be set for single account
        $this->assertSame($ofx->bankAccounts[0], $ofx->bankAccount);
    }
    
    /**
     * Test transaction with empty INVBUY container (should return null)
     */
    public function testBuyTransactionWithoutInvBuy()
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
        
        // Transaction should be filtered out (null returned, not added to array)
        $this->assertEmpty($ofx->bankAccounts[0]->statement->transactions);
    }
}
