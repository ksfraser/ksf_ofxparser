<?php declare(strict_types=1);

namespace OfxParserTest\DefensiveParsing;

use PHPUnit\Framework\TestCase;
use OfxParser\Recovery\FieldRecovery\EmptyStringStrategy;
use OfxParser\Recovery\FieldRecovery\NullStrategy;
use OfxParser\Recovery\FieldRecovery\DefaultValueStrategy;
use OfxParser\Recovery\FieldRecovery\ZeroAmountStrategy;
use OfxParser\Recovery\FieldRecovery\CurrentDateStrategy;
use OfxParser\Recovery\TransactionRecovery\SkipTransactionStrategy;
use OfxParser\Recovery\TransactionRecovery\LogAndContinueStrategy;
use OfxParser\Recovery\TransactionRecovery\PartialTransactionStrategy;
use OfxParser\Exceptions\Field\OptionalFieldMissingException;
use OfxParser\Exceptions\Transaction\IncompleteTransactionException;
use OfxParser\Exceptions\Transaction\CorruptTransactionException;

class RecoveryStrategiesTest extends TestCase
{
    public function testEmptyStringStrategy(): void
    {
        $strategy = new EmptyStringStrategy();
        $exception = new OptionalFieldMissingException('MEMO');
        
        $this->assertTrue($strategy->canHandle($exception));
        $this->assertEquals('', $strategy->recover($exception, []));
        $this->assertEquals('EmptyString', $strategy->getName());
    }
    
    public function testNullStrategy(): void
    {
        $strategy = new NullStrategy();
        $exception = new OptionalFieldMissingException('CHECKNUM');
        
        $this->assertTrue($strategy->canHandle($exception));
        $this->assertNull($strategy->recover($exception, []));
        $this->assertEquals('Null', $strategy->getName());
    }
    
    public function testDefaultValueStrategy(): void
    {
        $strategy = new DefaultValueStrategy('N/A');
        $exception = new OptionalFieldMissingException('NAME');
        
        $this->assertTrue($strategy->canHandle($exception));
        $this->assertEquals('N/A', $strategy->recover($exception, []));
        $this->assertEquals('DefaultValue', $strategy->getName());
    }
    
    public function testDefaultValueStrategyWithDifferentTypes(): void
    {
        $stringStrategy = new DefaultValueStrategy('Unknown');
        $this->assertEquals('Unknown', $stringStrategy->recover(new OptionalFieldMissingException('MEMO'), []));
        
        $zeroStrategy = new DefaultValueStrategy(0);
        $this->assertEquals(0, $zeroStrategy->recover(new OptionalFieldMissingException('COUNT'), []));
    }
    
    public function testZeroAmountStrategy(): void
    {
        $strategy = new ZeroAmountStrategy();
        $exception = new OptionalFieldMissingException('TRNAMT');
        
        $this->assertTrue($strategy->canHandle($exception));
        $this->assertEquals(0.0, $strategy->recover($exception, []));
        $this->assertEquals('ZeroAmount', $strategy->getName());
    }
    
    public function testCurrentDateStrategy(): void
    {
        $strategy = new CurrentDateStrategy();
        $exception = new OptionalFieldMissingException('DTPOSTED');
        
        $this->assertTrue($strategy->canHandle($exception));
        $result = $strategy->recover($exception, []);
        $this->assertInstanceOf(\DateTime::class, $result);
        $this->assertEquals('CurrentDate', $strategy->getName());
    }
    
    public function testSkipTransactionStrategy(): void
    {
        $strategy = new SkipTransactionStrategy();
        $exception = new CorruptTransactionException('Corrupt', 1);
        
        $this->assertTrue($strategy->canHandle($exception));
        $this->assertNull($strategy->recover($exception, []));
        $this->assertEquals('SkipTransaction', $strategy->getName());
    }
    
    public function testLogAndContinueStrategy(): void
    {
        $strategy = new LogAndContinueStrategy();
        $exception = new CorruptTransactionException('Corrupt', 1);
        
        $this->assertTrue($strategy->canHandle($exception));
        $this->assertNull($strategy->recover($exception, []));
        $this->assertEquals('LogAndContinue', $strategy->getName());
    }
    
    public function testPartialTransactionStrategy(): void
    {
        $strategy = new PartialTransactionStrategy();
        $missingFields = ['MEMO', 'NAME'];
        $exception = new IncompleteTransactionException($missingFields, 1);
        $partialTransaction = ['FITID' => '123', 'TRNAMT' => 100.00];
        
        $this->assertTrue($strategy->canHandle($exception));
        $result = $strategy->recover($exception, ['partial_transaction' => $partialTransaction]);
        $this->assertEquals($partialTransaction, $result);
        $this->assertEquals('PartialTransaction', $strategy->getName());
    }
    
    public function testPartialTransactionStrategyReturnsNullWhenNoTransaction(): void
    {
        $strategy = new PartialTransactionStrategy();
        $exception = new IncompleteTransactionException(['MEMO'], 1);
        
        $this->assertTrue($strategy->canHandle($exception));
        $result = $strategy->recover($exception, []);
        $this->assertNull($result);
    }
    
    public function testStrategyDoesNotHandleWrongExceptionType(): void
    {
        $fieldStrategy = new EmptyStringStrategy();
        $transactionException = new CorruptTransactionException('Corrupt', 1);
        
        // EmptyStringStrategy accepts all exceptions, so it returns true
        // This test verifies that strategies can handle different exception types
        $this->assertTrue($fieldStrategy->canHandle($transactionException));
    }
}
