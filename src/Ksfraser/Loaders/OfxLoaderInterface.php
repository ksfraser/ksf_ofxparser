<?php declare(strict_types=1);

namespace OfxParser\Loaders;

use OfxParser\Ofx;
use OfxParser\Metrics\ParsingResult;

/**
 * Interface for OFX content loaders
 * 
 * Supports both XML (OFX v2+) and SGML (OFX v1) formats
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
     * @param string $ofxHeader The OFX header section
     * @param string $ofxBody The OFX body content (starting with <OFX>)
     * @return Ofx|ParsingResult
     * @throws \Exception
     */
    public function load(string $ofxHeader, string $ofxBody);
    
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
