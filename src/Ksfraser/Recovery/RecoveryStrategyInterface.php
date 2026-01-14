<?php declare(strict_types=1);

namespace OfxParser\Recovery;

/**
 * Interface for recovery strategies (SRP: each strategy handles ONE type of recovery)
 */
interface RecoveryStrategyInterface
{
    /**
     * Attempt to recover from an exception
     * 
     * @param \Exception $exception The exception to recover from
     * @param array $context Additional context (field name, xml node, default value, etc)
     * @return mixed Recovered value or null if unrecoverable
     */
    public function recover(\Exception $exception, array $context);
    
    /**
     * Can this strategy handle this exception type?
     * 
     * @param \Exception $exception
     * @return bool
     */
    public function canHandle(\Exception $exception): bool;
    
    /**
     * Get the name of this recovery strategy
     * 
     * @return string
     */
    public function getName(): string;
}
