<?php declare(strict_types=1);

namespace OfxParser\Parsers;

use PHPUnit\Framework\TestCase;
use OfxParser\Parser;
use OfxParser\Entities\Loan\LoanAccount;

/**
 * Test Loan Account (LOANMSGSRSV1) Parsing
 * 
 * TDD: Tests written first to define expected behavior
 */
class LoanAccountTest extends TestCase
{
    /**
     * Test parsing basic loan account information
     */
    public function testParseLoanAccountBasicInformation(): void
    {
        $ofxContent = <<<OFX
OFXHEADER:100
DATA:OFXSGML
VERSION:102
SECURITY:NONE
ENCODING:USASCII
CHARSET:1252
COMPRESSION:NONE
OLDFILEUID:NONE
NEWFILEUID:NONE

<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<DTSERVER>20240115120000
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
<LOANMSGSRSV1>
<LOANSTMTTRNRS>
<TRNUID>1001
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<LOANSTMTRS>
<CURDEF>CAD
<LOANACCTFROM>
<LOANACCTID>LOAN-123456
<LOANACCTTYPE>MORTGAGE
</LOANACCTFROM>
<LOANBAL>
<BALLIST>
<BAL>
<NAME>PRINCIPAL
<DESC>Principal Balance
<BALTYPE>PRINCIPAL
<VALUE>250000.00
<DTASOF>20240115
</BAL>
</BALLIST>
</LOANBAL>
<LOANRATE>
<LOANINTRATE>3.5
<DTASOF>20240115
</LOANRATE>
</LOANSTMTRS>
</LOANSTMTTRNRS>
</LOANMSGSRSV1>
</OFX>
OFX;

        $parser = new Parser();
        $ofx = $parser->loadFromString($ofxContent);
        
        $this->assertNotNull($ofx->loanAccounts);
        $this->assertCount(1, $ofx->loanAccounts);
        
        $loan = $ofx->loanAccounts[0];
        $this->assertInstanceOf(LoanAccount::class, $loan);
        $this->assertEquals('LOAN-123456', $loan->accountNumber);
        $this->assertEquals('MORTGAGE', $loan->accountType);
        $this->assertEquals('CAD', $loan->currency);
        $this->assertEquals(250000.00, $loan->principalBalance);
        $this->assertEquals(3.5, $loan->interestRate);
    }
    
    /**
     * Test parsing car loan with payment information
     */
    public function testParseCarLoanWithPaymentInfo(): void
    {
        $ofxContent = <<<OFX
OFXHEADER:100
DATA:OFXSGML
VERSION:102
SECURITY:NONE
ENCODING:USASCII
CHARSET:1252
COMPRESSION:NONE
OLDFILEUID:NONE
NEWFILEUID:NONE

<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<DTSERVER>20240115120000
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
<LOANMSGSRSV1>
<LOANSTMTTRNRS>
<TRNUID>1001
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<LOANSTMTRS>
<CURDEF>CAD
<LOANACCTFROM>
<LOANACCTID>AUTO-789012
<LOANACCTTYPE>AUTO
</LOANACCTFROM>
<LOANBAL>
<BALLIST>
<BAL>
<NAME>PRINCIPAL
<DESC>Principal Balance
<BALTYPE>PRINCIPAL
<VALUE>25000.00
<DTASOF>20240115
</BAL>
</BALLIST>
</LOANBAL>
<LOANRATE>
<LOANINTRATE>5.99
<DTASOF>20240115
</LOANRATE>
<LOANPMTINFO>
<LOANPMT>500.00
<LOANNEXTPMT>20240201
<LOANPMTFREQ>MONTHLY
</LOANPMTINFO>
<LOANREMAINING>
<LOANPRINCIPAL>25000.00
<LOANINTEREST>2500.00
</LOANREMAINING>
</LOANSTMTRS>
</LOANSTMTTRNRS>
</LOANMSGSRSV1>
</OFX>
OFX;

        $parser = new Parser();
        $ofx = $parser->loadFromString($ofxContent);
        
        $loan = $ofx->loanAccounts[0];
        $this->assertEquals('AUTO-789012', $loan->accountNumber);
        $this->assertEquals('AUTO', $loan->accountType);
        $this->assertEquals(25000.00, $loan->principalBalance);
        $this->assertEquals(5.99, $loan->interestRate);
        $this->assertEquals(500.00, $loan->paymentAmount);
        $this->assertInstanceOf(\DateTimeInterface::class, $loan->nextPaymentDate);
        $this->assertEquals('2024-02-01', $loan->nextPaymentDate->format('Y-m-d'));
        $this->assertEquals('MONTHLY', $loan->paymentFrequency);
        $this->assertEquals(2500.00, $loan->remainingInterest);
    }
    
