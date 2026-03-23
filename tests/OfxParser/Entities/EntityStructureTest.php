<?php

namespace OfxParserTest\Entities;

use PHPUnit\Framework\TestCase;
use OfxParser\Entities\Transaction;
use OfxParser\Entities\BankAccount;
use OfxParser\Entities\Statement;
use OfxParser\Entities\SignOn;
use OfxParser\Entities\Status;
use OfxParser\Entities\Institute;
use OfxParser\Entities\Payee;
use OfxParser\Entities\AccountInfo;

/**
 * Test Entity Structure and Properties
 * 
 * What: Tests entity classes for proper structure, property access,
 * and data integrity.
 * 
 * Why: Entity classes hold parsed OFX data and must correctly represent
 * financial information. Testing ensures properties are accessible and
 * data types are preserved.
 */
class EntityStructureTest extends TestCase
{
    /**
     * Test Transaction entity properties
     */
    public function testTransactionEntityStructure()
    {
        $transaction = new Transaction();
        
        $transaction->type = 'DEBIT';
        $transaction->date = new \DateTime('2026-01-15');
        $transaction->amount = -100.50;
        $transaction->uniqueId = 'TXN123';
        $transaction->name = 'Test Transaction';
        $transaction->memo = 'Test memo';
        $transaction->sic = '5411';
        $transaction->checkNumber = '1234';
        
        $this->assertEquals('DEBIT', $transaction->type);
        $this->assertInstanceOf(\DateTimeInterface::class, $transaction->date);
        $this->assertEquals(-100.50, $transaction->amount);
        $this->assertEquals('TXN123', $transaction->uniqueId);
        $this->assertEquals('Test Transaction', $transaction->name);
        $this->assertEquals('Test memo', $transaction->memo);
        $this->assertEquals('5411', $transaction->sic);
        $this->assertEquals('1234', $transaction->checkNumber);
    }

    /**
     * Test Transaction with currency
     */
    public function testTransactionWithCurrency()
    {
        $transaction = new Transaction();
        $transaction->amount = 100.00;
        $transaction->currency = [
            'code' => 'EUR',
            'rate' => 1.18
        ];
        
        $this->assertIsArray($transaction->currency);
        $this->assertEquals('EUR', $transaction->currency['code']);
        $this->assertEquals(1.18, $transaction->currency['rate']);
        
        // Calculate amount in base currency
        $baseAmount = $transaction->amount * $transaction->currency['rate'];
        $this->assertEquals(118.00, $baseAmount);
    }

    /**
     * Test Transaction with payee
     */
    public function testTransactionWithPayee()
    {
        $transaction = new Transaction();
        
        $payee = new Payee();
        $payee->name = 'ACME Corp';
        $payee->address1 = '123 Main St';
        $payee->city = 'Springfield';
        $payee->state = 'IL';
        $payee->postalCode = '62701';
        $payee->phone = '555-1234';
        
        $transaction->payee = $payee;
        
        $this->assertInstanceOf(Payee::class, $transaction->payee);
        $this->assertEquals('ACME Corp', $transaction->payee->name);
        $this->assertEquals('123 Main St', $transaction->payee->address1);
        $this->assertEquals('Springfield', $transaction->payee->city);
    }

    /**
     * Test BankAccount entity properties
     */
    public function testBankAccountEntityStructure()
    {
        $account = new BankAccount();
        
        $account->accountNumber = '1234567890';
        $account->accountType = 'CHECKING';
        $account->balance = 5000.00;
        $account->balanceDate = new \DateTime('2026-01-15');
        $account->routingNumber = '123456789';
        $account->agencyNumber = '001';
        $account->transactionUid = 'TRANS123';
        
        $this->assertEquals('1234567890', $account->accountNumber);
        $this->assertEquals('CHECKING', $account->accountType);
        $this->assertEquals(5000.00, $account->balance);
        $this->assertInstanceOf(\DateTimeInterface::class, $account->balanceDate);
        $this->assertEquals('123456789', $account->routingNumber);
        $this->assertEquals('001', $account->agencyNumber);
        $this->assertEquals('TRANS123', $account->transactionUid);
    }

