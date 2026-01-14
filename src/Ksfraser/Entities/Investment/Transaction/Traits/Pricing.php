<?php declare(strict_types=1);

namespace OfxParser\Entities\Investment\Transaction\Traits;

use SimpleXMLElement;

/**
 * Combo for units, price, and total
 */
trait Pricing
{
    /**
     * @var float
     */
    public $units;

    /**
     * @var float
     */
    public $unitPrice;

    /**
     * @var float
     */
    public $total;

    /**
     * Where did the money for the transaction come from or go to?
     * CASH, MARGIN, SHORT, OTHER
     * @var string
     */
    public $subAccountFund;

    /**
     * Sub-account type for the security:
     * CASH, MARGIN, SHORT, OTHER
     * @var string
     */
    public $subAccountSec;

    /**
     * @param SimpleXMLElement $node
     * @return $this for chaining
     */
    protected function loadPricing(SimpleXMLElement $node): self
    {
        // Return null for missing fields instead of empty strings
        $this->units = isset($node->UNITS) && (string) $node->UNITS !== '' ? (string) $node->UNITS : null;
        $this->unitPrice = isset($node->UNITPRICE) && (string) $node->UNITPRICE !== '' ? (string) $node->UNITPRICE : null;
        $this->total = isset($node->TOTAL) && (string) $node->TOTAL !== '' ? (string) $node->TOTAL : null;
        $this->subAccountFund = isset($node->SUBACCTFUND) && (string) $node->SUBACCTFUND !== '' ? (string) $node->SUBACCTFUND : null;
        $this->subAccountSec = isset($node->SUBACCTSEC) && (string) $node->SUBACCTSEC !== '' ? (string) $node->SUBACCTSEC : null;

        return $this;
    }
}
