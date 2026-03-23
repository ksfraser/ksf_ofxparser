<?php declare(strict_types=1);

namespace Tests\Builders;

/**
 * Edge case amount value generators
 * 
 * Provides systematically chosen boundary values for amount testing.
 * Each method returns an array of amounts appropriate for testing
 * that specific edge case category.
 * 
 * Usage:
 *   $amounts = EdgeCaseAmounts::positiveExtremes();
 *   // Result: ['0.01', '100.00', '1000.50', '999999999999.99']
 */
class EdgeCaseAmounts
{
    /**
     * Positive amounts at extremes
     * Tests the bounds of positive values
     * 
     * @return array<string> Positive boundary amounts
     */
    public static function positiveExtremes(): array
    {
        return [
            '0.01',              // Minimum positive (one cent)
            '100.00',            // Standard amount
            '1000.50',           // Thousands separator needed
            '999999999999.99',   // Maximum amount (near max float)
        ];
    }
    
    /**
     * Negative amounts at extremes
     * Tests the bounds of negative values
     * 
     * @return array<string> Negative boundary amounts
     */
    public static function negativeExtremes(): array
    {
        return [
            '-0.01',             // Minimum negative (negative one cent)
            '-100.00',           // Standard negative
            '-1500.50',          // Negative with thousands
            '-999999999999.99',  // Maximum negative (near minimum float)
        ];
    }
    
    /**
     * Zero representations
     * Tests various ways zero can be represented
     * 
     * @return array<string> Zero variants
     */
    public static function zeroVariants(): array
    {
        return [
            '0',      // Zero as integer
            '0.00',   // Zero as decimal
            '-0.00',  // Negative zero (edge case)
        ];
    }
    
    /**
     * Precision edge cases
     * Tests decimal precision handling
     * 
     * @return array<string> Amounts with precision edge cases
     */
    public static function precisionEdgeCases(): array
    {
        return [
            '100.1',        // Single decimal
            '100.10',       // Trailing zero
            '100.123',      // Three decimals
            '100.1234',     // Four decimals
            '100.12345',    // Five decimals
            '100.123456',   // Six decimals (typical float limit)
            '100.1234567',  // Seven decimals (potential loss)
        ];
    }
    
    /**
     * Format edge cases
     * Tests different amount formats
     * 
     * @return array<string> Amounts with format variations
     */
    public static function formatEdgeCases(): array
    {
        return [
            '100',          // No decimal places
            '100.',         // Trailing decimal point
            '.50',          // Leading decimal point
            '00100.50',     // Leading zeros
            '00100.00',     // Leading zeros on whole number
            '-100',         // Negative without decimals
            '+100.00',      // Explicit positive sign
        ];
    }
    
    /**
     * Standard test amounts (common in real statements)
     * 
     * @return array<string> Standard amounts for typical cases
     */
    public static function standard(): array
    {
        return [
            '0.00',
            '10.00',
            '25.50',
            '50.00',
            '100.00',
            '500.00',
            '1000.00',
        ];
    }
    
    /**
     * All edge case amounts combined
     * Use when testing with all possibilities
     * 
     * @return array<string> All edge case amounts
     */
    public static function all(): array
    {
        $amounts = array_merge(
            self::positiveExtremes(),
            self::negativeExtremes(),
            self::zeroVariants(),
            self::precisionEdgeCases(),
            self::formatEdgeCases()
        );
        
        return array_unique($amounts);
    }
}
