<?php declare(strict_types=1);

namespace OfxParser\Entities\Investment\Transaction;

use SimpleXMLElement;
use OfxParser\Entities\AbstractEntity;
use OfxParser\Entities\Investment;
use OfxParser\Entities\Investment\Transaction\Traits\InvTran;
use OfxParser\Entities\Investment\Transaction\Traits\SecId;
use OfxParser\Entities\Investment\Transaction\Traits\Pricing;
use OfxParser\Entities\Investment\Transaction\Traits\BuyType;

/**
 * OFX 203 doc:
 * 13.9.2.4.3 Investment Buy/Sell Aggregates <INVBUY>/<INVSELL>
 *
 * Properties found in the <INVBUY> aggregate.
 * Used for "other securities" BUY activities and provides the 
 * base properties to extend for more specific activities.
 *
 * Required:
 * <INVTRAN> aggregate
 * <SECID> aggregate
 * <UNITS>
 * <UNITPRICE>
 * <TOTAL>
 * <SUBACCTSEC>
 * <SUBACCTFUND>
 *
 * Optional:
 * ...many...
 *
 * Partial implementation.
 */
class BuySecurity extends Investment
{
    /**
     * Traits used to define properties
     */
    use InvTran;
    use SecId;
    use Pricing;
    use BuyType;

    /**
     * @var string
     */
    public $nodeName = 'BUYOTHER';

    /**
     * Imports the OFX data for this node.
     * @param SimpleXMLElement $node
     * @return $this
     */
    public function loadOfx(SimpleXMLElement $node): self
    {
        // Transaction data is nested within <INVBUY> child node
        $this->loadInvTran($node->INVBUY->INVTRAN)
            ->loadSecId($node->INVBUY->SECID)
            ->loadPricing($node->INVBUY);

        return $this;
    }
}

