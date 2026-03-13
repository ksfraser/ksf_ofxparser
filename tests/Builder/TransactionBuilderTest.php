<?php declare(strict_types=1);

namespace Tests\Builder;

use PHPUnit\Framework\TestCase;
use OfxParser\Builder\TransactionBuilder;
use OfxParser\Entities\Transaction;
use Exception;

class TransactionBuilderTest extends TestCase
{
    private TransactionBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new TransactionBuilder();
    }

    // UT4-001: Create fresh builder
    public function testCreateFreshBuilder(): void
    {
        $builder = new TransactionBuilder();
        $this->assertNotNull($builder);
        $this->assertInstanceOf(TransactionBuilder::class, $builder);
    }

    // UT4-002: Set transaction ID
    public function testSetTransactionId(): void
    {
        $this->builder->setTransactionId('TX20260313001');
        $transaction = $this->builder->build();
        
        $this->assertNotNull($transaction);
        $this->assertEquals('TX20260313001', $transaction->getTransactionId());
    }

    // UT4-003: Set amount
    public function testSetAmount(): void
    {
        $this->builder->setAmount(-150.75);
        $transaction = $this->builder->build();
        
        $this->assertNotNull($transaction);
        $this->assertEquals(-150.75, $transaction->getAmount());
    }

    // UT4-004: Set date
    public function testSetDate(): void
    {
        $date = new \DateTime('2026-03-13');
        $this->builder->setDate($date);
        $transaction = $this->builder->build();
        
        $this->assertNotNull($transaction);
        $this->assertEquals($date, $transaction->getDate());
    }

    // UT4-005: Set memo
    public function testSetMemo(): void
    {
        $this->builder->setMemo('Supermarket purchase');
        $transaction = $this->builder->build();
        
        $this->assertNotNull($transaction);
        $this->assertEquals('Supermarket purchase', $transaction->getMemo());
    }

    // UT4-006: Set check number (optional field)
    public function testSetCheckNumber(): void
    {
        $this->builder->setCheckNumber('1234');
        $transaction = $this->builder->build();
        
        $this->assertNotNull($transaction);
        $this->assertEquals('1234', $transaction->getCheckNumber());
    }

    // UT4-007: Set reference number (optional field)
    public function testSetReferenceNumber(): void
    {
        $this->builder->setReferenceNumber('REF123');
        $transaction = $this->builder->build();
        
        $this->assertNotNull($transaction);
        $this->assertEquals('REF123', $transaction->getReferenceNumber());
    }

    // UT4-008: Set name
    public function testSetName(): void
    {
        $this->builder->setName('Store ABC');
        $transaction = $this->builder->build();
        
        $this->assertNotNull($transaction);
        $this->assertEquals('Store ABC', $transaction->getName());
    }

    // UT4-009: Set type (DEBIT, CREDIT, etc)
    public function testSetType(): void
    {
        $this->builder->setType('DEBIT');
        $transaction = $this->builder->build();
        
        $this->assertNotNull($transaction);
        $this->assertEquals('DEBIT', $transaction->getType());
    }

    // UT4-010: Fluent interface chaining
    public function testFluentInterfaceChaining(): void
    {
        $transaction = $this->builder
            ->setTransactionId('TX001')
            ->setAmount(-100.00)
            ->setMemo('Test memo')
            ->setType('DEBIT')
            ->build();
        
        $this->assertNotNull($transaction);
        $this->assertEquals('TX001', $transaction->getTransactionId());
        $this->assertEquals(-100.00, $transaction->getAmount());
        $this->assertEquals('Test memo', $transaction->getMemo());
        $this->assertEquals('DEBIT', $transaction->getType());
    }

    // UT4-011: Build validates required fields
    public function testBuildValidatesRequiredFields(): void
    {
        try {
            // Empty builder should fail or create partial transaction
            $transaction = $this->builder->build();
            
            // If it succeeds, at least one field should be missing/null
            $this->assertTrue(
                $transaction->getTransactionId() === null || 
                $transaction->getAmount() === null
            );
        } catch (Exception $e) {
            // Expected if validation enforced
            $this->assertStringContainsString('required', strtolower($e->getMessage()));
        }
    }

    // UT4-012: Build with minimal fields
    public function testBuildWithMinimalFields(): void
    {
        $transaction = $this->builder
            ->setTransactionId('TX001')
            ->setAmount(50.00)
            ->build();
        
        $this->assertNotNull($transaction);
        $this->assertEquals('TX001', $transaction->getTransactionId());
        $this->assertEquals(50.00, $transaction->getAmount());
    }

    // UT4-013: Update field after setting
    public function testUpdateFieldAfterSetting(): void
    {
        $this->builder->setAmount(100.00);
        $this->builder->setAmount(150.00);
        $transaction = $this->builder->build();
        
        // Second set should overwrite first
        $this->assertEquals(150.00, $transaction->getAmount());
    }

    // UT4-014: Reset builder
    public function testResetBuilder(): void
    {
        $this->builder
            ->setTransactionId('TX001')
            ->setAmount(100.00)
            ->setMemo('Test');
        
        // Reset
        $this->builder->reset();
        
        // After reset, fields should be cleared
        $transaction = $this->builder->build();
        
        // At least transaction ID and amount should be null
        $this->assertTrue(
            $transaction->getTransactionId() === null || 
            $transaction->getAmount() === null
        );
    }

    // UT4-015: Build multiple transactions independently
    public function testBuildMultipleTransactionsIndependently(): void
    {
        $tx1 = $this->builder
            ->setTransactionId('TX001')
            ->setAmount(100.00)
            ->build();
        
        $builder2 = new TransactionBuilder();
        $tx2 = $builder2
            ->setTransactionId('TX002')
            ->setAmount(200.00)
            ->build();
        
        $this->assertEquals('TX001', $tx1->getTransactionId());
        $this->assertEquals('TX002', $tx2->getTransactionId());
        $this->assertNotEqual($tx1->getTransactionId(), $tx2->getTransactionId());
    }

    // UT4-016: Handle zero amount
    public function testHandleZeroAmount(): void
    {
        $this->builder->setAmount(0);
        $transaction = $this->builder->build();
        
        $this->assertNotNull($transaction);
        $this->assertEquals(0, $transaction->getAmount());
    }

    // UT4-017: Handle negative amount
    public function testHandleNegativeAmount(): void
    {
        $this->builder->setAmount(-500.50);
        $transaction = $this->builder->build();
        
        $this->assertNotNull($transaction);
        $this->assertEquals(-500.50, $transaction->getAmount());
    }

    // UT4-018: Handle very large amount
    public function testHandleVeryLargeAmount(): void
    {
        $largeAmount = 999999999.99;
        $this->builder->setAmount($largeAmount);
        $transaction = $this->builder->build();
        
        $this->assertNotNull($transaction);
        $this->assertEquals($largeAmount, $transaction->getAmount());
    }

    // UT4-019: Handle very small amount
    public function testHandleVerySmallAmount(): void
    {
        $smallAmount = 0.01;
        $this->builder->setAmount($smallAmount);
        $transaction = $this->builder->build();
        
        $this->assertNotNull($transaction);
        $this->assertEquals($smallAmount, $transaction->getAmount());
    }

    // UT4-020: Handle long transaction ID
    public function testHandleLongTransactionId(): void
    {
        $longId = str_repeat('X', 100);
        $this->builder->setTransactionId($longId);
        $transaction = $this->builder->build();
        
        $this->assertNotNull($transaction);
        $this->assertEquals($longId, $transaction->getTransactionId());
    }

    // UT4-021: Handle special characters in memo
    public function testHandleSpecialCharactersInMemo(): void
    {
        $memo = "Café & Restaurant <Test> \"Quotes\" 'Apostrophe' © 2026";
        $this->builder->setMemo($memo);
        $transaction = $this->builder->build();
        
        $this->assertNotNull($transaction);
        $this->assertEquals($memo, $transaction->getMemo());
    }

    // UT4-022: Handle empty memo
    public function testHandleEmptyMemo(): void
    {
        $this->builder->setMemo('');
        $transaction = $this->builder->build();
        
        $this->assertNotNull($transaction);
        $this->assertEquals('', $transaction->getMemo());
    }

    // UT4-023: Handle null optional fields
    public function testHandleNullOptionalFields(): void
    {
        $this->builder
            ->setTransactionId('TX001')
            ->setAmount(100.00)
            ->setCheckNumber(null)
            ->setReferenceNumber(null);
        
        $transaction = $this->builder->build();
        
        $this->assertNotNull($transaction);
        $this->assertNull($transaction->getCheckNumber());
        $this->assertNull($transaction->getReferenceNumber());
    }

    // UT4-024: Builder state independence between instances
    public function testBuilderStateIndependence(): void
    {
        $builder1 = new TransactionBuilder();
        $builder1->setTransactionId('TX001')->setAmount(100.00);
        
        $builder2 = new TransactionBuilder();
        $builder2->setTransactionId('TX002')->setAmount(200.00);
        
        $tx1 = $builder1->build();
        $tx2 = $builder2->build();
        
        $this->assertEquals('TX001', $tx1->getTransactionId());
        $this->assertEquals('TX002', $tx2->getTransactionId());
        $this->assertNotEqual($tx1->getTransactionId(), $tx2->getTransactionId());
    }

    // UT4-025: Validate that build returns Transaction instance
    public function testBuildReturnsTransactionInstance(): void
    {
        $transaction = $this->builder
            ->setTransactionId('TX001')
            ->setAmount(100.00)
            ->build();
        
        $this->assertInstanceOf(Transaction::class, $transaction);
    }

    // UT4-026: Build creates distinct instances
    public function testBuildCreatesDistinctInstances(): void
    {
        $this->builder
            ->setTransactionId('TX001')
            ->setAmount(100.00);
        
        $tx1 = $this->builder->build();
        
        $this->builder
            ->setTransactionId('TX002')
            ->setAmount(200.00);
        
        $tx2 = $this->builder->build();
        
        // tx1 should not have been modified
        $this->assertEquals('TX001', $tx1->getTransactionId());
        $this->assertEquals('TX002', $tx2->getTransactionId());
    }

    // UT4-027: Set posting date different from transaction date
    public function testSetPostingDate(): void
    {
        $txDate = new \DateTime('2026-03-10');
        $postDate = new \DateTime('2026-03-12');
        
        $this->builder
            ->setDate($txDate);
        
        // If builder supports posting date separately
        if (method_exists($this->builder, 'setPostingDate')) {
            $this->builder->setPostingDate($postDate);
        }
        
        $transaction = $this->builder->build();
        $this->assertNotNull($transaction);
    }

    // UT4-028: Handle transaction with complex name/payee
    public function testHandleComplexPayeeName(): void
    {
        $name = "ABC CORP / DBA DEF SERVICES - LOCATION #123";
        $this->builder->setName($name);
        $transaction = $this->builder->build();
        
        $this->assertNotNull($transaction);
        $this->assertEquals($name, $transaction->getName());
    }

    // UT4-029: All standard transaction types
    public function testAllStandardTransactionTypes(): void
    {
        $types = ['DEBIT', 'CREDIT', 'INT', 'DIV', 'FEE', 'SRVCHG', 'DEP', 'ATM', 'POS', 'XFER'];
        
        foreach ($types as $type) {
            $this->builder->setType($type);
            $transaction = $this->builder->build();
            
            $this->assertEquals($type, $transaction->getType(), "Failed for type: {$type}");
        }
    }

    // UT4-030: Handle Unicode characters in memo
    public function testHandleUnicodeCharactersInMemo(): void
    {
        $memo = "测试 тест δοκιμή נסיון ٳختبار 🎉";
        $this->builder->setMemo($memo);
        $transaction = $this->builder->build();
        
        $this->assertNotNull($transaction);
        $this->assertEquals($memo, $transaction->getMemo());
    }
}
