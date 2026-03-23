<?php declare(strict_types=1);

namespace OfxParserTest\Entities\Investment\Transaction\Traits;

use PHPUnit\Framework\TestCase;
use OfxParser\Entities\Investment\Transaction\BuyStock;
use OfxParser\Entities\Investment\Transaction\SellStock;
use OfxParser\Entities\Investment\Transaction\Income;

/**
 * Test Investment Transaction Traits
 */
class InvestmentTraitsTest extends TestCase
{
    /**
     * Test BuyType trait loads buyType field
     */
    public function testBuyTypeTraitLoadsBuyType(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<BUYSTOCK>
    <INVBUY>
        <INVTRAN>
            <FITID>TXN001</FITID>
            <DTTRADE>20240115</DTTRADE>
        </INVTRAN>
        <SECID>
            <UNIQUEID>US1234567890</UNIQUEID>
            <UNIQUEIDTYPE>CUSIP</UNIQUEIDTYPE>
        </SECID>
        <UNITS>100</UNITS>
        <UNITPRICE>50.00</UNITPRICE>
        <TOTAL>5000.00</TOTAL>
        <SUBACCTSEC>CASH</SUBACCTSEC>
        <SUBACCTFUND>CASH</SUBACCTFUND>
    </INVBUY>
    <BUYTYPE>BUY</BUYTYPE>
</BUYSTOCK>
XML
        );
        
        $transaction = new BuyStock();
        $transaction->loadOfx($xml);
        
