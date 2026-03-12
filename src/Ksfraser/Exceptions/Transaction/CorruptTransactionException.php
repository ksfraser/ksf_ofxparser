<?php declare(strict_types=1);

namespace OfxParser\Exceptions\Transaction;

/**
 * Exception thrown when a transaction is corrupt (missing required fields)
 */
class CorruptTransactionException extends TransactionParsingException
{
    /**
     * @var string
     */
    private $reason;
    
    public function __construct(
        string $reason,
        int $transactionNumber = 0,
        ?\Throwable $previous = null
    ) {
        $this->reason = $reason;
        $message = "Transaction #{$transactionNumber} is corrupt: {$reason}";
        parent::__construct($message, $transactionNumber, $previous, ['reason' => $reason]);
    }
    
    public function getReason(): string
    {
        return $this->reason;
    }
}
