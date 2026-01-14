<?php declare(strict_types=1);

namespace OfxParser\Loaders;

use SimpleXMLElement;
use OfxParser\Sgml\Elements\Element;

/**
 * Interface for OFX content loaders
 * 
 * Loaders handle FORMAT parsing (XML vs SGML) and return parsed elements.
 * The Parser class uses these to get elements, then instantiates the 
 * appropriate Ofx TYPE (bank statement, investment, etc.).
 */
interface OfxLoaderInterface
{
    /**
     * Check if this loader can handle the given OFX content
     * 
     * @param string $ofxHeader The OFX header section
     * @param string $ofxBody The OFX body content (starting with <OFX>)
     * @return bool
     */
    public function canHandle(string $ofxHeader, string $ofxBody): bool;
    
    /**
     * Load and parse the OFX content
     * 
     * Returns parsed element structure (not Ofx objects) to allow
     * Parser subclasses to instantiate the correct Ofx type.
     * 
     * @param string $ofxHeader The OFX header section
     * @param string $ofxBody The OFX body content (starting with <OFX>)
     * @return array{element: SimpleXMLElement|Element, header: array}
     * @throws \Exception
     */
    public function load(string $ofxHeader, string $ofxBody): array;
    
    /**
     * Get the format identifier (e.g., 'xml', 'sgml')
     * 
     * @return string
     */
    public function getFormatName(): string;
    
    /**
     * Get the OFX version this loader handles (e.g., 'v1', 'v2+')
     * 
     * @return string
     */
    public function getVersion(): string;
}
