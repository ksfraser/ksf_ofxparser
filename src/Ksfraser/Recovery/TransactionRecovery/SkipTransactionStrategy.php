<?php declare(strict_types=1);

namespace OfxParser\Recovery\TransactionRecovery;

use OfxParser\Recovery\RecoveryStrategyInterface;

/**
 * SRP: Skip the transaction and continue parsing
 */
class SkipTransactionStrategy implements RecoveryStrategyInterface
{
    public function recover(\Exception $exception, array $context): ?array
    {
        // Return null to indicate transaction should be skipped
        return null;
    }
    
    public function canHandle(\Exception $exception): bool
    {
        // Can handle any transaction-level exception
        return true;
    }
    
    public function getName(): string
    {
        return 'SkipTransaction';
    }
}