    /**
     * Test Statement entity with transactions
     */
    public function testStatementEntityStructure()
    {
        $statement = new Statement();
        
        $statement->currency = 'USD';
        $statement->startDate = new \DateTime('2026-01-01');
        $statement->endDate = new \DateTime('2026-01-15');
        
        $transaction1 = new Transaction();
        $transaction1->amount = -50.00;
        $transaction1->uniqueId = 'TXN1';
        
        $transaction2 = new Transaction();
        $transaction2->amount = 100.00;
        $transaction2->uniqueId = 'TXN2';
        
        $statement->transactions = [$transaction1, $transaction2];
        
        $this->assertEquals('USD', $statement->currency);
        $this->assertInstanceOf(\DateTimeInterface::class, $statement->startDate);
        $this->assertInstanceOf(\DateTimeInterface::class, $statement->endDate);
        $this->assertCount(2, $statement->transactions);
        $this->assertEquals(-50.00, $statement->transactions[0]->amount);
        $this->assertEquals(100.00, $statement->transactions[1]->amount);
    }

    /**
     * Test SignOn entity properties
     */
    public function testSignOnEntityStructure()
    {
        $signOn = new SignOn();
        
        $signOn->status = new Status();
        $signOn->status->code = 0;
        $signOn->status->severity = 'INFO';
        $signOn->status->message = 'Success';
        
        $signOn->date = new \DateTime('2026-01-15 12:00:00');
        $signOn->language = 'ENG';
        
        $signOn->institute = new Institute();
        $signOn->institute->name = 'Test Bank';
        $signOn->institute->id = '12345';
        
        $this->assertInstanceOf(Status::class, $signOn->status);
        $this->assertEquals(0, $signOn->status->code);
        $this->assertEquals('INFO', $signOn->status->severity);
        $this->assertInstanceOf(\DateTimeInterface::class, $signOn->date);
        $this->assertEquals('ENG', $signOn->language);
        $this->assertInstanceOf(Institute::class, $signOn->institute);
        $this->assertEquals('Test Bank', $signOn->institute->name);
    }

    /**
     * Test Status entity
     */
    public function testStatusEntity()
    {
        $status = new Status();
        $status->code = 0;
        $status->severity = 'INFO';
        $status->message = 'Success';
        
        $this->assertEquals(0, $status->code);
        $this->assertEquals('INFO', $status->severity);
        $this->assertEquals('Success', $status->message);
        
        // Test error status
        $errorStatus = new Status();
        $errorStatus->code = 2000;
        $errorStatus->severity = 'ERROR';
        $errorStatus->message = 'General error';
        
        $this->assertEquals(2000, $errorStatus->code);
        $this->assertEquals('ERROR', $errorStatus->severity);
    }

    /**
     * Test Institute entity
     */
    public function testInstituteEntity()
    {
        $institute = new Institute();
        $institute->name = 'My Bank';
        $institute->id = 'BANK123';
        
        $this->assertEquals('My Bank', $institute->name);
        $this->assertEquals('BANK123', $institute->id);
    }

    /**
     * Test Payee entity complete address
     */
    public function testPayeeEntityCompleteAddress()
    {
        $payee = new Payee();
        $payee->name = 'ACME Corporation';
        $payee->address1 = '123 Main Street';
        $payee->address2 = 'Suite 100';
        $payee->address3 = 'Building B';
        $payee->city = 'Springfield';
        $payee->state = 'IL';
        $payee->postalCode = '62701';
        $payee->country = 'USA';
        $payee->phone = '555-1234';
        
        $this->assertEquals('ACME Corporation', $payee->name);
        $this->assertEquals('123 Main Street', $payee->address1);
        $this->assertEquals('Suite 100', $payee->address2);
        $this->assertEquals('Building B', $payee->address3);
        $this->assertEquals('Springfield', $payee->city);
        $this->assertEquals('IL', $payee->state);
        $this->assertEquals('62701', $payee->postalCode);
        $this->assertEquals('USA', $payee->country);
        $this->assertEquals('555-1234', $payee->phone);
    }

