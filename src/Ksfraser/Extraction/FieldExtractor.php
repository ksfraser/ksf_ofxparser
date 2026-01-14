<?php declare(strict_types=1);

namespace OfxParser\Extraction;

use OfxParser\Exceptions\Field\RequiredFieldMissingException;
use OfxParser\Exceptions\Field\OptionalFieldMissingException;
use OfxParser\Exceptions\Field\InvalidFieldFormatException;
use OfxParser\Recovery\RecoveryContext;
use OfxParser\Metrics\ParsingMetrics;
use OfxParser\Utils;

/**
 * Defensive field extractor with required/optional distinction
 */
class FieldExtractor
{
    private RecoveryContext $recoveryContext;
    private ParsingMetrics $metrics;
    
    public function __construct(RecoveryContext $recoveryContext, ParsingMetrics $metrics)
    {
        $this->recoveryContext = $recoveryContext;
        $this->metrics = $metrics;
    }
    
    /**
     * Extract a required field - throws if missing
     *
     * @param \SimpleXMLElement $element
     * @param string $fieldName
     * @return mixed
     * @throws RequiredFieldMissingException
     */
    public function extractRequired(\SimpleXMLElement $element, string $fieldName)
    {
        if (!isset($element->$fieldName)) {
            $this->metrics->incrementMissingRequiredField($fieldName);
            
            $exception = new RequiredFieldMissingException(
                "Required field '{$fieldName}' is missing",
                ['field_name' => $fieldName]
            );
            
            // Allow recovery context to handle if strategy configured
            if ($this->recoveryContext->canRecover($exception)) {
                $this->metrics->incrementFieldRecovery($fieldName);
                return $this->recoveryContext->recover($exception, $element, $fieldName);
            }
            
            throw $exception;
        }
        
        $value = (string) $element->$fieldName;
        
        // Empty string for required field is problematic
        if ($value === '') {
            $this->metrics->incrementMissingRequiredField($fieldName);
            
            $exception = new RequiredFieldMissingException(
                "Required field '{$fieldName}' is empty",
                ['field_name' => $fieldName]
            );
            
            if ($this->recoveryContext->canRecover($exception)) {
                $this->metrics->incrementFieldRecovery($fieldName);
                return $this->recoveryContext->recover($exception, $element, $fieldName);
            }
            
            throw $exception;
        }
        
        return $value;
    }
    
    /**
     * Extract an optional field - uses recovery strategy if missing
     *
     * @param \SimpleXMLElement $element
     * @param string $fieldName
     * @param mixed $defaultValue Default if no recovery strategy
     * @return mixed
     */
    public function extractOptional(\SimpleXMLElement $element, string $fieldName, $defaultValue = null)
    {
        if (!isset($element->$fieldName)) {
            $this->metrics->incrementMissingOptionalField($fieldName);
            
            $exception = new OptionalFieldMissingException($fieldName);
            
            // Try recovery strategy first
            if ($this->recoveryContext->canRecover($exception)) {
                $this->metrics->incrementFieldRecovery($fieldName);
                return $this->recoveryContext->recover($exception, $element, $fieldName);
            }
            
            // Fall back to default
            return $defaultValue;
        }
        
        $value = (string) $element->$fieldName;
        
        // Empty optional field - use recovery or default
        if ($value === '') {
            $this->metrics->incrementMissingOptionalField($fieldName);
            
            $exception = new OptionalFieldMissingException(
                "Optional field '{$fieldName}' is empty",
                ['field_name' => $fieldName]
            );
            
            if ($this->recoveryContext->canRecover($exception)) {
                $this->metrics->incrementFieldRecovery($fieldName);
                return $this->recoveryContext->recover($exception, $element, $fieldName);
            }
            
            return $defaultValue;
        }
        
        return $value;
    }
    
