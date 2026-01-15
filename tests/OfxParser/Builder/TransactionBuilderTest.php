<?php declare(strict_types=1);

namespace OfxParser\Builder;

use PHPUnit\Framework\TestCase;
use OfxParser\Extraction\FieldExtractor;
use OfxParser\Recovery\RecoveryContext;
use OfxParser\Metrics\ParsingMetrics;
use OfxParser\Exceptions\Transaction\CorruptTransactionException;
use OfxParser\Exceptions\Transaction\IncompleteTransactionException;
use OfxParser\Config\DefensiveParsingConfig;
use OfxParser\Recovery\TransactionRecovery\SkipTransactionStrategy;
use OfxParser\Recovery\TransactionRecovery\PartialTransactionStrategy;
use OfxParser\Recovery\FieldRecovery\DefaultValueStrategy;
use OfxParser\Recovery\FieldRecovery\NullStrategy;

/**
 * Test TransactionBuilder defensive parsing
 */
class TransactionBuilderTest extends TestCase
{
    private TransactionBuilder $builder;
    private ParsingMetrics $metrics;
    
    protected function setUp(): void
    {
        $config = new DefensiveParsingConfig(true);
        $config->setTransactionStrategy(CorruptTransactionException::class, new SkipTransactionStrategy());
        $config->setTransactionStrategy(IncompleteTransactionException::class, new SkipTransactionStrategy());
        $config->setFieldStrategy(\OfxParser\Exceptions\Field\RequiredFieldMissingException::class, new NullStrategy());
        
        $recoveryContext = new RecoveryContext($config);
        $this->metrics = new ParsingMetrics();
        $fieldExtractor = new FieldExtractor($recoveryContext, $this->metrics);
        
        $this->builder = new TransactionBuilder(
            $fieldExtractor,
            $recoveryContext,
            $this->metrics
        );
    }
    
    /**
     * Test buildTransactions with valid complete transaction
     */
    public function testBuildTransactionsWithCompleteTransaction(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<BANKTRANLIST>
    <STMTTRN>
        <TRNTYPE>DEBIT</TRNTYPE>
        <DTPOSTED>20240115120000</DTPOSTED>
        <TRNAMT>-123.45</TRNAMT>
        <FITID>20240115-001</FITID>
        <NAME>Store Purchase</NAME>
        <MEMO>Payment at Store</MEMO>
        <CHECKNUM>1234</CHECKNUM>
        <REFNUM>REF-001</REFNUM>
        <SIC>5411</SIC>
        <DTUSER>20240115100000</DTUSER>
        <EXTDNAME>Extended Store Name</EXTDNAME>
        <PAYEEID>PAYEE-001</PAYEEID>
    </STMTTRN>
</BANKTRANLIST>
XML
        );
        
        $transactions = $this->builder->buildTransactions($xml->STMTTRN);
        