    /**
     * Test AccountInfo entity
     */
    public function testAccountInfoEntity()
    {
        $accountInfo = new AccountInfo();
        $accountInfo->desc = 'Primary Checking';
        $accountInfo->number = '1234567890';
        
        $this->assertEquals('Primary Checking', $accountInfo->desc);
        $this->assertEquals('1234567890', $accountInfo->number);
    }

    /**
     * Test Transaction with bankAccountTo transfer
     */
    public function testTransactionWithBankAccountTo()
    {
        $transaction = new Transaction();
        $transaction->type = 'XFER';
        $transaction->amount = -500.00;
        
        $transaction->bankAccountTo = [
            'routingNumber' => '987654321',
            'accountNumber' => '0987654321',
            'accountType' => 'SAVINGS'
        ];
        
        $this->assertEquals('XFER', $transaction->type);
        $this->assertIsArray($transaction->bankAccountTo);
        $this->assertEquals('987654321', $transaction->bankAccountTo['routingNumber']);
        $this->assertEquals('0987654321', $transaction->bankAccountTo['accountNumber']);
        $this->assertEquals('SAVINGS', $transaction->bankAccountTo['accountType']);
    }

    /**
     * Test Transaction with cardAccountTo
     */
    public function testTransactionWithCardAccountTo()
    {
        $transaction = new Transaction();
        $transaction->type = 'XFER';
        $transaction->amount = -200.00;
        
        $transaction->cardAccountTo = [
            'accountNumber' => '4111111111111111'
        ];
        
        $this->assertIsArray($transaction->cardAccountTo);
        $this->assertEquals('4111111111111111', $transaction->cardAccountTo['accountNumber']);
    }

    /**
     * Test Transaction all date fields
     */
    public function testTransactionAllDateFields()
    {
        $transaction = new Transaction();
        
        $transaction->date = new \DateTime('2026-01-15 10:00:00');
        $transaction->userInitiatedDate = new \DateTime('2026-01-14 15:30:00');
        
        $this->assertInstanceOf(\DateTimeInterface::class, $transaction->date);
        $this->assertInstanceOf(\DateTimeInterface::class, $transaction->userInitiatedDate);
        
        // Posted date is later than user-initiated date
        $this->assertGreaterThan(
            $transaction->userInitiatedDate->getTimestamp(),
            $transaction->date->getTimestamp()
        );
    }

    /**
     * Test BankAccount with Statement
     */
    public function testBankAccountWithStatement()
    {
        $account = new BankAccount();
        $account->accountNumber = '1234567890';
        
        $statement = new Statement();
        $statement->currency = 'USD';
        $statement->startDate = new \DateTime('2026-01-01');
        $statement->endDate = new \DateTime('2026-01-15');
        $statement->transactions = [];
        
        $account->statement = $statement;
        
        $this->assertInstanceOf(Statement::class, $account->statement);
        $this->assertEquals('USD', $account->statement->currency);
        $this->assertIsArray($account->statement->transactions);
    }

    /**
     * Test empty collections are arrays
     */
    public function testEmptyCollections()
    {
        $statement = new Statement();
        $statement->transactions = [];
        
        $this->assertIsArray($statement->transactions);
        $this->assertEmpty($statement->transactions);
        $this->assertCount(0, $statement->transactions);
    }

    /**
     * Test Transaction with originalCurrency
     */
    public function testTransactionWithOriginalCurrency()
    {
        $transaction = new Transaction();
        $transaction->amount = 118.00; // USD after conversion
        
        // Original transaction was in EUR
        $transaction->originalCurrency = [
            'code' => 'EUR',
            'rate' => 1.18
        ];
        
        // Current transaction is in GBP
        $transaction->currency = [
            'code' => 'GBP',
            'rate' => 0.85
        ];
        
        $this->assertIsArray($transaction->originalCurrency);
        $this->assertIsArray($transaction->currency);
        $this->assertEquals('EUR', $transaction->originalCurrency['code']);
        $this->assertEquals('GBP', $transaction->currency['code']);
    }
}
