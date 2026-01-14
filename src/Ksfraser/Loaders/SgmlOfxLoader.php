<?php declare(strict_types=1);

namespace OfxParser\Loaders;

use OfxParser\Ofx;
use OfxParser\Metrics\ParsingResult;
use OfxParser\Builder\TransactionBuilder;
use OfxParser\Extraction\FieldExtractor;
use OfxParser\Metrics\ParsingMetrics;
use OfxParser\Sgml;

/**
 * SGML-based OFX loader (OFX v1)
 * 
 * Handles OFX files in SGML format using native SGML parser
 */
class SgmlOfxLoader implements OfxLoaderInterface
{
    private ?TransactionBuilder $transactionBuilder;
    private ?FieldExtractor $fieldExtractor;
    private ?ParsingMetrics $metrics;
    
    public function __construct(
        ?TransactionBuilder $transactionBuilder = null,
        ?FieldExtractor $fieldExtractor = null,
        ?ParsingMetrics $metrics = null
    ) {
        $this->transactionBuilder = $transactionBuilder;
        $this->fieldExtractor = $fieldExtractor;
        $this->metrics = $metrics;
    }
    
    /**
     * {@inheritdoc}
     */
    public function canHandle(string $ofxHeader, string $ofxBody): bool
    {
        // SGML files do NOT have <?xml declaration
        // They have OFXHEADER:100 and DATA:OFXSGML
        return stripos($ofxHeader, '<?xml') === false 
            && (stripos($ofxHeader, 'OFXHEADER') !== false || stripos($ofxHeader, 'DATA:OFXSGML') !== false);
    }
    
    /**
     * {@inheritdoc}
     */
    public function load(string $ofxHeader, string $ofxBody)
    {
        $header = $this->parseHeader($ofxHeader);
        
        // Use the existing, tested SGML to XML conversion
        // TODO: Future enhancement - parse SGML natively without conversion
        $ofxXml = $this->convertSgmlToXml($ofxBody);
        $xml = $this->xmlLoadString($ofxXml);
        
        if (empty($xml) || is_null($xml)) {
            throw new \InvalidArgumentException('Content is not valid OFX schema');
        }

        // Validate that the XML contains expected OFX structure
        if (!isset($xml->SIGNONMSGSRSV1) && !isset($xml->BANKMSGSRSV1) && 
            !isset($xml->CREDITCARDMSGSRSV1) && !isset($xml->INVSTMTMSGSRSV1)) {
            throw new \InvalidArgumentException('Content is not valid OFX schema - missing required message sets');
        }

        $ofx = new Ofx(
            $xml,
            $this->transactionBuilder,
            $this->fieldExtractor,
            $this->metrics
        );
        $ofx->buildHeader($header);

        // Return ParsingResult if defensive parsing is enabled
        if ($this->metrics !== null) {
            return new ParsingResult($ofx, $this->metrics);
        }

        return $ofx;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getFormatName(): string
    {
        return 'sgml';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getVersion(): string
    {
        return 'v1';
    }
    
    /**
     * Convert SGML Element tree to XML string for compatibility
     * @TODO: Refactor Ofx class to work directly with SGML Elements
     * 
     * @param Sgml\Elements\Element $element
     * @return string
     */
    private function sgmlElementToXmlString(Sgml\Elements\Element $element): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= $this->buildXmlFromElement($element);
        return $xml;
    }
    
    /**
     * Recursively build XML string from SGML element
     * 
     * @param Sgml\Elements\Element $element
     * @param int $depth
     * @return string
     */
    private function buildXmlFromElement(Sgml\Elements\Element $element, int $depth = 0): string
    {
        $indent = str_repeat('  ', $depth);
        $xml = '';
        
        $tagName = $element->getTagName();
        $xml .= $indent . '<' . $tagName . '>';
        
        // Check if it's a value element (has text content)
        if ($element instanceof Sgml\Elements\ValueElement) {
            $value = $element->getValue();
            
            // Handle DateTime objects
            if ($value instanceof \DateTimeInterface) {
                $value = $value->format('YmdHis');
            }
            
            // Escape XML special characters (cast to string to handle numeric values)
            $value = htmlspecialchars((string)$value, ENT_XML1, 'UTF-8');
            $xml .= $value . '</' . $tagName . '>' . "\n";
        } 
        // Check if it's a container element (has children)
        elseif ($element instanceof Sgml\Elements\ContainerElement) {
            $xml .= "\n";
            foreach ($element->getChildren() as $child) {
                $xml .= $this->buildXmlFromElement($child, $depth + 1);
            }
            $xml .= $indent . '</' . $tagName . '>' . "\n";
        }
        else {
            // Unknown or empty element
            $xml .= '</' . $tagName . '>' . "\n";
        }
        
        return $xml;
    }
    
    /**
     * Load an XML string without PHP errors - throws exception instead
     *
     * @param string $xmlString
     * @throws \RuntimeException
     * @return \SimpleXMLElement
     */
    private function xmlLoadString(string $xmlString): \SimpleXMLElement
    {
        libxml_clear_errors();
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlString);

        if ($errors = libxml_get_errors()) {
            throw new \RuntimeException('Failed to parse converted XML: ' . var_export($errors, true));
        }

        return $xml;
    }
    
    /**
     * Parse the SGML header to an array
     *
     * @param string $ofxHeader
     * @return array
     */
    private function parseHeader(string $ofxHeader): array
    {
        $header = [];
        
        $lines = explode("\n", $ofxHeader);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $header[$parts[0]] = $parts[1];
            }
        }
        
        return $header;
    }
}
