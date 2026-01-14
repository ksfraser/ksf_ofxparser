<?php declare(strict_types=1);

namespace OfxParser\Recovery\FieldRecovery;

use OfxParser\Recovery\RecoveryStrategyInterface;

/**
 * SRP: Returns current DateTime for missing date fields
 */
class CurrentDateStrategy implements RecoveryStrategyInterface
{
    public function recover(\Exception $exception, array $context): \DateTime
    {
        return new \DateTime();
    }
    
    public function canHandle(\Exception $exception): bool
    {
        // Can handle any exception - always returns current date
        return true;
    }
    
    public function getName(): string
    {
        return 'CurrentDate';
    }
}
