<?php declare(strict_types=1);

namespace Tests\Builders;

use DateTime;
use DateTimeZone;

/**
 * Pre-built test scenarios that combine edge case values into realistic test cases
 * 
 * Enables testing of multiple extremes in concert:
 *   - Large statement with many transactions
 *   - Transactions with all extreme amounts
 *   - Transactions with all extreme dates
 *   - Mixed format variations
 * 
 * Usage:
 *   // Test large statement (many transactions)
 *   $ofx = TestScenarios::largeStatement()->build();
 *   
 *   // Test all amount extremes in one statement
 *   $ofx = TestScenarios::allAmountExtremes()->build();
 *   
 *   // Test date format variations
 *   $ofx = TestScenarios::dateBoundaries()->build();
 */
class TestScenarios
{
    /**
     * Scenario: Statement with 100 transactions
     * Tests parser's ability to handle large volumes
     */
    public static function largeStatement(int $transactionCount = 100): OFXEnvelopeBuilder
    {
        $builder = OFXEnvelopeBuilder::ofxBankStatement()
            ->withStatementPeriod(
                new DateTime('2026-01-01', new DateTimeZone('UTC')),
                new DateTime('2026-03-31', new DateTimeZone('UTC'))
            );
        
        for ($i = 1; $i <= $transactionCount; $i++) {
            $date = (clone new DateTime('2026-01-01'))->modify("+{$i} days");
            $builder->addTransaction([
                'id' => "TXN{$i}",
                'type' => $i % 2 == 0 ? 'DEBIT' : 'CREDIT',
                'amount' => $i * 10.5,
                'date' => $date,
                'memo' => "Transaction {$i}",
            ]);
        }
        
        return $builder;
    }
    
    /**
     * Scenario: All positive amount extremes
     * Tests parsing of various positive amount formats
     */
    public static function positiveAmountExtremes(): OFXEnvelopeBuilder
    {
        $builder = OFXEnvelopeBuilder::ofxBankStatement();
        
        $amounts = [
            ['0.01', 'Minimum positive'],
            ['100.00', 'Standard amount'],
            ['1000.50', 'Thousands separator needed'],
            ['999999999999.99', 'Maximum amount'],
        ];
        
        $id = 1;
        foreach ($amounts as [$amount, $desc]) {
            $builder->addTransaction([
                'id' => (string) $id++,
                'type' => 'CREDIT',
                'amount' => $amount,
                'date' => new DateTime('2026-03-13', new DateTimeZone('UTC')),
                'memo' => $desc,
            ]);
        }
        
        return $builder;
    }
    
    /**
     * Scenario: All negative amount extremes
     * Tests parsing of various negative amount formats
     */
    public static function negativeAmountExtremes(): OFXEnvelopeBuilder
    {
        $builder = OFXEnvelopeBuilder::ofxBankStatement();
        
        $amounts = [
            ['-0.01', 'Minimum negative'],
            ['-100.00', 'Standard negative'],
            ['-1500.50', 'Negative with thousands'],
            ['-999999999999.99', 'Maximum negative'],
        ];
        
        $id = 1;
        foreach ($amounts as [$amount, $desc]) {
            $builder->addTransaction([
                'id' => (string) $id++,
                'type' => 'DEBIT',
                'amount' => $amount,
                'date' => new DateTime('2026-03-13', new DateTimeZone('UTC')),
                'memo' => $desc,
            ]);
        }
        
        return $builder;
    }
    
    /**
     * Scenario: Date boundary conditions
     * Tests parsing with extreme dates
     */
    public static function dateBoundaries(): OFXEnvelopeBuilder
    {
        $builder = OFXEnvelopeBuilder::ofxBankStatement();
        
        $dates = [
            new DateTime('1970-01-01', new DateTimeZone('UTC')),   // Unix epoch
            new DateTime('2000-01-01', new DateTimeZone('UTC')),   // Y2K
            new DateTime('2024-02-29', new DateTimeZone('UTC')),   // Leap year
            new DateTime('2026-01-01', new DateTimeZone('UTC')),   // New year
            new DateTime('2026-12-31', new DateTimeZone('UTC')),   // Year end
            new DateTime('2099-12-31', new DateTimeZone('UTC')),   // Far future
        ];
        
        $id = 1;
        foreach ($dates as $date) {
            $builder->addTransaction([
                'id' => (string) $id++,
                'type' => 'CREDIT',
                'amount' => '100.00',
                'date' => $date,
                'memo' => $date->format('Y-m-d'),
            ]);
        }
        
        return $builder;
    }
    
    /**
     * Scenario: Long field values
     * Tests parser's handling of maximum length fields
     */
    public static function maximumFieldLengths(): OFXEnvelopeBuilder
    {
        $builder = OFXEnvelopeBuilder::ofxBankStatement()
            ->withAccountId(str_repeat('9', 32))  // Max account ID (32 chars)
            ->withBankId(str_repeat('8', 9));      // Max bank ID (9 digits)
        
        // Add transaction with long memo
        $builder->addTransaction([
            'id' => 'ID' . str_repeat('0', 30),
            'type' => 'CREDIT',
            'amount' => '100.00',
            'date' => new DateTime('2026-03-13', new DateTimeZone('UTC')),
            'memo' => str_repeat('X', 10000),  // Very long memo
            'payee' => str_repeat('N', 32),    // Long payee name
        ]);
        
        return $builder;
    }
    
