<?php declare(strict_types=1);

namespace OfxParserTest\DefensiveParsing;

use PHPUnit\Framework\TestCase;
use OfxParser\Recovery\RecoveryContext;
use OfxParser\Config\DefensiveParsingConfig;
use OfxParser\Recovery\FieldRecovery\NullStrategy;
use OfxParser\Recovery\TransactionRecovery\SkipTransactionStrategy;
use OfxParser\Exceptions\Field\OptionalFieldMissingException;
use OfxParser\Exceptions\Transaction\CorruptTransactionException;

class RecoveryContextTest extends TestCase
{
    public function testConstructorWithDefaultConfig(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        $context = new RecoveryContext($config);
        
        $this->assertInstanceOf(RecoveryContext::class, $context);
        $this->assertSame($config, $context->getConfig());
    }
    
    public function testCanRecoverReturnsFalseInStrictMode(): void
    {
        $config = DefensiveParsingConfig::createStrict();
        $context = new RecoveryContext($config);
        
        $exception = new OptionalFieldMissingException('test');
        $this->assertFalse($context->canRecover($exception));
    }
    
    public function testCanRecoverReturnsTrueWhenStrategyConfigured(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        $config->setFieldStrategy('OptionalFieldMissingException', new NullStrategy());
        $context = new RecoveryContext($config);
        
        $exception = new OptionalFieldMissingException('test');
        $this->assertTrue($context->canRecover($exception));
    }
    
    public function testCanRecoverReturnsFalseWhenNoStrategyConfigured(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        $context = new RecoveryContext($config);
        
        // No strategy configured for this exception type
        $exception = new \OfxParser\Exceptions\Field\InvalidFieldValueException('TRNAMT', -999, 'Out of range');
        $this->assertFalse($context->canRecover($exception));
    }
    
    public function testRecoverUsesConfiguredStrategy(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        $config->setFieldStrategy('OptionalFieldMissingException', new NullStrategy());
        $context = new RecoveryContext($config);
        
        $exception = new OptionalFieldMissingException('MEMO', 'Missing memo');
        $result = $context->recover($exception, null, 'MEMO');
        
        $this->assertNull($result); // NullStrategy returns null
    }
    
    public function testRecoverThrowsWhenNoStrategy(): void
    {
        $this->expectException(\OfxParser\Exceptions\Field\InvalidFieldValueException::class);
        
        $config = DefensiveParsingConfig::createDefault();
        // Don't configure any strategy
        $context = new RecoveryContext($config);
        
        $exception = new \OfxParser\Exceptions\Field\InvalidFieldValueException('TRNAMT', -999, 'Out of range');
        $context->recover($exception, null, null);
    }
    
    public function testRecoverForTransactionException(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        $config->setTransactionStrategy('CorruptTransactionException', new SkipTransactionStrategy());
        $context = new RecoveryContext($config);
        
        $exception = new CorruptTransactionException('test');
        $result = $context->recover($exception, null, 1);
        
        $this->assertNull($result); // SkipTransactionStrategy returns null
    }
}
