<?php

namespace OfxParser\Sgml\Elements;

/**
 * Represents a CURRENCY element - handles OFX's hybrid format
 * 
 * OFX Specification allows CURRENCY in two formats:
 * - Simple value: <CURRENCY>USD (commonly used in SECINFO)
 * - Container with components: <CURRENCY><CURSYM>USD</CURSYM><CURRATE>1.25</CURRATE></CURRENCY>
 * 
 * Single Responsibility: Parse and provide access to currency information
 * regardless of which OFX format is used.
 */
class CurrencyElement extends Element
{
    /** @var string|null */
    private $currencyCode = null;
    /** @var float|null */
    private $exchangeRate = null;

    /**
     * Currency can have children (CURSYM, CURRATE) in container format
     */
    public function canHaveChildren(): bool
    {
        return true;
    }

    /**
     * Validate currency element structure
     */
    public function validate(): array
    {
        $errors = [];

        // If has children, they must be CURSYM/CURRATE
        if (!empty($this->children)) {
            foreach ($this->children as $child) {
                $tag = $child->getTagName();
                if ($tag !== 'CURSYM' && $tag !== 'CURRATE') {
                    $errors[] = "CURRENCY container can only contain CURSYM and CURRATE, found: {$tag}";
                }
            }
            
            // If container format, should not have text value
            if ($this->textValue !== null && trim($this->textValue) !== '') {
                $errors[] = "CURRENCY container format should not have direct text content";
            }
        }

        return $errors;
    }

    /**
     * Get currency code (e.g., "USD", "CAD", "EUR")
     * Works for both formats
     */
    public function getCurrencyCode(): ?string
    {
        // Check if we've already extracted it
        if ($this->currencyCode !== null) {
            return $this->currencyCode;
        }

        // Container format: look for CURSYM child
        if (!empty($this->children)) {
            $cursymChild = $this->getFirstChild('CURSYM');
            if ($cursymChild) {
                $this->currencyCode = $cursymChild->getValue();
                return $this->currencyCode;
            }
        }

        // Simple value format: use text value
        // Use getTextValue() accessor instead of direct property access
        $text = $this->getTextValue();
        if ($text !== null && trim($text) !== '') {
            $this->currencyCode = trim($text);
            return $this->currencyCode;
        }

        return null;
    }

    /**
     * Get exchange rate (only available in container format)
     */
    public function getExchangeRate(): ?float
    {
        // Check if we've already extracted it
        if ($this->exchangeRate !== null) {
            return $this->exchangeRate;
        }

        // Only available in container format
        if (!empty($this->children)) {
            $currateChild = $this->getFirstChild('CURRATE');
            if ($currateChild) {
                $rate = $currateChild->getValue();
                $this->exchangeRate = $rate !== null ? (float)$rate : null;
                return $this->exchangeRate;
            }
        }

        return null;
    }

    /**
     * Get value - returns currency code for compatibility with getValue() pattern
     */
    public function getValue()
    {
        return $this->getCurrencyCode();
    }

    /**
     * Check if this is container format (has CURSYM/CURRATE children)
     */
    public function isContainerFormat(): bool
    {
        return !empty($this->children);
    }

    /**
     * Check if this is simple value format (just text)
     */
    public function isSimpleFormat(): bool
    {
        return empty($this->children) && $this->textValue !== null;
    }
}
