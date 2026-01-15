<?php declare(strict_types=1);

namespace OfxParser\Entities\BillPay;

use OfxParser\Entities\AbstractEntity;

/**
 * Bill Pay Account Information
 * 
 * What: Contains bill payment account details and list of payments
 * from the BILLPAYMSGSRSV1 message set.
 * 
 * Why: Bill pay is a separate service from basic banking, often provided
 * by a third-party processor. This entity tracks:
 * - Payment processing account
 * - List of payment transactions
 * - Bill pay service status
 */
class BillPayAccount extends AbstractEntity
{
    /**
     * Bank account number used for bill payments
     * @var string
     */
    public $accountNumber;
    
    /**
     * Bank routing number
     * @var string|null
     */
    public $routingNumber;
    
    /**
     * Account type (CHECKING, SAVINGS, etc.)
     * @var string|null
     */
    public $accountType;
    
    /**
     * List of payment transactions
     * @var Payment[]
     */
    public $payments = [];
    
    /**
     * Bill pay service provider information
     * @var string|null
     */
    public $serviceProvider;
}
