<?php declare(strict_types=1);

namespace OfxParser\Exceptions\Transaction;

use OfxParser\Exceptions\OfxParsingException;

/**
 * Base exception for transaction-level parsing errors
 */
class TransactionParsingException extends OfxParsingException
{
    /**
     * @var int
     */
    protected $transactionNumber;
    
    public function __construct(
        string $message,
        int $transactionNumber = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        $this->transactionNumber = $transactionNumber;
        $context['transaction_number'] = $transactionNumber;
        parent::__construct($message, 0, $previous, $context);
    }
    
    public function getTransactionNumber(): int
    {
        return $this->transactionNumber;
    }
}
