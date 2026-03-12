<?php declare(strict_types=1);

namespace OfxParser\Exceptions;

/**
 * Base exception for all OFX parsing errors
 */
class OfxParsingException extends \Exception
{
    /**
     * @var array
     */
    protected $context = [];
    
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }
    
    public function getContext(): array
    {
        return $this->context;
    }
    
    public function setContext(array $context): void
    {
        $this->context = $context;
    }
}
