<?php declare(strict_types=1);

namespace OfxParser\Metrics;

use OfxParser\Ofx;

/**
 * Encapsulates parsing result with OFX data and metrics
 */
class ParsingResult
{
    private Ofx $ofx;
    private ParsingMetrics $metrics;
    private bool $hasErrors;
    private bool $isComplete;
    
    public function __construct(Ofx $ofx, ParsingMetrics $metrics)
    {
        $this->ofx = $ofx;
        $this->metrics = $metrics;
        $this->hasErrors = $metrics->getCorruptTransactions() > 0 || $metrics->getUnexpectedErrors() > 0;
        $this->isComplete = $metrics->getCorruptTransactions() === 0 
            && $metrics->getIncompleteTransactions() === 0
            && $metrics->getUnexpectedErrors() === 0;
    }
    
    /**
     * Get the parsed OFX object
     */
    public function getOfx(): Ofx
    {
        return $this->ofx;
    }
    
    /**
     * Get parsing metrics
     */
    public function getMetrics(): ParsingMetrics
    {
        return $this->metrics;
    }
    
    /**
     * Check if parsing encountered any errors
     */
    public function hasErrors(): bool
    {
        return $this->hasErrors;
    }
    
    /**
     * Check if parsing was 100% complete (no corrupt or incomplete transactions)
     */
    public function isComplete(): bool
    {
        return $this->isComplete;
    }
    
    /**
     * Get success rate percentage
     */
    public function getSuccessRate(): float
    {
        return $this->metrics->getSuccessRate();
    }
    
    /**
     * Get total number of transactions attempted
     */
    public function getTotalTransactions(): int
    {
        return $this->metrics->getTotalTransactions();
    }
    
    /**
     * Get number of successfully parsed transactions
     */
    public function getSuccessfulTransactions(): int
    {
        return $this->metrics->getSuccessfulTransactions();
    }
    
    /**
     * Get number of corrupt transactions
     */
    public function getCorruptTransactions(): int
    {
        return $this->metrics->getCorruptTransactions();
    }
    
    /**
     * Get number of incomplete transactions
     */
    public function getIncompleteTransactions(): int
    {
        return $this->metrics->getIncompleteTransactions();
    }
    
    /**
     * Export result summary
     */
    public function toArray(): array
    {
        return [
            'success' => !$this->hasErrors,
            'complete' => $this->isComplete,
            'metrics' => $this->metrics->toArray()
        ];
    }
}
