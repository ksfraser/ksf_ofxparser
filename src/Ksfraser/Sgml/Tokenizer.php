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
 *
 * @non-standard RBC International: Single-line SGML format
 *     Bank: Royal Bank of Canada (International Card)
 *     Issue: Entire OFX payload on single line with no formatting
 *     Workaround: Character-by-character tokenization ignores line breaks
 *
 * @non-standard Presidents Choice/Presco: Unclosed SGML tags
 *     Bank: Presidents Choice Mastercard
 *     Issue: Missing closing tags for efficiency
 *     Workaround: Tokenizer tolerates missing > terminators
 */
class Tokenizer
{
    private string $content;
    private int $position = 0;
    private int $length;
    private int $line = 1;
    private int $column = 1;

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
     *
     * @non-standard Unclosed tag handling for Presidents Choice/Presco
     *     Skip to '>' or end of line - Banks may omit closing > for wire efficiency
     *     This allows tags like: <SEVERITY>INFO (without closing >)
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
