<?php

namespace OfxParser\Sgml;

use OfxParser\Sgml\Elements\Element;
use OfxParser\Sgml\Elements\ContainerElement;

/**
 * SGML Parser - builds tree structure from SGML content
 * 
 * Handles:
 * - Properly nested elements
 * - Unclosed tags (auto-closes based on context)
 * - Value elements vs container elements
 * - Unknown tags (forward compatibility)
 */
class Parser
{
    /**
     * @var Tokenizer
     */
    private $tokenizer;
    
    /**
     * @var ElementFactory
     */
    private $factory;
    
    /**
     * @var array
     */
    private $elementStack = [];
    
    /**
     * @var array
     */
    private $errors = [];

    public function __construct(?ElementFactory $factory = null)
    {
        $this->factory = $factory ?? new ElementFactory();
    }

    /**
     * Parse SGML content into element tree
     */
    public function parse(string $sgmlContent): Element
    {
        $this->tokenizer = new Tokenizer($sgmlContent);
        $this->elementStack = [];
        $this->errors = [];

        // Expect opening <OFX> tag
        $root = $this->parseElement();

        if ($root->getTagName() !== 'OFX') {
            $this->errors[] = "Root element must be OFX, found: " . $root->getTagName();
        }

        return $root;
    }

    /**
     * Parse a single element and its contents
     */
    private function parseElement(): ?Element
    {
        $token = $this->tokenizer->nextToken();

        if (!$token->isOpenTag()) {
            return null;
        }

        // Create element based on tag name
        $element = $this->factory->createElement(
            $token->value,
            $token->line,
            $token->column
        );

        // Push onto stack
        $this->elementStack[] = $element;

        // Parse content based on element type
        if ($element->canHaveChildren()) {
            $this->parseChildren($element);
        } else {
            $this->parseTextValue($element);
        }

        // Pop from stack
        array_pop($this->elementStack);

        return $element;
    }

    /**
     * Parse child elements for a container
     */
    private function parseChildren(Element $parent): void
    {
        // Check if next token is text (for hybrid elements like CURRENCY)
        // Hybrid elements can contain either text OR children, not both
        $firstToken = $this->tokenizer->peekToken();
        if ($firstToken->isText()) {
            // This element has text content, not children
            $this->tokenizer->nextToken(); // Consume text
            $parent->setTextValue($firstToken->value);
            
            // Skip to closing tag
            $closeToken = $this->tokenizer->peekToken();
            if ($closeToken->isCloseTag() && $closeToken->value === $parent->getTagName()) {
                $this->tokenizer->nextToken(); // Consume close tag
            }
            return;
        }
        
        while (true) {
            $token = $this->tokenizer->peekToken();

            if ($token->isEof()) {
                // End of document - auto-close all open tags
                break;
            }

            if ($token->isCloseTag()) {
                // Check if this closes the current element
                if ($token->value === $parent->getTagName()) {
                    $this->tokenizer->nextToken(); // Consume close tag
                    break;
                }

                // Check if this closes a parent element (auto-close current)
                if ($this->closesParentElement($token->value)) {
                    // Don't consume the token - let parent handle it
                    break;
                }

                // Unexpected close tag - skip it
                $this->errors[] = "Unexpected close tag </{$token->value}> at line {$token->line}";
                $this->tokenizer->nextToken(); // Consume and skip
                continue;
            }

            if ($token->isOpenTag()) {
                // Check if this tag should auto-close the current element
                if ($this->shouldAutoClose($parent, $token->value)) {
                    // Don't consume token - let parent handle it
                    break;
                }

                // Parse child element
                $child = $this->parseElement();
                if ($child) {
                    $parent->addChild($child);
                }
                continue;
            }

            // Unexpected token
            $this->tokenizer->nextToken(); // Consume and skip
        }
    }

    /**
     * Parse text value for a value element
     */
    private function parseTextValue(Element $element): void
    {
        $token = $this->tokenizer->peekToken();

        if ($token->isText()) {
            $this->tokenizer->nextToken(); // Consume text
            $element->setTextValue($token->value);
        }

        // Check for closing tag or next opening tag (auto-close)
        $nextToken = $this->tokenizer->peekToken();
        
        if ($nextToken->isCloseTag() && $nextToken->value === $element->getTagName()) {
            $this->tokenizer->nextToken(); // Consume close tag
        }
        // Otherwise auto-close (next tag or EOF)
    }

    /**
     * Check if close tag closes a parent element in the stack
     */
    private function closesParentElement(string $tagName): bool
    {
        foreach ($this->elementStack as $element) {
            if ($element->getTagName() === $tagName) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if new opening tag should auto-close current element
     * 
     * SGML rules:
     * - Value element is auto-closed by any opening tag
     * - Container element is auto-closed by sibling tag at same level
     */
    private function shouldAutoClose(Element $current, string $newTagName): bool
    {
        // Value elements are always auto-closed by opening tags
        if (!$current->canHaveChildren()) {
            return true;
        }

        // Container auto-closes when:
        // 1. New tag is same as current (sibling at same level)
        if ($newTagName === $current->getTagName()) {
            return true;
        }

        // 2. New tag would be a sibling of parent (we're done with children)
        if (count($this->elementStack) > 1) {
            $parent = $this->elementStack[count($this->elementStack) - 2];
            
            // Check if new tag is a known sibling of current element
            // This requires some domain knowledge of OFX structure
            if ($this->areSiblings($current->getTagName(), $newTagName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if two tags are likely siblings in OFX structure
     */
    private function areSiblings(string $tag1, string $tag2): bool
    {
        // Common sibling groups in OFX
        $siblingGroups = [
            ['BANKMSGSRSV1', 'CREDITCARDMSGSRSV1', 'INVSTMTMSGSRSV1', 'SIGNONMSGSRSV1'],
            ['LEDGERBAL', 'AVAILBAL'],
            ['DTSTART', 'DTEND'],
            ['BANKACCTFROM', 'BANKACCTTO'],
            ['CODE', 'SEVERITY', 'MESSAGE'],
        ];

        foreach ($siblingGroups as $group) {
            if (in_array($tag1, $group) && in_array($tag2, $group)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get parsing errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if there were parsing errors
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get element factory
     */
    public function getFactory(): ElementFactory
    {
        return $this->factory;
    }
}
