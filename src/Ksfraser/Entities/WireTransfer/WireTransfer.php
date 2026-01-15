<?php declare(strict_types=1);

namespace OfxParser\Entities\WireTransfer;

use OfxParser\Entities\AbstractEntity;

/**
 * Wire Transfer Transaction
 * 
 * What: Represents an electronic funds transfer between financial institutions
 * using wire transfer networks (Fedwire, SWIFT, etc.).
 * 
 * Why: Wire transfers are distinct from ACH/EFT transfers and require special handling:
 * - Higher fees and faster processing
 * - International transfer support (SWIFT codes, IBAN)
 * - Enhanced security and fraud prevention
 * - Detailed beneficiary and originator information
 * - Regulatory reporting requirements (CTR, SAR)
 * 
 * The OFX spec provides WIREXFERMSGSRSV1 to capture wire-specific data like
 * intermediary banks, correspondent fees, and international routing codes that
 * don't apply to regular transfers.
 */
class WireTransfer extends AbstractEntity
{
    /**
     * Server transaction ID
     * @var string
     */
    public $serverTransactionId;
    
    /**
     * Transfer amount
     * @var float
     */
    public $amount;
    
    /**
     * Currency code (USD, EUR, etc.)
     * @var string
     */
    public $currency;
    
    /**
     * Source account (originator)
     * @var array ['routingNumber' => string, 'accountNumber' => string, 'accountType' => string]
     */
    public $fromAccount;
    
    /**
     * Destination account (beneficiary)
     * @var array ['routingNumber' => string, 'accountNumber' => string, 'accountType' => string]
     */
    public $toAccount;
    
    /**
     * Beneficiary bank name
     * @var string|null
     */
    public $beneficiaryBank;
    
    /**
     * SWIFT/BIC code for international wires
     * @var string|null
     */
    public $swiftCode;
    
    /**
     * IBAN for international beneficiary
     * @var string|null
     */
    public $iban;
    
    /**
     * Intermediary bank information (for international wires)
     * @var array|null ['name' => string, 'swift' => string, 'routingNumber' => string]
     */
    public $intermediaryBank;
    
    /**
     * Date wire was sent
     * @var \DateTimeInterface|null
     */
    public $datePosted;
    
    /**
     * Date funds available to beneficiary
     * @var \DateTimeInterface|null
     */
    public $dateAvailable;
    
    /**
     * Wire reference number/confirmation
     * @var string|null
     */
    public $referenceNumber;
    
    /**
     * Purpose of payment/transfer reason
     * @var string|null
     */
    public $memo;
    
    /**
     * Beneficiary name
     * @var string
     */
    public $beneficiaryName;
    
    /**
     * Originator name
     * @var string
     */
    public $originatorName;
    
    /**
     * Wire type (DOMESTIC, INTERNATIONAL)
     * @var string
     */
    public $wireType;
    
    /**
     * Wire fees charged
     * @var float|null
     */
    public $fee;
}
