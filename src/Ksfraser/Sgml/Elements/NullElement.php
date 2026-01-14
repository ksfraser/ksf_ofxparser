<?php

namespace OfxParser\Sgml\Elements;

/**
 * Represents a null/non-existent element (for SimpleXML compatibility)
 * Returns null-like values when accessed
 */
class NullElement extends Element
{
    public function __construct(string $tagName)
    {
        parent::__construct($tagName);
    }

    public function canHaveChildren(): bool
    {
        return false;
    }

    public function validate(): array
    {
        return [];
    }

    public function getValue()
    {
        return null;
    }

    public function getTextValue(): ?string
    {
        return null;
    }

    public function __toString(): string
    {
        return '';
    }

    public function __get(string $name)
    {
        // Chain null elements
        return new NullElement($name);
    }
}
