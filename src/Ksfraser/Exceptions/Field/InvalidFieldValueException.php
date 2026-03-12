<?php declare(strict_types=1);

namespace OfxParser\Exceptions\Field;

use OfxParser\Exceptions\OfxParsingException;

/**
 * Exception thrown when a field value is invalid (e.g., out of range, wrong type)
 */
class InvalidFieldValueException extends OfxParsingException
{
    /**
     * @var string
     */
    private $fieldName;
    
    /**
     * @var mixed
     */
    private $invalidValue;
    
    /**
     * @var string
     */
    private $reason;
    
    public function __construct(
        string $fieldName,
        $invalidValue,
        string $reason = '',
        string $message = "",
        ?\Throwable $previous = null
    ) {
        $this->fieldName = $fieldName;
        $this->invalidValue = $invalidValue;
        $this->reason = $reason;
        
        $valueStr = is_scalar($invalidValue) ? (string)$invalidValue : gettype($invalidValue);
        $finalMessage = $message ?: "Field '{$fieldName}' has invalid value: '{$valueStr}'" . 
            ($reason ? " - {$reason}" : "");
        
        parent::__construct($finalMessage, 0, $previous, [
            'field' => $fieldName,
            'value' => $invalidValue,
            'reason' => $reason
        ]);
    }
    
    public function getFieldName(): string
    {
        return $this->fieldName;
    }
    
    public function getInvalidValue()
    {
        return $this->invalidValue;
    }
    
    public function getReason(): string
    {
        return $this->reason;
    }
}
