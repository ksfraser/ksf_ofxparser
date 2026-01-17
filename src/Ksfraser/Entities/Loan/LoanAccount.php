<?php declare(strict_types=1);

namespace OfxParser\Entities\Loan;

use OfxParser\Entities\AbstractEntity;
use OfxParser\Entities\Statement;

/**
 * Loan Account Entity
 * 
 * What: Represents a loan account (mortgage, car loan, personal loan, line of credit)
 * from LOANMSGSRSV1 message set.
 * 
 * Why: Canadian individuals typically have mortgages, car loans, lines of credit, and
 * personal loans. The OFX LOANMSGSRSV1 provides:
 * - Current principal balance
 * - Interest rate (critical for Canadians tracking mortgage rates)
 * - Payment schedule and amounts
 * - Remaining term
 * - Payment history (principal vs interest breakdown)
 * 
 * Use Cases:
 * - Mortgage tracking and renewal planning
 * - Car loan payoff calculations
 * - Line of credit monitoring
 * - Debt consolidation planning
 * - Tax reporting (interest paid on investment loans)
 * 
 * SOLID Principles:
 * - Single Responsibility: Represents only loan account data
 * - Open/Closed: Can be extended for specific loan types without modification
 * - Dependency Inversion: Uses Statement abstraction for transactions
 */
class LoanAccount extends AbstractEntity
{
    /**
     * Loan account identifier
     * @var string
     */
    public $accountNumber;
    
    /**
     * Loan account type
     * Values: MORTGAGE, AUTO, PERSONAL, LINEOFCREDIT, COMMERCIAL, OTHER
     * @var string
     */
    public $accountType;
    
    /**
     * Currency code (CAD, USD, etc.)
     * @var string
     */
    public $currency;
    
    /**
     * Current principal balance (remaining amount owed)
     * @var float
     */
    public $principalBalance;
    
    /**
     * Current interest rate (annual percentage)
     * @var float
     */
    public $interestRate;
    
    /**
     * Date interest rate was set/updated
     * @var \DateTimeInterface|null
     */
    public $interestRateAsOf;
    
    /**
     * Regular payment amount
     * @var float|null
     */
    public $paymentAmount;
    
    /**
     * Next payment due date
     * @var \DateTimeInterface|null
     */
    public $nextPaymentDate;
    
    /**
     * Payment frequency
     * Values: WEEKLY, BIWEEKLY, TWICEMONTHLY, MONTHLY, FOURWEEKS, BIMONTHLY, 
     *         QUARTERLY, SEMIANNUALLY, ANNUALLY
     * @var string|null
     */
    public $paymentFrequency;
    
    /**
     * Number of payments remaining
     * @var int|null
     */
    public $paymentsRemaining;
    
    /**
     * Loan maturity date (final payment date)
     * @var \DateTimeInterface|null
     */
    public $maturityDate;
    
    /**
     * Initial loan amount (original principal)
     * @var float|null
     */
    public $initialBalance;
    
    /**
     * Total remaining interest to be paid
     * @var float|null
     */
    public $remainingInterest;
    
    /**
     * Available credit (for lines of credit)
     * @var float|null
     */
    public $availableCredit;
    
    /**
     * Credit limit (for lines of credit)
     * @var float|null
     */
    public $creditLimit;
    
    /**
     * Statement with transaction history
     * @var Statement|null
     */
    public $statement;
}
