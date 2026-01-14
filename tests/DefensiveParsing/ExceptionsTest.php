<?php declare(strict_types=1);

namespace OfxParserTest\DefensiveParsing;

use PHPUnit\Framework\TestCase;
use OfxParser\Exceptions\OfxParsingException;
use OfxParser\Exceptions\Field\RequiredFieldMissingException;
use OfxParser\Exceptions\Field\OptionalFieldMissingException;
use OfxParser\Exceptions\Field\InvalidFieldFormatException;
use OfxParser\Exceptions\Field\InvalidFieldValueException;
use OfxParser\Exceptions\Transaction\TransactionParsingException;
use OfxParser\Exceptions\Transaction\CorruptTransactionException;
use OfxParser\Exceptions\Transaction\IncompleteTransactionException;

class ExceptionsTest extends TestCase
{
    public function testOfxParsingExceptionWithContext(): void
    {
        $context = ['field' => 'FITID', 'value' => ''];
        $exception = new OfxParsingException('Test message', 0, null, $context);
        
        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals($context, $exception->getContext());
        $this->assertArrayHasKey('field', $exception->getContext());
    }
    
    public function testOfxParsingExceptionWithoutContext(): void
    {
        $exception = new OfxParsingException('Test message');
        
        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals([], $exception->getContext());
    }
    
    public function testRequiredFieldMissingException(): void
    {
        $exception = new RequiredFieldMissingException('FITID', 'FITID is required');
        
        $this->assertInstanceOf(OfxParsingException::class, $exception);
        $this->assertStringContainsString('FITID', $exception->getMessage());
        $this->assertEquals('FITID', $exception->getFieldName());
        $this->assertEquals(['field' => 'FITID'], $exception->getContext());
    }
    
    public function testOptionalFieldMissingException(): void
    {
        $exception = new OptionalFieldMissingException('MEMO', 'MEMO is missing');
        
        $this->assertInstanceOf(OfxParsingException::class, $exception);
        $this->assertStringContainsString('MEMO', $exception->getMessage());
        $this->assertEquals('MEMO', $exception->getFieldName());
    }
    
    public function testInvalidFieldFormatException(): void
    {
        $exception = new InvalidFieldFormatException('DTPOSTED', 'invalid-date');
        
        $this->assertInstanceOf(OfxParsingException::class, $exception);
        $this->assertStringContainsString('DTPOSTED', $exception->getMessage());
        $this->assertEquals('DTPOSTED', $exception->getFieldName());
        $this->assertEquals('invalid-date', $exception->getInvalidValue());
    }
    
    public function testInvalidFieldValueException(): void
    {
        $exception = new InvalidFieldValueException('TRNAMT', -999999999, 'Amount out of range');
        
        $this->assertInstanceOf(OfxParsingException::class, $exception);
        $this->assertStringContainsString('TRNAMT', $exception->getMessage());
        $this->assertStringContainsString('Amount out of range', $exception->getMessage());
        $this->assertEquals('TRNAMT', $exception->getFieldName());
    }
    
    public function testTransactionParsingException(): void
    {
        $exception = new TransactionParsingException('Transaction error', 42);
        
        $this->assertInstanceOf(OfxParsingException::class, $exception);
        $this->assertEquals('Transaction error', $exception->getMessage());
        $this->assertEquals(42, $exception->getTransactionNumber());
    }
    
    public function testCorruptTransactionException(): void
    {
        $exception = new CorruptTransactionException('Transaction missing required fields', 10);
        
        $this->assertInstanceOf(TransactionParsingException::class, $exception);
        $this->assertStringContainsString('missing required fields', $exception->getMessage());
        $this->assertEquals(10, $exception->getTransactionNumber());
    }
    
    public function testIncompleteTransactionException(): void
    {
        $missingFields = ['NAME', 'MEMO'];
        $exception = new IncompleteTransactionException($missingFields, 5);
        
        $this->assertInstanceOf(TransactionParsingException::class, $exception);
        $this->assertStringContainsString('incomplete', $exception->getMessage());
        $this->assertEquals($missingFields, $exception->getMissingFields());
        $this->assertEquals(5, $exception->getTransactionNumber());
    }
}
