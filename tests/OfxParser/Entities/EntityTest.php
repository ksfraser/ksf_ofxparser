<?php

namespace OfxParserTest\Entities;

use PHPUnit\Framework\TestCase;
use OfxParser\Entities\Transaction;
use OfxParser\Entities\Status;
use OfxParser\Entities\BankAccount;
use OfxParser\Entities\AccountInfo;
use OfxParser\Entities\Institute;
use OfxParser\Entities\SignOn;
use OfxParser\Entities\Statement;
use OfxParser\Entities\Payee;

/**
 * @covers OfxParser\Entities\Transaction
 * @covers OfxParser\Entities\Status
 * @covers OfxParser\Entities\BankAccount
 * @covers OfxParser\Entities\AccountInfo
 * @covers OfxParser\Entities\Institute
 * @covers OfxParser\Entities\SignOn
 * @covers OfxParser\Entities\Statement
 * @covers OfxParser\Entities\Payee
 */
class EntityTest extends TestCase
{
    public function testTransactionTypeDesc()
    {
        $transaction = new Transaction();
        
        $transaction->type = 'CREDIT';
        self::assertEquals('Generic credit', $transaction->typeDesc);
        
        $transaction->type = 'DEBIT';
        self::assertEquals('Generic debit', $transaction->typeDesc);
        
        $transaction->type = 'INT';
        self::assertEquals('Interest earned or paid', $transaction->typeDesc);
        
        $transaction->type = 'DIV';
        self::assertEquals('Dividend', $transaction->typeDesc);
        
        $transaction->type = 'FEE';
        self::assertEquals('FI fee', $transaction->typeDesc);
        
        $transaction->type = 'SRVCHG';
        self::assertEquals('Service charge', $transaction->typeDesc);
        
        $transaction->type = 'DEP';
        self::assertEquals('Deposit', $transaction->typeDesc);
        
        $transaction->type = 'ATM';
        self::assertEquals('ATM debit or credit', $transaction->typeDesc);
        
        $transaction->type = 'POS';
        self::assertEquals('Point of sale debit or credit', $transaction->typeDesc);
        
        $transaction->type = 'XFER';
        self::assertEquals('Transfer', $transaction->typeDesc);
        
        $transaction->type = 'CHECK';
        self::assertEquals('Cheque', $transaction->typeDesc);
        
        $transaction->type = 'PAYMENT';
        self::assertEquals('Electronic payment', $transaction->typeDesc);
        
        $transaction->type = 'CASH';
        self::assertEquals('Cash withdrawal', $transaction->typeDesc);
        
        $transaction->type = 'DIRECTDEP';
        self::assertEquals('Direct deposit', $transaction->typeDesc);
        
        $transaction->type = 'DIRECTDEBIT';
        self::assertEquals('Merchant initiated debit', $transaction->typeDesc);
        
        $transaction->type = 'REPEATPMT';
        self::assertEquals('Repeating payment/standing order', $transaction->typeDesc);
        
        $transaction->type = 'OTHER';
        self::assertEquals('Other', $transaction->typeDesc);
        
        $transaction->type = 'UNKNOWN';
        self::assertEquals('Unknown', $transaction->typeDesc);
    }

    public function testTransactionProperties()
    {
        $transaction = new Transaction();
        
        $transaction->type = 'DEBIT';
        $transaction->date = new \DateTime('2020-01-15');
        $transaction->userInitiatedDate = new \DateTime('2020-01-14');
        $transaction->amount = -50.00;
        $transaction->uniqueId = 'TXN123';
        $transaction->name = 'Test Transaction';
        $transaction->memo = 'Test memo';
        $transaction->sic = '1234';
        $transaction->checkNumber = '5678';
        $transaction->refNumber = 'REF123';
        $transaction->nameExtended = 'Extended Name';
        $transaction->payeeId = 'PAYEE456';
        
        self::assertEquals('DEBIT', $transaction->type);
        self::assertEquals('2020-01-15', $transaction->date->format('Y-m-d'));
        self::assertEquals(-50.00, $transaction->amount);
        self::assertEquals('TXN123', $transaction->uniqueId);
        self::assertEquals('Test Transaction', $transaction->name);
        self::assertEquals('Test memo', $transaction->memo);
        self::assertEquals('1234', $transaction->sic);
        self::assertEquals('5678', $transaction->checkNumber);
        self::assertEquals('REF123', $transaction->refNumber);
        self::assertEquals('Extended Name', $transaction->nameExtended);
        self::assertEquals('PAYEE456', $transaction->payeeId);
    }

    public function testTransactionWithPayee()
    {
        $transaction = new Transaction();
        $payee = new Payee();
        $payee->name = 'Test Payee';
        $payee->city = 'Springfield';
        
        $transaction->payee = $payee;
        
        self::assertInstanceOf(Payee::class, $transaction->payee);
        self::assertEquals('Test Payee', $transaction->payee->name);
        self::assertEquals('Springfield', $transaction->payee->city);
    }

    public function testTransactionWithBankAccountTo()
    {
        $transaction = new Transaction();
        $bankAccountTo = new BankAccount();
        $bankAccountTo->accountNumber = '1234567890';
        $bankAccountTo->routingNumber = '987654321';
        
        $transaction->bankAccountTo = $bankAccountTo;
        
        self::assertInstanceOf(BankAccount::class, $transaction->bankAccountTo);
        self::assertEquals('1234567890', $transaction->bankAccountTo->accountNumber);
    }

