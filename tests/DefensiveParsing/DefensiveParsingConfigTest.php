<?php declare(strict_types=1);

namespace OfxParserTest\DefensiveParsing;

use PHPUnit\Framework\TestCase;
use OfxParser\Config\DefensiveParsingConfig;
use OfxParser\Recovery\FieldRecovery\EmptyStringStrategy;
use OfxParser\Recovery\FieldRecovery\NullStrategy;
use OfxParser\Recovery\FieldRecovery\ZeroAmountStrategy;
use OfxParser\Recovery\TransactionRecovery\SkipTransactionStrategy;
use OfxParser\Recovery\TransactionRecovery\LogAndContinueStrategy;
use OfxParser\Recovery\TransactionRecovery\PartialTransactionStrategy;

class DefensiveParsingConfigTest extends TestCase
{
    public function testCreateDefaultConfiguration(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        
        $this->assertInstanceOf(DefensiveParsingConfig::class, $config);
        $this->assertFalse($config->isStrictMode());
        $this->assertTrue($config->isMetricsEnabled());
    }
    
    public function testCreateStrictConfiguration(): void
    {
        $config = DefensiveParsingConfig::createStrict();
        
        $this->assertTrue($config->isStrictMode());
        $this->assertTrue($config->isMetricsEnabled());
    }
    
    public function testCreateLenientConfiguration(): void
    {
        $config = DefensiveParsingConfig::createLenient();
        
        $this->assertFalse($config->isStrictMode());
        $this->assertTrue($config->isMetricsEnabled());
        
        // Lenient should have strategies configured
        $fieldStrategies = $config->getFieldStrategies();
        $this->assertNotEmpty($fieldStrategies);
    }
    
    public function testSetFieldStrategy(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        $strategy = new NullStrategy();
        
        $config->setFieldStrategy('OptionalFieldMissingException', $strategy);
        
        $strategies = $config->getFieldStrategies();
        $this->assertArrayHasKey('OptionalFieldMissingException', $strategies);
        $this->assertSame($strategy, $strategies['OptionalFieldMissingException']);
    }
    
    public function testSetTransactionStrategy(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        $strategy = new SkipTransactionStrategy();
        
        $config->setTransactionStrategy('CorruptTransactionException', $strategy);
        
        $strategies = $config->getTransactionStrategies();
        $this->assertArrayHasKey('CorruptTransactionException', $strategies);
        $this->assertSame($strategy, $strategies['CorruptTransactionException']);
    }
    
    public function testGetStrategyForException(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        $strategy = new ZeroAmountStrategy();
        $config->setFieldStrategy('OptionalFieldMissingException', $strategy);
        
        $exception = new \OfxParser\Exceptions\Field\OptionalFieldMissingException('TRNAMT', 'Test message');
        $retrievedStrategy = $config->getStrategyForException($exception);
        
        $this->assertSame($strategy, $retrievedStrategy);
    }
    
    public function testGetStrategyForExceptionReturnsNullWhenNotConfigured(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        
        $exception = new \OfxParser\Exceptions\Field\InvalidFieldValueException('TRNAMT', -999, 'Out of range');
        $retrievedStrategy = $config->getStrategyForException($exception);
        
        $this->assertNull($retrievedStrategy);
    }
    
    public function testSetMetricsEnabled(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        
        $config->setMetricsEnabled(false);
        $this->assertFalse($config->isMetricsEnabled());
        
        $config->setMetricsEnabled(true);
        $this->assertTrue($config->isMetricsEnabled());
    }
    
    public function testSetStrictMode(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        
        $config->setStrictMode(true);
        $this->assertTrue($config->isStrictMode());
        
        $config->setStrictMode(false);
        $this->assertFalse($config->isStrictMode());
    }
}
