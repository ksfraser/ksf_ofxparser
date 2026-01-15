<?php declare(strict_types=1);

namespace OfxParser\Entities\Tax1099;

/**
 * Form 1099-B: Proceeds from Broker and Barter Exchange Transactions
 * 
 * What: Reports the sale of stocks, bonds, options, commodities, and other
 * securities through a broker.
 * 
 * Why: When you sell investments, brokers must report:
 * - Sale proceeds
 * - Cost basis (what you paid)
 * - Gain/loss (short-term vs long-term)
 * - Wash sale adjustments
 * 
 * This is critical for calculating capital gains taxes. The IRS matches
 * 1099-B reports with taxpayer Schedule D filings to ensure accurate
 * capital gains reporting.
 */
class Tax1099B extends Tax1099
{
    /**
     * Description of property sold
     * @var string
     */
    public $description;
    
    /**
     * Date acquired
     * @var \DateTimeInterface|null
     */
    public $dateAcquired;
    
    /**
     * Date sold or disposed
     * @var \DateTimeInterface
     */
    public $dateSold;
    
    /**
     * Proceeds (gross sale amount)
     * @var float
     */
    public $proceeds;
    
    /**
     * Cost or other basis
     * @var float|null
     */
    public $costBasis;
    
    /**
     * Whether cost basis was reported to IRS
     * @var bool
     */
    public $basisReportedToIRS = false;
    
    /**
     * Adjustment code (W = wash sale, B = basisadjustment, etc.)
     * @var string|null
     */
    public $adjustmentCode;
    
    /**
     * Adjustment amount
     * @var float|null
     */
    public $adjustmentAmount;
    
    /**
     * Gain or loss (proceeds - basis)
     * @var float|null
     */
    public $gainOrLoss;
    
    /**
     * Whether short-term or long-term
     * Values: 'SHORT', 'LONG'
     * @var string|null
     */
    public $termType;
    
    /**
     * Federal income tax withheld
     * @var float|null
     */
    public $federalTaxWithheld;
    
    /**
     * Non-covered securities (acquired before basis reporting requirements)
     * @var bool
     */
    public $nonCoveredSecurity = false;
}
