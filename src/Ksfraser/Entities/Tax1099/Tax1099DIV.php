<?php declare(strict_types=1);

namespace OfxParser\Entities\Tax1099;

/**
 * Form 1099-DIV: Dividends and Distributions
 * 
 * What: Reports dividend income and capital gains distributions from stocks,
 * mutual funds, and other investments.
 * 
 * Why: Investment accounts generate taxable events through:
 * - Ordinary dividends (qualified and non-qualified)
 * - Capital gains distributions (short-term and long-term)
 * - Non-dividend distributions (return of capital)
 * - Foreign taxes paid on dividends
 * 
 * Different dividend types have different tax rates, so proper categorization
 * is essential for accurate tax filing.
 */
class Tax1099DIV extends Tax1099
{
    /**
     * Box 1a: Total ordinary dividends
     * @var float
     */
    public $ordinaryDividends;
    
    /**
     * Box 1b: Qualified dividends (subset of 1a, taxed at capital gains rates)
     * @var float|null
     */
    public $qualifiedDividends;
    
    /**
     * Box 2a: Total capital gain distributions
     * @var float|null
     */
    public $capitalGainDistributions;
    
    /**
     * Box 2b: Unrecaptured Section 1250 gain
     * @var float|null
     */
    public $section1250Gain;
    
    /**
     * Box 2c: Section 1202 gain
     * @var float|null
     */
    public $section1202Gain;
    
    /**
     * Box 2d: Collectibles (28%) gain
     * @var float|null
     */
    public $collectiblesGain;
    
    /**
     * Box 3: Non-dividend distributions (return of capital)
     * @var float|null
     */
    public $nondividendDistributions;
    
    /**
     * Box 4: Federal income tax withheld
     * @var float|null
     */
    public $federalTaxWithheld;
    
    /**
     * Box 6: Foreign tax paid
     * @var float|null
     */
    public $foreignTaxPaid;
    
    /**
     * Box 11: FATCA filing requirement
     * @var bool
     */
    public $fatcaFilingRequired = false;
    
    /**
     * Box 12: Exempt-interest dividends
     * @var float|null
     */
    public $exemptInterestDividends;
}