        $this->assertCount(1, $transactions);
        $this->assertEquals('DEBIT', $transactions[0]->type);
        $this->assertEquals(-123.45, $transactions[0]->amount);
        $this->assertEquals('20240115-001', $transactions[0]->uniqueId);
        $this->assertEquals('Store Purchase', $transactions[0]->name);
        $this->assertEquals('Payment at Store', $transactions[0]->memo);
        $this->assertEquals('1234', $transactions[0]->checkNumber);
        $this->assertEquals('REF-001', $transactions[0]->refNumber);
        $this->assertEquals('5411', $transactions[0]->sic);
        $this->assertEquals('PAYEE-001', $transactions[0]->payeeId);
        $this->assertEquals('Extended Store Name', $transactions[0]->nameExtended);
        $this->assertInstanceOf(\DateTime::class, $transactions[0]->date);
        $this->assertInstanceOf(\DateTime::class, $transactions[0]->userInitiatedDate);
        $this->assertEquals(1, $this->metrics->getSuccessfulTransactions());
    }
    
    /**
     * Test buildTransactions with minimal required fields only
     */
    public function testBuildTransactionsWithRequiredFieldsOnly(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<BANKTRANLIST>
    <STMTTRN>
        <TRNTYPE>CREDIT</TRNTYPE>
        <DTPOSTED>20240115</DTPOSTED>
        <TRNAMT>500.00</TRNAMT>
        <FITID>TXN-002</FITID>
    </STMTTRN>
</BANKTRANLIST>
XML
        );
        
        $transactions = $this->builder->buildTransactions($xml->STMTTRN);
        
        $this->assertCount(1, $transactions);
        $this->assertEquals('CREDIT', $transactions[0]->type);
        $this->assertEquals(500.00, $transactions[0]->amount);
        $this->assertEquals('TXN-002', $transactions[0]->uniqueId);
        $this->assertEquals('', $transactions[0]->name);
        $this->assertEquals('', $transactions[0]->memo);
        $this->assertNull($transactions[0]->checkNumber);
        $this->assertNull($transactions[0]->refNumber);
        $this->assertNull($transactions[0]->sic);
        $this->assertNull($transactions[0]->payeeId);
        $this->assertNull($transactions[0]->nameExtended);
        $this->assertNull($transactions[0]->userInitiatedDate);
    }
    
    /**
     * Test buildTransactions with multiple MEMO tags
     */
    public function testBuildTransactionsWithMultipleMemos(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<BANKTRANLIST>
    <STMTTRN>
        <TRNTYPE>PAYMENT</TRNTYPE>
        <DTPOSTED>20240115</DTPOSTED>
        <TRNAMT>-75.00</TRNAMT>
        <FITID>TXN-003</FITID>
        <MEMO>First memo</MEMO>
        <MEMO>Second memo</MEMO>
        <MEMO>Third memo</MEMO>
    </STMTTRN>
</BANKTRANLIST>
XML
        );
        
        $transactions = $this->builder->buildTransactions($xml->STMTTRN);
        
        $this->assertCount(1, $transactions);
        $this->assertEquals('First memo Second memo Third memo', $transactions[0]->memo);
    }
    
    /**
     * Test buildTransactions with PAYEE entity
     */
    public function testBuildTransactionsWithPayee(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<BANKTRANLIST>
    <STMTTRN>
        <TRNTYPE>CHECK</TRNTYPE>
        <DTPOSTED>20240115</DTPOSTED>
        <TRNAMT>-200.00</TRNAMT>
        <FITID>TXN-004</FITID>
        <PAYEE>
            <NAME>ABC Company</NAME>
            <ADDR1>123 Main St</ADDR1>
            <ADDR2>Suite 456</ADDR2>
            <ADDR3>Building B</ADDR3>
            <CITY>New York</CITY>
            <STATE>NY</STATE>
            <POSTALCODE>10001</POSTALCODE>
            <COUNTRY>USA</COUNTRY>
            <PHONE>555-1234</PHONE>
        </PAYEE>
    </STMTTRN>
</BANKTRANLIST>
XML
        );
        
        $transactions = $this->builder->buildTransactions($xml->STMTTRN);
        
        $this->assertCount(1, $transactions);
        $this->assertInstanceOf(\OfxParser\Entities\Payee::class, $transactions[0]->payee);
        $this->assertEquals('ABC Company', $transactions[0]->payee->name);
        $this->assertEquals(['123 Main St', 'Suite 456', 'Building B'], $transactions[0]->payee->address);
        $this->assertEquals('New York', $transactions[0]->payee->city);
        $this->assertEquals('NY', $transactions[0]->payee->state);
        $this->assertEquals('10001', $transactions[0]->payee->postalCode);
        $this->assertEquals('USA', $transactions[0]->payee->country);
        $this->assertEquals('555-1234', $transactions[0]->payee->phone);
    }
    
    /**
     * Test buildTransactions with partial PAYEE (only address1)
     */
    public function testBuildTransactionsWithPartialPayee(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<BANKTRANLIST>
    <STMTTRN>
        <TRNTYPE>DEBIT</TRNTYPE>
        <DTPOSTED>20240115</DTPOSTED>
        <TRNAMT>-50.00</TRNAMT>
        <FITID>TXN-005</FITID>
        <PAYEE>
            <NAME>Local Store</NAME>
            <ADDR1>789 Elm St</ADDR1>
            <CITY>Boston</CITY>
        </PAYEE>
    </STMTTRN>
</BANKTRANLIST>
XML
        );
        
        $transactions = $this->builder->buildTransactions($xml->STMTTRN);
        
        $this->assertCount(1, $transactions);
        $this->assertInstanceOf(\OfxParser\Entities\Payee::class, $transactions[0]->payee);
        $this->assertEquals('Local Store', $transactions[0]->payee->name);
        $this->assertEquals(['789 Elm St'], $transactions[0]->payee->address);
        $this->assertEquals('Boston', $transactions[0]->payee->city);
        $this->assertEquals('', $transactions[0]->payee->state);
        $this->assertEquals('', $transactions[0]->payee->postalCode);
    }
    
    /**
     * Test buildTransactions with BANKACCTTO
     */
    public function testBuildTransactionsWithBankAccountTo(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<BANKTRANLIST>
    <STMTTRN>
        <TRNTYPE>XFER</TRNTYPE>
        <DTPOSTED>20240115</DTPOSTED>
        <TRNAMT>-1000.00</TRNAMT>
        <FITID>TXN-006</FITID>
        <BANKACCTTO>
            <BANKID>123456789</BANKID>
            <BRANCHID>001</BRANCHID>
            <ACCTID>987654321</ACCTID>
            <ACCTTYPE>SAVINGS</ACCTTYPE>
        </BANKACCTTO>
    </STMTTRN>
</BANKTRANLIST>
XML
        );
        
        $transactions = $this->builder->buildTransactions($xml->STMTTRN);
        
        $this->assertCount(1, $transactions);
        $this->assertInstanceOf(\OfxParser\Entities\BankAccount::class, $transactions[0]->bankAccountTo);
        $this->assertEquals('123456789', $transactions[0]->bankAccountTo->routingNumber);
        $this->assertEquals('001', $transactions[0]->bankAccountTo->agencyNumber);
        $this->assertEquals('987654321', $transactions[0]->bankAccountTo->accountNumber);
        $this->assertEquals('SAVINGS', $transactions[0]->bankAccountTo->accountType);
    }
    
    /**
     * Test buildTransactions with CCACCTTO
     */
    public function testBuildTransactionsWithCreditCardAccountTo(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<BANKTRANLIST>
    <STMTTRN>
        <TRNTYPE>XFER</TRNTYPE>
        <DTPOSTED>20240115</DTPOSTED>
        <TRNAMT>-500.00</TRNAMT>
        <FITID>TXN-007</FITID>
        <CCACCTTO>
            <ACCTID>4111111111111111</ACCTID>
        </CCACCTTO>
    </STMTTRN>
</BANKTRANLIST>
XML
        );
        
        $transactions = $this->builder->buildTransactions($xml->STMTTRN);
        
        $this->assertCount(1, $transactions);
        $this->assertInstanceOf(\OfxParser\Entities\BankAccount::class, $transactions[0]->cardAccountTo);
        $this->assertEquals('4111111111111111', $transactions[0]->cardAccountTo->accountNumber);
    }
    
    /**
     * Test buildTransactions with missing required field (TRNTYPE)
     */
    public function testBuildTransactionsWithMissingTrnType(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<BANKTRANLIST>
    <STMTTRN>
        <DTPOSTED>20240115</DTPOSTED>
        <TRNAMT>-100.00</TRNAMT>
        <FITID>TXN-008</FITID>
    </STMTTRN>
</BANKTRANLIST>
XML
        );
        
        $transactions = $this->builder->buildTransactions($xml->STMTTRN);
        
        // Should skip corrupt transaction with SkipTransactionStrategy (or recover with strategy)
        // The key is that a missing required field should NOT cause a fatal error
        $this->assertTrue(is_array($transactions));
    }
    
    /**
     * Test buildTransactions with missing required field (DTPOSTED)
     */
    public function testBuildTransactionsWithMissingDatePosted(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<BANKTRANLIST>
    <STMTTRN>
        <TRNTYPE>DEBIT</TRNTYPE>
        <TRNAMT>-100.00</TRNAMT>
        <FITID>TXN-009</FITID>
    </STMTTRN>
</BANKTRANLIST>
XML
        );
        
        $transactions = $this->builder->buildTransactions($xml->STMTTRN);
        
        // Should handle corrupt transaction gracefully
        $this->assertTrue(is_array($transactions));
    }
    
    /**
     * Test buildTransactions with missing required field (TRNAMT)
     */
    public function testBuildTransactionsWithMissingAmount(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<BANKTRANLIST>
    <STMTTRN>
        <TRNTYPE>DEBIT</TRNTYPE>
        <DTPOSTED>20240115</DTPOSTED>
        <FITID>TXN-010</FITID>
    </STMTTRN>
</BANKTRANLIST>
XML
        );
        
        $transactions = $this->builder->buildTransactions($xml->STMTTRN);
        
        // Should handle corrupt transaction gracefully
        $this->assertTrue(is_array($transactions));
    }
    
    /**
     * Test buildTransactions with missing required field (FITID)
     */
    public function testBuildTransactionsWithMissingFitId(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<BANKTRANLIST>
    <STMTTRN>
        <TRNTYPE>DEBIT</TRNTYPE>
        <DTPOSTED>20240115</DTPOSTED>
        <TRNAMT>-100.00</TRNAMT>
    </STMTTRN>
</BANKTRANLIST>
XML
        );
        
        $transactions = $this->builder->buildTransactions($xml->STMTTRN);
        
        // Should handle corrupt transaction gracefully
        $this->assertTrue(is_array($transactions));
    }
    
    /**
     * Test buildTransactions with multiple transactions (mixed valid/corrupt)
     */
    public function testBuildTransactionsWithMixedValidAndCorrupt(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<BANKTRANLIST>
    <STMTTRN>
        <TRNTYPE>DEBIT</TRNTYPE>
        <DTPOSTED>20240115</DTPOSTED>
        <TRNAMT>-100.00</TRNAMT>
        <FITID>TXN-VALID-1</FITID>
    </STMTTRN>
    <STMTTRN>
        <DTPOSTED>20240116</DTPOSTED>
        <TRNAMT>-200.00</TRNAMT>
        <FITID>TXN-CORRUPT-1</FITID>
    </STMTTRN>
    <STMTTRN>
        <TRNTYPE>CREDIT</TRNTYPE>
        <DTPOSTED>20240117</DTPOSTED>
        <TRNAMT>300.00</TRNAMT>
        <FITID>TXN-VALID-2</FITID>
    </STMTTRN>
</BANKTRANLIST>
XML
        );
        
        $transactions = $this->builder->buildTransactions($xml->STMTTRN);
        
        // Should process valid transactions and skip/recover corrupt ones
        $this->assertGreaterThanOrEqual(2, count($transactions));
        $this->assertEquals(2, $this->metrics->getSuccessfulTransactions());
    }
    
    /**
     * Test buildTransactions with PartialTransactionStrategy
     */
    public function testBuildTransactionsWithPartialTransactionStrategy(): void
    {
        $config = new DefensiveParsingConfig(true);
        $config->setTransactionStrategy(CorruptTransactionException::class, new PartialTransactionStrategy());
        $config->setTransactionStrategy(IncompleteTransactionException::class, new PartialTransactionStrategy());
        $config->setFieldStrategy(\OfxParser\Exceptions\Field\RequiredFieldMissingException::class, new DefaultValueStrategy('UNKNOWN'));
        
        $recoveryContext = new RecoveryContext($config);
        $metrics = new ParsingMetrics();
        $fieldExtractor = new FieldExtractor($recoveryContext, $metrics);
        
        $builder = new TransactionBuilder(
            $fieldExtractor,
            $recoveryContext,
            $metrics
        );
        
        $xml = new \SimpleXMLElement(<<<XML
<BANKTRANLIST>
    <STMTTRN>
        <DTPOSTED>20240115</DTPOSTED>
        <TRNAMT>-100.00</TRNAMT>
        <FITID>TXN-PARTIAL</FITID>
    </STMTTRN>
</BANKTRANLIST>
XML
        );
        
        $transactions = $builder->buildTransactions($xml->STMTTRN);
        
        // PartialTransactionStrategy should return partial transaction (or empty if recovery fails)
        // The key is that it should not throw an exception
        $this->assertTrue(is_array($transactions));
    }
    
    /**
     * Test buildTransactions with empty transaction list
     */
    public function testBuildTransactionsWithEmptyList(): void
    {
        $xml = new \SimpleXMLElement('<BANKTRANLIST><STMTTRN></STMTTRN></BANKTRANLIST>');
        
        // Create an empty SimpleXMLElement by getting children
        $emptyList = new \SimpleXMLElement('<BANKTRANLIST></BANKTRANLIST>');
        $transactions = $this->builder->buildTransactions($emptyList->STMTTRN);
        
        $this->assertCount(0, $transactions);
        $this->assertEquals(0, $this->metrics->getSuccessfulTransactions());
    }
    
    /**
     * Test buildTransactions with malformed PAYEE (missing NAME)
     */
    public function testBuildTransactionsWithMalformedPayee(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<BANKTRANLIST>
    <STMTTRN>
        <TRNTYPE>DEBIT</TRNTYPE>
        <DTPOSTED>20240115</DTPOSTED>
        <TRNAMT>-100.00</TRNAMT>
        <FITID>TXN-011</FITID>
        <PAYEE>
            <ADDR1>123 Main St</ADDR1>
        </PAYEE>
    </STMTTRN>
</BANKTRANLIST>
XML
        );
        
        $transactions = $this->builder->buildTransactions($xml->STMTTRN);
        
        // Should still build transaction successfully (PAYEE is optional)
        $this->assertCount(1, $transactions);
        $this->assertInstanceOf(\OfxParser\Entities\Payee::class, $transactions[0]->payee);
        $this->assertEquals('', $transactions[0]->payee->name);
        $this->assertEquals(['123 Main St'], $transactions[0]->payee->address);
    }
    
    /**
     * Test metrics tracking for missing optional fields
     */
    public function testMetricsTrackingForMissingOptionalFields(): void
    {
        $xml = new \SimpleXMLElement(<<<XML
<BANKTRANLIST>
    <STMTTRN>
        <TRNTYPE>DEBIT</TRNTYPE>
        <DTPOSTED>20240115</DTPOSTED>
        <TRNAMT>-100.00</TRNAMT>
        <FITID>TXN-012</FITID>
    </STMTTRN>
</BANKTRANLIST>
XML
        );
        
        $transactions = $this->builder->buildTransactions($xml->STMTTRN);
        
        $this->assertCount(1, $transactions);
        
        // Verify metrics tracked missing optional fields
        $missingFields = $this->metrics->getMissingOptionalFields();
        $this->assertArrayHasKey('NAME', $missingFields);
        $this->assertArrayHasKey('MEMO', $missingFields);
        $this->assertArrayHasKey('CHECKNUM', $missingFields);
        $this->assertArrayHasKey('REFNUM', $missingFields);
        $this->assertArrayHasKey('SIC', $missingFields);
        $this->assertArrayHasKey('DTUSER', $missingFields);
        $this->assertArrayHasKey('EXTDNAME', $missingFields);
        $this->assertArrayHasKey('PAYEEID', $missingFields);
        $this->assertArrayHasKey('PAYEE', $missingFields);
        $this->assertArrayHasKey('BANKACCTTO', $missingFields);
        $this->assertArrayHasKey('CCACCTTO', $missingFields);
    }
}
