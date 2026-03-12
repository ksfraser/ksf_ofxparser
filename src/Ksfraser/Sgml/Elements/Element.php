<?php

namespace OfxParser\Sgml\Elements;

/**
 * Base class for all SGML elements
 */
abstract class Element
{
    /**
     * @var string
     */
    protected $tagName;
    
    /**
     * @var Element|null
     */
    protected $parent = null;
    
    /**
     * @var array
     */
    protected $children = [];
    
    /**
     * @var string|null
     */
    protected $textValue = null;
    
    /**
     * @var int
     */
    protected $line = 0;
    
    /**
     * @var int
     */
    protected $column = 0;

    public function __construct(string $tagName, int $line = 0, int $column = 0)
    {
        $this->tagName = $tagName;
        $this->line = $line;
        $this->column = $column;
    }

    /**
     * Get tag name
     */
    public function getTagName(): string
    {
        return $this->tagName;
    }

    /**
     * Get parent element
     */
    public function getParent(): ?Element
    {
        return $this->parent;
    }

    /**
     * Set parent element
     */
    public function setParent(?Element $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * Add child element
     */
    public function addChild(Element $child): void
    {
        $this->children[] = $child;
        $child->setParent($this);
    }

    /**
     * Get all children
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * Get children with specific tag name
     */
    public function getChildrenByTag(string $tagName): array
    {
        return array_filter($this->children, function (Element $child) use ($tagName) {
            return $child->getTagName() === $tagName;
        });
    }

    /**
     * Get first child with specific tag name
     */
    public function getFirstChild(string $tagName): ?Element
    {
        $children = $this->getChildrenByTag($tagName);
        return !empty($children) ? reset($children) : null;
    }

    /**
     * Set text value
     */
    public function setTextValue(?string $value): void
    {
        $this->textValue = $value;
    }

    /**
     * Get raw text value
     */
    public function getTextValue(): ?string
    {
        return $this->textValue;
    }

    /**
     * Check if this element can contain children
     */
    abstract public function canHaveChildren(): bool;

    /**
     * Validate this element's content
     * Returns array of validation errors (empty = valid)
     */
    abstract public function validate(): array;

    /**
     * Get typed/converted value (for value elements)
     * Override in subclasses for type conversion
     */
    public function getValue()
    {
        return $this->textValue;
    }

    /**
     * Get location in source document
     */
    public function getLocation(): array
    {
        return [
            'line' => $this->line,
            'column' => $this->column,
        ];
    }

    /**
     * Magic getter for child access (SimpleXML compatibility)
     */
    public function __get(string $name)
    {
        $child = $this->getFirstChild($name);
        if ($child) {
            return $child;
        }

        // Return null-like object for non-existent children
        return new NullElement($name);
    }

    /**
     * Convert to string (returns text value)
     */
    public function __toString(): string
    {
        return $this->textValue ?? '';
    }
}