    /**
     * Test parsing line of credit
     */
    public function testParseLineOfCredit(): void
    {
        $ofxContent = <<<OFX
OFXHEADER:100
DATA:OFXSGML
VERSION:102
SECURITY:NONE
ENCODING:USASCII
CHARSET:1252
COMPRESSION:NONE
OLDFILEUID:NONE
NEWFILEUID:NONE

<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<DTSERVER>20240115120000
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
<LOANMSGSRSV1>
<LOANSTMTTRNRS>
<TRNUID>1001
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<LOANSTMTRS>
<CURDEF>CAD
<LOANACCTFROM>
<LOANACCTID>LOC-456789
<LOANACCTTYPE>LINEOFCREDIT
</LOANACCTFROM>
<LOANBAL>
<BALLIST>
<BAL>
<NAME>PRINCIPAL
<DESC>Current Balance
<BALTYPE>PRINCIPAL
<VALUE>15000.00
<DTASOF>20240115
</BAL>
<BAL>
<NAME>AVAILABLE
<DESC>Available Credit
<BALTYPE>AVAILABLE
<VALUE>35000.00
<DTASOF>20240115
</BAL>
</BALLIST>
</LOANBAL>
<LOANRATE>
<LOANINTRATE>6.45
<DTASOF>20240115
</LOANRATE>
<LOANINITBAL>50000.00
</LOANSTMTRS>
</LOANSTMTTRNRS>
</LOANMSGSRSV1>
</OFX>
OFX;

        $parser = new Parser();
        $ofx = $parser->loadFromString($ofxContent);
        
        $loan = $ofx->loanAccounts[0];
        $this->assertEquals('LOC-456789', $loan->accountNumber);
        $this->assertEquals('LINEOFCREDIT', $loan->accountType);
        $this->assertEquals(15000.00, $loan->principalBalance);
        $this->assertEquals(35000.00, $loan->availableCredit);
        $this->assertEquals(6.45, $loan->interestRate);
        $this->assertEquals(50000.00, $loan->creditLimit);
    }
    
    /**
     * Test parsing loan with term information
     */
    public function testParseLoanWithTermInformation(): void
    {
        $ofxContent = <<<OFX
OFXHEADER:100
DATA:OFXSGML
VERSION:102
SECURITY:NONE
ENCODING:USASCII
CHARSET:1252
COMPRESSION:NONE
OLDFILEUID:NONE
NEWFILEUID:NONE

<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<DTSERVER>20240115120000
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
<LOANMSGSRSV1>
<LOANSTMTTRNRS>
<TRNUID>1001
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<LOANSTMTRS>
<CURDEF>CAD
<LOANACCTFROM>
<LOANACCTID>MORT-111222
<LOANACCTTYPE>MORTGAGE
</LOANACCTFROM>
<LOANBAL>
<BALLIST>
<BAL>
<NAME>PRINCIPAL
<DESC>Principal Balance
<BALTYPE>PRINCIPAL
<VALUE>300000.00
<DTASOF>20240115
</BAL>
</BALLIST>
</LOANBAL>
<LOANRATE>
<LOANINTRATE>4.25
<DTASOF>20240115
</LOANRATE>
<LOANPMTINFO>
<LOANPMT>1800.00
<LOANNEXTPMT>20240201
<LOANPMTFREQ>MONTHLY
<LOANPMTSREMAINING>180
</LOANPMTINFO>
<LOANMATURITYDATE>20390115
<LOANINITBAL>350000.00
</LOANSTMTRS>
</LOANSTMTTRNRS>
</LOANMSGSRSV1>
</OFX>
OFX;

        $parser = new Parser();
        $ofx = $parser->loadFromString($ofxContent);
        
        $loan = $ofx->loanAccounts[0];
        $this->assertEquals(300000.00, $loan->principalBalance);
        $this->assertEquals(1800.00, $loan->paymentAmount);
        $this->assertEquals(180, $loan->paymentsRemaining);
        $this->assertInstanceOf(\DateTimeInterface::class, $loan->maturityDate);
        $this->assertEquals('2039-01-15', $loan->maturityDate->format('Y-m-d'));
        $this->assertEquals(350000.00, $loan->initialBalance);
    }
    
