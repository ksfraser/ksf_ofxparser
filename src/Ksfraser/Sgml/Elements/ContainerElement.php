<?php

namespace OfxParser\Sgml\Elements;

/**
 * Element that contains child elements but no text value
 * Examples: OFX, BANKMSGSRSV1, BANKTRANLIST, STMTTRN
 */
class ContainerElement extends Element
{
    /**
     * Container elements can have children
     */
    public function canHaveChildren(): bool
    {
        return true;
    }

    /**
     * Minimal validation - just check structure
     */
    public function validate(): array
    {
        $errors = [];

        // Container elements shouldn't have text values
        if ($this->textValue !== null && trim($this->textValue) !== '') {
            $errors[] = "Container element '{$this->tagName}' should not have text content";
        }

        return $errors;
    }

    /**
     * Containers don't have values, only children
     */
    public function getValue()
    {
        return null;
    }
}
