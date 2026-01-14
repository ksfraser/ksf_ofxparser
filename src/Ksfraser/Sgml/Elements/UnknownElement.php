<?php

namespace OfxParser\Sgml\Elements;

/**
 * Represents an unknown/undefined SGML element
 * Allows forward compatibility with new OFX tags
 */
class UnknownElement extends Element
{
    /**
     * Unknown elements can have children (we don't know the schema)
     */
    public function canHaveChildren(): bool
    {
        return true;
    }

    /**
     * Unknown elements don't have validation rules
     */
    public function validate(): array
    {
        // No validation errors - we accept anything
        return [];
    }

    /**
     * Get value - if has text, return it; if has children, return null
     */
    public function getValue()
    {
        if (!empty($this->children)) {
            return null; // Container element
        }
        return $this->textValue;
    }
}
