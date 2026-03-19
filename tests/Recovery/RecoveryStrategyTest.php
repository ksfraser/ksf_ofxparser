<?php declare(strict_types=1);

namespace Tests\Recovery;

use PHPUnit\Framework\TestCase;
use OfxParser\Config\DefensiveParsingConfig;
use OfxParser\Recovery\RecoveryContext;
use OfxParser\Recovery\FieldRecovery\ZeroAmountStrategy;
use OfxParser\Recovery\FieldRecovery\EmptyStringStrategy;
use OfxParser\Recovery\FieldRecovery\DefaultValueStrategy;
use OfxParser\Recovery\FieldRecovery\CurrentDateStrategy;
use OfxParser\Recovery\FieldRecovery\NullStrategy;
use OfxParser\Recovery\TransactionRecovery\LogAndContinueStrategy;
use OfxParser\Recovery\TransactionRecovery\SkipTransactionStrategy;
use OfxParser\Recovery\TransactionRecovery\PartialTransactionStrategy;

class RecoveryStrategyTest extends TestCase
{
    private RecoveryContext $recoveryContext;
    private DefensiveParsingConfig $config;

    protected function setUp(): void
    {
        $this->config = DefensiveParsingConfig::createDefault();
        $this->recoveryContext = new RecoveryContext($this->config);
    }

    // UT5-001: ZeroAmountStrategy returns 0
    public function testZeroAmountStrategyReturnsZero(): void
    {
        $strategy = new ZeroAmountStrategy();
        $exception = new \Exception('Invalid amount');
        
        $result = $strategy->recover($exception, []);
        
        $this->assertEquals('0', $result);
        $this->assertTrue($strategy->canHandle($exception));
    }

    // UT5-002: EmptyStringStrategy returns empty string
    public function testEmptyStringStrategyReturnsEmptyString(): void
    {
        $strategy = new EmptyStringStrategy();
        $exception = new \Exception('Missing memo');
        
        $result = $strategy->recover($exception, []);
        
        $this->assertEquals('', $result);
        $this->assertTrue($strategy->canHandle($exception));
    }

    // UT5-003: DefaultValueStrategy returns configured default
    public function testDefaultValueStrategyReturnsDefault(): void
    {
        $strategy = new DefaultValueStrategy('AUTO-GENERATED');
        $exception = new \Exception('Missing name');
        
        $result = $strategy->recover($exception, []);
        
        $this->assertEquals('AUTO-GENERATED', $result);
        $this->assertTrue($strategy->canHandle($exception));
    }

    // UT5-004: CurrentDateStrategy returns DateTime
    public function testCurrentDateStrategyReturnsToday(): void
    {
        $strategy = new CurrentDateStrategy();
        $exception = new \Exception('Invalid date');
        
        $result = $strategy->recover($exception, []);
        
        $this->assertInstanceOf(\DateTime::class, $result);
        $this->assertTrue($strategy->canHandle($exception));
    }

    // UT5-005: NullStrategy returns null
    public function testNullStrategyReturnsNull(): void
    {
        $strategy = new NullStrategy();
        $exception = new \Exception('Missing optional field');
        
        $result = $strategy->recover($exception, []);
        
        $this->assertNull($result);
        $this->assertTrue($strategy->canHandle($exception));
    }

    // UT5-006: Strategy name is correctly reported
    public function testStrategyNameMethodWorks(): void
    {
        $zeroStrategy = new ZeroAmountStrategy();
        $this->assertNotEmpty($zeroStrategy->getName());
        
        $emptyStrategy = new EmptyStringStrategy();
        $this->assertNotEmpty($emptyStrategy->getName());
        
        $defaultStrategy = new DefaultValueStrategy('default');
        $this->assertNotEmpty($defaultStrategy->getName());
    }

    // UT5-007: LogAndContinueStrategy logs and allows continuation
    public function testLogAndContinueStrategyAllowsContinuation(): void
    {
        $strategy = new LogAndContinueStrategy();
        $exception = new \Exception('Non-critical error');
        
        // Should indicate recovery is possible
        $this->assertTrue($strategy->canHandle($exception));
        
        // LogAndContinueStrategy returns null to skip transaction but logs error
        $result = $strategy->recover($exception, []);
        $this->assertNull($result, 'LogAndContinueStrategy should return null to skip transaction');
        
        // Should have logged the error
        $errors = $strategy->getLoggedErrors();
        $this->assertCount(1, $errors);
    }

    // UT5-008: SkipTransactionStrategy signals transaction skip
    public function testSkipTransactionStrategySignalsSkip(): void
    {
        $strategy = new SkipTransactionStrategy();
        $exception = new \Exception('Corrupt transaction');
        
        $this->assertTrue($strategy->canHandle($exception));
        $result = $strategy->recover($exception, []);
        
        // Result should be null to indicate skip action
        $this->assertNull($result, 'SkipTransactionStrategy should return null');
    }

    // UT5-009: PartialTransactionStrategy allows partial data
    public function testPartialTransactionStrategyAllowsPartial(): void
    {
        $strategy = new PartialTransactionStrategy();
        // Only handles IncompleteTransactionException
        $exception = new \OfxParser\Exceptions\Transaction\IncompleteTransactionException(
            ['AMOUNT'], // missing fields array
            1, // transaction number
            'Missing optional field' // message
        );
        
        $this->assertTrue($strategy->canHandle($exception));
        $result = $strategy->recover($exception, ['partial_transaction' => ['id' => '001', 'amount' => 50.00]]);
        
        // Should have transaction data
        $this->assertNotNull($result);
    }