    /**
     * Test parsing loan with transaction history
     */
    public function testParseLoanWithTransactionHistory(): void
    {
        $ofxContent = <<<OFX
OFXHEADER:100
DATA:OFXSGML
VERSION:102
SECURITY:NONE
ENCODING:USASCII
CHARSET:1252
COMPRESSION:NONE
OLDFILEUID:NONE
NEWFILEUID:NONE

<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<DTSERVER>20240115120000
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
<LOANMSGSRSV1>
<LOANSTMTTRNRS>
<TRNUID>1001
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<LOANSTMTRS>
<CURDEF>CAD
<LOANACCTFROM>
<LOANACCTID>LOAN-999888
<LOANACCTTYPE>PERSONAL
</LOANACCTFROM>
<LOANTRANLIST>
<DTSTART>20240101
<DTEND>20240131
<STMTTRN>
<TRNTYPE>PAYMENT
<DTPOSTED>20240101
<TRNAMT>-500.00
<FITID>TXN001
<NAME>Monthly Payment
<MEMO>Principal: $400.00, Interest: $100.00
</STMTTRN>
</LOANTRANLIST>
<LOANBAL>
<BALLIST>
<BAL>
<NAME>PRINCIPAL
<DESC>Principal Balance
<BALTYPE>PRINCIPAL
<VALUE>19600.00
<DTASOF>20240131
</BAL>
</BALLIST>
</LOANBAL>
<LOANRATE>
<LOANINTRATE>7.5
<DTASOF>20240115
</LOANRATE>
</LOANSTMTRS>
</LOANSTMTTRNRS>
</LOANMSGSRSV1>
</OFX>
OFX;

        $parser = new Parser();
        $ofx = $parser->loadFromString($ofxContent);
        
        $loan = $ofx->loanAccounts[0];
        $this->assertEquals('LOAN-999888', $loan->accountNumber);
        $this->assertEquals('PERSONAL', $loan->accountType);
        
        $this->assertNotNull($loan->statement);
        $this->assertNotNull($loan->statement->transactions);
        $this->assertCount(1, $loan->statement->transactions);
        
        $transaction = $loan->statement->transactions[0];
        $this->assertEquals('PAYMENT', $transaction->type);
        $this->assertEquals(-500.00, $transaction->amount);
        $this->assertEquals('Monthly Payment', $transaction->name);
        $this->assertEquals('Principal: $400.00, Interest: $100.00', $transaction->memo);
    }
    
    /**
     * Test parsing multiple loan accounts
     */
    public function testParseMultipleLoanAccounts(): void
    {
        $ofxContent = <<<OFX
OFXHEADER:100
DATA:OFXSGML
VERSION:102
SECURITY:NONE
ENCODING:USASCII
CHARSET:1252
COMPRESSION:NONE
OLDFILEUID:NONE
NEWFILEUID:NONE

<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<DTSERVER>20240115120000
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
<LOANMSGSRSV1>
<LOANSTMTTRNRS>
<TRNUID>1001
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<LOANSTMTRS>
<CURDEF>CAD
<LOANACCTFROM>
<LOANACCTID>MORT-111
<LOANACCTTYPE>MORTGAGE
</LOANACCTFROM>
<LOANBAL>
<BALLIST>
<BAL>
<NAME>PRINCIPAL
<BALTYPE>PRINCIPAL
<VALUE>250000.00
<DTASOF>20240115
</BAL>
</BALLIST>
</LOANBAL>
<LOANRATE>
<LOANINTRATE>3.5
<DTASOF>20240115
</LOANRATE>
</LOANSTMTRS>
</LOANSTMTTRNRS>
<LOANSTMTTRNRS>
<TRNUID>1002
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<LOANSTMTRS>
<CURDEF>CAD
<LOANACCTFROM>
<LOANACCTID>AUTO-222
<LOANACCTTYPE>AUTO
</LOANACCTFROM>
<LOANBAL>
<BALLIST>
<BAL>
<NAME>PRINCIPAL
<BALTYPE>PRINCIPAL
<VALUE>20000.00
<DTASOF>20240115
</BAL>
</BALLIST>
</LOANBAL>
<LOANRATE>
<LOANINTRATE>6.5
<DTASOF>20240115
</LOANRATE>
</LOANSTMTRS>
</LOANSTMTTRNRS>
</LOANMSGSRSV1>
</OFX>
OFX;

        $parser = new Parser();
        $ofx = $parser->loadFromString($ofxContent);
        
        $this->assertCount(2, $ofx->loanAccounts);
        $this->assertEquals('MORT-111', $ofx->loanAccounts[0]->accountNumber);
        $this->assertEquals('MORTGAGE', $ofx->loanAccounts[0]->accountType);
        $this->assertEquals('AUTO-222', $ofx->loanAccounts[1]->accountNumber);
        $this->assertEquals('AUTO', $ofx->loanAccounts[1]->accountType);
    }
    
