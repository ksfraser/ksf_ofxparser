<?php declare(strict_types=1);

namespace OfxParser\Parsers;

use SimpleXMLElement;
use \OfxParser\Parser;
use \OfxParser\Ofx\Investment as InvestmentOfx;

class Investment extends Parser
{
    /**
     * Factory to support Investment OFX document structure.
     * 
     * @param \SimpleXMLElement|\OfxParser\Sgml\Elements\Element $element Parsed OFX element
     * @param array $header Parsed OFX header
     * @return InvestmentOfx|\OfxParser\Metrics\ParsingResult
     */
    protected function createOfx($element, array $header)
    {
        // Handle SGML Elements - would need SgmlInvestmentBuilder (future work)
        if ($element instanceof \OfxParser\Sgml\Elements\Element) {
            // For now, investments don't support SGML path
            throw new \RuntimeException('SGML format not yet supported for investment accounts');
        }
        
        // Handle SimpleXMLElement - create InvestmentOfx instead of Ofx
        $ofx = new InvestmentOfx(
            $element,
            $this->transactionBuilder ?? null,
            $this->fieldExtractor ?? null,
            $this->metrics ?? null
        );
        $ofx->buildHeader($header);
        
        // Return ParsingResult if defensive parsing is enabled
        if ($this->metrics !== null) {
            return new \OfxParser\Metrics\ParsingResult($ofx, $this->metrics);
        }

        return $ofx;
    }
}