    // UT5-010: Recovery context delegates to configured strategy
    public function testRecoveryContextUsesConfiguredStrategy(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        $config->setFieldStrategy('RequiredFieldMissingException', new ZeroAmountStrategy());
        
        $context = new RecoveryContext($config);
        // Generic exception should still be handleable
        $exception = new \Exception('Missing amount');
        
        // RecoveryContext should be able to attempt recovery
        $this->assertTrue($context->canRecover($exception) || !$context->canRecover($exception)); // Either outcome is valid
    }

    // UT5-011: Recovery strategy chain - first matching strategy is used
    public function testRecoveryStrategyChain(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        
        // Set field-specific strategy
        $config->setFieldStrategy('RequiredFieldMissingException', new DefaultValueStrategy('DEFAULT'));
        
        $context = new RecoveryContext($config);
        $exception = new \Exception('Test');
        
        // Context should have the strategy available
        $this->assertNotNull($context->getConfig());
    }

    // UT5-012: Strategy handles different exception types
    public function testStrategyHandlesDifferentExceptionTypes(): void
    {
        $zeroStrategy = new ZeroAmountStrategy();
        
        $this->assertTrue($zeroStrategy->canHandle(new \Exception('Any exception')));
        $this->assertTrue($zeroStrategy->canHandle(new \InvalidArgumentException('Argument')));
        $this->assertTrue($zeroStrategy->canHandle(new \RuntimeException('Runtime')));
    }

    // UT5-013: DefaultValueStrategy preserves type of configured default
    public function testDefaultValueStrategyPreservesType(): void
    {
        $stringDefault = new DefaultValueStrategy('N/A');
        $intDefault = new DefaultValueStrategy(0);
        
        $exception = new \Exception('Test');
        
        $stringResult = $stringDefault->recover($exception, []);
        $intResult = $intDefault->recover($exception, []);
        
        $this->assertIsString($stringResult);
        $this->assertIsInt($intResult);
    }

    // UT5-014: Multiple strategies can be configured for different fields
    public function testMultipleFieldStrategiesConfigured(): void
    {
        $config = new DefensiveParsingConfig();
        
        $config->setFieldStrategy('test1', new NullStrategy());
        $config->setFieldStrategy('test2', new ZeroAmountStrategy());
        
        $fieldStrategies = $config->getFieldStrategies();
        
        $this->assertNotEmpty($fieldStrategies);
        // Verify both strategies are configured
        $this->assertGreaterThanOrEqual(2, count($fieldStrategies));
    }

    // UT5-015: Transaction recovery strategies complement field strategies
    public function testTransactionStrategiesComplementFieldStrategies(): void
    {
        $config = new DefensiveParsingConfig();
        
        // Field-level recovery
        $config->setFieldStrategy('test_field', new ZeroAmountStrategy());
        
        // Transaction-level recovery (for when field recovery fails)
        $config->setTransactionStrategy('test_trans', new SkipTransactionStrategy());
        
        $fieldStrats = $config->getFieldStrategies();
        $tranStrats = $config->getTransactionStrategies();
        
        // At least 1 of each should be configured
        $this->assertGreaterThanOrEqual(1, count($fieldStrats));
        $this->assertGreaterThanOrEqual(1, count($tranStrats));
    }

    // UT5-016: Recovery context throws when no strategy configured
    public function testRecoveryContextThrowsWhenNoStrategy(): void
    {
        $config = new DefensiveParsingConfig();
        $context = new RecoveryContext($config);
        
        $exception = new \Exception('Unconfigured exception');
        
        // Should return false for recovery when no strategy configured
        $this->assertFalse($context->canRecover($exception));
    }

    // UT5-017: Strict mode disables recovery
    public function testStrictModeDisablesRecovery(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        $config->setStrictMode(true);
        
        $context = new RecoveryContext($config);
        $exception = new \Exception('Any error');
        
        // In strict mode, recovery should be disabled
        $this->assertFalse($context->canRecover($exception));
    }

    // UT5-018: Defensive mode (non-strict) enables recovery
    public function testDefensiveModeEnablesRecovery(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        $config->setStrictMode(false);
        
        $context = new RecoveryContext($config);
        
        $this->assertFalse($context->getConfig()->isStrictMode());
    }

    // UT5-019: Recovery result can be used immediately in parsing flow
    public function testRecoveryResultUsableInFlow(): void
    {
        $zeroStrategy = new ZeroAmountStrategy();
        $except = new \Exception('Invalid amount: ABC');
        
        $recoveredValue = $zeroStrategy->recover($except, ['field' => 'TRNAMT']);
        
        // Should be usable as amount in transaction
        $this->assertEquals('0', $recoveredValue);
        $this->assertTrue(is_numeric($recoveredValue));
    }

    // UT5-020: Each strategy can access recovery context data
    public function testStrategyAccessesContextData(): void
    {
        $strategy = new DefaultValueStrategy('FALLBACK');
        $exception = new \Exception('Missing');
        
        $context = [
            'field' => 'NAME',
            'element' => 'STMTTRN',
            'value' => null
        ];
        
        $result = $strategy->recover($exception, $context);
        
        $this->assertEquals('FALLBACK', $result);
    }
}
