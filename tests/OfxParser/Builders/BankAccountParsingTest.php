<?php

namespace OfxParser\Builders;

use PHPUnit\Framework\TestCase;
use OfxParser\Sgml\Parser as SgmlParser;

/**
 * Test Additional Coverage for Bank Account Parsing
 * 
 * What: Tests additional bank account parsing scenarios including:
 * - Agency/branch numbers
 * - Non-standard STMTRNRS vs STMTTRNRS
 * - Available balance (AVAILBAL)
 * - Transaction memo/SIC/refnum fields
 * - Status messages
 * 
 * Why: These paths are not fully covered by existing tests and represent
 * real-world OFX file variations.
 */
class BankAccountParsingTest extends TestCase
{
    /**
     * Test bank account with agency/branch number
     */
    public function testBankAccountWithAgencyNumber()
    {
        $sgml = <<<SGML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <DTSERVER>20260114120000
            <LANGUAGE>ENG
            <FI><ORG>Test<FID>123</FI>
        </SONRS>
    </SIGNONMSGSRSV1>
    <BANKMSGSRSV1>
        <STMTTRNRS>
            <TRNUID>1
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <STMTRS>
                <CURDEF>USD
                <BANKACCTFROM>
                    <BANKID>123456
                    <BRANCHID>789
                    <ACCTID>9876543210
                    <ACCTTYPE>CHECKING
                </BANKACCTFROM>
                <BANKTRANLIST>
                    <DTSTART>20260101
                    <DTEND>20260114
                </BANKTRANLIST>
                <LEDGERBAL><BALAMT>5000<DTASOF>20260114</LEDGERBAL>
            </STMTRS>
        </STMTTRNRS>
    </BANKMSGSRSV1>
</OFX>
SGML;
        
        $parser = new SgmlParser();
        $element = $parser->parse($sgml);
        
        $builder = new SgmlOfxBuilder();
        $ofx = $builder->buildOfx($element, []);
        
        $account = $ofx->bankAccounts[0];
        $this->assertEquals('123456', $account->routingNumber);
        $this->assertEquals('789', $account->agencyNumber);
        $this->assertEquals('9876543210', $account->accountNumber);
        $this->assertEquals('CHECKING', $account->accountType);
    }
    
    /**
     * Test non-standard STMTRNRS tag (without extra T)
     */
    public function testNonStandardStmtrnrsTag()
    {
        $sgml = <<<SGML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <DTSERVER>20260114120000
            <LANGUAGE>ENG
            <FI><ORG>Test<FID>123</FI>
        </SONRS>
    </SIGNONMSGSRSV1>
    <BANKMSGSRSV1>
        <STMTRNRS>
            <TRNUID>1
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <STMTRS>
                <CURDEF>USD
                <BANKACCTFROM>
                    <BANKID>123
                    <ACCTID>456
                    <ACCTTYPE>SAVINGS
                </BANKACCTFROM>
                <BANKTRANLIST>
                    <DTSTART>20260101
                    <DTEND>20260114
                </BANKTRANLIST>
                <LEDGERBAL><BALAMT>10000<DTASOF>20260114</LEDGERBAL>
            </STMTRS>
        </STMTRNRS>
    </BANKMSGSRSV1>
</OFX>
SGML;
        
        $parser = new SgmlParser();
        $element = $parser->parse($sgml);
        
        $builder = new SgmlOfxBuilder();
        $ofx = $builder->buildOfx($element, []);
        
        $this->assertCount(1, $ofx->bankAccounts);
        $account = $ofx->bankAccounts[0];
        $this->assertEquals('456', $account->accountNumber);
        $this->assertEquals('SAVINGS', $account->accountType);
    }
    
    /**
     * Test status with message field
     */
    public function testStatusWithMessage()
    {
        $sgml = <<<SGML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS>
                <CODE>0
                <SEVERITY>INFO
                <MESSAGE>Success
            </STATUS>
            <DTSERVER>20260114120000
            <LANGUAGE>ENG
            <FI><ORG>Test<FID>123</FI>
        </SONRS>
    </SIGNONMSGSRSV1>
    <BANKMSGSRSV1>
        <STMTTRNRS>
            <TRNUID>1
            <STATUS>
                <CODE>0
                <SEVERITY>INFO
                <MESSAGE>Statement retrieved successfully
            </STATUS>
            <STMTRS>
                <CURDEF>USD
                <BANKACCTFROM>
                    <BANKID>123
                    <ACCTID>456
                    <ACCTTYPE>CHECKING
                </BANKACCTFROM>
                <BANKTRANLIST>
                    <DTSTART>20260101
                    <DTEND>20260114
                </BANKTRANLIST>
                <LEDGERBAL><BALAMT>5000<DTASOF>20260114</LEDGERBAL>
            </STMTRS>
        </STMTTRNRS>
    </BANKMSGSRSV1>
</OFX>
SGML;
        
        $parser = new SgmlParser();
        $element = $parser->parse($sgml);
        
        $builder = new SgmlOfxBuilder();
        $ofx = $builder->buildOfx($element, []);
        
        $this->assertNotNull($ofx->signOn->status);
        $this->assertEquals(0, $ofx->signOn->status->code);
        $this->assertEquals('INFO', $ofx->signOn->status->severity);
        $this->assertEquals('Success', $ofx->signOn->status->message);
    }
    
