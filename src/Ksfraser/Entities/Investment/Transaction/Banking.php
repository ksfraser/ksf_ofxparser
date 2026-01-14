<?php declare(strict_types=1);

namespace OfxParser\Entities\Investment\Transaction;

use SimpleXMLElement;
use OfxParser\Utils;
use OfxParser\Entities\Inspectable;
use OfxParser\Entities\OfxLoadable;
use OfxParser\Entities\Transaction as BaseTransaction;

/**
 * Per OFX 203 doc, this is a wrapper for a <STMTTRN> node
 * (aka "Banking aggregate") with an additional <SUBACCTFUND> node.
 *
 * Requires Inspectable interface to match API of Invesetment entities
 * extending OfxParser\Entities\Investment.
 */
class Banking extends BaseTransaction implements OfxLoadable, Inspectable
{
    /**
     * @var string
     */
    public $nodeName = 'INVBANKTRAN';

    /**
     * @var string
     */
    public $subAccountFund;

    /**
     * Get a list of properties defined for this entity.
     * @return array array('prop_name' => 'prop_name', ...)
     */
    public function getProperties(): array
    {
        $props = array_keys(get_object_vars($this));

        return array_combine($props, $props);
    }

    /**
     * Imports the OFX data for this node.
     * @param SimpleXMLElement $node
     * @return $this
     */
    public function loadOfx(SimpleXMLElement $node): self
    {
        // Duplication of code in Ofx::buildTransactions()
        $this->type = (string) $node->STMTTRN->TRNTYPE;
        $this->date = Utils::createDateTimeFromStr((string) $node->STMTTRN->DTPOSTED);
        $this->amount = Utils::createAmountFromStr((string) $node->STMTTRN->TRNAMT);
        $this->uniqueId = (string) $node->STMTTRN->FITID;

        // Could put this in another trait.
        // Return null for missing fields instead of empty strings
        $this->subAccountFund = isset($node->SUBACCTFUND) && (string) $node->SUBACCTFUND !== '' ? (string) $node->SUBACCTFUND : null;

        return $this;
    }
}
