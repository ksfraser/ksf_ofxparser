<?php declare(strict_types=1);

namespace OfxParser\Config;

use OfxParser\Recovery\RecoveryStrategyInterface;
use OfxParser\Recovery\FieldRecovery\EmptyStringStrategy;
use OfxParser\Recovery\FieldRecovery\NullStrategy;
use OfxParser\Recovery\FieldRecovery\ZeroAmountStrategy;
use OfxParser\Recovery\FieldRecovery\CurrentDateStrategy;
use OfxParser\Recovery\TransactionRecovery\SkipTransactionStrategy;
use OfxParser\Recovery\TransactionRecovery\LogAndContinueStrategy;
use OfxParser\Recovery\TransactionRecovery\PartialTransactionStrategy;

/**
 * Configuration for defensive parsing behavior
 */
class DefensiveParsingConfig
{
    /**
     * @var array<string, RecoveryStrategyInterface>
     */
    private $fieldStrategies = [];
    
    /**
     * @var array<string, RecoveryStrategyInterface>
     */
    private $transactionStrategies = [];
    
    /**
     * @var bool
     */
    private $enableMetrics = true;
    
    /**
     * @var bool
     */
    private $strictMode = false;
    
    /**
     * @var bool
     */
    private $continueOnError = true;
    
    /**
     * Create default configuration
     */
    public static function createDefault(): self
    {
        $config = new self();
        
        // Default field recovery strategies
        $config->setFieldStrategy('OptionalFieldMissingException', new NullStrategy());
        $config->setFieldStrategy('InvalidFieldFormatException', new NullStrategy());
        
        // Default transaction recovery strategies
        $config->setTransactionStrategy('CorruptTransactionException', new SkipTransactionStrategy());
        $config->setTransactionStrategy('IncompleteTransactionException', new PartialTransactionStrategy());
        
        return $config;
    }
    
    /**
     * Create strict configuration (no recovery, fail on errors)
     */
    public static function createStrict(): self
    {
        $config = new self();
        $config->strictMode = true;
        return $config;
    }
    
    /**
     * Create lenient configuration (aggressive recovery)
     */
    public static function createLenient(): self
    {
        $config = new self();
        
        // Lenient field strategies
        $config->setFieldStrategy('OptionalFieldMissingException', new EmptyStringStrategy());
        $config->setFieldStrategy('InvalidFieldFormatException', new CurrentDateStrategy());
        
        // Lenient transaction strategies
        $config->setTransactionStrategy('CorruptTransactionException', new LogAndContinueStrategy());
        $config->setTransactionStrategy('IncompleteTransactionException', new PartialTransactionStrategy());
        
        return $config;
    }
    
    /**
     * Set recovery strategy for a specific field exception type
     *
     * @param string $exceptionClass Simple class name (e.g., 'OptionalFieldMissingException')
     * @param RecoveryStrategyInterface $strategy
     */
    public function setFieldStrategy(string $exceptionClass, RecoveryStrategyInterface $strategy): void
    {
        $this->fieldStrategies[$exceptionClass] = $strategy;
    }
    
    /**
     * Set recovery strategy for a specific transaction exception type
     *
     * @param string $exceptionClass Simple class name (e.g., 'CorruptTransactionException')
     * @param RecoveryStrategyInterface $strategy
     */
    public function setTransactionStrategy(string $exceptionClass, RecoveryStrategyInterface $strategy): void
    {
        $this->transactionStrategies[$exceptionClass] = $strategy;
    }
    
    /**
     * Get recovery strategy for exception
     *
     * @param \Exception $exception
     * @return RecoveryStrategyInterface|null
     */
    public function getStrategyForException(\Exception $exception): ?RecoveryStrategyInterface
    {
        $className = $this->getSimpleClassName($exception);
        
        // Check field strategies first
        if (isset($this->fieldStrategies[$className])) {
            return $this->fieldStrategies[$className];
        }
        
        // Check transaction strategies
        if (isset($this->transactionStrategies[$className])) {
            return $this->transactionStrategies[$className];
        }
        
        return null;
    }
    
    /**
     * Check if metrics tracking is enabled
     */
    public function isMetricsEnabled(): bool
    {
        return $this->enableMetrics;
    }
    
    /**
     * Enable/disable metrics tracking
     */
    public function setMetricsEnabled(bool $enabled): void
    {
        $this->enableMetrics = $enabled;
    }
    
    /**
     * Check if strict mode is enabled
     */
    public function isStrictMode(): bool
    {
        return $this->strictMode;
    }
    
    /**
     * Enable/disable strict mode
     */
    public function setStrictMode(bool $strict): void
    {
        $this->strictMode = $strict;
    }
    
    /**
     * Check if should continue on error
     */
    public function shouldContinueOnError(): bool
    {
        return $this->continueOnError;
    }
    
    /**
     * Enable/disable continue on error
     */
    public function setContinueOnError(bool $continue): void
    {
        $this->continueOnError = $continue;
    }
    
    /**
     * Get all configured field strategies
     *
     * @return array<string, RecoveryStrategyInterface>
     */
    public function getFieldStrategies(): array
    {
        return $this->fieldStrategies;
    }
    
    /**
     * Get all configured transaction strategies
     *
     * @return array<string, RecoveryStrategyInterface>
     */
    public function getTransactionStrategies(): array
    {
        return $this->transactionStrategies;
    }
    
    /**
     * Get simple class name from exception
     */
    private function getSimpleClassName(\Exception $exception): string
    {
        $fullClassName = get_class($exception);
        $parts = explode('\\', $fullClassName);
        return end($parts);
    }
}
