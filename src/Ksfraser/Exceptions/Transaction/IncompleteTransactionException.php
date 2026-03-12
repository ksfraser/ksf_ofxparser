<?php declare(strict_types=1);

namespace OfxParser\Exceptions\Transaction;

/**
 * Exception thrown when a transaction is incomplete (missing optional fields that impact usability)
 */
class IncompleteTransactionException extends TransactionParsingException
{
    /**
     * @var array
     */
    private $missingFields;
    
    public function __construct(
        array $missingFields,
        int $transactionNumber = 0,
        string $message = "",
        ?\Throwable $previous = null
    ) {
        $this->missingFields = $missingFields;
        $finalMessage = $message ?: "Transaction #{$transactionNumber} is incomplete. Missing fields: " . 
            implode(', ', $missingFields);
        parent::__construct($finalMessage, $transactionNumber, $previous, ['missing_fields' => $missingFields]);
    }
    
    public function getMissingFields(): array
    {
        return $this->missingFields;
    }
}
