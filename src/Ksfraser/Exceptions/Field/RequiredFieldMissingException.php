<?php declare(strict_types=1);

namespace OfxParser\Exceptions\Field;

use OfxParser\Exceptions\OfxParsingException;

/**
 * Exception thrown when a required OFX field is missing
 */
class RequiredFieldMissingException extends OfxParsingException
{
    private string $fieldName;
    
    public function __construct(string $fieldName, string $message = "", ?\Throwable $previous = null)
    {
        $this->fieldName = $fieldName;
        $finalMessage = $message ?: "Required field '{$fieldName}' is missing";
        parent::__construct($finalMessage, 0, $previous, ['field' => $fieldName]);
    }
    
    public function getFieldName(): string
    {
        return $this->fieldName;
    }
}
