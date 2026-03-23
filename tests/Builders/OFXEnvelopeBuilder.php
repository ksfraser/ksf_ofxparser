<?php declare(strict_types=1);

namespace Tests\Builders;

use DateTime;
use DateTimeZone;

/**
 * Fluent builder for constructing valid OFX documents with known, controllable field values
 * 
 * Purpose: Generate test OFX documents where all field values are known upfront,
 * enabling predictable assertions in both unit and integration tests.
 * 
 * Usage:
 *   $builder = OFXEnvelopeBuilder::ofxBankStatement()
 *       ->withServerDateTime(new DateTime('2026-03-13 12:00:00'))
 *       ->addTransaction([
 *           'id' => '1',
 *           'type' => 'DEBIT',
 *           'amount' => '-50.00',
 *           'date' => new DateTime('2026-03-13'),
 *           'memo' => 'Test'
 *       ])
 *       ->build();
 * 
 * Expected behavior: Can assert exact $ofx->bankAccount->statement->transactions[0]->amount == -50.00
 */
class OFXEnvelopeBuilder
{
    private ?DateTime $serverDateTime = null;
    private ?DateTime $statementStart = null;
    private ?DateTime $statementEnd = null;
    private string $currency = 'USD';
    private string $messageSetType = 'bank';
    private string $bankId = '123456789';
    private string $accountId = 'ACC123456';
    private string $accountType = 'CHECKING';
    private string $balanceAmount = '1000.00';
    private ?DateTime $balanceDate = null;
    
    /** @var array<array{id: string, type: string, amount: string|float, date: DateTime, memo: string}> */
    private array $transactions = [];
    
    public static function ofxBankStatement(): self
    {
        $builder = new self();
        $builder->messageSetType = 'bank';
        $builder->serverDateTime = new DateTime('2026-03-13 12:00:00', new DateTimeZone('UTC'));
        $builder->statementStart = new DateTime('2026-03-01', new DateTimeZone('UTC'));
        $builder->statementEnd = new DateTime('2026-03-13', new DateTimeZone('UTC'));
        $builder->balanceDate = new DateTime('2026-03-13', new DateTimeZone('UTC'));
        return $builder;
    }
    
    public static function ofxCreditCardStatement(): self
    {
        $builder = new self();
        $builder->messageSetType = 'creditcard';
        $builder->accountType = 'CREDITLINE';
        return $builder;
    }
    
    public static function ofxInvestmentStatement(): self
    {
        $builder = new self();
        $builder->messageSetType = 'investment';
        return $builder;
    }
    
    public function withServerDateTime(DateTime $dateTime): self
    {
        $this->serverDateTime = $dateTime;
        return $this;
    }
    
    public function withStatementPeriod(DateTime $start, DateTime $end): self
    {
        $this->statementStart = $start;
        $this->statementEnd = $end;
        return $this;
    }
    
