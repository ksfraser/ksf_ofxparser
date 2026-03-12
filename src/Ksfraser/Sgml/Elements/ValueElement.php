<?php

namespace OfxParser\Sgml\Elements;

use OfxParser\Sgml\DateFormatter;

/**
 * Element that contains only text value (no child elements)
 * Examples: TRNTYPE, DTPOSTED, TRNAMT, FITID, NAME, MEMO
 */
class ValueElement extends Element
{
    /**
     * @var string
     */
    private $dataType;
    
    /**
     * @var bool
     */
    private $required;

    public function __construct(
        string $tagName,
        string $dataType = 'string',
        bool $required = false,
        int $line = 0,
        int $column = 0
    ) {
        parent::__construct($tagName, $line, $column);
        $this->dataType = $dataType;
        $this->required = $required;
    }

    /**
     * Value elements cannot have children
     */
    public function canHaveChildren(): bool
    {
        return false;
    }

    /**
     * Validate text value based on data type
     */
    public function validate(): array
    {
        $errors = [];

        // Check required
        if ($this->required && ($this->textValue === null || $this->textValue === '')) {
            $errors[] = "Required field '{$this->tagName}' is missing or empty";
        }

        // Skip further validation if empty and not required
        if ($this->textValue === null || $this->textValue === '') {
            return $errors;
        }

        // Type-specific validation
        switch ($this->dataType) {
            case 'date':
            case 'datetime':
                if (!$this->validateDateTime($this->textValue)) {
                    $errors[] = "Invalid date format in '{$this->tagName}': {$this->textValue}";
                }
                break;

            case 'amount':
            case 'float':
            case 'decimal':
                if (!$this->validateNumeric($this->textValue)) {
                    $errors[] = "Invalid numeric value in '{$this->tagName}': {$this->textValue}";
                }
                break;

            case 'int':
            case 'integer':
                if (!$this->validateInteger($this->textValue)) {
                    $errors[] = "Invalid integer value in '{$this->tagName}': {$this->textValue}";
                }
                break;
        }

        return $errors;
    }

    /**
     * Get typed value based on data type
     */
    public function getValue()
    {
        if ($this->textValue === null || $this->textValue === '') {
            return null;
        }

        switch ($this->dataType) {
            case 'date':
            case 'datetime':
                return $this->parseDateTime($this->textValue);

            case 'amount':
            case 'float':
            case 'decimal':
                return $this->parseFloat($this->textValue);

            case 'int':
            case 'integer':
                return (int) $this->textValue;

            case 'bool':
            case 'boolean':
                return $this->parseBoolean($this->textValue);

            default:
                return $this->textValue;
        }
    }

    /**
     * Validate datetime format (OFX format: YYYYMMDDHHMMSS[.XXX][offset])
     */
    private function validateDateTime(string $value): bool
    {
        return DateFormatter::isValid($value);
    }

    /**
     * Parse OFX datetime to PHP DateTime
     */
    private function parseDateTime(string $value): ?\DateTime
    {
        return DateFormatter::parseToDateTime($value);
    }

    /**
     * Validate numeric value
     */
    private function validateNumeric(string $value): bool
    {
        // OFX amounts can be: 123.45, -123.45, 123,45, etc.
        return preg_match('/^-?\d+([.,]\d+)?$/', $value) === 1;
    }

    /**
     * Parse float value (handle different decimal separators)
     */
    private function parseFloat(string $value): float
    {
        // Replace comma with period for decimal separator
        $normalized = str_replace(',', '.', $value);
        return (float) $normalized;
    }

    /**
     * Validate integer value
     */
    private function validateInteger(string $value): bool
    {
        return preg_match('/^-?\d+$/', $value) === 1;
    }

    /**
     * Parse boolean value
     */
    private function parseBoolean(string $value): bool
    {
        $lower = strtolower($value);
        return in_array($lower, ['y', 'yes', 'true', '1', 't']);
    }

    /**
     * Get data type
     */
    public function getDataType(): string
    {
        return $this->dataType;
    }

    /**
     * Check if required
     */
    public function isRequired(): bool
    {
        return $this->required;
    }
}
