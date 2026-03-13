<?php

namespace OfxParserTest;

use PhpParser\Error;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\Node;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Tests to ensure code maintains PHP 7.3 compatibility
 * 
 * Catches usage of features from:
 * - PHP 7.4+: Typed properties, arrow functions
 * - PHP 8.0+: Union types, match expressions, named arguments
 */
class VersionCompatibilityTest extends TestCase
{
    private Parser $parser;

    protected function setUp(): void
    {
        $factory = new ParserFactory();
        $this->parser = $factory->create(ParserFactory::PREFER_PHP7);
    }

    /**
     * Checks for typed properties (PHP 7.4+)
     * Syntax: private int $count; is not allowed, must be private $count;
     */
    public function testNoTypedPropertiesInPhp73Code(): void
    {
        $srcDir = __DIR__ . '/../src';
        $violations = [];

        foreach ($this->getPhpFiles($srcDir) as $filePath) {
            $violations = array_merge($violations, $this->checkTypedProperties($filePath));
        }

        $this->assertEmpty($violations, "Typed properties found (PHP 7.4+ feature):\n" . implode("\n", $violations));
    }

    /**
     * Checks for arrow functions (PHP 7.4+)
     * Syntax: fn($x) => $x * 2; is not allowed
     */
    public function testNoArrowFunctionsInPhp73Code(): void
    {
        $srcDir = __DIR__ . '/../src';
        $violations = [];

        foreach ($this->getPhpFiles($srcDir) as $filePath) {
            $code = file_get_contents($filePath);
            // Look for "fn(" pattern that indicates arrow function
            if (preg_match('/\bfn\s*\(/m', $code)) {
                $violations[] = "$filePath: Arrow function 'fn() => ' syntax found (PHP 7.4+)";
            }
        }

        $this->assertEmpty($violations, implode("\n", $violations));
    }

    /**
     * Checks for union types (PHP 8.0+)
     * Syntax: function foo(int|string $x) {} is not allowed
     */
    public function testNoUnionTypesInPhp73Code(): void
    {
        $srcDir = __DIR__ . '/../src';
        $violations = [];

        foreach ($this->getPhpFiles($srcDir) as $filePath) {
            $code = file_get_contents($filePath);
            
            // Match union types like int|string, int|null, string|int|bool
            // Negative lookbehind to avoid matching URL schemes
            if (preg_match('/:\s*(\w+\s*\|\s*\w+|mixed)\s*(\{|;|\))/m', $code)) {
                $violations[] = "$filePath: Union types or mixed keyword found (PHP 8.0+)";
            }
        }

        $this->assertEmpty($violations, implode("\n", $violations));
    }

    /**
     * Checks for match expressions (PHP 8.0+)
     * Syntax: match($x) { ... } is not allowed, use switch instead
     */
    public function testNoMatchExpressionsInPhp73Code(): void
    {
        $srcDir = __DIR__ . '/../src';
        $violations = [];

        foreach ($this->getPhpFiles($srcDir) as $filePath) {
            $code = file_get_contents($filePath);
            
            if (preg_match('/\bmatch\s*\(/m', $code)) {
                $violations[] = "$filePath: Match expression found (PHP 8.0+)";
            }
        }

        $this->assertEmpty($violations, implode("\n", $violations));
    }

    /**
     * Checks for named arguments (PHP 8.0+)
     * Syntax: functionName(arg: $value) is not allowed
     */
    public function testNoNamedArgumentsInPhp73Code(): void
    {
        $srcDir = __DIR__ . '/../src';
        $violations = [];

        foreach ($this->getPhpFiles($srcDir) as $filePath) {
            $code = file_get_contents($filePath);
            
            // Match identifier: $variable pattern (simplified detection)
            // This is a heuristic check and may have false positives/negatives
            if (preg_match('/\w+\s*:\s*\$\w+/', $code)) {
                // Double-check it's not in a comment, string, or array key
                $lines = explode("\n", $code);
                foreach ($lines as $lineNum => $line) {
                    // Skip comments and strings
                    if (preg_match('/\/\/|#|\*\//', $line) || strpos($line, '//') !== false) {
                        continue;
                    }
                    if (preg_match('/\w+\s*:\s*\$\w+\s*[,\)]/', $line) && !preg_match('/\s*=\s*\[/', $line)) {
                        $violations[] = "$filePath:" . ($lineNum + 1) . ": Possible named argument syntax found (PHP 8.0+)";
                    }
                }
            }
        }

        $this->assertEmpty($violations, implode("\n", $violations));
    }

    /**
     * Checks for null coalescing assignment operator (PHP 7.4+)
     * Syntax: $x ??= $y; is not allowed, use $x = $x ?? $y;
     */
    public function testNoNullCoalescingAssignmentInPhp73Code(): void
    {
        $srcDir = __DIR__ . '/../src';
        $violations = [];

        foreach ($this->getPhpFiles($srcDir) as $filePath) {
            $code = file_get_contents($filePath);
            
            if (preg_match('/\?\?=/', $code)) {
                $violations[] = "$filePath: Null coalescing assignment operator '??=' found (PHP 7.4+)";
            }
        }

        $this->assertEmpty($violations, implode("\n", $violations));
    }

    private function checkTypedProperties(string $filePath): array
    {
        $violations = [];
        
        try {
            $code = file_get_contents($filePath);
            $stmts = $this->parser->parse($code);
            
            foreach ($stmts as $stmt) {
                if ($stmt instanceof Node\Stmt\Class_) {
                    foreach ($stmt->stmts as $classStmt) {
                        if ($classStmt instanceof Node\Stmt\Property) {
                            // Check if property has a type hint (PHP 7.4+)
                            if ($classStmt->type !== null) {
                                $propName = $classStmt->props[0]->name ?? 'unknown';
                                $violations[] = "$filePath: Typed property '\${$propName}' found (PHP 7.4+)";
                            }
                        }
                    }
                }
            }
        } catch (Error $e) {
            // If parser fails, let other tests catch it
            $violations[] = "$filePath: Parse error - {$e->getMessage()}";
        }
        
        return $violations;
    }

    private function getPhpFiles(string $dir): \Iterator
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                yield $file->getRealPath();
            }
        }
    }
}
