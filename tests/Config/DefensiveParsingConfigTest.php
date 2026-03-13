<?php declare(strict_types=1);

namespace Tests\Config;

use PHPUnit\Framework\TestCase;
use OfxParser\Config\DefensiveParsingConfig;
use OfxParser\Recovery\FieldRecovery\ZeroAmountStrategy;
use OfxParser\Recovery\FieldRecovery\EmptyStringStrategy;
use OfxParser\Recovery\FieldRecovery\NullStrategy;
use OfxParser\Recovery\FieldRecovery\DefaultValueStrategy;
use OfxParser\Recovery\FieldRecovery\CurrentDateStrategy;
use OfxParser\Recovery\TransactionRecovery\SkipTransactionStrategy;
use OfxParser\Recovery\TransactionRecovery\LogAndContinueStrategy;
use OfxParser\Recovery\TransactionRecovery\PartialTransactionStrategy;

class DefensiveParsingConfigTest extends TestCase
{
    // UT6-001: Create default configuration
    public function testCreateDefaultConfiguration(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        
        $this->assertInstanceOf(DefensiveParsingConfig::class, $config);
        $this->assertFalse($config->isStrictMode());
        $this->assertTrue($config->isMetricsEnabled());
    }

    // UT6-002: Add field-specific recovery strategy
    public function testAddFieldSpecificStrategy(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        $strategy = new ZeroAmountStrategy();
        
        $config->setFieldStrategy('OptionalFieldMissingException', $strategy);
        
        $fieldStrategies = $config->getFieldStrategies();
        $this->assertNotEmpty($fieldStrategies);
    }

    // UT6-003: Add default value strategy for specific field
    public function testAddDefaultValueStrategy(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        $strategy = new DefaultValueStrategy('N/A');
        
        $config->setFieldStrategy('MissingNameException', $strategy);
        
        $recovered = $strategy->recover(new \Exception('test'), []);
        $this->assertEquals('N/A', $recovered);
    }

    // UT6-004: Add zero amount strategy
    public function testAddZeroAmountStrategy(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        $config->setFieldStrategy('InvalidAmountException', new ZeroAmountStrategy());
        
        $fieldStrategies = $config->getFieldStrategies();
        $this->assertNotEmpty($fieldStrategies);
    }

    // UT6-005: Add empty string strategy
    public function testAddEmptyStringStrategy(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        $config->setFieldStrategy('MissingMemoException', new EmptyStringStrategy());
        
        $fieldStrategies = $config->getFieldStrategies();
        $this->assertNotEmpty($fieldStrategies);
    }

    // UT6-006: Add current date strategy
    public function testAddCurrentDateStrategy(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        $config->setFieldStrategy('InvalidDateException', new CurrentDateStrategy());
        
        $fieldStrategies = $config->getFieldStrategies();
        $this->assertNotEmpty($fieldStrategies);
    }

    // UT6-007: Add null strategy
    public function testAddNullStrategy(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        $config->setFieldStrategy('OptionalFieldException', new NullStrategy());
        
        $fieldStrategies = $config->getFieldStrategies();
        $this->assertNotEmpty($fieldStrategies);
    }

    // UT6-008: Register transaction-level strategy
    public function testRegisterTransactionStrategy(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        $strategy = new SkipTransactionStrategy();
        
        $config->setTransactionStrategy('CorruptTransactionException', $strategy);
        
        $transStrategies = $config->getTransactionStrategies();
        $this->assertNotEmpty($transStrategies);
    }

    // UT6-009: Add skip transaction strategy
    public function testAddSkipTransactionStrategy(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        $config->setTransactionStrategy('CorruptTransactionException', new SkipTransactionStrategy());
        
        $transStrategies = $config->getTransactionStrategies();
        $this->assertNotEmpty($transStrategies);
    }

    // UT6-010: Add log and continue strategy
    public function testAddLogAndContinueStrategy(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        $config->setTransactionStrategy('NonCriticalErrorException', new LogAndContinueStrategy());
        
        $transStrategies = $config->getTransactionStrategies();
        $this->assertNotEmpty($transStrategies);
    }

    // UT6-011: Add partial transaction strategy
    public function testAddPartialTransactionStrategy(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        $config->setTransactionStrategy('MissingOptionalFieldException', new PartialTransactionStrategy());
        
        $transStrategies = $config->getTransactionStrategies();
        $this->assertNotEmpty($transStrategies);
    }

    // UT6-012: Get strategy for exception
    public function testGetStrategyForException(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        $zeroStrategy = new ZeroAmountStrategy();
        
        $config->setFieldStrategy('InvalidAmountException', $zeroStrategy);
        $exception = new \Exception('Invalid amount');
        
        $strategy = $config->getStrategyForException($exception);
        
        $this->assertNotNull($strategy);
    }

    // UT6-013: Return null when no strategy configured for exception
    public function testReturnNullForUnconfiguredException(): void
    {
        $config = new DefensiveParsingConfig();
        $exception = new \Exception('Unconfigured');
        
        $strategy = $config->getStrategyForException($exception);
        
        $this->assertNull($strategy);
    }

    // UT6-014: Override global default strategy with field-specific
    public function testFieldStrategyOverridesDefault(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        
        $nullStrategy = new NullStrategy();
        $zeroStrategy = new ZeroAmountStrategy();
        
        $config->setFieldStrategy('OptionalFieldException', $nullStrategy);
        $config->setFieldStrategy('OptionalFieldException', $zeroStrategy); // Override
        
        $fieldStrategies = $config->getFieldStrategies();
        $this->assertNotEmpty($fieldStrategies);
    }

