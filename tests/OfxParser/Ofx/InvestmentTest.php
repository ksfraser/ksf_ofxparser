<?php declare(strict_types=1);

namespace OfxParserTest\Ofx;

use PHPUnit\Framework\TestCase;
use OfxParser\Ofx\Investment;

/**
 * Test Investment OFX parsing
 */
class InvestmentTest extends TestCase
{
    /**
     * Test constructor with null XML (for SGML building)
     */
    public function testConstructorWithNullXml(): void
    {
        $investment = new Investment(null);
        
        $this->assertInstanceOf(Investment::class, $investment);
    }
    
    /**
     * Test buildAccounts with single investment account
     */
    public function testBuildAccountsWithSingleAccount(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
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
    <INVSTMTMSGSRSV1>
        <INVSTMTTRNRS>
            <TRNUID>1001</TRNUID>
            <STATUS>
                <CODE>0</CODE>
                <SEVERITY>INFO</SEVERITY>
            </STATUS>
            <INVSTMTRS>
                <DTASOF>20240115120000</DTASOF>
                <CURDEF>USD</CURDEF>
                <INVACCTFROM>
                    <BROKERID>BROKER123</BROKERID>
                    <ACCTID>INV-123456</ACCTID>
                </INVACCTFROM>
            </INVSTMTRS>
        </INVSTMTTRNRS>
    </INVSTMTMSGSRSV1>
</OFX>
XML
        );
        
        $investment = new Investment($xml);
        