    /**
     * Test XML format loan account parsing
     */
    public function testParseXmlFormatLoanAccount(): void
    {
        $ofxContent = <<<OFX
<?xml version="1.0" encoding="UTF-8"?>
<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS>
<CODE>0</CODE>
<SEVERITY>INFO</SEVERITY>
</STATUS>
<DTSERVER>20240115120000</DTSERVER>
<LANGUAGE>ENG</LANGUAGE>
</SONRS>
</SIGNONMSGSRSV1>
<LOANMSGSRSV1>
<LOANSTMTTRNRS>
<TRNUID>1001</TRNUID>
<STATUS>
<CODE>0</CODE>
<SEVERITY>INFO</SEVERITY>
</STATUS>
<LOANSTMTRS>
<CURDEF>CAD</CURDEF>
<LOANACCTFROM>
<LOANACCTID>LOAN-XML</LOANACCTID>
<LOANACCTTYPE>MORTGAGE</LOANACCTTYPE>
</LOANACCTFROM>
<LOANBAL>
<BALLIST>
<BAL>
<NAME>PRINCIPAL</NAME>
<BALTYPE>PRINCIPAL</BALTYPE>
<VALUE>200000.00</VALUE>
<DTASOF>20240115</DTASOF>
</BAL>
</BALLIST>
</LOANBAL>
<LOANRATE>
<LOANINTRATE>4.0</LOANINTRATE>
<DTASOF>20240115</DTASOF>
</LOANRATE>
</LOANSTMTRS>
</LOANSTMTTRNRS>
</LOANMSGSRSV1>
</OFX>
OFX;

        $parser = new Parser();
        $ofx = $parser->loadFromString($ofxContent);
        
        $this->assertNotNull($ofx->loanAccounts);
        $this->assertCount(1, $ofx->loanAccounts);
        
        $loan = $ofx->loanAccounts[0];
        $this->assertEquals('LOAN-XML', $loan->accountNumber);
        $this->assertEquals('MORTGAGE', $loan->accountType);
        $this->assertEquals(200000.00, $loan->principalBalance);
        $this->assertEquals(4.0, $loan->interestRate);
    }
    
    /**
     * Test OFX without loan accounts
     */
    public function testOfxWithoutLoanAccounts(): void
    {
        $ofxContent = <<<OFX
OFXHEADER:100
DATA:OFXSGML
VERSION:102
SECURITY:NONE
ENCODING:USASCII
CHARSET:1252
COMPRESSION:NONE
OLDFILEUID:NONE
NEWFILEUID:NONE

<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<DTSERVER>20240115120000
<LANGUAGE>ENG
</SONRS>
</SIGNONMSGSRSV1>
<BANKMSGSRSV1>
<STMTTRNRS>
<TRNUID>1001
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<STMTRS>
<CURDEF>CAD
<BANKACCTFROM>
<BANKID>123456
<ACCTID>098765
<ACCTTYPE>CHECKING
</BANKACCTFROM>
<BANKTRANLIST>
<DTSTART>20240101
<DTEND>20240131
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</BANKMSGSRSV1>
</OFX>
OFX;

        $parser = new Parser();
        $ofx = $parser->loadFromString($ofxContent);
        
        $this->assertEmpty($ofx->loanAccounts);
    }
}
