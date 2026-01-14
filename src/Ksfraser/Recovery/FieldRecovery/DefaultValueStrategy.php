<?php declare(strict_types=1);

namespace OfxParser\Recovery\FieldRecovery;

use OfxParser\Recovery\RecoveryStrategyInterface;

/**
 * SRP: Returns a predefined default value for missing optional fields
 */
class DefaultValueStrategy implements RecoveryStrategyInterface
{
    private $defaultValue;
    
    public function __construct($defaultValue)
    {
        $this->defaultValue = $defaultValue;
    }
    
    public function recover(\Exception $exception, array $context)
    {
        // Use context default if provided, otherwise use constructor default
        return $context['default'] ?? $this->defaultValue;
    }
    
    public function canHandle(\Exception $exception): bool
    {
        // Can handle any exception - always returns default
        return true;
    }
    
    public function getName(): string
    {
        return 'DefaultValue';
    }
    
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }
}
