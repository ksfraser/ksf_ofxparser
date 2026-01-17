<?php declare(strict_types=1);

namespace OfxParser\Builders;

use OfxParser\Ofx;
use OfxParser\Entities\BankAccount;
use OfxParser\Entities\Transaction;
use OfxParser\Entities\Statement;
use OfxParser\Entities\SignOn;
use OfxParser\Entities\Status;
use OfxParser\Entities\Institute;
use OfxParser\Entities\Payee;
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
        
        // Build Security List
        $secListMsgsElement = $this->findChild($ofxElement, 'SECLISTMSGSRSV1');
        if ($secListMsgsElement) {
            $ofx->securityList = $this->buildSecurityList($secListMsgsElement);
        }
        
        // Build Loan Accounts
        $loanMsgsElement = $this->findChild($ofxElement, 'LOANMSGSRSV1');
        if ($loanMsgsElement) {
            $ofx->loanAccounts = $this->buildLoanAccounts($loanMsgsElement);
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
            // Some files have multiple STMTRS siblings under one STMTTRNRS
            $stmtRsList = $this->findChildren($stmtTrnRs, 'STMTRS');
            
            if (empty($stmtRsList)) {
                // Try single child for backwards compatibility
                $stmtRs = $this->findChild($stmtTrnRs, 'STMTRS');
                if ($stmtRs) {
                    $stmtRsList = [$stmtRs];
                }
            }
            
            foreach ($stmtRsList as $stmtRs) {
                $account = $this->buildBankAccount($stmtRs);
                if ($account) {
                    $accounts[] = $account;
                }
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
        
        // Payee information (OFX spec: optional structured payment recipient data)
        $transaction->payeeId = $this->getValue($stmtTrn, 'PAYEEID', null);
        $payeeElement = $this->findChild($stmtTrn, 'PAYEE');
        if ($payeeElement) {
            $transaction->payee = $this->buildPayee($payeeElement);
        }
        
        // Currency information (OFX spec: for multi-currency transactions)
        // Why: When transaction currency differs from account currency (CURDEF),
        // CURRENCY provides the exchange rate and currency code used
        $currencyElement = $this->findChild($stmtTrn, 'CURRENCY');
        if ($currencyElement) {
            $transaction->currency = $this->buildCurrency($currencyElement);
        }
        
        // Original currency (OFX spec: for transactions converted through multiple currencies)
        // Why: Preserves original transaction currency before any conversions
        $origCurrencyElement = $this->findChild($stmtTrn, 'ORIGCURRENCY');
        if ($origCurrencyElement) {
            $transaction->originalCurrency = $this->buildCurrency($origCurrencyElement);
        }
        
        return $transaction;
    }
    
    /**
     * Build Payee entity from SGML PAYEE element
     * 
     * What: Constructs a Payee object containing structured payment recipient information
     * including name, multi-line address, city, state, postal code, country, and phone.
     * 
     * Why: The OFX spec defines PAYEE as a container for structured payee information
     * in transactions. This is used for bill payments, checks, and other payment types
     * where detailed recipient information is available. Parsing payee data allows
     * applications to:
     * - Display complete recipient information
     * - Store structured address data for record-keeping
     * - Enable payee-based transaction analysis and reporting
     * - Support bill pay and payment tracking features
     * 
     * @param Element $payeeElement The PAYEE container element
     * @return Payee Populated payee entity with all available fields
     */
    private function buildPayee(Element $payeeElement): Payee
    {
        $payee = new Payee();
        
        // Required: Payee name
        $payee->name = $this->getValue($payeeElement, 'NAME', '');
        
        // Optional: Multi-line address (ADDR1, ADDR2, ADDR3)
        // Why: OFX supports up to 3 address lines for complete mailing addresses
        $address = [];
        $addr1 = $this->getValue($payeeElement, 'ADDR1', '');
        if ($addr1 !== '') {
            $address[] = $addr1;
        }
        $addr2 = $this->getValue($payeeElement, 'ADDR2', '');
        if ($addr2 !== '') {
            $address[] = $addr2;
        }
        $addr3 = $this->getValue($payeeElement, 'ADDR3', '');
        if ($addr3 !== '') {
            $address[] = $addr3;
        }
        $payee->address = count($address) > 0 ? $address : null;
        
        // Optional: Location and contact information
        $payee->city = $this->getValue($payeeElement, 'CITY', null);
        $payee->state = $this->getValue($payeeElement, 'STATE', null);
        $payee->postalCode = $this->getValue($payeeElement, 'POSTALCODE', null);
        $payee->country = $this->getValue($payeeElement, 'COUNTRY', null);
        $payee->phone = $this->getValue($payeeElement, 'PHONE', null);
        
        return $payee;
    }
    
    /**
     * Build currency information from CURRENCY or ORIGCURRENCY element
     * 
     * What: Extracts currency code (CURSYM) and exchange rate (CURRATE) from
     * OFX currency elements.
     * 
     * Why: Multi-currency transactions are common in international banking. The OFX
     * spec defines CURRENCY and ORIGCURRENCY containers to provide:
     * - Currency code (ISO 4217: USD, EUR, GBP, etc.)
     * - Exchange rate applied for conversion
     * 
     * This enables applications to:
     * - Display amounts in both account currency and original currency
     * - Calculate original foreign amounts: original = amount / rate
     * - Track currency conversion history
     * - Support international transaction reporting
     * 
     * Example: EUR account with USD purchase:
     * - TRNAMT: -100.00 (in EUR, the account currency)
     * - CURRENCY: {code: 'USD', rate: 1.18}
     * - Original amount: -100.00 / 1.18 = -84.75 USD
     * 
     * @param Element $currencyElement CURRENCY or ORIGCURRENCY container
     * @return array|null ['code' => string, 'rate' => float] or null if incomplete
     */
    private function buildCurrency(Element $currencyElement): ?array
    {
        $code = $this->getValue($currencyElement, 'CURSYM', null);
        $rate = $this->getValue($currencyElement, 'CURRATE', null);
        
        // Both fields required for valid currency information
        if ($code === null || $rate === null) {
            return null;
        }
        
        return [
            'code' => (string)$code,
            'rate' => (float)$rate
        ];
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
        
        if (!$child) {
            return $default;
        }
        
        // Try getValue() method (works for ValueElement and UnknownElement)
        $value = $child->getValue();
        if ($value !== null && $value !== '') {
            return $value;
        }
        
        // Handle ContainerElement that might have a text value  
        // (e.g., <SECNAME>Apple Inc.</SECNAME> where SECNAME is parsed as a container)
        $value = (string) $child; // Uses Element::__toString() which returns textValue
        return $value ?: $default;
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
    /**
     * Build an InvestmentOfx object from SGML root element (native, no XML conversion)
     * 
     * @param Element $ofxElement The root OFX element
     * @param array $header Parsed OFX header
     * @return \OfxParser\Ofx\Investment
     */
    public function buildInvestmentOfx(Element $ofxElement, array $header): \OfxParser\Ofx\Investment
    {
        // Create InvestmentOfx without calling constructor (which requires SimpleXMLElement)
        $investmentOfx = new \OfxParser\Ofx\Investment(null);
        
        // Build SignOn
        $signOnElement = $this->findChild($ofxElement, 'SIGNONMSGSRSV1');
        if ($signOnElement) {
            $investmentOfx->signOn = $this->buildSignOn($signOnElement);
        }
        
        // Build Investment Accounts
        $invMsgsElement = $this->findChild($ofxElement, 'INVSTMTMSGSRSV1');
        if ($invMsgsElement) {
            $investmentOfx->bankAccounts = $this->buildInvestmentAccounts($invMsgsElement);
        }
        
        // Set helper if only one account
        if (count($investmentOfx->bankAccounts) === 1) {
            $investmentOfx->bankAccount = $investmentOfx->bankAccounts[0];
        }
        
        // Set header
        $investmentOfx->buildHeader($header);
        
        return $investmentOfx;
    }
    
    /**
     * Build investment accounts from SGML element
     * 
     * @return \OfxParser\Entities\Investment\Account[]
     */
    private function buildInvestmentAccounts(Element $invMsgs): array
    {
        $accounts = [];
        
        // Find all INVSTMTTRNRS elements
        $stmtTrnRsList = $this->findChildren($invMsgs, 'INVSTMTTRNRS');
        
        foreach ($stmtTrnRsList as $stmtTrnRs) {
            $trnUid = $this->getValue($stmtTrnRs, 'TRNUID', '');
            
            // Find all INVSTMTRS elements (can be multiple)
            $stmtRsList = $this->findChildren($stmtTrnRs, 'INVSTMTRS');
            
            foreach ($stmtRsList as $stmtRs) {
                $account = $this->buildInvestmentAccount($trnUid, $stmtRs);
                if ($account) {
                    $accounts[] = $account;
                }
            }
        }
        
        return $accounts;
    }
    
    /**
     * Build a single investment account from SGML element
     */
    private function buildInvestmentAccount(string $trnUid, Element $stmtRs): ?\OfxParser\Entities\Investment\Account
    {
        $account = new \OfxParser\Entities\Investment\Account();
        $account->transactionUid = $trnUid;
        
        // Account info from INVACCTFROM
        $acctFrom = $this->findChild($stmtRs, 'INVACCTFROM');
        if ($acctFrom) {
            $account->brokerId = trim($this->getValue($acctFrom, 'BROKERID', ''));
            $account->accountNumber = trim($this->getValue($acctFrom, 'ACCTID', ''));
        }
        
        // Statement
        $account->statement = new Statement();
        $account->statement->currency = $this->getValue($stmtRs, 'CURDEF', '');
        
        // Transaction list
        $tranList = $this->findChild($stmtRs, 'INVTRANLIST');
        if ($tranList) {
            $dtStart = $this->getValue($tranList, 'DTSTART');
            $dtEnd = $this->getValue($tranList, 'DTEND');
            
            if ($dtStart instanceof \DateTimeInterface) {
                $account->statement->startDate = $dtStart;
            } elseif ($dtStart) {
                $account->statement->startDate = $this->parseDateTime($dtStart);
            }
            
            if ($dtEnd instanceof \DateTimeInterface) {
                $account->statement->endDate = $dtEnd;
            } elseif ($dtEnd) {
                $account->statement->endDate = $this->parseDateTime($dtEnd);
            }
            
            $account->statement->transactions = $this->buildInvestmentTransactions($tranList);
        } else {
            $account->statement->transactions = [];
        }
        
        return $account;
    }
    
    /**
     * Build investment transactions from SGML element
     */
    private function buildInvestmentTransactions(Element $tranList): array
    {
        $transactions = [];
        
        foreach ($tranList->getChildren() as $child) {
            $transaction = null;
            $tagName = $child->getTagName();
            
            switch ($tagName) {
                case 'BUYMF':
                    $transaction = $this->buildBuyMutualFund($child);
                    break;
                case 'BUYSTOCK':
                    $transaction = $this->buildBuyStock($child);
                    break;
                case 'BUYOTHER':
                    $transaction = $this->buildBuySecurity($child);
                    break;
                case 'SELLMF':
                    $transaction = $this->buildSellMutualFund($child);
                    break;
                case 'SELLSTOCK':
                    $transaction = $this->buildSellStock($child);
                    break;
                case 'SELLOTHER':
                    $transaction = $this->buildSellSecurity($child);
                    break;
                case 'REINVEST':
                    $transaction = $this->buildReinvest($child);
                    break;
                case 'INCOME':
                    $transaction = $this->buildIncome($child);
                    break;
                case 'INVBANKTRAN':
                    $transaction = $this->buildInvestmentBanking($child);
                    break;
            }
            
            if ($transaction) {
                $transactions[] = $transaction;
            }
        }
        
        return $transactions;
    }
    
    /**
     * Build common INVTRAN data
     */
    private function loadInvTran($transaction, Element $invTranElement)
    {
        $transaction->uniqueId = $this->getValue($invTranElement, 'FITID', '');
        
        $dtTrade = $this->getValue($invTranElement, 'DTTRADE');
        if ($dtTrade instanceof \DateTimeInterface) {
            $transaction->tradeDate = $dtTrade;
        } elseif ($dtTrade) {
            $transaction->tradeDate = $this->parseDateTime($dtTrade);
        }
        
        $dtSettle = $this->getValue($invTranElement, 'DTSETTLE');
        if ($dtSettle instanceof \DateTimeInterface) {
            $transaction->settlementDate = $dtSettle;
        } elseif ($dtSettle) {
            $transaction->settlementDate = $this->parseDateTime($dtSettle);
        }
        
        $transaction->memo = $this->getValue($invTranElement, 'MEMO', '');
        
        return $transaction;
    }
    
    /**
     * Build common SECID data
     */
    private function loadSecId($transaction, Element $secIdElement)
    {
        $transaction->securityId = $this->getValue($secIdElement, 'UNIQUEID', '');
        $transaction->securityIdType = $this->getValue($secIdElement, 'UNIQUEIDTYPE', '');
        return $transaction;
    }
    
    /**
     * Build common pricing data
     */
    private function loadPricing($transaction, Element $element)
    {
        $units = $this->getValue($element, 'UNITS');
        $transaction->units = ($units !== null && $units !== '') ? (string)$units : null;
        
        $unitPrice = $this->getValue($element, 'UNITPRICE');
        $transaction->unitPrice = ($unitPrice !== null && $unitPrice !== '') ? (string)$unitPrice : null;
        
        $total = $this->getValue($element, 'TOTAL');
        $transaction->total = ($total !== null && $total !== '') ? (string)$total : null;
        
        $subAcctFund = $this->getValue($element, 'SUBACCTFUND');
        $transaction->subAccountFund = ($subAcctFund !== null && $subAcctFund !== '') ? $subAcctFund : null;
        
        $subAcctSec = $this->getValue($element, 'SUBACCTSEC');
        $transaction->subAccountSec = ($subAcctSec !== null && $subAcctSec !== '') ? $subAcctSec : null;
        
        return $transaction;
    }
    
    private function buildBuyMutualFund(Element $element)
    {
        $transaction = new \OfxParser\Entities\Investment\Transaction\BuyMutualFund();
        $invBuy = $this->findChild($element, 'INVBUY');
        if (!$invBuy) return null;
        
        $invTran = $this->findChild($invBuy, 'INVTRAN');
        if ($invTran) $this->loadInvTran($transaction, $invTran);
        
        $secId = $this->findChild($invBuy, 'SECID');
        if ($secId) $this->loadSecId($transaction, $secId);
        
        $this->loadPricing($transaction, $invBuy);
        $transaction->buyType = $this->getValue($element, 'BUYTYPE', '');
        
        return $transaction;
    }
    
    private function buildBuyStock(Element $element)
    {
        $transaction = new \OfxParser\Entities\Investment\Transaction\BuyStock();
        $invBuy = $this->findChild($element, 'INVBUY');
        if (!$invBuy) return null;
        
        $invTran = $this->findChild($invBuy, 'INVTRAN');
        if ($invTran) $this->loadInvTran($transaction, $invTran);
        
        $secId = $this->findChild($invBuy, 'SECID');
        if ($secId) $this->loadSecId($transaction, $secId);
        
        $this->loadPricing($transaction, $invBuy);
        $transaction->buyType = $this->getValue($element, 'BUYTYPE', '');
        
        return $transaction;
    }
    
    private function buildBuySecurity(Element $element)
    {
        $transaction = new \OfxParser\Entities\Investment\Transaction\BuySecurity();
        $invBuy = $this->findChild($element, 'INVBUY');
        if (!$invBuy) return null;
        
        $invTran = $this->findChild($invBuy, 'INVTRAN');
        if ($invTran) $this->loadInvTran($transaction, $invTran);
        
        $secId = $this->findChild($invBuy, 'SECID');
        if ($secId) $this->loadSecId($transaction, $secId);
        
        $this->loadPricing($transaction, $invBuy);
        $transaction->buyType = $this->getValue($element, 'BUYTYPE', '');
        
        return $transaction;
    }
    
    private function buildSellMutualFund(Element $element)
    {
        $transaction = new \OfxParser\Entities\Investment\Transaction\SellMutualFund();
        $invSell = $this->findChild($element, 'INVSELL');
        if (!$invSell) return null;
        
        $invTran = $this->findChild($invSell, 'INVTRAN');
        if ($invTran) $this->loadInvTran($transaction, $invTran);
        
        $secId = $this->findChild($invSell, 'SECID');
        if ($secId) $this->loadSecId($transaction, $secId);
        
        $this->loadPricing($transaction, $invSell);
        $transaction->sellType = $this->getValue($element, 'SELLTYPE', '');
        
        return $transaction;
    }
    
    private function buildSellStock(Element $element)
    {
        $transaction = new \OfxParser\Entities\Investment\Transaction\SellStock();
        $invSell = $this->findChild($element, 'INVSELL');
        if (!$invSell) return null;
        
        $invTran = $this->findChild($invSell, 'INVTRAN');
        if ($invTran) $this->loadInvTran($transaction, $invTran);
        
        $secId = $this->findChild($invSell, 'SECID');
        if ($secId) $this->loadSecId($transaction, $secId);
        
        $this->loadPricing($transaction, $invSell);
        $transaction->sellType = $this->getValue($element, 'SELLTYPE', '');
        
        return $transaction;
    }
    
    private function buildSellSecurity(Element $element)
    {
        $transaction = new \OfxParser\Entities\Investment\Transaction\SellSecurity();
        $invSell = $this->findChild($element, 'INVSELL');
        if (!$invSell) return null;
        
        $invTran = $this->findChild($invSell, 'INVTRAN');
        if ($invTran) $this->loadInvTran($transaction, $invTran);
        
        $secId = $this->findChild($invSell, 'SECID');
        if ($secId) $this->loadSecId($transaction, $secId);
        
        $this->loadPricing($transaction, $invSell);
        $transaction->sellType = $this->getValue($element, 'SELLTYPE', '');
        
        return $transaction;
    }
    
    private function buildReinvest(Element $element)
    {
        $transaction = new \OfxParser\Entities\Investment\Transaction\Reinvest();
        
        $invTran = $this->findChild($element, 'INVTRAN');
        if ($invTran) $this->loadInvTran($transaction, $invTran);
        
        $secId = $this->findChild($element, 'SECID');
        if ($secId) $this->loadSecId($transaction, $secId);
        
        $this->loadPricing($transaction, $element);
        $transaction->incomeType = $this->getValue($element, 'INCOMETYPE', '');
        
        return $transaction;
    }
    
    private function buildIncome(Element $element)
    {
        $transaction = new \OfxParser\Entities\Investment\Transaction\Income();
        
        $invTran = $this->findChild($element, 'INVTRAN');
        if ($invTran) $this->loadInvTran($transaction, $invTran);
        
        $secId = $this->findChild($element, 'SECID');
        if ($secId) $this->loadSecId($transaction, $secId);
        
        $total = $this->getValue($element, 'TOTAL');
        $transaction->total = ($total !== null && $total !== '') ? $total : null;
        
        $transaction->incomeType = $this->getValue($element, 'INCOMETYPE', '');
        
        $subAcctSec = $this->getValue($element, 'SUBACCTSEC');
        $transaction->subAccountSec = ($subAcctSec !== null && $subAcctSec !== '') ? $subAcctSec : null;
        
        $subAcctFund = $this->getValue($element, 'SUBACCTFUND');
        $transaction->subAccountFund = ($subAcctFund !== null && $subAcctFund !== '') ? $subAcctFund : null;
        
        return $transaction;
    }
    
    private function buildInvestmentBanking(Element $element)
    {
        $transaction = new \OfxParser\Entities\Investment\Transaction\Banking();
        
        $stmtTrn = $this->findChild($element, 'STMTTRN');
        if ($stmtTrn) {
            $transaction->type = $this->getValue($stmtTrn, 'TRNTYPE', '');
            
            $dtPosted = $this->getValue($stmtTrn, 'DTPOSTED');
            if ($dtPosted instanceof \DateTimeInterface) {
                $transaction->date = $dtPosted;
            } elseif ($dtPosted) {
                $transaction->date = $this->parseDateTime($dtPosted);
            }
            
            $trnAmt = $this->getValue($stmtTrn, 'TRNAMT');
            $transaction->amount = $trnAmt !== null ? (float)$trnAmt : 0.0;
            
            $transaction->uniqueId = $this->getValue($stmtTrn, 'FITID', '');
        }
        
        $subAcctFund = $this->getValue($element, 'SUBACCTFUND');
        $transaction->subAccountFund = ($subAcctFund !== null && $subAcctFund !== '') ? $subAcctFund : null;
        
        return $transaction;
    }
    
    /**
     * Build Security List from SECLISTMSGSRSV1 element
     * 
     * @param Element $element SECLISTMSGSRSV1 element
     * @return \OfxParser\Entities\Investment\SecurityList
     */
    private function buildSecurityList(Element $element): \OfxParser\Entities\Investment\SecurityList
    {
        $securityList = new \OfxParser\Entities\Investment\SecurityList();
        
        $secList = $this->findChild($element, 'SECLIST');
        if (!$secList) {
            return $securityList;
        }
        
        // Parse different security types
        $securityTypes = [
            'STOCKINFO' => 'STOCK',
            'DEBTINFO' => 'BOND',
            'MFINFO' => 'MUTUALFUND',
            'OPTINFO' => 'OPTION',
            'OTHERINFO' => 'OTHER'
        ];
        
        foreach ($securityTypes as $xmlTag => $securityType) {
            $secInfoElements = $this->findChildren($secList, $xmlTag);
            foreach ($secInfoElements as $secInfo) {
                $security = $this->buildSecurity($secInfo, $securityType);
                $securityList->addSecurity($security);
            }
        }
        
        return $securityList;
    }
    
    /**
     * Build individual Security entity from SGML
     * 
     * @param Element $element Security info element (STOCKINFO, DEBTINFO, etc.)
     * @param string $securityType Type of security
     * @return \OfxParser\Entities\Investment\Security
     */
    private function buildSecurity(Element $element, string $securityType): \OfxParser\Entities\Investment\Security
    {
        $security = new \OfxParser\Entities\Investment\Security();
        
        // All securities have SECINFO child
        $secInfo = $this->findChild($element, 'SECINFO');
        if (!$secInfo) {
            return $security;
        }
        
        // Required fields
        $secId = $this->findChild($secInfo, 'SECID');
        if ($secId) {
            $security->securityId = $this->getValue($secId, 'UNIQUEID', '');
            $security->securityIdType = $this->getValue($secId, 'UNIQUEIDTYPE', '');
        }
        
        $security->name = $this->getValue($secInfo, 'SECNAME', '');
        $security->securityType = $securityType;
        
        // Optional common fields
        $ticker = $this->getValue($secInfo, 'TICKER');
        $security->ticker = ($ticker !== null && $ticker !== '') ? $ticker : null;
        
        $memo = $this->getValue($secInfo, 'MEMO');
        $security->memo = ($memo !== null && $memo !== '') ? $memo : null;
        
        $unitPrice = $this->getValue($secInfo, 'UNITPRICE');
        $security->unitPrice = ($unitPrice !== null && $unitPrice !== '') ? (float)$unitPrice : null;
        
        // CURRENCY can be either a simple value (<CURRENCY>USD) or a container (<CURRENCY><CURSYM>USD</CURSYM>...)
        // When simple, getValue returns the text; when container, check for CURSYM child
        $currency = $this->getValue($secInfo, 'CURRENCY');
        if (!$currency || $currency === '') {
            // Try CURSYM child (container format)
            $currencyElement = $this->findChild($secInfo, 'CURRENCY');
            if ($currencyElement) {
                $currency = $this->getValue($currencyElement, 'CURSYM');
            }
        }
        $security->currency = ($currency !== null && $currency !== '') ? $currency : null;
        
        $dtPriceAsOf = $this->getValue($secInfo, 'DTPRICEASOF');
        if ($dtPriceAsOf) {
            $security->priceDateOf = $dtPriceAsOf instanceof \DateTimeInterface 
                ? $dtPriceAsOf 
                : $this->parseDateTime($dtPriceAsOf);
        }
        
        // Bond-specific fields
        if ($securityType === 'BOND') {
            $debtType = $this->getValue($element, 'DEBTTYPE');
            $security->debtType = ($debtType !== null && $debtType !== '') ? $debtType : null;
            
            $debtClass = $this->getValue($element, 'DEBTCLASS');
            $security->debtClass = ($debtClass !== null && $debtClass !== '') ? $debtClass : null;
            
            $couponRate = $this->getValue($element, 'COUPONRT');
            $security->couponRate = ($couponRate !== null && $couponRate !== '') ? (float)$couponRate : null;
            
            $parValue = $this->getValue($element, 'PARVALUE');
            $security->parValue = ($parValue !== null && $parValue !== '') ? (float)$parValue : null;
            
            $dtMat = $this->getValue($element, 'DTMAT');
            if ($dtMat) {
                $security->maturityDate = $dtMat instanceof \DateTimeInterface 
                    ? $dtMat 
                    : $this->parseDateTime($dtMat);
            }
        }
        
        // Mutual fund-specific fields
        if ($securityType === 'MUTUALFUND') {
            $assetClass = $this->getValue($element, 'MFASSETCLASS');
            $security->assetClass = ($assetClass !== null && $assetClass !== '') ? $assetClass : null;
            
            $fiAssetClass = $this->getValue($element, 'FIMFASSETCLASS');
            $security->fiAssetClass = ($fiAssetClass !== null && $fiAssetClass !== '') ? $fiAssetClass : null;
        }
        
        return $security;
    }
    
    /**
     * Build Loan Accounts from LOANMSGSRSV1 element
     * 
     * @param Element $element LOANMSGSRSV1 element
     * @return \OfxParser\Entities\Loan\LoanAccount[]
     */
    private function buildLoanAccounts(Element $element): array
    {
        $loanAccounts = [];
        
        $loanStmtTrnRsElements = $this->findChildren($element, 'LOANSTMTTRNRS');
        
        foreach ($loanStmtTrnRsElements as $loanStmtTrnRs) {
            $loanStmtRs = $this->findChild($loanStmtTrnRs, 'LOANSTMTRS');
            if ($loanStmtRs) {
                $loanAccount = $this->buildLoanAccount($loanStmtRs);
                $loanAccounts[] = $loanAccount;
            }
        }
        
        return $loanAccounts;
    }
    
    /**
     * Build individual Loan Account entity from SGML
     * 
     * @param Element $element LOANSTMTRS element
     * @return \OfxParser\Entities\Loan\LoanAccount
     */
    private function buildLoanAccount(Element $element): \OfxParser\Entities\Loan\LoanAccount
    {
        $loan = new \OfxParser\Entities\Loan\LoanAccount();
        
        // Currency
        $loan->currency = $this->getValue($element, 'CURDEF', '');
        
        // Account identification
        $loanAcctFrom = $this->findChild($element, 'LOANACCTFROM');
        if ($loanAcctFrom) {
            $loan->accountNumber = $this->getValue($loanAcctFrom, 'LOANACCTID', '');
            $acctType = $this->getValue($loanAcctFrom, 'LOANACCTTYPE');
            $loan->accountType = ($acctType !== null && $acctType !== '') ? $acctType : 'OTHER';
        }
        
        // Balance information
        $loanBal = $this->findChild($element, 'LOANBAL');
        if ($loanBal) {
            $balList = $this->findChild($loanBal, 'BALLIST');
            if ($balList) {
                $balElements = $this->findChildren($balList, 'BAL');
                foreach ($balElements as $bal) {
                    $balType = $this->getValue($bal, 'BALTYPE');
                    $value = $this->getValue($bal, 'VALUE');
                    
                    if ($balType === 'PRINCIPAL' && $value !== null) {
                        $loan->principalBalance = (float)$value;
                    } elseif ($balType === 'AVAILABLE' && $value !== null) {
                        $loan->availableCredit = (float)$value;
                    }
                }
            }
        }
        
        // Interest rate
        $loanRate = $this->findChild($element, 'LOANRATE');
        if ($loanRate) {
            $intRate = $this->getValue($loanRate, 'LOANINTRATE');
            $loan->interestRate = ($intRate !== null && $intRate !== '') ? (float)$intRate : null;
            
            $dtAsOf = $this->getValue($loanRate, 'DTASOF');
            if ($dtAsOf) {
                $loan->interestRateAsOf = $dtAsOf instanceof \DateTimeInterface 
                    ? $dtAsOf 
                    : $this->parseDateTime($dtAsOf);
            }
        }
        
        // Payment information
        $pmtInfo = $this->findChild($element, 'LOANPMTINFO');
        if ($pmtInfo) {
            $loanPmt = $this->getValue($pmtInfo, 'LOANPMT');
            $loan->paymentAmount = ($loanPmt !== null && $loanPmt !== '') ? (float)$loanPmt : null;
            
            $pmtFreq = $this->getValue($pmtInfo, 'LOANPMTFREQ');
            $loan->paymentFrequency = ($pmtFreq !== null && $pmtFreq !== '') ? $pmtFreq : null;
            
            $pmtsRemaining = $this->getValue($pmtInfo, 'LOANPMTSREMAINING');
            $loan->paymentsRemaining = ($pmtsRemaining !== null && $pmtsRemaining !== '') ? (int)$pmtsRemaining : null;
            
            $nextPmt = $this->getValue($pmtInfo, 'LOANNEXTPMT');
            if ($nextPmt) {
                $loan->nextPaymentDate = $nextPmt instanceof \DateTimeInterface 
                    ? $nextPmt 
                    : $this->parseDateTime($nextPmt);
            }
        }
        
        // Remaining amounts
        $loanRemaining = $this->findChild($element, 'LOANREMAINING');
        if ($loanRemaining) {
            $loanInterest = $this->getValue($loanRemaining, 'LOANINTEREST');
            $loan->remainingInterest = ($loanInterest !== null && $loanInterest !== '') ? (float)$loanInterest : null;
        }
        
        // Loan terms
        $initBal = $this->getValue($element, 'LOANINITBAL');
        $loan->initialBalance = ($initBal !== null && $initBal !== '') ? (float)$initBal : null;
        $loan->creditLimit = $loan->initialBalance; // For LOC
        
        $matDate = $this->getValue($element, 'LOANMATURITYDATE');
        if ($matDate) {
            $loan->maturityDate = $matDate instanceof \DateTimeInterface 
                ? $matDate 
                : $this->parseDateTime($matDate);
        }
        
        // Transaction history
        $tranList = $this->findChild($element, 'LOANTRANLIST');
        if ($tranList) {
            $loan->statement = $this->buildLoanStatement($tranList, $loan->currency);
        }
        
        return $loan;
    }
    
    /**
     * Build Statement for loan transactions from SGML
     * 
     * @param Element $element LOANTRANLIST element
     * @param string $currency Currency code
     * @return Statement
     */
    private function buildLoanStatement(Element $element, string $currency): Statement
    {
        $statement = new Statement();
        $statement->currency = $currency;
        
        $dtStart = $this->getValue($element, 'DTSTART');
        if ($dtStart) {
            $statement->startDate = $dtStart instanceof \DateTimeInterface 
                ? $dtStart 
                : $this->parseDateTime($dtStart);
        }
        
        $dtEnd = $this->getValue($element, 'DTEND');
        if ($dtEnd) {
            $statement->endDate = $dtEnd instanceof \DateTimeInterface 
                ? $dtEnd 
                : $this->parseDateTime($dtEnd);
        }
        
        // Build transactions
        $stmtTrnElements = $this->findChildren($element, 'STMTTRN');
        foreach ($stmtTrnElements as $stmtTrn) {
            $transaction = $this->buildTransaction($stmtTrn, $statement);
            $statement->transactions[] = $transaction;
        }
        
        return $statement;
    }
}
