<?php declare(strict_types=1);

namespace OfxParser\Loaders;

use OfxParser\Ofx;
use OfxParser\Metrics\ParsingResult;
use OfxParser\Builder\TransactionBuilder;
use OfxParser\Extraction\FieldExtractor;
use OfxParser\Metrics\ParsingMetrics;

/**
 * XML-based OFX loader (OFX v2+)
 * 
 * Handles OFX files in XML format using SimpleXML
 */
class XmlOfxLoader implements OfxLoaderInterface
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
        // XML files have <?xml declaration in header
        return stripos($ofxHeader, '<?xml') === 0;
    }
    
    /**
     * {@inheritdoc}
     */
    public function load(string $ofxHeader, string $ofxBody)
    {
        $header = $this->parseHeader($ofxHeader);
        
        // Load XML directly with SimpleXML
        $xml = $this->xmlLoadString($ofxBody);

        if (empty($xml) || is_null($xml)) {
            throw new \InvalidArgumentException('Content is not valid OFX XML schema');
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
        return 'xml';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getVersion(): string
    {
        return 'v2+';
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
            throw new \RuntimeException('Failed to parse OFX XML: ' . var_export($errors, true));
        }

        return $xml;
    }
    
    /**
     * Parse the OFX header to an array
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
            if (empty($line) || strpos($line, '<?xml') === 0) {
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
