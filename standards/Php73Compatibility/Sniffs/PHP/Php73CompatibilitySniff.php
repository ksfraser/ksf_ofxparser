<?php

namespace PHP_CodeSniffer\Standards\Php73Compatibility\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Sniff to ensure code maintains PHP 7.3 compatibility
 * Detects PHP 7.4+ and PHP 8.0+ features that should not be used
 */
class Php73CompatibilitySniff implements Sniff
{
    /**
     * @return int[]
     */
    public function register(): array
    {
        return [
            T_PROPERTY,
            T_FUNCTION,
            T_MATCH,
            T_FN,
            T_QUESTION_QUESTION_EQUAL,
        ];
    }

    /**
     * @param File $phpcsFile
     * @param int $stackPtr
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $token = $tokens[$stackPtr];

        // Check for typed properties (PHP 7.4+)
        if ($token['code'] === T_PROPERTY) {
            $this->checkTypedProperty($phpcsFile, $stackPtr);
        }

        // Check for arrow functions (PHP 7.4+)
        if ($token['code'] === T_FN) {
            $error = 'Arrow function syntax (fn) is not compatible with PHP 7.3. Use regular anonymous functions instead.';
            $phpcsFile->addError($error, $stackPtr, 'Php74ArrowFunction');
        }

        // Check for match expressions (PHP 8.0+)
        if ($token['code'] === T_MATCH) {
            $error = 'Match expression is not compatible with PHP 7.3. Use switch statement instead.';
            $phpcsFile->addError($error, $stackPtr, 'Php80MatchExpression');
        }

        // Check for null coalescing assignment operator (PHP 7.4+)
        if ($token['code'] === T_QUESTION_QUESTION_EQUAL) {
            $error = 'Null coalescing assignment operator (??=) is not compatible with PHP 7.3. Use $x = $x ?? $y; instead.';
            $phpcsFile->addError($error, $stackPtr, 'Php74NullCoalescingAssignment');
        }

        // Check for union types in function declarations (PHP 8.0+)
        if ($token['code'] === T_FUNCTION) {
            $this->checkUnionTypes($phpcsFile, $stackPtr);
        }
    }

    private function checkTypedProperty(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        
        // Look for type hint before the property
        $prevNonEmpty = $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, null, true);
        
        if ($prevNonEmpty === false) {
            return;
        }

        $prevToken = $tokens[$prevNonEmpty];
        
        // Check for type keywords before property
        $typeTokens = [
            T_STRING,
            T_ARRAY,
            T_CALLABLE,
            T_BOOL,
            T_INT,
            T_FLOAT,
            T_STRING_CAST,
            T_INT_CAST,
            T_FLOAT_CAST,
            T_BOOL_CAST,
            T_ARRAY_CAST,
        ];

        if (in_array($prevToken['code'], $typeTokens, true)) {
            $error = 'Typed property "%s" is not compatible with PHP 7.3. Remove the type hint and document it in a comment.';
            
            // Find the property name
            $varPtr = $phpcsFile->findNext(T_VARIABLE, $stackPtr);
            if ($varPtr !== false) {
                $varName = $tokens[$varPtr]['content'];
                $phpcsFile->addError($error, $stackPtr, 'Php74TypedProperty', [$varName]);
            }
        }
    }

    private function checkUnionTypes(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        
        // Find opening parenthesis
        $openParenPtr = $phpcsFile->findNext(T_OPEN_PARENTHESIS, $stackPtr);
        if ($openParenPtr === false) {
            return;
        }

        // Find closing parenthesis
        $closeParenPtr = $tokens[$openParenPtr]['parenthesis_closer'];
        
        // Look for pipe character (union operator) in parameter list
        for ($i = $openParenPtr; $i < $closeParenPtr; $i++) {
            if ($tokens[$i]['type'] === T_BITWISE_OR || $tokens[$i]['code'] === T_PIPE) {
                // Check if this is a union type (not a bitwise or in default value)
                $prevNonEmpty = $phpcsFile->findPrevious(T_WHITESPACE, $i - 1, null, true);
                if ($prevNonEmpty !== false && in_array($tokens[$prevNonEmpty]['code'], [T_STRING, T_ARRAY], true)) {
                    $error = 'Union type syntax (using |) is not compatible with PHP 7.3. Use separate parameters or PHPDoc @param tags instead.';
                    $phpcsFile->addError($error, $i, 'Php80UnionType');
                }
            }
        }

        // Also check the return type  
        $bodyStart = $phpcsFile->findNext([T_OPEN_CURLY_BRACKET, T_SEMICOLON], $closeParenPtr);
        if ($bodyStart === false) {
            return;
        }

        for ($i = $closeParenPtr; $i < $bodyStart; $i++) {
            if ($tokens[$i]['type'] === T_BITWISE_OR || $tokens[$i]['code'] === T_PIPE) {
                $error = 'Union type syntax (using |) is not compatible with PHP 7.3. Use PHPDoc @return tag instead.';
                $phpcsFile->addError($error, $i, 'Php80UnionTypeReturn');
            }
        }
    }
}
