<?php declare(strict_types=1);

namespace Tests\Builders;

use DateTime;
use DateTimeZone;

/**
 * Generates extreme field values for boundary condition and data format variation testing
 * 
 * Each generator returns a tuple of [value, expectedParsedValue, description]
 * This enables reusing the same values for:
 *   - Unit tests: Assert field parsing handles the value correctly
 *   - Integration tests: Verify full document parsing produces expected results
 * 
 * Example:
 *   $amounts = EdgeCaseValueGenerator::amounts();
 *   foreach ($amounts as [$value, $expected, $desc]) {
 *       // Unit test: Assert Parser::parseAmount($value) == $expected
 *       // Integration test: Build full OFX with $value, assert $ofx->account->txn[0]->amount == $expected
 *   }
 */
class EdgeCaseValueGenerator
{
    /**
     * Generate amount field values with known expected parsing results
     * 
     * @return array<array{0: string, 1: float, 2: string}> Array of [ofxValue, expectedParsed, description]
     */
    public static function amounts(): array
    {
        return [
            // Standard positive amounts
            ['100.00', 100.0, 'Standard positive amount'],
            ['1000.00', 1000.0, 'Positive with thousands'],
            ['0.01', 0.01, 'Very small positive amount'],
            
            // Negative amounts (debits)
            ['-50.00', -50.0, 'Standard negative amount'],
            ['-1500.50', -1500.50, 'Large negative amount'],
            ['-0.01', -0.01, 'Very small negative amount'],
            
            // Zero
            ['0', 0.0, 'Zero as integer'],
            ['0.00', 0.0, 'Zero as decimal'],
            ['-0.00', 0.0, 'Negative zero'],
            
            // Boundary values
            ['999999999999.99', 999999999999.99, 'Very large amount'],
            ['-999999999999.99', -999999999999.99, 'Very large negative amount'],
            
            // Format variations
            ['1000,00', 1000.0, 'European decimal format (comma)'],
            ['1.000,00', 1000.0, 'European thousands separator'],
            ['1,000.00', 1000.0, 'English thousands separator'],
        ];
    }
    
    /**
     * Generate date field values with known expected DateTime results
     * 
     * @return array<array{0: string, 1: DateTime|string, 2: string}> Array of [ofxValue, expectedDate, description]
     */
    public static function dates(): array
    {
        return [
            // Standard formats
            ['20260313', new DateTime('2026-03-13', new DateTimeZone('UTC')), 'Standard YYYYMMDD format'],
            ['19700101', new DateTime('1970-01-01', new DateTimeZone('UTC')), 'Unix epoch date'],
            ['20991231', new DateTime('2099-12-31', new DateTimeZone('UTC')), 'Far future date'],
            
            // With time
            ['202603131200', new DateTime('2026-03-13 12:00', new DateTimeZone('UTC')), 'Date with time YYYYMMDDHHMM'],
            ['20260313120000', new DateTime('2026-03-13 12:00:00', new DateTimeZone('UTC')), 'Date with seconds YYYYMMDDHHMMSS'],
            ['20260313120000.000', new DateTime('2026-03-13 12:00:00', new DateTimeZone('UTC')), 'Date with milliseconds'],
            
            // Leap year dates
            ['20240229', new DateTime('2024-02-29', new DateTimeZone('UTC')), 'Leap year date'],
            
            // Month boundaries
            ['20260101', new DateTime('2026-01-01', new DateTimeZone('UTC')), 'First day of year'],
            ['20261231', new DateTime('2026-12-31', new DateTimeZone('UTC')), 'Last day of year'],
        ];
    }
    
    /**
     * Generate transaction ID values with various formats
     * 
     * @return array<array{0: string, 1: string, 2: string}> Array of [value, expectedId, description]
     */
    public static function transactionIds(): array
    {
        return [
            // Standard formats
            ['1', '1', 'Single digit ID'],
            ['12345', '12345', 'Numeric ID'],
            ['TXN20260313001', 'TXN20260313001', 'Prefixed ID with date'],
            
            // Boundary values
            ['000000000000000000000001', '000000000000000000000001', 'ID with leading zeros'],
            [str_repeat('A', 32), str_repeat('A', 32), 'Maximum length ID (32 chars)'],
            
            // Special characters (if supported)
            ['TXN-001', 'TXN-001', 'ID with hyphen'],
            ['TXN_001', 'TXN_001', 'ID with underscore'],
        ];
    }
    
