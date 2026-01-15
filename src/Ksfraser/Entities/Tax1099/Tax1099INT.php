<?php declare(strict_types=1);

namespace OfxParser\Entities\Tax1099;

/**
 * Form 1099-INT: Interest Income
 * 
 * What: Reports interest income paid by financial institutions (banks, credit unions,
 * brokers) to account holders.
 * 
 * Why: Any interest income over $10/year must be reported to the IRS. This form is
 * used for:
 * - Savings account interest
 * - Checking account interest
 * - Certificate of Deposit (CD) interest
 * - Money market account interest
 * - Bond interest
 * 
 * Taxpayers need this to report interest income on Schedule B of their tax return.
 */
class Tax1099INT extends Tax1099
{
    /**
     * Box 1: Interest income
     * @var float
     */
    public $interestIncome;
    
    /**
     * Box 2: Early withdrawal penalty
     * @var float|null
     */
    public $earlyWithdrawalPenalty;
    
    /**
     * Box 3: Interest on U.S. Savings Bonds and Treasury obligations
     * @var float|null
     */
    public $usSavingsBondsInterest;
    
    /**
     * Box 4: Federal income tax withheld
     * @var float|null
     */
    public $federalTaxWithheld;
    
    /**
     * Box 5: Investment expenses
     * @var float|null
     */
    public $investmentExpenses;
    
    /**
     * Box 6: Foreign tax paid
     * @var float|null
     */
    public $foreignTaxPaid;
    
    /**
     * Box 8: Tax-exempt interest
     * @var float|null
     */
    public $taxExemptInterest;
    
    /**
     * Box 9: Specified private activity bond interest
     * @var float|null
     */
    public $privateActivityBondInterest;
}