    public function withCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }
    
    public function withBankId(string $bankId): self
    {
        $this->bankId = $bankId;
        return $this;
    }
    
    public function withAccountId(string $accountId): self
    {
        $this->accountId = $accountId;
        return $this;
    }
    
    public function withAccountType(string $type): self
    {
        $this->accountType = $type;
        return $this;
    }
    
    public function withBalance(string $amount, DateTime $asOf): self
    {
        $this->balanceAmount = $amount;
        $this->balanceDate = $asOf;
        return $this;
    }
    
    /**
     * Add a transaction with known, testable field values
     * 
     * @param array{id: string, type: string, amount: string|float, date: DateTime, memo: string, payee?: string} $transaction
     */
    public function addTransaction(array $transaction): self
    {
        $this->transactions[] = [
            'id' => $transaction['id'],
            'type' => $transaction['type'],
            'amount' => (string) $transaction['amount'],
            'date' => $transaction['date'],
            'memo' => $transaction['memo'],
            'payee' => $transaction['payee'] ?? null,
        ];
        return $this;
    }
    
    /**
     * Add multiple transactions at once
     * @param array<array> $transactions
     */
    public function addTransactions(array $transactions): self
    {
        foreach ($transactions as $transaction) {
            $this->addTransaction($transaction);
        }
        return $this;
    }
    
    /**
     * Build the complete OFX XML document
     */
    public function build(): string
    {
        $serverDateTime = $this->serverDateTime ?? new DateTime('2026-03-13 12:00:00', new DateTimeZone('UTC'));
        $statementStart = $this->statementStart ?? new DateTime('2026-03-01', new DateTimeZone('UTC'));
        $statementEnd = $this->statementEnd ?? new DateTime('2026-03-13', new DateTimeZone('UTC'));
        $balanceDate = $this->balanceDate ?? new DateTime('2026-03-13', new DateTimeZone('UTC'));
        
        // Format datetime values for OFX (YYYYMMDDHHMMSS format, no timezone)
        $serverDateTimeStr = $serverDateTime->format('YmdHis');
        $statementStartStr = $statementStart->format('Ymd');
        $statementEndStr = $statementEnd->format('Ymd');
        $balanceDateStr = $balanceDate->format('Ymd');
        
        // Build transactions
        $transactionsXml = '';
        foreach ($this->transactions as $trx) {
            $dateStr = $trx['date']->format('Ymd');
            $transactionsXml .= "<STMTTRN>\n";
            $transactionsXml .= "<TRNTYPE>{$trx['type']}\n";
            $transactionsXml .= "<DTPOSTED>{$dateStr}\n";
            $transactionsXml .= "<TRNAMT>{$trx['amount']}\n";
            $transactionsXml .= "<FITID>{$trx['id']}\n";
            if ($trx['payee']) {
                $transactionsXml .= "<NAME>{$trx['payee']}\n";
            }
            $transactionsXml .= "<MEMO>{$trx['memo']}\n";
            $transactionsXml .= "</STMTTRN>\n";
        }
        
        // Build complete OFX envelope
        $ofx = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS>
<CODE>0</CODE>
<SEVERITY>INFO</SEVERITY>
</STATUS>
<DTSERVER>{$serverDateTimeStr}</DTSERVER>
<LANGUAGE>ENG</LANGUAGE>
</SONRS>
</SIGNONMSGSRSV1>
XML;
        
        // Add message set based on type
        switch ($this->messageSetType) {
            case 'bank':
                $ofx .= "\n<BANKMSGSRSV1>\n";
                $ofx .= "<STMTTRNRS>\n";
                $ofx .= "<STATUS>\n";
                $ofx .= "<CODE>0</CODE>\n";
                $ofx .= "<SEVERITY>INFO</SEVERITY>\n";
                $ofx .= "</STATUS>\n";
                $ofx .= "<STMTRS>\n";
                $ofx .= "<CURDEF>{$this->currency}\n";
                $ofx .= "<BANKTRANLIST>\n";
                $ofx .= "<DTSTART>{$statementStartStr}\n";
                $ofx .= "<DTEND>{$statementEndStr}\n";
                $ofx .= $transactionsXml;
                $ofx .= "</BANKTRANLIST>\n";
                $ofx .= "<LEDGERBAL>\n";
                $ofx .= "<BALAMT>{$this->balanceAmount}\n";
                $ofx .= "<DTASOF>{$balanceDateStr}\n";
                $ofx .= "</LEDGERBAL>\n";
                $ofx .= "<BANKACCTFROM>\n";
                $ofx .= "<BANKID>{$this->bankId}\n";
                $ofx .= "<ACCTID>{$this->accountId}\n";
                $ofx .= "<ACCTTYPE>{$this->accountType}\n";
                $ofx .= "</BANKACCTFROM>\n";
                $ofx .= "</STMTRS>\n";
                $ofx .= "</STMTTRNRS>\n";
                $ofx .= "</BANKMSGSRSV1>\n";
                break;
                
            case 'creditcard':
                $ofx .= "\n<CREDITCARDMSGSRSV1>\n";
                $ofx .= "<CCSTMTTRNRS>\n";
                $ofx .= "<STATUS>\n";
                $ofx .= "<CODE>0</CODE>\n";
                $ofx .= "<SEVERITY>INFO</SEVERITY>\n";
                $ofx .= "</STATUS>\n";
                $ofx .= "<CCSTMTRS>\n";
                $ofx .= "<CURDEF>{$this->currency}\n";
                $ofx .= "<BANKTRANLIST>\n";
                $ofx .= "<DTSTART>{$statementStartStr}\n";
                $ofx .= "<DTEND>{$statementEndStr}\n";
                $ofx .= $transactionsXml;
                $ofx .= "</BANKTRANLIST>\n";
                $ofx .= "<LEDGERBAL>\n";
                $ofx .= "<BALAMT>{$this->balanceAmount}\n";
                $ofx .= "<DTASOF>{$balanceDateStr}\n";
                $ofx .= "</LEDGERBAL>\n";
                $ofx .= "<CCACCTFROM>\n";
                $ofx .= "<ACCTID>{$this->accountId}\n";
                $ofx .= "</CCACCTFROM>\n";
                $ofx .= "</CCSTMTRS>\n";
                $ofx .= "</CCSTMTTRNRS>\n";
                $ofx .= "</CREDITCARDMSGSRSV1>\n";
                break;
        }
        
        $ofx .= "</OFX>";
        
        return $ofx;
    }
}