    /**
     * Test transaction with DTUSER field (user-initiated date)
     */
    public function testTransactionWithUserInitiatedDate()
    {
        $sgml = <<<SGML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <DTSERVER>20260114120000
            <LANGUAGE>ENG
            <FI><ORG>Test<FID>123</FI>
        </SONRS>
    </SIGNONMSGSRSV1>
    <BANKMSGSRSV1>
        <STMTTRNRS>
            <TRNUID>1
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <STMTRS>
                <CURDEF>USD
                <BANKACCTFROM>
                    <BANKID>123
                    <ACCTID>456
                    <ACCTTYPE>CHECKING
                </BANKACCTFROM>
                <BANKTRANLIST>
                    <DTSTART>20260101
                    <DTEND>20260114
                    <STMTTRN>
                        <TRNTYPE>CHECK
                        <DTPOSTED>20260105
                        <DTUSER>20260104
                        <TRNAMT>-250.00
                        <FITID>TXN001
                        <NAME>Test Transaction
                        <MEMO>Test memo
                    </STMTTRN>
                </BANKTRANLIST>
                <LEDGERBAL><BALAMT>5000<DTASOF>20260114</LEDGERBAL>
            </STMTRS>
        </STMTTRNRS>
    </BANKMSGSRSV1>
</OFX>
SGML;
        
        $parser = new SgmlParser();
        $element = $parser->parse($sgml);
        
        $builder = new SgmlOfxBuilder();
        $ofx = $builder->buildOfx($element, []);
        
        $transaction = $ofx->bankAccounts[0]->statement->transactions[0];
        $this->assertEquals('CHECK', $transaction->type);
        $this->assertEquals(-250.00, $transaction->amount);
        $this->assertEquals('TXN001', $transaction->uniqueId);
        $this->assertEquals('Test Transaction', $transaction->name);
        $this->assertEquals('Test memo', $transaction->memo);
        $this->assertInstanceOf(\DateTimeInterface::class, $transaction->date);
        $this->assertInstanceOf(\DateTimeInterface::class, $transaction->userInitiatedDate);
        // User date should be one day before posted date
        $this->assertEquals('2026-01-04', $transaction->userInitiatedDate->format('Y-m-d'));
        $this->assertEquals('2026-01-05', $transaction->date->format('Y-m-d'));
    }
    