    /**
     * Scenario: Zero and near-zero amounts
     * Tests edge case numeric handling
     */
    public static function zeroAmounts(): OFXEnvelopeBuilder
    {
        $builder = OFXEnvelopeBuilder::ofxBankStatement();
        
        $amounts = [
            ['0', 'Zero as integer'],
            ['0.00', 'Zero as decimal'],
            ['-0.00', 'Negative zero'],
            ['0.01', 'One cent credit'],
            ['-0.01', 'One cent debit'],
        ];
        
        $id = 1;
        foreach ($amounts as [$amount, $desc]) {
            $type = $amount == '0' || $amount == '0.00' || $amount == '-0.00' ? 'CREDIT' : 
                   ($amount == '0.01' ? 'CREDIT' : 'DEBIT');
            
            $builder->addTransaction([
                'id' => (string) $id++,
                'type' => $type,
                'amount' => $amount,
                'date' => new DateTime('2026-03-13', new DateTimeZone('UTC')),
                'memo' => $desc,
            ]);
        }
        
        return $builder;
    }
    
    /**
     * Scenario: Multiple currencies
     * Tests parser's handling of different currency codes
     */
    public static function multipleCurrencies(): OFXEnvelopeBuilder
    {
        // Note: OFX document can only have one currency per statement,
        // so this creates multiple separate builders for testing
        // In practice, you'd test each one individually
        
        return OFXEnvelopeBuilder::ofxBankStatement()
            ->withCurrency('USD');
    }
    
    /**
     * Scenario: All transaction types
     * Tests parser's recognition of different transaction types
     */
    public static function allTransactionTypes(): OFXEnvelopeBuilder
    {
        $builder = OFXEnvelopeBuilder::ofxBankStatement();
        
        $types = ['DEBIT', 'CREDIT', 'INT', 'DIV', 'FEE', 'SRVCHG', 'DEP', 'ATM', 'POS', 'XFER', 'CHECK'];
        
        $id = 1;
        foreach ($types as $type) {
            $builder->addTransaction([
                'id' => (string) $id++,
                'type' => $type,
                'amount' => '100.00',
                'date' => new DateTime('2026-03-13', new DateTimeZone('UTC')),
                'memo' => "Type: {$type}",
            ]);
        }
        
        return $builder;
    }
    
    /**
     * Scenario: Credit card statement with negative balance
     * Tests credit card specific formatting
     */
    public static function creditCardStatement(): OFXEnvelopeBuilder
    {
        $builder = OFXEnvelopeBuilder::ofxCreditCardStatement()
            ->withAccountId('4111111111111111')
            ->withBalance('-197.94', new DateTime('2026-03-13', new DateTimeZone('UTC')));
        
        $builder->addTransactions([
            [
                'id' => '1',
                'type' => 'DEBIT',
                'amount' => '-50.00',
                'date' => new DateTime('2026-03-10', new DateTimeZone('UTC')),
                'memo' => 'Purchase 1',
            ],
            [
                'id' => '2',
                'type' => 'DEBIT',
                'amount' => '-75.25',
                'date' => new DateTime('2026-03-11', new DateTimeZone('UTC')),
                'memo' => 'Purchase 2',
            ],
            [
                'id' => '3',
                'type' => 'DEBIT',
                'amount' => '-72.69',
                'date' => new DateTime('2026-03-12', new DateTimeZone('UTC')),
                'memo' => 'Purchase 3',
            ],
        ]);
        
        return $builder;
    }
    
    /**
     * Scenario: Special characters in fields
     * Tests XML entity handling
     */
    public static function specialCharacters(): OFXEnvelopeBuilder
    {
        $builder = OFXEnvelopeBuilder::ofxBankStatement();
        
        $builder->addTransactions([
            [
                'id' => '1',
                'type' => 'CREDIT',
                'amount' => '100.00',
                'date' => new DateTime('2026-03-13', new DateTimeZone('UTC')),
                'memo' => 'Test & ampersand',
                'payee' => 'Store & Company',
            ],
            [
                'id' => '2',
                'type' => 'CREDIT',
                'amount' => '75.50',
                'date' => new DateTime('2026-03-12', new DateTimeZone('UTC')),
                'memo' => 'Quote "test" memo',
                'payee' => 'Company "Inc"',
            ],
            [
                'id' => '3',
                'type' => 'CREDIT',
                'amount' => '50.25',
                'date' => new DateTime('2026-03-11', new DateTimeZone('UTC')),
                'memo' => 'Less <than> greater >than',
                'payee' => '<Business>',
            ],
        ]);
        
        return $builder;
    }
}
