<?php declare(strict_types=1);

namespace OfxParser\Entities\Investment;

use OfxParser\Entities\AbstractEntity;

/**
 * Security Information from Security List
 * 
 * What: Detailed information about a security (stock, bond, mutual fund) referenced
 * in investment transactions.
 * 
 * Why: Investment transactions reference securities by ID (CUSIP, ticker, etc.) but
 * don't include full security details. The OFX spec provides SECLISTMSGSRSV1 to
 * deliver a separate "security master list" with complete information about each
 * security in the account. This enables:
 * 
 * - Display of human-readable security names (not just ticker symbols)
 * - Security type classification (stock, bond, mutual fund, option, etc.)
 * - Price and valuation updates
 * - Corporate action processing
 * - Portfolio analysis and reporting
 * 
 * Without SECLIST, applications would need to look up security details from external
 * sources, which may not match the FI's records (especially for restricted securities,
 * private placements, or securities with multiple share classes).
 */
class Security extends AbstractEntity
{
    /**
     * Security identifier (CUSIP, ISIN, ticker, etc.)
     * @var string
     */
    public $securityId;
    
    /**
     * Type of identifier (CUSIP, ISIN, TICKER, OTHER)
     * @var string
     */
    public $securityIdType;
    
    /**
     * Security name
     * @var string
     */
    public $name;
    
    /**
     * Ticker symbol (if applicable)
     * @var string|null
     */
    public $ticker;
    
    /**
     * Security type
     * Values: STOCK, BOND, MUTUALFUND, OPTION, OTHER
     * @var string
     */
    public $securityType;
    
    /**
     * Debt type (for bonds)
     * Values: COUPON, ZERO (zero-coupon bond)
     * @var string|null
     */
    public $debtType;
    
    /**
     * Debt class (for bonds)
     * Values: TREASURY, MUNICIPAL, CORPORATE, OTHER
     * @var string|null
     */
    public $debtClass;
    
    /**
     * Coupon rate (for bonds, as percentage)
     * @var float|null
     */
    public $couponRate;
    
    /**
     * Maturity date (for bonds)
     * @var \DateTimeInterface|null
     */
    public $maturityDate;
    
    /**
     * Par value (face value for bonds)
     * @var float|null
     */
    public $parValue;
    
    /**
     * Unit price (current market price per share/unit)
     * @var float|null
     */
    public $unitPrice;
    
    /**
     * Date of unit price
     * @var \DateTimeInterface|null
     */
    public $priceDateOf;
    
    /**
     * Currency code
     * @var string|null
     */
    public $currency;
    
    /**
     * Memo/additional information
     * @var string|null
     */
    public $memo;
    
    /**
     * FI-specific asset class
     * @var string|null
     */
    public $assetClass;
    
    /**
     * FI-specific financial institution asset class
     * @var string|null
     */
    public $fiAssetClass;
}
