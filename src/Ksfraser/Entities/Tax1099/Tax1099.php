<?php declare(strict_types=1);

namespace OfxParser\Entities\Tax1099;

use OfxParser\Entities\AbstractEntity;

/**
 * Base class for 1099 tax forms
 * 
 * What: Abstract base for all 1099 tax form types (INT, DIV, B, MISC, etc.)
 * 
 * Why: The IRS requires financial institutions to report various types of income
 * on Form 1099. The OFX spec provides TAX1099MSGSRSV1 to deliver these tax forms
 * electronically to account holders. This enables:
 * - Automated tax preparation and filing
 * - Year-end tax document delivery
 * - Historical tax record access
 * - Integration with tax software (TurboTax, H&R Block, etc.)
 * 
 * Each 1099 type has specific fields per IRS requirements, but all share common
 * elements like tax year, payer/payee information, and filing status.
 */
abstract class Tax1099 extends AbstractEntity
{
    /**
     * Tax year (YYYY)
     * @var string
     */
    public $taxYear;
    
    /**
     * Whether this is a void/corrected form
     * @var bool
     */
    public $void = false;
    
    /**
     * Whether this is a corrected form
     * @var bool
     */
    public $corrected = false;
    
    /**
     * Payer information (the financial institution)
     */
    public $payerName;
    public $payerAddress;
    public $payerCity;
    public $payerState;
    public $payerZip;
    public $payerTin; // Taxpayer Identification Number
    
    /**
     * Payee information (the account holder/taxpayer)
     */
    public $payeeName;
    public $payeeAddress;
    public $payeeCity;
    public $payeeState;
    public $payeeZip;
    public $payeeTin; // SSN or EIN
    
    /**
     * Account number at payer
     * @var string|null
     */
    public $accountNumber;
}
