<?php

namespace OfxParser\Sgml;

/**
 * Tokenizes SGML content into a stream of tokens
 * 
 * Handles:
 * - Opening tags: <TAGNAME>
 * - Closing tags: </TAGNAME>
 * - Text content between tags
 * - Malformed tags (missing >)
 */
class Tokenizer
{
    /**
     * @var string
     */
    private $content;
    
    /**
     * @var int
     */
    private $position = 0;
    
    /**
     * @var int
     */
    private $length;
    
    /**
     * @var int
     */
    private $line = 1;
    
    /**
     * @var int
     */
    private $column = 1;

    public function __construct(string $content)
    {
        $this->content = $content;
        $this->length = strlen($content);
    }

    /**
     * Get next token from stream
     */
    public function nextToken(): Token
    {
        $this->skipWhitespace();

        if ($this->position >= $this->length) {
            return new Token(Token::TYPE_EOF, '', $this->line, $this->column);
        }

        $char = $this->content[$this->position];

        if ($char === '<') {
            return $this->readTag();
        }

        return $this->readText();
    }

    /**
     * Peek at next token without consuming it
     */
    public function peekToken(): Token
    {
        $savedPos = $this->position;
        $savedLine = $this->line;
        $savedColumn = $this->column;

        $token = $this->nextToken();

        $this->position = $savedPos;
        $this->line = $savedLine;
        $this->column = $savedColumn;

        return $token;
    }

    /**
     * Read opening or closing tag
     */
    private function readTag(): Token
    {
        $line = $this->line;
        $column = $this->column;

        $this->advance(); // Skip '<'

        // Check for closing tag
        $isClosing = false;
        if ($this->peek() === '/') {
            $isClosing = true;
            $this->advance(); // Skip '/'
        }

        // Read tag name
        $tagName = '';
        while ($this->position < $this->length) {
            $char = $this->peek();
            
            // Tag ends at '>' or whitespace or another '<'
            if ($char === '>' || $char === '<' || ctype_space($char)) {
                break;
            }
            
            $tagName .= $char;
            $this->advance();
        }

        // Skip to '>' or end of line (SGML can have unclosed tags)
        while ($this->position < $this->length) {
            $char = $this->peek();
            if ($char === '>' || $char === "\n" || $char === '<') {
                break;
            }
            $this->advance();
        }

        // Consume '>' if present
        if ($this->peek() === '>') {
            $this->advance();
        }

        $type = $isClosing ? Token::TYPE_CLOSE_TAG : Token::TYPE_OPEN_TAG;
        return new Token($type, $tagName, $line, $column);
    }

    /**
     * Read text content until next tag
     */
    private function readText(): Token
    {
        $line = $this->line;
        $column = $this->column;
        $text = '';

        while ($this->position < $this->length) {
            $char = $this->peek();
            
            if ($char === '<') {
                break;
            }
            
            $text .= $char;
            $this->advance();
        }

        // Trim whitespace from text content
        $text = trim($text);

        return new Token(Token::TYPE_TEXT, $text, $line, $column);
    }

    /**
     * Skip whitespace between tags
     */
    private function skipWhitespace(): void
    {
        while ($this->position < $this->length) {
            $char = $this->peek();
            
            if (!ctype_space($char)) {
                break;
            }
            
            $this->advance();
        }
    }

    /**
     * Peek at current character without advancing
     */
    private function peek(): string
    {
        if ($this->position >= $this->length) {
            return '';
        }
        return $this->content[$this->position];
    }

    /**
     * Advance position and track line/column
     */
    private function advance(): void
    {
        if ($this->position >= $this->length) {
            return;
        }

        $char = $this->content[$this->position];
        
        if ($char === "\n") {
            $this->line++;
            $this->column = 1;
        } else {
            $this->column++;
        }

        $this->position++;
    }

    /**
     * Check if at end of content
     */
    public function isEof(): bool
    {
        return $this->position >= $this->length;
    }
}
