<?php declare(strict_types=1);

namespace OfxParser\Exceptions\Document;

use OfxParser\Exceptions\OfxParsingException;

/**
 * Exception thrown when XML is malformed
 */
class MalformedXmlException extends OfxParsingException
{
    private array $xmlErrors;
    
    public function __construct(
        array $xmlErrors = [],
        string $message = "",
        ?\Throwable $previous = null
    ) {
        $this->xmlErrors = $xmlErrors;
        $finalMessage = $message ?: "Malformed XML in OFX document";
        parent::__construct($finalMessage, 0, $previous, ['xml_errors' => $xmlErrors]);
    }
    
    public function getXmlErrors(): array
    {
        return $this->xmlErrors;
    }
}