        $this->assertCount(1, $investment->bankAccounts);
        $this->assertNotNull($investment->bankAccount);
        $this->assertEquals('BROKER123', $investment->bankAccount->brokerId);
        $this->assertEquals('INV-123456', $investment->bankAccount->accountNumber);
    }
    
    /**
     * Test buildAccounts with multiple investment accounts
     */
    public function testBuildAccountsWithMultipleAccounts(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0</CODE><SEVERITY>INFO</SEVERITY></STATUS>
            <DTSERVER>20240115120000</DTSERVER>
            <LANGUAGE>ENG</LANGUAGE>
        </SONRS>
    </SIGNONMSGSRSV1>
    <INVSTMTMSGSRSV1>
        <INVSTMTTRNRS>
            <TRNUID>1001</TRNUID>
            <INVSTMTRS>
                <DTASOF>20240115120000</DTASOF>
                <CURDEF>USD</CURDEF>
                <INVACCTFROM>
                    <BROKERID>BROKER123</BROKERID>
                    <ACCTID>INV-111111</ACCTID>
                </INVACCTFROM>
            </INVSTMTRS>
        </INVSTMTTRNRS>
        <INVSTMTTRNRS>
            <TRNUID>1002</TRNUID>
            <INVSTMTRS>
                <DTASOF>20240115120000</DTASOF>
                <CURDEF>USD</CURDEF>
                <INVACCTFROM>
                    <BROKERID>BROKER123</BROKERID>
                    <ACCTID>INV-222222</ACCTID>
                </INVACCTFROM>
            </INVSTMTRS>
        </INVSTMTTRNRS>
    </INVSTMTMSGSRSV1>
</OFX>
XML
        );
        
        $investment = new Investment($xml);
        
        $this->assertCount(2, $investment->bankAccounts);
        $this->assertNull($investment->bankAccount); // No helper when multiple accounts
        $this->assertEquals('INV-111111', $investment->bankAccounts[0]->accountNumber);
        $this->assertEquals('INV-222222', $investment->bankAccounts[1]->accountNumber);
    }
    
    /**
     * Test buildTransactions with BUYMF transaction
     */
    public function testBuildTransactionsWithBuyMutualFund(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0</CODE><SEVERITY>INFO</SEVERITY></STATUS>
            <DTSERVER>20240115120000</DTSERVER>
        </SONRS>
    </SIGNONMSGSRSV1>
    <INVSTMTMSGSRSV1>
        <INVSTMTTRNRS>
            <TRNUID>1001</TRNUID>
            <INVSTMTRS>
                <DTASOF>20240115120000</DTASOF>
                <CURDEF>USD</CURDEF>
                <INVACCTFROM>
                    <BROKERID>BROKER123</BROKERID>
                    <ACCTID>INV-123456</ACCTID>
                </INVACCTFROM>
                <INVTRANLIST>
                    <DTSTART>20240101</DTSTART>
                    <DTEND>20240131</DTEND>
                    <BUYMF>
                        <INVBUY>
                            <INVTRAN>
                                <FITID>TXN001</FITID>
                                <DTTRADE>20240115</DTTRADE>
                            </INVTRAN>
                            <SECID>
                                <UNIQUEID>MF123456789</UNIQUEID>
                                <UNIQUEIDTYPE>CUSIP</UNIQUEIDTYPE>
                            </SECID>
                            <UNITS>100</UNITS>
                            <UNITPRICE>50.00</UNITPRICE>
                            <TOTAL>5000.00</TOTAL>
                            <SUBACCTSEC>OTHER</SUBACCTSEC>
                            <SUBACCTFUND>OTHER</SUBACCTFUND>
                        </INVBUY>
                        <BUYTYPE>BUY</BUYTYPE>
                    </BUYMF>
                </INVTRANLIST>
            </INVSTMTRS>
        </INVSTMTTRNRS>
    </INVSTMTMSGSRSV1>
</OFX>
XML
        );
        
        $investment = new Investment($xml);
        
        $this->assertCount(1, $investment->bankAccount->statement->transactions);
        $this->assertInstanceOf(\OfxParser\Entities\Investment\Transaction\BuyMutualFund::class, $investment->bankAccount->statement->transactions[0]);
    }
    
    /**
     * Test buildTransactions with BUYSTOCK transaction
     */
    public function testBuildTransactionsWithBuyStock(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0</CODE><SEVERITY>INFO</SEVERITY></STATUS>
            <DTSERVER>20240115</DTSERVER>
        </SONRS>
    </SIGNONMSGSRSV1>
    <INVSTMTMSGSRSV1>
        <INVSTMTTRNRS>
            <TRNUID>1001</TRNUID>
            <INVSTMTRS>
                <DTASOF>20240115</DTASOF>
                <CURDEF>USD</CURDEF>
                <INVACCTFROM>
                    <BROKERID>BROKER123</BROKERID>
                    <ACCTID>INV-123456</ACCTID>
                </INVACCTFROM>
                <INVTRANLIST>
                    <DTSTART>20240101</DTSTART>
                    <DTEND>20240131</DTEND>
                    <BUYSTOCK>
                        <INVBUY>
                            <INVTRAN>
                                <FITID>TXN002</FITID>
                                <DTTRADE>20240115</DTTRADE>
                            </INVTRAN>
                            <SECID>
                                <UNIQUEID>US1234567890</UNIQUEID>
                                <UNIQUEIDTYPE>CUSIP</UNIQUEIDTYPE>
                            </SECID>
                            <UNITS>50</UNITS>
                            <UNITPRICE>100.00</UNITPRICE>
                            <TOTAL>5000.00</TOTAL>
                            <SUBACCTSEC>CASH</SUBACCTSEC>
                            <SUBACCTFUND>CASH</SUBACCTFUND>
                        </INVBUY>
                        <BUYTYPE>BUY</BUYTYPE>
                    </BUYSTOCK>
                </INVTRANLIST>
            </INVSTMTRS>
        </INVSTMTTRNRS>
    </INVSTMTMSGSRSV1>
</OFX>
XML
        );
        
        $investment = new Investment($xml);
        
        $this->assertCount(1, $investment->bankAccount->statement->transactions);
        $this->assertInstanceOf(\OfxParser\Entities\Investment\Transaction\BuyStock::class, $investment->bankAccount->statement->transactions[0]);
    }
    
    /**
     * Test buildTransactions with BUYOTHER transaction
     */
    public function testBuildTransactionsWithBuyOther(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0</CODE><SEVERITY>INFO</SEVERITY></STATUS>
            <DTSERVER>20240115</DTSERVER>
        </SONRS>
    </SIGNONMSGSRSV1>
    <INVSTMTMSGSRSV1>
        <INVSTMTTRNRS>
            <TRNUID>1001</TRNUID>
            <INVSTMTRS>
                <DTASOF>20240115</DTASOF>
                <CURDEF>USD</CURDEF>
                <INVACCTFROM>
                    <BROKERID>BROKER123</BROKERID>
                    <ACCTID>INV-123456</ACCTID>
                </INVACCTFROM>
                <INVTRANLIST>
                    <DTSTART>20240101</DTSTART>
                    <DTEND>20240131</DTEND>
                    <BUYOTHER>
                        <INVBUY>
                            <INVTRAN>
                                <FITID>TXN003</FITID>
                                <DTTRADE>20240115</DTTRADE>
                            </INVTRAN>
                            <SECID>
                                <UNIQUEID>OTHER123456</UNIQUEID>
                                <UNIQUEIDTYPE>CUSIP</UNIQUEIDTYPE>
                            </SECID>
                            <UNITS>25</UNITS>
                            <UNITPRICE>200.00</UNITPRICE>
                            <TOTAL>5000.00</TOTAL>
                            <SUBACCTSEC>CASH</SUBACCTSEC>
                            <SUBACCTFUND>CASH</SUBACCTFUND>
                        </INVBUY>
                        <BUYTYPE>BUY</BUYTYPE>
                    </BUYOTHER>
                </INVTRANLIST>
            </INVSTMTRS>
        </INVSTMTTRNRS>
    </INVSTMTMSGSRSV1>
</OFX>
XML
        );
        
        $investment = new Investment($xml);
        
        $this->assertCount(1, $investment->bankAccount->statement->transactions);
        $this->assertInstanceOf(\OfxParser\Entities\Investment\Transaction\BuySecurity::class, $investment->bankAccount->statement->transactions[0]);
    }
    
    /**
     * Test buildTransactions with SELLMF transaction
     */
    public function testBuildTransactionsWithSellMutualFund(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0</CODE><SEVERITY>INFO</SEVERITY></STATUS>
            <DTSERVER>20240115</DTSERVER>
        </SONRS>
    </SIGNONMSGSRSV1>
    <INVSTMTMSGSRSV1>
        <INVSTMTTRNRS>
            <TRNUID>1001</TRNUID>
            <INVSTMTRS>
                <DTASOF>20240115</DTASOF>
                <CURDEF>USD</CURDEF>
                <INVACCTFROM>
                    <BROKERID>BROKER123</BROKERID>
                    <ACCTID>INV-123456</ACCTID>
                </INVACCTFROM>
                <INVTRANLIST>
                    <DTSTART>20240101</DTSTART>
                    <DTEND>20240131</DTEND>
                    <SELLMF>
                        <INVSELL>
                            <INVTRAN>
                                <FITID>TXN004</FITID>
                                <DTTRADE>20240115</DTTRADE>
                            </INVTRAN>
                            <SECID>
                                <UNIQUEID>MF987654321</UNIQUEID>
                                <UNIQUEIDTYPE>CUSIP</UNIQUEIDTYPE>
                            </SECID>
                            <UNITS>100</UNITS>
                            <UNITPRICE>55.00</UNITPRICE>
                            <TOTAL>5500.00</TOTAL>
                            <SUBACCTSEC>OTHER</SUBACCTSEC>
                            <SUBACCTFUND>OTHER</SUBACCTFUND>
                        </INVSELL>
                        <SELLTYPE>SELL</SELLTYPE>
                    </SELLMF>
                </INVTRANLIST>
            </INVSTMTRS>
        </INVSTMTTRNRS>
    </INVSTMTMSGSRSV1>
</OFX>
XML
        );
        
        $investment = new Investment($xml);
        
        $this->assertCount(1, $investment->bankAccount->statement->transactions);
        $this->assertInstanceOf(\OfxParser\Entities\Investment\Transaction\SellMutualFund::class, $investment->bankAccount->statement->transactions[0]);
    }
    
    /**
     * Test buildTransactions with SELLSTOCK transaction
     */
    public function testBuildTransactionsWithSellStock(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0</CODE><SEVERITY>INFO</SEVERITY></STATUS>
            <DTSERVER>20240115</DTSERVER>
        </SONRS>
    </SIGNONMSGSRSV1>
    <INVSTMTMSGSRSV1>
        <INVSTMTTRNRS>
            <TRNUID>1001</TRNUID>
            <INVSTMTRS>
                <DTASOF>20240115</DTASOF>
                <CURDEF>USD</CURDEF>
                <INVACCTFROM>
                    <BROKERID>BROKER123</BROKERID>
                    <ACCTID>INV-123456</ACCTID>
                </INVACCTFROM>
                <INVTRANLIST>
                    <DTSTART>20240101</DTSTART>
                    <DTEND>20240131</DTEND>
                    <SELLSTOCK>
                        <INVSELL>
                            <INVTRAN>
                                <FITID>TXN005</FITID>
                                <DTTRADE>20240115</DTTRADE>
                            </INVTRAN>
                            <SECID>
                                <UNIQUEID>US9876543210</UNIQUEID>
                                <UNIQUEIDTYPE>CUSIP</UNIQUEIDTYPE>
                            </SECID>
                            <UNITS>50</UNITS>
                            <UNITPRICE>110.00</UNITPRICE>
                            <TOTAL>5500.00</TOTAL>
                            <SUBACCTSEC>CASH</SUBACCTSEC>
                            <SUBACCTFUND>CASH</SUBACCTFUND>
                        </INVSELL>
                        <SELLTYPE>SELL</SELLTYPE>
                    </SELLSTOCK>
                </INVTRANLIST>
            </INVSTMTRS>
        </INVSTMTTRNRS>
    </INVSTMTMSGSRSV1>
</OFX>
XML
        );
        
        $investment = new Investment($xml);
        
        $this->assertCount(1, $investment->bankAccount->statement->transactions);
        $this->assertInstanceOf(\OfxParser\Entities\Investment\Transaction\SellStock::class, $investment->bankAccount->statement->transactions[0]);
    }
    
    /**
     * Test buildTransactions with SELLOTHER transaction
     */
    public function testBuildTransactionsWithSellOther(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0</CODE><SEVERITY>INFO</SEVERITY></STATUS>
            <DTSERVER>20240115</DTSERVER>
        </SONRS>
    </SIGNONMSGSRSV1>
    <INVSTMTMSGSRSV1>
        <INVSTMTTRNRS>
            <TRNUID>1001</TRNUID>
            <INVSTMTRS>
                <DTASOF>20240115</DTASOF>
                <CURDEF>USD</CURDEF>
                <INVACCTFROM>
                    <BROKERID>BROKER123</BROKERID>
                    <ACCTID>INV-123456</ACCTID>
                </INVACCTFROM>
                <INVTRANLIST>
                    <DTSTART>20240101</DTSTART>
                    <DTEND>20240131</DTEND>
                    <SELLOTHER>
                        <INVSELL>
                            <INVTRAN>
                                <FITID>TXN006</FITID>
                                <DTTRADE>20240115</DTTRADE>
                            </INVTRAN>
                            <SECID>
                                <UNIQUEID>OTHER987654</UNIQUEID>
                                <UNIQUEIDTYPE>CUSIP</UNIQUEIDTYPE>
                            </SECID>
                            <UNITS>25</UNITS>
                            <UNITPRICE>220.00</UNITPRICE>
                            <TOTAL>5500.00</TOTAL>
                            <SUBACCTSEC>CASH</SUBACCTSEC>
                            <SUBACCTFUND>CASH</SUBACCTFUND>
                        </INVSELL>
                        <SELLTYPE>SELL</SELLTYPE>
                    </SELLOTHER>
                </INVTRANLIST>
            </INVSTMTRS>
        </INVSTMTTRNRS>
    </INVSTMTMSGSRSV1>
</OFX>
XML
        );
        
        $investment = new Investment($xml);
        
        $this->assertCount(1, $investment->bankAccount->statement->transactions);
        $this->assertInstanceOf(\OfxParser\Entities\Investment\Transaction\SellSecurity::class, $investment->bankAccount->statement->transactions[0]);
    }
    
    /**
     * Test buildTransactions with INCOME transaction
     */
    public function testBuildTransactionsWithIncome(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0</CODE><SEVERITY>INFO</SEVERITY></STATUS>
            <DTSERVER>20240115</DTSERVER>
        </SONRS>
    </SIGNONMSGSRSV1>
    <INVSTMTMSGSRSV1>
        <INVSTMTTRNRS>
            <TRNUID>1001</TRNUID>
            <INVSTMTRS>
                <DTASOF>20240115</DTASOF>
                <CURDEF>USD</CURDEF>
                <INVACCTFROM>
                    <BROKERID>BROKER123</BROKERID>
                    <ACCTID>INV-123456</ACCTID>
                </INVACCTFROM>
                <INVTRANLIST>
                    <DTSTART>20240101</DTSTART>
                    <DTEND>20240131</DTEND>
                    <INCOME>
                        <INVTRAN>
                            <FITID>TXN007</FITID>
                            <DTTRADE>20240115</DTTRADE>
                        </INVTRAN>
                        <SECID>
                            <UNIQUEID>US1111111111</UNIQUEID>
                            <UNIQUEIDTYPE>CUSIP</UNIQUEIDTYPE>
                        </SECID>
                        <INCOMETYPE>DIV</INCOMETYPE>
                        <TOTAL>150.00</TOTAL>
                        <SUBACCTSEC>CASH</SUBACCTSEC>
                        <SUBACCTFUND>CASH</SUBACCTFUND>
                    </INCOME>
                </INVTRANLIST>
            </INVSTMTRS>
        </INVSTMTTRNRS>
    </INVSTMTMSGSRSV1>
</OFX>
XML
        );
        
        $investment = new Investment($xml);
        
        $this->assertCount(1, $investment->bankAccount->statement->transactions);
        $this->assertInstanceOf(\OfxParser\Entities\Investment\Transaction\Income::class, $investment->bankAccount->statement->transactions[0]);
    }
    
    /**
     * Test buildTransactions with REINVEST transaction
     */
    public function testBuildTransactionsWithReinvest(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0</CODE><SEVERITY>INFO</SEVERITY></STATUS>
            <DTSERVER>20240115</DTSERVER>
        </SONRS>
    </SIGNONMSGSRSV1>
    <INVSTMTMSGSRSV1>
        <INVSTMTTRNRS>
            <TRNUID>1001</TRNUID>
            <INVSTMTRS>
                <DTASOF>20240115</DTASOF>
                <CURDEF>USD</CURDEF>
                <INVACCTFROM>
                    <BROKERID>BROKER123</BROKERID>
                    <ACCTID>INV-123456</ACCTID>
                </INVACCTFROM>
                <INVTRANLIST>
                    <DTSTART>20240101</DTSTART>
                    <DTEND>20240131</DTEND>
                    <REINVEST>
                        <INVTRAN>
                            <FITID>TXN008</FITID>
                            <DTTRADE>20240115</DTTRADE>
                        </INVTRAN>
                        <SECID>
                            <UNIQUEID>MF555555555</UNIQUEID>
                            <UNIQUEIDTYPE>CUSIP</UNIQUEIDTYPE>
                        </SECID>
                        <INCOMETYPE>DIV</INCOMETYPE>
                        <TOTAL>100.00</TOTAL>
                        <SUBACCTSEC>OTHER</SUBACCTSEC>
                        <UNITS>5</UNITS>
                        <UNITPRICE>20.00</UNITPRICE>
                    </REINVEST>
                </INVTRANLIST>
            </INVSTMTRS>
        </INVSTMTTRNRS>
    </INVSTMTMSGSRSV1>
</OFX>
XML
        );
        
        $investment = new Investment($xml);
        
        $this->assertCount(1, $investment->bankAccount->statement->transactions);
        $this->assertInstanceOf(\OfxParser\Entities\Investment\Transaction\Reinvest::class, $investment->bankAccount->statement->transactions[0]);
    }
    
    /**
     * Test buildTransactions with INVBANKTRAN transaction
     */
    public function testBuildTransactionsWithInvBankTran(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0</CODE><SEVERITY>INFO</SEVERITY></STATUS>
            <DTSERVER>20240115</DTSERVER>
        </SONRS>
    </SIGNONMSGSRSV1>
    <INVSTMTMSGSRSV1>
        <INVSTMTTRNRS>
            <TRNUID>1001</TRNUID>
            <INVSTMTRS>
                <DTASOF>20240115</DTASOF>
                <CURDEF>USD</CURDEF>
                <INVACCTFROM>
                    <BROKERID>BROKER123</BROKERID>
                    <ACCTID>INV-123456</ACCTID>
                </INVACCTFROM>
                <INVTRANLIST>
                    <DTSTART>20240101</DTSTART>
                    <DTEND>20240131</DTEND>
                    <INVBANKTRAN>
                        <STMTTRN>
                            <TRNTYPE>DEBIT</TRNTYPE>
                            <DTPOSTED>20240115</DTPOSTED>
                            <TRNAMT>-500.00</TRNAMT>
                            <FITID>BANK001</FITID>
                        </STMTTRN>
                        <SUBACCTFUND>CASH</SUBACCTFUND>
                    </INVBANKTRAN>
                </INVTRANLIST>
            </INVSTMTRS>
        </INVSTMTTRNRS>
    </INVSTMTMSGSRSV1>
</OFX>
XML
        );
        
        $investment = new Investment($xml);
        
        $this->assertCount(1, $investment->bankAccount->statement->transactions);
        $this->assertInstanceOf(\OfxParser\Entities\Investment\Transaction\Banking::class, $investment->bankAccount->statement->transactions[0]);
    }
    
    /**
     * Test empty INVTRANLIST creates empty transactions array
     */
    public function testEmptyInvTranList(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0</CODE><SEVERITY>INFO</SEVERITY></STATUS>
            <DTSERVER>20240115</DTSERVER>
        </SONRS>
    </SIGNONMSGSRSV1>
    <INVSTMTMSGSRSV1>
        <INVSTMTTRNRS>
            <TRNUID>1001</TRNUID>
            <INVSTMTRS>
                <DTASOF>20240115</DTASOF>
                <CURDEF>USD</CURDEF>
                <INVACCTFROM>
                    <BROKERID>BROKER123</BROKERID>
                    <ACCTID>INV-123456</ACCTID>
                </INVACCTFROM>
            </INVSTMTRS>
        </INVSTMTTRNRS>
    </INVSTMTMSGSRSV1>
</OFX>
XML
        );
        
        $investment = new Investment($xml);
        
        $this->assertCount(0, $investment->bankAccount->statement->transactions);
    }
    
    /**
     * Test statement dates are parsed correctly
     */
    public function testStatementDatesAreParsed(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0</CODE><SEVERITY>INFO</SEVERITY></STATUS>
            <DTSERVER>20240115</DTSERVER>
        </SONRS>
    </SIGNONMSGSRSV1>
    <INVSTMTMSGSRSV1>
        <INVSTMTTRNRS>
            <TRNUID>1001</TRNUID>
            <INVSTMTRS>
                <DTASOF>20240115</DTASOF>
                <CURDEF>USD</CURDEF>
                <INVACCTFROM>
                    <BROKERID>BROKER123</BROKERID>
                    <ACCTID>INV-123456</ACCTID>
                </INVACCTFROM>
                <INVTRANLIST>
                    <DTSTART>20240101120000</DTSTART>
                    <DTEND>20240131235959</DTEND>
                </INVTRANLIST>
            </INVSTMTRS>
        </INVSTMTTRNRS>
    </INVSTMTMSGSRSV1>
</OFX>
XML
        );
        
        $investment = new Investment($xml);
        
        $this->assertInstanceOf(\DateTimeInterface::class, $investment->bankAccount->statement->startDate);
        $this->assertInstanceOf(\DateTimeInterface::class, $investment->bankAccount->statement->endDate);
        $this->assertEquals('2024-01-01', $investment->bankAccount->statement->startDate->format('Y-m-d'));
        $this->assertEquals('2024-01-31', $investment->bankAccount->statement->endDate->format('Y-m-d'));
    }
    
    /**
     * Test signOn is parsed correctly
     */
    public function testSignOnIsParsed(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS>
                <CODE>0</CODE>
                <SEVERITY>INFO</SEVERITY>
            </STATUS>
            <DTSERVER>20240115120000</DTSERVER>
            <LANGUAGE>ENG</LANGUAGE>
            <FI>
                <ORG>MYBROKER</ORG>
                <FID>12345</FID>
            </FI>
        </SONRS>
    </SIGNONMSGSRSV1>
    <INVSTMTMSGSRSV1>
        <INVSTMTTRNRS>
            <TRNUID>1001</TRNUID>
            <INVSTMTRS>
                <DTASOF>20240115</DTASOF>
                <CURDEF>USD</CURDEF>
                <INVACCTFROM>
                    <BROKERID>BROKER123</BROKERID>
                    <ACCTID>INV-123456</ACCTID>
                </INVACCTFROM>
            </INVSTMTRS>
        </INVSTMTTRNRS>
    </INVSTMTMSGSRSV1>
</OFX>
XML
        );
        
        $investment = new Investment($xml);
        
        $this->assertNotNull($investment->signOn);
        $this->assertInstanceOf(\OfxParser\Entities\SignOn::class, $investment->signOn);
    }
}