        $this->assertEquals('BUY', $transaction->buyType);
    }
    
    /**
     * Test BuyType trait with BUYTOCOVER type
     */
    public function testBuyTypeTraitWithBuyToCover(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<BUYSTOCK>
    <INVBUY>
        <INVTRAN>
            <FITID>TXN002</FITID>
            <DTTRADE>20240115</DTTRADE>
        </INVTRAN>
        <SECID>
            <UNIQUEID>US9876543210</UNIQUEID>
            <UNIQUEIDTYPE>CUSIP</UNIQUEIDTYPE>
        </SECID>
        <UNITS>50</UNITS>
        <UNITPRICE>25.00</UNITPRICE>
        <TOTAL>1250.00</TOTAL>
        <SUBACCTSEC>CASH</SUBACCTSEC>
        <SUBACCTFUND>CASH</SUBACCTFUND>
    </INVBUY>
    <BUYTYPE>BUYTOCOVER</BUYTYPE>
</BUYSTOCK>
XML
        );
        
        $transaction = new BuyStock();
        $transaction->loadOfx($xml);
        
        $this->assertEquals('BUYTOCOVER', $transaction->buyType);
    }
    
    /**
     * Test SellType trait loads sellType field
     */
    public function testSellTypeTraitLoadsSellType(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<SELLSTOCK>
    <INVSELL>
        <INVTRAN>
            <FITID>TXN003</FITID>
            <DTTRADE>20240115</DTTRADE>
        </INVTRAN>
        <SECID>
            <UNIQUEID>US1111111111</UNIQUEID>
            <UNIQUEIDTYPE>CUSIP</UNIQUEIDTYPE>
        </SECID>
        <UNITS>100</UNITS>
        <UNITPRICE>60.00</UNITPRICE>
        <TOTAL>6000.00</TOTAL>
        <SUBACCTSEC>CASH</SUBACCTSEC>
        <SUBACCTFUND>CASH</SUBACCTFUND>
    </INVSELL>
    <SELLTYPE>SELL</SELLTYPE>
</SELLSTOCK>
XML
        );
        
        $transaction = new SellStock();
        $transaction->loadOfx($xml);
        
        $this->assertEquals('SELL', $transaction->sellType);
    }
    
    /**
     * Test SellType trait with SELLSHORT type
     */
    public function testSellTypeTraitWithSellShort(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<SELLSTOCK>
    <INVSELL>
        <INVTRAN>
            <FITID>TXN004</FITID>
            <DTTRADE>20240115</DTTRADE>
        </INVTRAN>
        <SECID>
            <UNIQUEID>US2222222222</UNIQUEID>
            <UNIQUEIDTYPE>CUSIP</UNIQUEIDTYPE>
        </SECID>
        <UNITS>50</UNITS>
        <UNITPRICE>30.00</UNITPRICE>
        <TOTAL>1500.00</TOTAL>
        <SUBACCTSEC>CASH</SUBACCTSEC>
        <SUBACCTFUND>CASH</SUBACCTFUND>
    </INVSELL>
    <SELLTYPE>SELLSHORT</SELLTYPE>
</SELLSTOCK>
XML
        );
        
        $transaction = new SellStock();
        $transaction->loadOfx($xml);
        
        $this->assertEquals('SELLSHORT', $transaction->sellType);
    }
    
    /**
     * Test IncomeType trait loads incomeType field
     */
    public function testIncomeTypeTraitLoadsIncomeType(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<INCOME>
    <INVTRAN>
        <FITID>TXN005</FITID>
        <DTTRADE>20240115</DTTRADE>
        <MEMO>Dividend payment</MEMO>
    </INVTRAN>
    <SECID>
        <UNIQUEID>US3333333333</UNIQUEID>
        <UNIQUEIDTYPE>CUSIP</UNIQUEIDTYPE>
    </SECID>
    <INCOMETYPE>DIV</INCOMETYPE>
    <TOTAL>150.00</TOTAL>
    <SUBACCTSEC>CASH</SUBACCTSEC>
    <SUBACCTFUND>CASH</SUBACCTFUND>
</INCOME>
XML
        );
        
        $transaction = new Income();
        $transaction->loadOfx($xml);
        
        $this->assertEquals('DIV', $transaction->incomeType);
    }
    
    /**
     * Test IncomeType trait with INTEREST type
     */
    public function testIncomeTypeTraitWithInterest(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<INCOME>
    <INVTRAN>
        <FITID>TXN006</FITID>
        <DTTRADE>20240115</DTTRADE>
    </INVTRAN>
    <SECID>
        <UNIQUEID>US4444444444</UNIQUEID>
        <UNIQUEIDTYPE>CUSIP</UNIQUEIDTYPE>
    </SECID>
    <INCOMETYPE>INTEREST</INCOMETYPE>
    <TOTAL>75.50</TOTAL>
    <SUBACCTSEC>CASH</SUBACCTSEC>
    <SUBACCTFUND>CASH</SUBACCTFUND>
</INCOME>
XML
        );
        
        $transaction = new Income();
        $transaction->loadOfx($xml);
        
        $this->assertEquals('INTEREST', $transaction->incomeType);
    }
    
    /**
     * Test IncomeType trait with CGLONG (long-term capital gains)
     */
    public function testIncomeTypeTraitWithCapitalGains(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<INCOME>
    <INVTRAN>
        <FITID>TXN007</FITID>
        <DTTRADE>20240115</DTTRADE>
    </INVTRAN>
    <SECID>
        <UNIQUEID>US5555555555</UNIQUEID>
        <UNIQUEIDTYPE>CUSIP</UNIQUEIDTYPE>
    </SECID>
    <INCOMETYPE>CGLONG</INCOMETYPE>
    <TOTAL>200.00</TOTAL>
    <SUBACCTSEC>CASH</SUBACCTSEC>
    <SUBACCTFUND>CASH</SUBACCTFUND>
</INCOME>
XML
        );
        
        $transaction = new Income();
        $transaction->loadOfx($xml);
        
        $this->assertEquals('CGLONG', $transaction->incomeType);
    }
    
    /**
     * Test SecId trait loads security ID
     */
    public function testSecIdTraitLoadsSecurityId(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<BUYSTOCK>
    <INVBUY>
        <INVTRAN>
            <FITID>TXN008</FITID>
            <DTTRADE>20240115</DTTRADE>
        </INVTRAN>
        <SECID>
            <UNIQUEID>US6666666666</UNIQUEID>
            <UNIQUEIDTYPE>CUSIP</UNIQUEIDTYPE>
        </SECID>
        <UNITS>100</UNITS>
        <UNITPRICE>45.00</UNITPRICE>
        <TOTAL>4500.00</TOTAL>
        <SUBACCTSEC>CASH</SUBACCTSEC>
        <SUBACCTFUND>CASH</SUBACCTFUND>
    </INVBUY>
    <BUYTYPE>BUY</BUYTYPE>
</BUYSTOCK>
XML
        );
        
        $transaction = new \OfxParser\Entities\Investment\Transaction\BuyStock();
        $transaction->loadOfx($xml);
        
        $this->assertEquals('US6666666666', $transaction->securityId);
        $this->assertEquals('CUSIP', $transaction->securityIdType);
    }
    
    /**
     * Test Pricing trait loads pricing fields
     */
    public function testPricingTraitLoadsPricingFields(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<BUYSTOCK>
    <INVBUY>
        <INVTRAN>
            <FITID>TXN009</FITID>
            <DTTRADE>20240115</DTTRADE>
        </INVTRAN>
        <SECID>
            <UNIQUEID>US7777777777</UNIQUEID>
            <UNIQUEIDTYPE>CUSIP</UNIQUEIDTYPE>
        </SECID>
        <UNITS>200</UNITS>
        <UNITPRICE>55.50</UNITPRICE>
        <TOTAL>11100.00</TOTAL>
        <COMMISSION>10.00</COMMISSION>
        <FEES>5.00</FEES>
        <SUBACCTSEC>CASH</SUBACCTSEC>
        <SUBACCTFUND>CASH</SUBACCTFUND>
    </INVBUY>
    <BUYTYPE>BUY</BUYTYPE>
</BUYSTOCK>
XML
        );
        
        $transaction = new BuyStock();
        $transaction->loadOfx($xml);
        
        $this->assertEquals(200, $transaction->units);
        $this->assertEquals(55.50, $transaction->unitPrice);
        $this->assertEquals(11100.00, $transaction->total);
    }
    
    /**
     * Test InvTran trait loads investment transaction fields
     */
    public function testInvTranTraitLoadsTransactionFields(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<INCOME>
    <INVTRAN>
        <FITID>TXN010</FITID>
        <DTTRADE>20240115120000</DTTRADE>
        <DTSETTLE>20240117120000</DTSETTLE>
        <MEMO>Quarterly dividend</MEMO>
    </INVTRAN>
    <SECID>
        <UNIQUEID>US8888888888</UNIQUEID>
        <UNIQUEIDTYPE>CUSIP</UNIQUEIDTYPE>
    </SECID>
    <INCOMETYPE>DIV</INCOMETYPE>
    <TOTAL>125.00</TOTAL>
    <SUBACCTSEC>CASH</SUBACCTSEC>
    <SUBACCTFUND>CASH</SUBACCTFUND>
</INCOME>
XML
        );
        
        $transaction = new Income();
        $transaction->loadOfx($xml);
        
        $this->assertEquals('TXN010', $transaction->uniqueId);
        $this->assertEquals('Quarterly dividend', $transaction->memo);
        $this->assertInstanceOf(\DateTimeInterface::class, $transaction->tradeDate);
        $this->assertEquals('2024-01-15', $transaction->tradeDate->format('Y-m-d'));
    }
    
    /**
     * Test all traits work together in BuyStock
     */
    public function testAllTraitsInBuySecurity(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<BUYSTOCK>
    <INVBUY>
        <INVTRAN>
            <FITID>TXN011</FITID>
            <DTTRADE>20240115</DTTRADE>
            <DTSETTLE>20240117</DTSETTLE>
        </INVTRAN>
        <SECID>
            <UNIQUEID>US9999999999</UNIQUEID>
            <UNIQUEIDTYPE>CUSIP</UNIQUEIDTYPE>
        </SECID>
        <UNITS>150</UNITS>
        <UNITPRICE>35.00</UNITPRICE>
        <TOTAL>5250.00</TOTAL>
        <COMMISSION>15.00</COMMISSION>
        <SUBACCTSEC>CASH</SUBACCTSEC>
        <SUBACCTFUND>CASH</SUBACCTFUND>
    </INVBUY>
    <BUYTYPE>BUY</BUYTYPE>
</BUYSTOCK>
XML
        );
        
        $transaction = new BuyStock();
        $transaction->loadOfx($xml);
        
        // InvTran trait
        $this->assertEquals('TXN011', $transaction->uniqueId);
        
        // SecId trait
        $this->assertEquals('US9999999999', $transaction->securityId);
        $this->assertEquals('CUSIP', $transaction->securityIdType);
        
        // Pricing trait
        $this->assertEquals(150, $transaction->units);
        $this->assertEquals(35.00, $transaction->unitPrice);
        $this->assertEquals(5250.00, $transaction->total);
        
        // BuyType trait
        $this->assertEquals('BUY', $transaction->buyType);
    }
    
    /**
     * Test all traits work together in SellSecurity
     */
    public function testAllTraitsInSellSecurity(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<SELLSTOCK>
    <INVSELL>
        <INVTRAN>
            <FITID>TXN012</FITID>
            <DTTRADE>20240115</DTTRADE>
        </INVTRAN>
        <SECID>
            <UNIQUEID>US0000000001</UNIQUEID>
            <UNIQUEIDTYPE>CUSIP</UNIQUEIDTYPE>
        </SECID>
        <UNITS>75</UNITS>
        <UNITPRICE>80.00</UNITPRICE>
        <TOTAL>6000.00</TOTAL>
        <COMMISSION>20.00</COMMISSION>
        <FEES>10.00</FEES>
        <SUBACCTSEC>CASH</SUBACCTSEC>
        <SUBACCTFUND>CASH</SUBACCTFUND>
    </INVSELL>
    <SELLTYPE>SELL</SELLTYPE>
</SELLSTOCK>
XML
        );
        
        $transaction = new SellStock();
        $transaction->loadOfx($xml);
        
        // InvTran trait
        $this->assertEquals('TXN012', $transaction->uniqueId);
        
        // SecId trait
        $this->assertEquals('US0000000001', $transaction->securityId);
        
        // Pricing trait
        $this->assertEquals(75, $transaction->units);
        $this->assertEquals(80.00, $transaction->unitPrice);
        $this->assertEquals(6000.00, $transaction->total);
        
        // SellType trait
        $this->assertEquals('SELL', $transaction->sellType);
    }
}
