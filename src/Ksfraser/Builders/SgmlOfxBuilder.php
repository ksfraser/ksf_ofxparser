<?php declare(strict_types=1);

namespace OfxParser\Builders;

use OfxParser\Ofx;
use OfxParser\Entities\BankAccount;
use OfxParser\Entities\Transaction;
use OfxParser\Entities\Statement;
use OfxParser\Entities\SignOn;
use OfxParser\Entities\Status;
use OfxParser\Entities\Institute;
use OfxParser\Sgml\Elements\Element;
use OfxParser\Sgml\Elements\ContainerElement;
use OfxParser\Sgml\Elements\ValueElement;

/**
 * Builds Ofx entity objects directly from SGML Elements
 * 
 * This allows the SGML parser path to avoid XML conversion entirely
 */
class SgmlOfxBuilder
{
    /**
     * Build an Ofx object from SGML root element
     * 
     * @param Element $ofxElement The root OFX element
     * @param array $header Parsed OFX header
     * @return Ofx
     */
    public function buildOfx(Element $ofxElement, array $header): Ofx
    {
        $ofx = new Ofx(null); // Pass null - we'll populate directly
        
        // Build SignOn
        $signOnElement = $this->findChild($ofxElement, 'SIGNONMSGSRSV1');
        if ($signOnElement) {
            $ofx->signOn = $this->buildSignOn($signOnElement);
        }
        
        // Build Bank Accounts
        $bankMsgsElement = $this->findChild($ofxElement, 'BANKMSGSRSV1');
        if ($bankMsgsElement) {
            $ofx->bankAccounts = $this->buildBankAccounts($bankMsgsElement);
        }
        
        // Build Credit Card Accounts and merge into bankAccounts
        $ccMsgsElement = $this->findChild($ofxElement, 'CREDITCARDMSGSRSV1');
        if ($ccMsgsElement) {
            $ccAccounts = $this->buildCreditCardAccounts($ccMsgsElement);
            $ofx->bankAccounts = array_merge($ofx->bankAccounts, $ccAccounts);
        }
        
        // Set helper if only one bank account
        if (count($ofx->bankAccounts) === 1) {
            $ofx->bankAccount = $ofx->bankAccounts[0];
        }
        
        // Set header
        $ofx->buildHeader($header);
        
        return $ofx;
    }
    
    /**
     * Build SignOn entity from SGML element
     */
    private function buildSignOn(Element $signOnMsgs): SignOn
    {
        $signOn = new SignOn();
        
        $sonrs = $this->findChild($signOnMsgs, 'SONRS');
        if (!$sonrs) {
            return $signOn;
        }
        
        // Status
        $statusElement = $this->findChild($sonrs, 'STATUS');
        if ($statusElement) {
            $signOn->status = $this->buildStatus($statusElement);
        }
        
        // Date/Time
        $dtServer = $this->getValue($sonrs, 'DTSERVER');
        if ($dtServer) {
            // Handle both string and DateTime types
            if ($dtServer instanceof \DateTimeInterface) {
                $signOn->date = $dtServer;
            } else {
                $signOn->date = $this->parseDateTime($dtServer);
            }
        }
        
        // Language
        $signOn->language = $this->getValue($sonrs, 'LANGUAGE', '');
        
        // Institute
        $fiElement = $this->findChild($sonrs, 'FI');
        if ($fiElement) {
            $institute = new Institute();
            $institute->name = $this->getValue($fiElement, 'ORG', '');
            $institute->id = $this->getValue($fiElement, 'FID', '');
            $signOn->institute = $institute;
        }
        
        return $signOn;
    }
    
    /**
     * Build Status entity from SGML element
     */
    private function buildStatus(Element $statusElement): Status
    {
        $status = new Status();
        $status->code = (int)$this->getValue($statusElement, 'CODE', 0);
        $status->severity = $this->getValue($statusElement, 'SEVERITY', '');
        $status->message = $this->getValue($statusElement, 'MESSAGE', '');
        return $status;
    }
    
