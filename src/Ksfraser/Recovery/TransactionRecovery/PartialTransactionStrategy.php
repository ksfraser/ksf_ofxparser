<?php declare(strict_types=1);

namespace OfxParser\Recovery\TransactionRecovery;

use OfxParser\Recovery\RecoveryStrategyInterface;

/**
 * SRP: Save partial transaction with available data (nulls for missing fields)
 */
class PartialTransactionStrategy implements RecoveryStrategyInterface
{
    public function recover(\Exception $exception, array $context)
    {
        // Get the partial transaction from the exception context
        if ($exception instanceof \OfxParser\Exceptions\Transaction\IncompleteTransactionException) {
            $exceptionContext = $exception->getContext();
            $transaction = $exceptionContext['transaction'] ?? null;
            
            if ($transaction !== null) {
                // Return the transaction object
                return $transaction;
            }
        }
        
        // Fallback: check if context has partial_transaction
        return $context['partial_transaction'] ?? null;
    }
    
    public function canHandle(\Exception $exception): bool
    {
        // Only handle incomplete transaction exceptions, not corrupt ones
        return $exception instanceof \OfxParser\Exceptions\Transaction\IncompleteTransactionException;
    }
    
    public function getName(): string
    {
        return 'PartialTransaction';
    }
}
