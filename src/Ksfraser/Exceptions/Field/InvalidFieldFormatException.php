<?php declare(strict_types=1);

namespace OfxParser\Exceptions\Field;

use OfxParser\Exceptions\OfxParsingException;

/**
 * Exception thrown when a field has invalid format (e.g., bad date format)
 */
class InvalidFieldFormatException extends OfxParsingException
{
    /**
     * @var string
     */
    private $fieldName;
    
    /**
     * @var string
     */
    private $invalidValue;
    
    /**
     * @var string
     */
    private $expectedFormat;
    
    public function __construct(
        string $fieldName,
        string $invalidValue,
        string $expectedFormat = '',
        string $message = "",
        ?\Throwable $previous = null
    ) {
        $this->fieldName = $fieldName;
        $this->invalidValue = $invalidValue;
        $this->expectedFormat = $expectedFormat;
        
        $finalMessage = $message ?: "Field '{$fieldName}' has invalid format. Value: '{$invalidValue}'" . 
            ($expectedFormat ? ", Expected: {$expectedFormat}" : "");
        
        parent::__construct($finalMessage, 0, $previous, [
            'field' => $fieldName,
            'value' => $invalidValue,
            'expected_format' => $expectedFormat
        ]);
    }
    
    public function getFieldName(): string
    {
        return $this->fieldName;
    }
    
    public function getInvalidValue(): string
    {
        return $this->invalidValue;
    }
    
    public function getExpectedFormat(): string
    {
        return $this->expectedFormat;
    }
}