    /**
     * Build bank accounts from SGML element
     * 
     * @return BankAccount[]
     */
    private function buildBankAccounts(Element $bankMsgs): array
    {
        $accounts = [];
        
        // Find all STMTRNRS elements (OFX spec uses STMTRNRS, not STMTTRNRS)
        $stmtTrnRsList = $this->findChildren($bankMsgs, 'STMTRNRS');
        if (empty($stmtTrnRsList)) {
            $stmtTrnRsList = $this->findChildren($bankMsgs, 'STMTTRNRS'); // Fallback for non-standard files
        }
        
        foreach ($stmtTrnRsList as $stmtTrnRs) {
            $stmtRs = $this->findChild($stmtTrnRs, 'STMTRS');
            if (!$stmtRs) {
                continue;
            }
            
            $account = $this->buildBankAccount($stmtRs);
            if ($account) {
                $accounts[] = $account;
            }
        }
        
        return $accounts;
    }
    
    /**
     * Build a single bank account from SGML element
     */
    private function buildBankAccount(Element $stmtRs): ?BankAccount
    {
        $account = new BankAccount();
        
        // Account info from BANKACCTFROM
        $acctFrom = $this->findChild($stmtRs, 'BANKACCTFROM');
        if ($acctFrom) {
            $account->routingNumber = $this->getValue($acctFrom, 'BANKID', '');
            $account->agencyNumber = $this->getValue($acctFrom, 'BRANCHID', '');
            $account->accountNumber = $this->getValue($acctFrom, 'ACCTID', '');
            $account->accountType = $this->getValue($acctFrom, 'ACCTTYPE', '');
        }
        
        // Balance from LEDGERBAL
        $ledgerBal = $this->findChild($stmtRs, 'LEDGERBAL');
        if ($ledgerBal) {
            $account->balance = $this->getValue($ledgerBal, 'BALAMT', '0');
            $dtAsOf = $this->getValue($ledgerBal, 'DTASOF');
            if ($dtAsOf) {
                if ($dtAsOf instanceof \DateTimeInterface) {
                    $account->balanceDate = $dtAsOf;
                } else {
                    $account->balanceDate = $this->parseDateTime($dtAsOf);
                }
            }
        }
        
        // Statement with transactions
        $account->statement = $this->buildStatement($stmtRs);
        
        return $account;
    }
    
    /**
     * Build credit card accounts from SGML element
     * 
     * @return BankAccount[]
     */
    private function buildCreditCardAccounts(Element $ccMsgs): array
    {
        $accounts = [];
        
        $ccStmtTrnRsList = $this->findChildren($ccMsgs, 'CCSTMTTRNRS');
        
        foreach ($ccStmtTrnRsList as $ccStmtTrnRs) {
            $ccStmtRs = $this->findChild($ccStmtTrnRs, 'CCSTMTRS');
            if (!$ccStmtRs) {
                continue;
            }
            
            $account = $this->buildCreditCardAccount($ccStmtRs);
            if ($account) {
                $accounts[] = $account;
            }
        }
        
        return $accounts;
    }
    
    /**
     * Build a single credit card account from SGML element
     */
    private function buildCreditCardAccount(Element $ccStmtRs): ?BankAccount
    {
        $account = new BankAccount();
        
        // Account info from CCACCTFROM
        $acctFrom = $this->findChild($ccStmtRs, 'CCACCTFROM');
        if ($acctFrom) {
            $account->accountNumber = $this->getValue($acctFrom, 'ACCTID', '');
            $account->accountType = $this->getValue($acctFrom, 'ACCTTYPE', 'CREDITCARD');
        }
        
        // Balance from LEDGERBAL
        $ledgerBal = $this->findChild($ccStmtRs, 'LEDGERBAL');
        if ($ledgerBal) {
            $account->balance = $this->getValue($ledgerBal, 'BALAMT', '0');
            $dtAsOf = $this->getValue($ledgerBal, 'DTASOF');
            if ($dtAsOf) {
                if ($dtAsOf instanceof \DateTimeInterface) {
                    $account->balanceDate = $dtAsOf;
                } else {
                    $account->balanceDate = $this->parseDateTime($dtAsOf);
                }
            }
        }
        
        // Statement with transactions
        $account->statement = $this->buildStatement($ccStmtRs);
        
        return $account;
    }
    
