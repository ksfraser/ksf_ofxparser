<?php declare(strict_types=1);

namespace OfxParser\Parsers;

use SimpleXMLElement;
use \OfxParser\Parser;
use \OfxParser\Ofx\Investment as InvestmentOfx;

class Investment extends Parser
{
    /**
     * Factory to support Investment OFX document structure.
     * @param SimpleXMLElement $xml
     * @return InvestmentOfx
     */
    protected function createOfx($xml): InvestmentOfx
    {
        return new InvestmentOfx(
            $xml,
            $this->transactionBuilder ?? null,
            $this->fieldExtractor ?? null,
            $this->metrics ?? null
        );
    }
}
