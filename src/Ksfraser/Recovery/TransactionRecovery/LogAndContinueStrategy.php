<?php declare(strict_types=1);

namespace OfxParser\Recovery\TransactionRecovery;

use OfxParser\Recovery\RecoveryStrategyInterface;

/**
 * SRP: Log the error and continue parsing next transaction
 */
class LogAndContinueStrategy implements RecoveryStrategyInterface
{
    /**
     * @var array
     */
    private $loggedErrors = [];
    
    public function recover(\Exception $exception, array $context): ?array
    {
        // Log the error
        $this->loggedErrors[] = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'context' => $context,
            'timestamp' => new \DateTime()
        ];
        
        // Return null to skip transaction
        return null;
    }
    
    public function canHandle(\Exception $exception): bool
    {
        // Can handle any exception
        return true;
    }
    
    public function getName(): string
    {
        return 'LogAndContinue';
    }
    
    public function getLoggedErrors(): array
    {
        return $this->loggedErrors;
    }
    
    public function clearLog(): void
    {
        $this->loggedErrors = [];
    }
}
