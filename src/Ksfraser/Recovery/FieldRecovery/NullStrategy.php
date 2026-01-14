<?php declare(strict_types=1);

namespace OfxParser\Recovery\FieldRecovery;

use OfxParser\Recovery\RecoveryStrategyInterface;

/**
 * SRP: Returns null for missing optional fields
 */
class NullStrategy implements RecoveryStrategyInterface
{
    public function recover(\Exception $exception, array $context): ?string
    {
        return null;
    }
    
    public function canHandle(\Exception $exception): bool
    {
        // Can handle any exception - always returns null
        return true;
    }
    
    public function getName(): string
    {
        return 'Null';
    }
}