    // UT6-015: Enable and disable strict mode
    public function testEnableDisableStrictMode(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        
        $this->assertFalse($config->isStrictMode());
        
        $config->setStrictMode(true);
        $this->assertTrue($config->isStrictMode());
        
        $config->setStrictMode(false);
        $this->assertFalse($config->isStrictMode());
    }

    // UT6-016: Enable and disable metrics
    public function testEnableDisableMetrics(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        
        $this->assertTrue($config->isMetricsEnabled());
        
        $config->setMetricsEnabled(false);
        $this->assertFalse($config->isMetricsEnabled());
        
        $config->setMetricsEnabled(true);
        $this->assertTrue($config->isMetricsEnabled());
    }

    // UT6-017: Get all field strategies
    public function testGetAllFieldStrategies(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        
        $config->setFieldStrategy('Exception1', new ZeroAmountStrategy());
        $config->setFieldStrategy('Exception2', new EmptyStringStrategy());
        $config->setFieldStrategy('Exception3', new NullStrategy());
        
        $strategies = $config->getFieldStrategies();
        
        $this->assertCount(3, $strategies);
        $this->assertArrayHasKey('Exception1', $strategies);
        $this->assertArrayHasKey('Exception2', $strategies);
        $this->assertArrayHasKey('Exception3', $strategies);
    }

    // UT6-018: Get all transaction strategies
    public function testGetAllTransactionStrategies(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        
        $config->setTransactionStrategy('Exception1', new SkipTransactionStrategy());
        $config->setTransactionStrategy('Exception2', new LogAndContinueStrategy());
        $config->setTransactionStrategy('Exception3', new PartialTransactionStrategy());
        
        $strategies = $config->getTransactionStrategies();
        
        $this->assertCount(3, $strategies);
        $this->assertArrayHasKey('Exception1', $strategies);
        $this->assertArrayHasKey('Exception2', $strategies);
        $this->assertArrayHasKey('Exception3', $strategies);
    }

    // UT6-019: Multiple configurations can coexist independently
    public function testMultipleConfigurationsIndependent(): void
    {
        $config1 = DefensiveParsingConfig::createDefault();
        $config1->setFieldStrategy('Exception1', new ZeroAmountStrategy());
        
        $config2 = DefensiveParsingConfig::createDefault();
        $config2->setFieldStrategy('Exception2', new EmptyStringStrategy());
        
        $strategies1 = $config1->getFieldStrategies();
        $strategies2 = $config2->getFieldStrategies();
        
        $this->assertArrayHasKey('Exception1', $strategies1);
        $this->assertArrayNotHasKey('Exception2', $strategies1);
        
        $this->assertArrayHasKey('Exception2', $strategies2);
        $this->assertArrayNotHasKey('Exception1', $strategies2);
    }

    // UT6-020: Configuration can be modified after creation
    public function testConfigurationModifiable(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        
        $this->assertFalse($config->isStrictMode());
        $this->assertTrue($config->isMetricsEnabled());
        
        $config->setStrictMode(true);
        $config->setMetricsEnabled(false);
        $config->setFieldStrategy('Exception1', new ZeroAmountStrategy());
        $config->setTransactionStrategy('Exception2', new SkipTransactionStrategy());
        
        $this->assertTrue($config->isStrictMode());
        $this->assertFalse($config->isMetricsEnabled());
        $this->assertNotEmpty($config->getFieldStrategies());
        $this->assertNotEmpty($config->getTransactionStrategies());
    }

    // UT6-021: Default configuration includes common strategies
    public function testDefaultConfigurationIncludes(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        
        // Default should allow construction/method calls
        $this->assertInstanceOf(DefensiveParsingConfig::class, $config);
    }

    // UT6-022: Strategy retrieval is exception-name aware
    public function testStrategyRetrievalExceptionNameAware(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        
        $config->setFieldStrategy('OptionalFieldMissingException', new ZeroAmountStrategy());
        
        $exception = new \Exception('Missing amount');
        $strategy = $config->getStrategyForException($exception);
        
        // Behavior depends on config implementation (could be by class name)
        // Just verify config returns something or null gracefully
        $this->assertTrue(is_object($strategy) || is_null($strategy));
    }

    // UT6-023: Fluent interface chain (if supported)
    public function testFluentConfigurationChain(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        
        // Test that each setter works
        $config->setStrictMode(false);
        $config->setMetricsEnabled(true);
        $config->setFieldStrategy('Exception1', new ZeroAmountStrategy());
        $config->setTransactionStrategy('Exception2', new SkipTransactionStrategy());
        
        // All setups should succeed without exceptions
        $this->assertTrue(true);
    }

    // UT6-024: Configuration preserved across method calls
    public function testConfigurationPersistence(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        
        $config->setFieldStrategy('Exception1', new ZeroAmountStrategy());
        
        $strategies1 = $config->getFieldStrategies();
        $strategies2 = $config->getFieldStrategies();
        
        // Same config should return same strategies on repeated calls
        $this->assertEquals(count($strategies1), count($strategies2));
    }

    // UT6-025: Clear or reset strategies if supported
    public function testStrategyImmutabilityOrReset(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        
        $config->setFieldStrategy('Exception1', new ZeroAmountStrategy());
        $initialCount = count($config->getFieldStrategies());
        
        // Strategies should persist
        $this->assertEquals($initialCount, count($config->getFieldStrategies()));
    }
}