    /**
     * Extract and parse date field (required)
     *
     * @param \SimpleXMLElement $element
     * @param string $fieldName
     * @return \DateTime
     * @throws RequiredFieldMissingException
     * @throws InvalidFieldFormatException
     */
    public function extractRequiredDate(\SimpleXMLElement $element, string $fieldName): \DateTime
    {
        $value = $this->extractRequired($element, $fieldName);
        
        try {
            $date = Utils::createDateTimeFromStr($value, false);
            if ($date === null) {
                throw new \Exception("Date parsing returned null");
            }
            return $date;
        } catch (\Exception $e) {
            $exception = new InvalidFieldFormatException(
                "Invalid date format for field '{$fieldName}': {$value}",
                [
                    'field_name' => $fieldName,
                    'value' => $value,
                    'error' => $e->getMessage()
                ]
            );
            
            if ($this->recoveryContext->canRecover($exception)) {
                $this->metrics->incrementFieldRecovery($fieldName);
                return $this->recoveryContext->recover($exception, $element, $fieldName);
            }
            
            throw $exception;
        }
    }
    
    /**
     * Extract and parse date field (optional)
     *
     * @param \SimpleXMLElement $element
     * @param string $fieldName
     * @param \DateTime|null $defaultValue
     * @return \DateTime|null
     */
    public function extractOptionalDate(\SimpleXMLElement $element, string $fieldName, ?\DateTime $defaultValue = null): ?\DateTime
    {
        $value = $this->extractOptional($element, $fieldName, null);
        
        if ($value === null) {
            return $defaultValue;
        }
        
        try {
            return Utils::createDateTimeFromStr($value, true);
        } catch (\Exception $e) {
            $exception = new InvalidFieldFormatException(
                "Invalid date format for optional field '{$fieldName}': {$value}",
                [
                    'field_name' => $fieldName,
                    'value' => $value,
                    'error' => $e->getMessage()
                ]
            );
            
            if ($this->recoveryContext->canRecover($exception)) {
                $this->metrics->incrementFieldRecovery($fieldName);
                return $this->recoveryContext->recover($exception, $element, $fieldName);
            }
            
            return $defaultValue;
        }
    }
    
    /**
     * Extract and parse amount field (required)
     *
     * @param \SimpleXMLElement $element
     * @param string $fieldName
     * @return float
     * @throws RequiredFieldMissingException
     */
    public function extractRequiredAmount(\SimpleXMLElement $element, string $fieldName): float
    {
        $value = $this->extractRequired($element, $fieldName);
        return Utils::createAmountFromStr($value);
    }
    
    /**
     * Extract and parse amount field (optional)
     *
     * @param \SimpleXMLElement $element
     * @param string $fieldName
     * @param float|null $defaultValue
     * @return float|null
     */
    public function extractOptionalAmount(\SimpleXMLElement $element, string $fieldName, ?float $defaultValue = null): ?float
    {
        $value = $this->extractOptional($element, $fieldName, null);
        
        if ($value === null) {
            return $defaultValue;
        }
        
        return Utils::createAmountFromStr($value);
    }
    
    /**
     * Parse OFX date format to DateTime
     * OFX dates: YYYYMMDDHHMMSS[.XXX][TZ]
     *
     * @param string $dateString
     * @return \DateTime
     * @throws \Exception
     */
    private function parseOfxDate(string $dateString): \DateTime
    {
        // Remove timezone suffix if present
        $dateString = preg_replace('/\[.*\]$/', '', $dateString);
        
        // Handle fractional seconds
        if (strpos($dateString, '.') !== false) {
            $dateString = substr($dateString, 0, strpos($dateString, '.'));
        }
        
        // Pad to at least YYYYMMDD
        $dateString = str_pad($dateString, 8, '0');
        
        // Try various formats
        $formats = [
            'YmdHis',  // YYYYMMDDHHMMSS
            'YmdHi',   // YYYYMMDDHHMM
            'YmdH',    // YYYYMMDDHH
            'Ymd',     // YYYYMMDD
        ];
        
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, substr($dateString, 0, strlen($format)));
            if ($date !== false) {
                return $date;
            }
        }
        
        throw new \Exception("Unable to parse date: {$dateString}");
    }
}