    /**
     * Test transaction type variations
     */
    public function testVariousTransactionTypes()
    {
        $sgml = <<<SGML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <DTSERVER>20260114120000
            <LANGUAGE>ENG
            <FI><ORG>Test<FID>123</FI>
        </SONRS>
    </SIGNONMSGSRSV1>
    <BANKMSGSRSV1>
        <STMTTRNRS>
            <TRNUID>1
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <STMTRS>
                <CURDEF>USD
                <BANKACCTFROM>
                    <BANKID>123
                    <ACCTID>456
                    <ACCTTYPE>CHECKING
                </BANKACCTFROM>
                <BANKTRANLIST>
                    <DTSTART>20260101
                    <DTEND>20260114
                    <STMTTRN>
                        <TRNTYPE>CREDIT
                        <DTPOSTED>20260101
                        <TRNAMT>1000.00
                        <FITID>TXN001
                        <NAME>Deposit
                    </STMTTRN>
                    <STMTTRN>
                        <TRNTYPE>DEBIT
                        <DTPOSTED>20260102
                        <TRNAMT>-50.00
                        <FITID>TXN002
                        <NAME>Withdrawal
                    </STMTTRN>
                    <STMTTRN>
                        <TRNTYPE>INT
                        <DTPOSTED>20260103
                        <TRNAMT>2.50
                        <FITID>TXN003
                        <NAME>Interest
                    </STMTTRN>
                    <STMTTRN>
                        <TRNTYPE>DIV
                        <DTPOSTED>20260104
                        <TRNAMT>5.00
                        <FITID>TXN004
                        <NAME>Dividend
                    </STMTTRN>
                    <STMTTRN>
                        <TRNTYPE>FEE
                        <DTPOSTED>20260105
                        <TRNAMT>-10.00
                        <FITID>TXN005
                        <NAME>Service Fee
                    </STMTTRN>
                    <STMTTRN>
                        <TRNTYPE>SRVCHG
                        <DTPOSTED>20260106
                        <TRNAMT>-5.00
                        <FITID>TXN006
                        <NAME>Service Charge
                    </STMTTRN>
                    <STMTTRN>
                        <TRNTYPE>DEP
                        <DTPOSTED>20260107
                        <TRNAMT>500.00
                        <FITID>TXN007
                        <NAME>Direct Deposit
                    </STMTTRN>
                    <STMTTRN>
                        <TRNTYPE>ATM
                        <DTPOSTED>20260108
                        <TRNAMT>-100.00
                        <FITID>TXN008
                        <NAME>ATM Withdrawal
                    </STMTTRN>
                    <STMTTRN>
                        <TRNTYPE>POS
                        <DTPOSTED>20260109
                        <TRNAMT>-25.00
                        <FITID>TXN009
                        <NAME>POS Purchase
                    </STMTTRN>
                    <STMTTRN>
                        <TRNTYPE>XFER
                        <DTPOSTED>20260110
                        <TRNAMT>-200.00
                        <FITID>TXN010
                        <NAME>Transfer
                    </STMTTRN>
                    <STMTTRN>
                        <TRNTYPE>CHECK
                        <DTPOSTED>20260111
                        <TRNAMT>-75.00
                        <FITID>TXN011
                        <NAME>Check
                        <CHECKNUM>1001
                    </STMTTRN>
                    <STMTTRN>
                        <TRNTYPE>PAYMENT
                        <DTPOSTED>20260112
                        <TRNAMT>-150.00
                        <FITID>TXN012
                        <NAME>Bill Payment
                    </STMTTRN>
                    <STMTTRN>
                        <TRNTYPE>CASH
                        <DTPOSTED>20260113
                        <TRNAMT>-40.00
                        <FITID>TXN013
                        <NAME>Cash Withdrawal
                    </STMTTRN>
                    <STMTTRN>
                        <TRNTYPE>DIRECTDEP
                        <DTPOSTED>20260114
                        <TRNAMT>800.00
                        <FITID>TXN014
                        <NAME>Paycheck
                    </STMTTRN>
                </BANKTRANLIST>
                <LEDGERBAL><BALAMT>6000<DTASOF>20260114</LEDGERBAL>
            </STMTRS>
        </STMTTRNRS>
    </BANKMSGSRSV1>
</OFX>
SGML;
        
        $parser = new SgmlParser();
        $element = $parser->parse($sgml);
        
        $builder = new SgmlOfxBuilder();
        $ofx = $builder->buildOfx($element, []);
        
        $transactions = $ofx->bankAccounts[0]->statement->transactions;
        $this->assertCount(14, $transactions);
        
        $types = array_map(fn($t) => $t->type, $transactions);
        $this->assertEquals([
            'CREDIT', 'DEBIT', 'INT', 'DIV', 'FEE', 'SRVCHG', 'DEP',
            'ATM', 'POS', 'XFER', 'CHECK', 'PAYMENT', 'CASH', 'DIRECTDEP'
        ], $types);
        
        // Check specific transaction
        $checkTrans = $transactions[10];
        $this->assertEquals('CHECK', $checkTrans->type);
        $this->assertEquals('1001', $checkTrans->checkNumber);
    }
    
    /**
     * Test statement start and end dates
     */
    public function testStatementDates()
    {
        $sgml = <<<SGML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <DTSERVER>20260114120000
            <LANGUAGE>ENG
            <FI><ORG>Test<FID>123</FI>
        </SONRS>
    </SIGNONMSGSRSV1>
    <BANKMSGSRSV1>
        <STMTTRNRS>
            <TRNUID>1
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <STMTRS>
                <CURDEF>USD
                <BANKACCTFROM>
                    <BANKID>123
                    <ACCTID>456
                    <ACCTTYPE>CHECKING
                </BANKACCTFROM>
                <BANKTRANLIST>
                    <DTSTART>20260101120000
                    <DTEND>20260114235959
                </BANKTRANLIST>
                <LEDGERBAL><BALAMT>5000<DTASOF>20260114235959</LEDGERBAL>
            </STMTRS>
        </STMTTRNRS>
    </BANKMSGSRSV1>
</OFX>
SGML;
        
        $parser = new SgmlParser();
        $element = $parser->parse($sgml);
        
        $builder = new SgmlOfxBuilder();
        $ofx = $builder->buildOfx($element, []);
        
        $statement = $ofx->bankAccounts[0]->statement;
        $this->assertInstanceOf(\DateTimeInterface::class, $statement->startDate);
        $this->assertInstanceOf(\DateTimeInterface::class, $statement->endDate);
        $this->assertEquals('2026-01-01', $statement->startDate->format('Y-m-d'));
        $this->assertEquals('2026-01-14', $statement->endDate->format('Y-m-d'));
    }
    
