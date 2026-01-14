<?php

namespace OfxParser\Sgml;

/**
 * Represents a single token in SGML document
 */
class Token
{
    public const TYPE_OPEN_TAG = 'open_tag';
    public const TYPE_CLOSE_TAG = 'close_tag';
    public const TYPE_TEXT = 'text';
    public const TYPE_EOF = 'eof';

    public string $type;
    public string $value;
    public int $line;
    public int $column;

    public function __construct(string $type, string $value, int $line = 0, int $column = 0)
    {
        $this->type = $type;
        $this->value = $value;
        $this->line = $line;
        $this->column = $column;
    }

    public function isOpenTag(): bool
    {
        return $this->type === self::TYPE_OPEN_TAG;
    }

    public function isCloseTag(): bool
    {
        return $this->type === self::TYPE_CLOSE_TAG;
    }

    public function isText(): bool
    {
        return $this->type === self::TYPE_TEXT;
    }

    public function isEof(): bool
    {
        return $this->type === self::TYPE_EOF;
    }
}
