<?php declare(strict_types=1);

namespace OfxParser\Entities\BillPay;

use OfxParser\Entities\AbstractEntity;

/**
 * Bill Payment Transaction
 * 
 * What: Represents a single bill payment instruction or confirmation. This is different
 * from regular bank transactions because it includes detailed payment processing info,
 * payee details, and payment scheduling.
 * 
 * Why: The OFX spec separates bill payments from banking transactions because bill pay
 * involves additional complexity:
 * - Scheduled/recurring payments vs immediate transfers
 * - Payee management and validation
 * - Payment processor status and tracking
 * - Merchant/biller integration
 * - Future-dated payments
 * 
 * Bill payments go through a payment processor (the FI's bill pay service) rather than
 * directly debiting/crediting accounts. This requires tracking payment status, processing
 * dates, and confirmation numbers separately from simple account transactions.
 */
class Payment extends AbstractEntity
{
    /**
     * Server-assigned transaction ID
     * @var string
     */
    public $serverTransactionId;
    
    /**
     * Client-assigned transaction ID
     * @var string
     */
    public $clientTransactionId;
    
    /**
     * Payment amount
     * @var float
     */
    public $amount;
    
    /**
     * Account number from which payment is made
     * @var string
     */
    public $accountFrom;
    
    /**
     * Payee information (who receives the payment)
     * @var \OfxParser\Entities\Payee
     */
    public $payee;
    
    /**
     * Payment due date (when payee expects payment)
     * @var \DateTimeInterface|null
     */
    public $dueDate;
    
    /**
     * Date payment was sent to payee
     * @var \DateTimeInterface|null
     */
    public $paymentDate;
    
    /**
     * Payment memo/note
     * @var string|null
     */
    public $memo;
    
    /**
     * Payment status
     * Possible values:
     * - WILLPROCESSON: Scheduled, will process on date
     * - PROCESSEDON: Payment processed
     * - NOFUNDSON: NSF - insufficient funds
     * - CANCELEDON: Payment canceled
     * - FAILEDON: Payment failed
     * 
     * @var string
     */
    public $status;
    
    /**
     * Date associated with status (processed date, failed date, etc.)
     * @var \DateTimeInterface|null
     */
    public $statusDate;
    
    /**
     * Confirmation number from payee (if payment confirmed)
     * @var string|null
     */
    public $confirmationNumber;
    
    /**
     * Check number (if payment made by check)
     * @var string|null
     */
    public $checkNumber;
}
