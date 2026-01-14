<?php declare(strict_types=1);

namespace OfxParser\Recovery\FieldRecovery;

use OfxParser\Recovery\RecoveryStrategyInterface;

/**
 * SRP: Returns empty string for missing optional fields
 */
class EmptyStringStrategy implements RecoveryStrategyInterface
{
    public function recover(\Exception $exception, array $context): string
    {
        return '';
    }
    
    public function canHandle(\Exception $exception): bool
    {
        // Can handle any exception - always returns empty string
        return true;
    }
    
    public function getName(): string
    {
        return 'EmptyString';
    }
}