    /**
     * Generate memo field values with various lengths and content
     * 
     * @return array<array{0: string, 1: string, 2: string}> Array of [value, expectedMemo, description]
     */
    public static function memos(): array
    {
        return [
            // Normal memos
            ['Test transaction', 'Test transaction', 'Standard memo'],
            ['Deposit', 'Deposit', 'Short memo'],
            
            // Long memos
            [str_repeat('X', 255), str_repeat('X', 255), 'Maximum memo length (255 chars)'],
            [str_repeat('X', 10000), str_repeat('X', 10000), 'Very long memo (10K chars)'],
            
            // Special characters
            ['Test & memo', 'Test & memo', 'Memo with ampersand'],
            ['Test "quoted" memo', 'Test "quoted" memo', 'Memo with quotes'],
            ['Test <special> chars', 'Test <special> chars', 'Memo with angle brackets'],
            
            // Whitespace
            ['Test  double  spaces', 'Test  double  spaces', 'Memo with double spaces'],
            ["Test\twith\ttabs", "Test\twith\ttabs", 'Memo with tabs'],
            ["Test\nmultiline", "Test\nmultiline", 'Memo with newlines'],
        ];
    }
    
    /**
     * Generate currency codes
     * 
     * @return array<array{0: string, 1: string, 2: string}> Array of [value, expectedCurrency, description]
     */
    public static function currencies(): array
    {
        return [
            ['USD', 'USD', 'US Dollar'],
            ['CAD', 'CAD', 'Canadian Dollar'],
            ['EUR', 'EUR', 'Euro'],
            ['GBP', 'GBP', 'British Pound'],
            ['JPY', 'JPY', 'Japanese Yen'],
            ['CHF', 'CHF', 'Swiss Franc'],
        ];
    }
    
    /**
     * Generate account IDs with various formats
     * 
     * @return array<array{0: string, 1: string, 2: string}> Array of [value, expectedId, description]
     */
    public static function accountIds(): array
    {
        return [
            // Standard formats
            ['123456789', '123456789', 'Numeric account ID'],
            ['098-121', '098-121', 'Account ID with hyphen'],
            
            // Boundary values
            ['9999999999', '9999999999', 'Large account number'],
            ['1', '1', 'Single digit account ID'],
            
            // Special formats
            ['ACC-2026-03-13-001', 'ACC-2026-03-13-001', 'Account ID with date pattern'],
        ];
    }
    
    /**
     * Generate bank routing numbers
     * 
     * @return array<array{0: string, 1: string, 2: string}> Array of [value, expectedId, description]
     */
    public static function routingNumbers(): array
    {
        return [
            ['123456789', '123456789', 'Standard routing number'],
            ['600000100', '600000100', 'Real CIBC routing number (simulated)'],
            ['900000100', '900000100', 'Real RBC routing number (simulated)'],
            ['999999999', '999999999', 'Fake/generic routing number'],
        ];
    }
    
    /**
     * Generate balance amounts with extremes
     * 
     * @return array<array{0: string, 1: float, 2: string}> Array of [value, expectedAmount, description]
     */
    public static function balances(): array
    {
        return [
            // Positive balances
            ['1000.00', 1000.0, 'Standard positive balance'],
            ['0.01', 0.01, 'Very small positive balance'],
            
            // Negative balances (credit card or overdraft)
            ['-500.00', -500.0, 'Negative balance (credit)'],
            ['-0.01', -0.01, 'Very small negative balance'],
            
            // Extremes
            ['9999999999.99', 9999999999.99, 'Very large balance'],
            ['-9999999999.99', -9999999999.99, 'Very large negative balance'],
            ['0', 0.0, 'Zero balance'],
        ];
    }
    
    /**
     * Generate account types
     * 
     * @return array<array{0: string, 1: string, 2: string}> Array of [value, expectedType, description]
     */
    public static function accountTypes(): array
    {
        return [
            ['CHECKING', 'CHECKING', 'Checking account'],
            ['SAVINGS', 'SAVINGS', 'Savings account'],
            ['CREDITLINE', 'CREDITLINE', 'Credit card account'],
            ['MONEYMRKT', 'MONEYMRKT', 'Money market account'],
            ['INVESTMENT', 'INVESTMENT', 'Investment account'],
        ];
    }
    
    /**
     * Generate transaction types
     * 
     * @return array<array{0: string, 1: string, 2: string}> Array of [value, expectedType, description]
     */
    public static function transactionTypes(): array
    {
        return [
            ['DEBIT', 'DEBIT', 'Debit transaction'],
            ['CREDIT', 'CREDIT', 'Credit transaction'],
            ['INT', 'INT', 'Interest'],
            ['DIV', 'DIV', 'Dividend'],
            ['FEE', 'FEE', 'Fee'],
            ['SRVCHG', 'SRVCHG', 'Service charge'],
            ['DEP', 'DEP', 'Deposit'],
            ['ATM', 'ATM', 'ATM withdrawal'],
            ['POS', 'POS', 'Point of sale'],
            ['XFER', 'XFER', 'Transfer'],
            ['CHECK', 'CHECK', 'Check'],
        ];
    }
}