    public function testStatusCodeDesc()
    {
        $status = new Status();
        
        $status->code = 0;
        self::assertEquals('Success', $status->codeDesc);
        
        $status->code = 2000;
        self::assertEquals('General error', $status->codeDesc);
        
        $status->code = 15500;
        self::assertEquals('Signon invalid', $status->codeDesc);
        
        $status->code = 15501;
        self::assertEquals('Customer account already in use', $status->codeDesc);
        
        $status->code = 999999;
        self::assertEquals('', $status->codeDesc);
    }

    public function testStatusProperties()
    {
        $status = new Status();
        
        $status->code = 0;
        $status->severity = 'INFO';
        $status->message = 'Operation successful';
        
        self::assertEquals(0, $status->code);
        self::assertEquals('INFO', $status->severity);
        self::assertEquals('Operation successful', $status->message);
        self::assertEquals('Success', $status->codeDesc);
    }

    public function testBankAccountProperties()
    {
        $account = new BankAccount();
        
        $account->transactionUid = 'TXN-UID-123';
        $account->agencyNumber = 'AG001';
        $account->accountNumber = '1234567890';
        $account->routingNumber = '987654321';
        $account->accountType = 'CHECKING';
        $account->balance = 1000.00;
        $account->balanceDate = new \DateTime('2020-01-15');
        
        self::assertEquals('TXN-UID-123', $account->transactionUid);
        self::assertEquals('AG001', $account->agencyNumber);
        self::assertEquals('1234567890', $account->accountNumber);
        self::assertEquals('987654321', $account->routingNumber);
        self::assertEquals('CHECKING', $account->accountType);
        self::assertEquals(1000.00, $account->balance);
        self::assertEquals('2020-01-15', $account->balanceDate->format('Y-m-d'));
    }

    public function testBankAccountWithStatement()
    {
        $account = new BankAccount();
        $statement = new Statement();
        $statement->currency = 'USD';
        $statement->startDate = new \DateTime('2020-01-01');
        $statement->endDate = new \DateTime('2020-01-31');
        
        $account->statement = $statement;
        
        self::assertInstanceOf(Statement::class, $account->statement);
        self::assertEquals('USD', $account->statement->currency);
    }

    public function testAccountInfoProperties()
    {
        $accountInfo = new AccountInfo();
        
        $accountInfo->desc = 'My Checking Account';
        $accountInfo->number = '1234567890';
        
        self::assertEquals('My Checking Account', $accountInfo->desc);
        self::assertEquals('1234567890', $accountInfo->number);
    }

    public function testInstituteProperties()
    {
        $institute = new Institute();
        
        $institute->name = 'Test Bank';
        $institute->id = 'BANK123';
        
        self::assertEquals('Test Bank', $institute->name);
        self::assertEquals('BANK123', $institute->id);
    }

    public function testSignOnProperties()
    {
        $signOn = new SignOn();
        
        $status = new Status();
        $status->code = 0;
        $signOn->status = $status;
        
        $signOn->date = new \DateTime('2020-01-15 12:00:00');
        $signOn->language = 'ENG';
        
        $institute = new Institute();
        $institute->name = 'Test Bank';
        $signOn->institute = $institute;
        
        self::assertInstanceOf(Status::class, $signOn->status);
        self::assertEquals('2020-01-15 12:00:00', $signOn->date->format('Y-m-d H:i:s'));
        self::assertEquals('ENG', $signOn->language);
        self::assertInstanceOf(Institute::class, $signOn->institute);
        self::assertEquals('Test Bank', $signOn->institute->name);
    }

    public function testStatementProperties()
    {
        $statement = new Statement();
        
        $statement->currency = 'USD';
        $statement->startDate = new \DateTime('2020-01-01');
        $statement->endDate = new \DateTime('2020-01-31');
        $statement->transactions = [];
        
        self::assertEquals('USD', $statement->currency);
        self::assertEquals('2020-01-01', $statement->startDate->format('Y-m-d'));
        self::assertEquals('2020-01-31', $statement->endDate->format('Y-m-d'));
        self::assertIsArray($statement->transactions);
    }

    public function testStatementWithTransactions()
    {
        $statement = new Statement();
        
        $transaction1 = new Transaction();
        $transaction1->uniqueId = 'TXN1';
        $transaction1->amount = -50.00;
        
        $transaction2 = new Transaction();
        $transaction2->uniqueId = 'TXN2';
        $transaction2->amount = 100.00;
        
        $statement->transactions = [$transaction1, $transaction2];
        
        self::assertCount(2, $statement->transactions);
        self::assertEquals('TXN1', $statement->transactions[0]->uniqueId);
        self::assertEquals(-50.00, $statement->transactions[0]->amount);
    }

    public function testPayeeProperties()
    {
        $payee = new Payee();
        
        $payee->name = 'John Doe';
        $payee->address = ['123 Main St', 'Apt 4B'];
        $payee->city = 'Springfield';
        $payee->state = 'IL';
        $payee->postalCode = '62701';
        $payee->phone = '555-1234';
        
        self::assertEquals('John Doe', $payee->name);
        self::assertIsArray($payee->address);
        self::assertCount(2, $payee->address);
        self::assertEquals('123 Main St', $payee->address[0]);
        self::assertEquals('Springfield', $payee->city);
        self::assertEquals('IL', $payee->state);
        self::assertEquals('62701', $payee->postalCode);
        self::assertEquals('555-1234', $payee->phone);
    }

    public function testPayeeWithMinimalData()
    {
        $payee = new Payee();
        $payee->name = 'Minimal Payee';
        
        self::assertEquals('Minimal Payee', $payee->name);
        self::assertNull($payee->address);
        self::assertNull($payee->city);
        self::assertNull($payee->state);
        self::assertNull($payee->postalCode);
        self::assertNull($payee->phone);
    }
}