    /**
     * Build statement from SGML element
     */
    private function buildStatement(Element $stmtRs): Statement
    {
        $statement = new Statement();
        
        // Currency
        $statement->currency = $this->getValue($stmtRs, 'CURDEF', 'USD');
        
        // Transaction list
        $tranList = $this->findChild($stmtRs, 'BANKTRANLIST');
        if ($tranList) {
            $dtStart = $this->getValue($tranList, 'DTSTART');
            $dtEnd = $this->getValue($tranList, 'DTEND');
            
            if ($dtStart) {
                if ($dtStart instanceof \DateTimeInterface) {
                    $statement->startDate = $dtStart;
                } else {
                    $statement->startDate = $this->parseDateTime($dtStart);
                }
            }
            if ($dtEnd) {
                if ($dtEnd instanceof \DateTimeInterface) {
                    $statement->endDate = $dtEnd;
                } else {
                    $statement->endDate = $this->parseDateTime($dtEnd);
                }
            }
            
            // Build transactions
            $stmtTrnList = $this->findChildren($tranList, 'STMTTRN');
            $statement->transactions = [];
            foreach ($stmtTrnList as $stmtTrn) {
                $transaction = $this->buildTransaction($stmtTrn);
                if ($transaction) {
                    $statement->transactions[] = $transaction;
                }
            }
        }
        
        return $statement;
    }
    
    /**
     * Build transaction from SGML element
     */
    private function buildTransaction(Element $stmtTrn): ?Transaction
    {
        $transaction = new Transaction();
        
        // Required fields
        $transaction->type = $this->getValue($stmtTrn, 'TRNTYPE', '');
        $transaction->uniqueId = $this->getValue($stmtTrn, 'FITID', '');
        $transaction->amount = (float)$this->getValue($stmtTrn, 'TRNAMT', 0);
        
        $dtPosted = $this->getValue($stmtTrn, 'DTPOSTED');
        if ($dtPosted) {
            if ($dtPosted instanceof \DateTimeInterface) {
                $transaction->date = $dtPosted;
            } else {
                $transaction->date = $this->parseDateTime($dtPosted);
            }
        }
        
        // Optional fields
        $transaction->name = $this->getValue($stmtTrn, 'NAME', '');
        $transaction->memo = $this->getValue($stmtTrn, 'MEMO', '');
        $transaction->sic = $this->getValue($stmtTrn, 'SIC', '');
        $transaction->checkNumber = $this->getValue($stmtTrn, 'CHECKNUM', '');
        $transaction->refNumber = $this->getValue($stmtTrn, 'REFNUM', '');
        
        $dtUser = $this->getValue($stmtTrn, 'DTUSER');
        if ($dtUser) {
            if ($dtUser instanceof \DateTimeInterface) {
                $transaction->userInitiatedDate = $dtUser;
            } else {
                $transaction->userInitiatedDate = $this->parseDateTime($dtUser);
            }
        }
        
        return $transaction;
    }
    
    /**
     * Find first child element with given tag name
     */
    private function findChild(Element $parent, string $tagName): ?Element
    {
        foreach ($parent->getChildren() as $child) {
            if ($child->getTagName() === $tagName) {
                return $child;
            }
        }
        
        return null;
    }
    
    /**
     * Find all children with given tag name
     * 
     * @return Element[]
     */
    private function findChildren(Element $parent, string $tagName): array
    {
        $matches = [];
        foreach ($parent->getChildren() as $child) {
            if ($child->getTagName() === $tagName) {
                $matches[] = $child;
            }
        }
        
        return $matches;
    }
    
    /**
     * Get value of a child element
     */
    private function getValue(Element $parent, string $tagName, $default = null)
    {
        $child = $this->findChild($parent, $tagName);
        
        if (!$child || !($child instanceof ValueElement)) {
            return $default;
        }
        
        return $child->getValue();
    }
    
    /**
     * Parse OFX date/time string to DateTime
     */
    private function parseDateTime(string $dateStr): ?\DateTimeInterface
    {
        // Remove timezone and milliseconds if present
        // Format: 20250114120000.000[-5:EST] -> 20250114120000
        $dateStr = preg_replace('/\[.*?\]$/', '', $dateStr); // Remove timezone
        $dateStr = preg_replace('/\..*$/', '', $dateStr); // Remove milliseconds
        
        // Parse YYYYMMDDHHmmss format
        $dt = \DateTime::createFromFormat('YmdHis', $dateStr);
        if ($dt === false) {
            // Try YYYYMMDD format (date only)
            $dt = \DateTime::createFromFormat('Ymd', substr($dateStr, 0, 8));
        }
        
        return $dt ?: null;
    }
}
