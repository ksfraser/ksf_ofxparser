<?php declare(strict_types=1);

namespace OfxParser\Recovery\FieldRecovery;

use OfxParser\Recovery\RecoveryStrategyInterface;

/**
 * SRP: Returns 0.00 for missing amount fields
 */
class ZeroAmountStrategy implements RecoveryStrategyInterface
{
    public function recover(\Exception $exception, array $context): float
    {
        return 0.00;
    }
    
    public function canHandle(\Exception $exception): bool
    {
        // Can handle any exception - always returns zero
        return true;
    }
    
    public function getName(): string
    {
        return 'ZeroAmount';
    }
}
