<?php declare(strict_types=1);

namespace OfxParser\Metrics;

/**
 * Tracks parsing metrics and statistics
 */
class ParsingMetrics
{
    private int $successfulTransactions = 0;
    private int $incompleteTransactions = 0;
    private int $corruptTransactions = 0;
    private int $unexpectedErrors = 0;
    
    private array $missingRequiredFields = [];
    private array $missingOptionalFields = [];
    private array $fieldRecoveries = [];
    
    private array $corruptTransactionLogs = [];
    private array $unexpectedErrorLogs = [];
    private array $incompleteTransactionLogs = [];
    
    public function incrementSuccessfulTransaction(): void
    {
        $this->successfulTransactions++;
    }
    
    public function incrementIncompleteTransaction(): void
    {
        $this->incompleteTransactions++;
    }
    
    public function incrementCorruptTransaction(): void
    {
        $this->corruptTransactions++;
    }
    
    public function incrementUnexpectedError(): void
    {
        $this->unexpectedErrors++;
    }
    
    public function incrementMissingRequiredField(string $fieldName): void
    {
        if (!isset($this->missingRequiredFields[$fieldName])) {
            $this->missingRequiredFields[$fieldName] = 0;
        }
        $this->missingRequiredFields[$fieldName]++;
    }
    
    public function incrementMissingOptionalField(string $fieldName): void
    {
        if (!isset($this->missingOptionalFields[$fieldName])) {
            $this->missingOptionalFields[$fieldName] = 0;
        }
        $this->missingOptionalFields[$fieldName]++;
    }
    
    public function incrementFieldRecovery(string $fieldName): void
    {
        if (!isset($this->fieldRecoveries[$fieldName])) {
            $this->fieldRecoveries[$fieldName] = 0;
        }
        $this->fieldRecoveries[$fieldName]++;
    }
    
    public function logCorruptTransaction(int $number, string $reason, ?string $xmlSnippet = null): void
    {
        $this->corruptTransactionLogs[] = [
            'transaction_number' => $number,
            'reason' => $reason,
            'xml_snippet' => $xmlSnippet,
            'timestamp' => new \DateTime()
        ];
    }
    
    public function logIncompleteTransaction(int $number, array $missingFields): void
    {
        $this->incompleteTransactionLogs[] = [
            'transaction_number' => $number,
            'missing_fields' => $missingFields,
            'timestamp' => new \DateTime()
        ];
    }
    
    public function logUnexpectedError(int $number, string $error, ?string $trace = null): void
    {
        $this->unexpectedErrorLogs[] = [
            'transaction_number' => $number,
            'error' => $error,
            'trace' => $trace,
            'timestamp' => new \DateTime()
        ];
    }
    
    public function getTotalTransactions(): int
    {
        return $this->successfulTransactions + $this->incompleteTransactions + $this->corruptTransactions;
    }
    
    public function getSuccessfulTransactions(): int
    {
        return $this->successfulTransactions;
    }
    
    public function getIncompleteTransactions(): int
    {
        return $this->incompleteTransactions;
    }
    
    public function getCorruptTransactions(): int
    {
        return $this->corruptTransactions;
    }
    
    public function getUnexpectedErrors(): int
    {
        return $this->unexpectedErrors;
    }
    
    public function getSuccessRate(): float
    {
        $total = $this->getTotalTransactions();
        return $total > 0 ? ($this->successfulTransactions / $total) * 100 : 0.0;
    }
    
    public function getMissingRequiredFields(): array
    {
        return $this->missingRequiredFields;
    }
    
    public function getMissingOptionalFields(): array
    {
        return $this->missingOptionalFields;
    }
    
    public function getFieldRecoveries(): array
    {
        return $this->fieldRecoveries;
    }
    
    public function getCorruptTransactionLogs(): array
    {
        return $this->corruptTransactionLogs;
    }
    
    public function getIncompleteTransactionLogs(): array
    {
        return $this->incompleteTransactionLogs;
    }
    
    public function getUnexpectedErrorLogs(): array
    {
        return $this->unexpectedErrorLogs;
    }
    
    /**
     * Export all metrics as array
     */
    public function toArray(): array
    {
        return [
            'summary' => [
                'total' => $this->getTotalTransactions(),
                'successful' => $this->successfulTransactions,
                'incomplete' => $this->incompleteTransactions,
                'corrupt' => $this->corruptTransactions,
                'unexpected_errors' => $this->unexpectedErrors,
                'success_rate' => round($this->getSuccessRate(), 2) . '%'
            ],
            'field_issues' => [
                'missing_required' => $this->missingRequiredFields,
                'missing_optional' => $this->missingOptionalFields,
                'recoveries' => $this->fieldRecoveries
            ],
            'logs' => [
                'corrupt_transactions' => $this->corruptTransactionLogs,
                'incomplete_transactions' => $this->incompleteTransactionLogs,
                'unexpected_errors' => $this->unexpectedErrorLogs
            ]
        ];
    }
    
    /**
     * Reset all metrics
     */
    public function reset(): void
    {
        $this->successfulTransactions = 0;
        $this->incompleteTransactions = 0;
        $this->corruptTransactions = 0;
        $this->unexpectedErrors = 0;
        $this->missingRequiredFields = [];
        $this->missingOptionalFields = [];
        $this->fieldRecoveries = [];
        $this->corruptTransactionLogs = [];
        $this->unexpectedErrorLogs = [];
        $this->incompleteTransactionLogs = [];
    }
}
