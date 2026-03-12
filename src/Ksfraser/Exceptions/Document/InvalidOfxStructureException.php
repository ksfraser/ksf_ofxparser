<?php declare(strict_types=1);

namespace OfxParser\Exceptions\Document;

use OfxParser\Exceptions\OfxParsingException;

/**
 * Exception thrown when OFX document structure is invalid
 */
class InvalidOfxStructureException extends OfxParsingException
{
    /**
     * @var string
     */
    private $structureIssue;
    
    public function __construct(
        string $structureIssue,
        string $message = "",
        ?\Throwable $previous = null
    ) {
        $this->structureIssue = $structureIssue;
        $finalMessage = $message ?: "Invalid OFX structure: {$structureIssue}";
        parent::__construct($finalMessage, 0, $previous, ['issue' => $structureIssue]);
    }
    
    public function getStructureIssue(): string
    {
        return $this->structureIssue;
    }
}