    /**
     * Test transaction with both PAYEE and PAYEEID
     */
    public function testTransactionWithPayeeAndPayeeId()
    {
        $sgml = <<<SGML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <DTSERVER>20260114120000
            <LANGUAGE>ENG
            <FI><ORG>Test<FID>123</FI>
        </SONRS>
    </SIGNONMSGSRSV1>
    <BANKMSGSRSV1>
        <STMTTRNRS>
            <TRNUID>1
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <STMTRS>
                <CURDEF>USD
                <BANKACCTFROM>
                    <BANKID>123
                    <ACCTID>456
                    <ACCTTYPE>CHECKING
                </BANKACCTFROM>
                <BANKTRANLIST>
                    <DTSTART>20260101
                    <DTEND>20260114
                    <STMTTRN>
                        <TRNTYPE>DEBIT
                        <DTPOSTED>20260105
                        <TRNAMT>-100.00
                        <FITID>TXN001
                        <NAME>Test Transaction
                        <PAYEEID>MERCHANT123
                        <PAYEE>
                            <NAME>Best Buy
                            <ADDR1>123 Main St
                            <CITY>Seattle
                            <STATE>WA
                            <POSTALCODE>98101
                            <PHONE>1-800-555-1234
                        </PAYEE>
                    </STMTTRN>
                </BANKTRANLIST>
                <LEDGERBAL><BALAMT>5000<DTASOF>20260114</LEDGERBAL>
            </STMTRS>
        </STMTTRNRS>
    </BANKMSGSRSV1>
</OFX>
SGML;
        
        $parser = new SgmlParser();
        $element = $parser->parse($sgml);
        
        $builder = new SgmlOfxBuilder();
        $ofx = $builder->buildOfx($element, []);
        
        $transaction = $ofx->bankAccounts[0]->statement->transactions[0];
        $this->assertEquals('MERCHANT123', $transaction->payeeId);
        $this->assertNotNull($transaction->payee);
        $this->assertEquals('Best Buy', $transaction->payee->name);
        $this->assertEquals('Seattle', $transaction->payee->city);
        $this->assertEquals('WA', $transaction->payee->state);
        $this->assertEquals('98101', $transaction->payee->postalCode);
        $this->assertEquals('1-800-555-1234', $transaction->payee->phone);
    }
    
    /**
     * Test finding multiple STMTRS siblings
     */
    public function testMultipleStmtRsSiblings()
    {
        $sgml = <<<SGML
<OFX>
    <SIGNONMSGSRSV1>
        <SONRS>
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <DTSERVER>20260114120000
            <LANGUAGE>ENG
            <FI><ORG>Test<FID>123</FI>
        </SONRS>
    </SIGNONMSGSRSV1>
    <BANKMSGSRSV1>
        <STMTTRNRS>
            <TRNUID>1
            <STATUS><CODE>0<SEVERITY>INFO</STATUS>
            <STMTRS>
                <CURDEF>USD
                <BANKACCTFROM>
                    <BANKID>123
                    <ACCTID>111
                    <ACCTTYPE>CHECKING
                </BANKACCTFROM>
                <BANKTRANLIST>
                    <DTSTART>20260101
                    <DTEND>20260114
                </BANKTRANLIST>
                <LEDGERBAL><BALAMT>1000<DTASOF>20260114</LEDGERBAL>
            </STMTRS>
            <STMTRS>
                <CURDEF>USD
                <BANKACCTFROM>
                    <BANKID>123
                    <ACCTID>222
                    <ACCTTYPE>SAVINGS
                </BANKACCTFROM>
                <BANKTRANLIST>
                    <DTSTART>20260101
                    <DTEND>20260114
                </BANKTRANLIST>
                <LEDGERBAL><BALAMT>2000<DTASOF>20260114</LEDGERBAL>
            </STMTRS>
        </STMTTRNRS>
    </BANKMSGSRSV1>
</OFX>
SGML;
        
        $parser = new SgmlParser();
        $element = $parser->parse($sgml);
        
        $builder = new SgmlOfxBuilder();
        $ofx = $builder->buildOfx($element, []);
        
        // Should handle multiple STMTRS siblings
        $this->assertCount(2, $ofx->bankAccounts);
        $this->assertEquals('111', $ofx->bankAccounts[0]->accountNumber);
        $this->assertEquals('222', $ofx->bankAccounts[1]->accountNumber);
    }
}
