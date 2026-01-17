<?php declare(strict_types=1);

namespace OfxParser;

use SimpleXMLElement;
use OfxParser\Entities\AccountInfo;
use OfxParser\Entities\BankAccount;
use OfxParser\Entities\Institute;
use OfxParser\Entities\SignOn;
use OfxParser\Entities\Statement;
use OfxParser\Entities\Status;
use OfxParser\Entities\Transaction;
use OfxParser\Entities\Payee;
use OfxParser\Builder\TransactionBuilder;
use OfxParser\Extraction\FieldExtractor;
use OfxParser\Metrics\ParsingMetrics;

/**
 * The OFX object
 *
 * Heavily refactored from Guillaume Bailleul's grimfor/ofxparser
 *
 * Second refactor by Oliver Lowe to unify the API across all
 * OFX data-types.
 *
 * Based on Andrew A Smith's Ruby ofx-parser
 *
 * @author Guillaume BAILLEUL <contact@guillaume-bailleul.fr>
 * @author James Titcumb <hello@jamestitcumb.com>
 * @author Oliver Lowe <mrtriangle@gmail.com>
 */
class Ofx
{
    /**
     * OFX Header - contains file metadata like version, encoding, charset
     * Example: ['OFXHEADER' => '100', 'VERSION' => '102', 'ENCODING' => 'USASCII', ...]
     * @var array
     */
    public $header = [];
    
    /**
     * @var TransactionBuilder|null
     */
    private ?TransactionBuilder $transactionBuilder = null;
    
    /**
     * @var FieldExtractor|null
     */
    private ?FieldExtractor $fieldExtractor = null;
    
    /**
     * @var ParsingMetrics|null
     */
    private ?ParsingMetrics $metrics = null;

    /**
     * @var SignOn
     */
    public $signOn;

    /**
     * @var AccountInfo[]
     */
    public $signupAccountInfo;

    /**
     * @var BankAccount[]
     */
    public $bankAccounts = [];

    /**
     * Only populated if there is only one bank account
     * @var BankAccount|null
     * @deprecated This will be removed in future versions
     */
    public $bankAccount;
    
    /**
     * Security List (SECLISTMSGSRSV1)
     * @var \OfxParser\Entities\Investment\SecurityList|null
     */
    public $securityList;
    
    /**
     * Loan Accounts (LOANMSGSRSV1)
     * @var \OfxParser\Entities\Loan\LoanAccount[]
     */
    public $loanAccounts = [];

    /**
     * Profile (PROFMSGSRSV1)
     * @var \OfxParser\Entities\Profile\Profile|null
     */
    public $profile;

    /**
     * Interbank Transfers (INTERXFERMSGSRSV1)
     * @var \OfxParser\Entities\InterXfer[]
     */
    public $interXfers = [];

    /**
     * @param SimpleXMLElement|null $xml Optional XML element to parse (null for SGML builder path)
     * @param TransactionBuilder|null $transactionBuilder Optional defensive transaction builder
     * @param FieldExtractor|null $fieldExtractor Optional defensive field extractor
     * @param ParsingMetrics|null $metrics Optional metrics tracker
     * @throws \Exception
     */
    public function __construct(
        ?SimpleXMLElement $xml = null,
        ?TransactionBuilder $transactionBuilder = null,
        ?FieldExtractor $fieldExtractor = null,
        ?ParsingMetrics $metrics = null
    ) {
        $this->transactionBuilder = $transactionBuilder;
        $this->fieldExtractor = $fieldExtractor;
        $this->metrics = $metrics;
        
        // If no XML provided, allow direct population (for SGML builder)
        if ($xml === null) {
            return;
        }
        
        //From upgestao repo
        if (!property_exists($xml, 'SIGNONMSGSRSV1') || !property_exists($xml->SIGNONMSGSRSV1, 'SONRS')) {
            $xml = self::createTags($xml);
        }
        //!upgestao
        $this->signOn = $this->buildSignOn($xml->SIGNONMSGSRSV1->SONRS);
        
        // Handle optional SIGNUPMSGSRSV1
        if (isset($xml->SIGNUPMSGSRSV1) && isset($xml->SIGNUPMSGSRSV1->ACCTINFOTRNRS)) {
            $this->signupAccountInfo = $this->buildAccountInfo($xml->SIGNUPMSGSRSV1->ACCTINFOTRNRS);
        } else {
            $this->signupAccountInfo = [];
        }

        if (isset($xml->BANKMSGSRSV1)) {
            $this->bankAccounts = $this->buildBankAccounts($xml);
        } elseif (isset($xml->CREDITCARDMSGSRSV1)) {
            $this->bankAccounts = $this->buildCreditAccounts($xml);
        }

        // Set a helper if only one bank account
        if (count($this->bankAccounts) === 1) {
            $this->bankAccount = $this->bankAccounts[0];
        }
        
        // Parse Security List (SECLISTMSGSRSV1)
        if (isset($xml->SECLISTMSGSRSV1)) {
            $this->securityList = $this->buildSecurityList($xml->SECLISTMSGSRSV1);
        }
        
        // Parse Loan Accounts (LOANMSGSRSV1)
        if (isset($xml->LOANMSGSRSV1)) {
            $this->loanAccounts = $this->buildLoanAccounts($xml->LOANMSGSRSV1);
        }
        
        // Parse Profile (PROFMSGSRSV1)
        if (isset($xml->PROFMSGSRSV1->PROFRS)) {
            $this->profile = $this->buildProfile($xml->PROFMSGSRSV1->PROFRS);
        }
        
        // Parse Interbank Transfers (INTERXFERMSGSRSV1)
        if (isset($xml->INTERXFERMSGSRSV1)) {
            $this->interXfers = $this->buildInterXfers($xml->INTERXFERMSGSRSV1);
        }
    }

    /**
     * Get the transactions that have been processed
     *
     * @return array
     * @deprecated This will be removed in future versions
     */
    public function getTransactions(): array
    {
        return $this->bankAccount->statement->transactions;
    }

    /**
     * Store the OFX header information (file metadata)
     * 
     * The header contains important metadata about the OFX file:
     * - OFXHEADER: OFX specification version (e.g., "100")
     * - VERSION: OFX version number (e.g., "102", "202")
     * - DATA: Data format (e.g., "OFXSGML", "OFXXML")
     * - ENCODING: Character encoding (e.g., "USASCII", "UTF-8")
     * - CHARSET: Character set (e.g., "1252", "NONE")
     * - COMPRESSION: Compression method (e.g., "NONE")
     * - OLDFILEUID/NEWFILEUID: Unique file identifiers
     * 
     * @param array $header Associative array of header key-value pairs
     * @return self For method chaining
     */
    public function buildHeader(array $header): self
    {
        $this->header = $header;
        return $this;
    }

    /**
     * @param SimpleXMLElement $xml
     * @return SignOn
     * @throws \Exception
     */
    protected function buildSignOn(SimpleXMLElement $xml): SignOn
    {
        $signOn = new SignOn();
        $signOn->status = $this->buildStatus($xml->STATUS);
        $signOn->date = $this->createDateTimeFromStr((string)$xml->DTSERVER, true);
        $signOn->language = (string)$xml->LANGUAGE;

        $signOn->institute = new Institute();
        $signOn->institute->name = (string)$xml->FI->ORG;
//20240721 MANU files don't have an FI sectuib in the signog.
	if( isset( $xml->FI->FID ) )
        	$signOn->institute->id = (string)$xml->FI->FID;
	else
	{
		if( isset( $xml->{'INTU.BID'} ) )
		{
			//var_dump( __LINE__ );
        		$signOn->institute->id = (string)$xml->{'INTU.BID'};
			//var_dump( $signOn->institute );
		}
		else
		{
			//var_dump( __LINE__ );
		}
/* */
	}

        return $signOn;
    }

    /**
     * @param SimpleXMLElement|null $xml
     * @return array AccountInfo
     */
    private function buildAccountInfo(?SimpleXMLElement $xml = null): array
    {
        if (null === $xml || !isset($xml->ACCTINFO)) {
            return [];
        }

        $accounts = [];
        foreach ($xml->ACCTINFO as $account) {
            $accountInfo = new AccountInfo();
            $accountInfo->desc = (string)$account->DESC;
            $accountInfo->number = (string)$account->ACCTID;
            $accounts[] = $accountInfo;
        }

        return $accounts;
    }

    /**
     * @param SimpleXMLElement $xml
     * @return array
     * @throws \Exception
     */
    private function buildCreditAccounts(SimpleXMLElement $xml): array
    {
        // Loop through the bank accounts
        $bankAccounts = [];

        foreach ($xml->CREDITCARDMSGSRSV1->CCSTMTTRNRS as $accountStatement) {
            $bankAccounts[] = $this->buildCreditAccount($accountStatement);
        }
        return $bankAccounts;
    }

    /**
     * @param SimpleXMLElement $xml
     * @return array
     * @throws \Exception
     */
    private function buildBankAccounts(SimpleXMLElement $xml): array
    {
        // Loop through the bank accounts
        // Note: OFX spec uses STMTRNRS (not STMTTRNRS with extra T)
        $bankAccounts = [];
        $tagName = isset($xml->BANKMSGSRSV1->STMTRNRS) ? 'STMTRNRS' : 'STMTTRNRS';
        
        foreach ($xml->BANKMSGSRSV1->$tagName as $accountStatement) {
            foreach ($accountStatement->STMTRS as $statementResponse) {
                $bankAccounts[] = $this->buildBankAccount((string)$accountStatement->TRNUID, $statementResponse);
            }
        }
        return $bankAccounts;
    }

    /**
     * @param string $transactionUid
     * @param SimpleXMLElement $statementResponse
     * @return BankAccount
     * @throws \Exception
     */
    private function buildBankAccount(string $transactionUid, SimpleXMLElement $statementResponse): BankAccount
    {
        $bankAccount = new BankAccount();
        $bankAccount->transactionUid = $transactionUid;
        $bankAccount->agencyNumber = (string)$statementResponse->BANKACCTFROM->BRANCHID;
        $bankAccount->accountNumber = (string)$statementResponse->BANKACCTFROM->ACCTID;
        $bankAccount->routingNumber = (string)$statementResponse->BANKACCTFROM->BANKID;
        $bankAccount->accountType = (string)$statementResponse->BANKACCTFROM->ACCTTYPE;
        /**
         * Original
        *$bankAccount->balance = $statementResponse->LEDGERBAL->BALAMT;
        *$bankAccount->balanceDate = $this->createDateTimeFromStr(
        *    $statementResponse->LEDGERBAL->DTASOF,
        *    true
        *);
        */
        //From upgastao repo
        $bankAccount->balance = isset($statementResponse->LEDGERBAL->BALAMT) ? (string)$statementResponse->LEDGERBAL->BALAMT : '';
        $bankAccount->balanceDate = isset($statementResponse->LEDGERBAL->DTASOF) ? $this->createDateTimeFromStr((string)$statementResponse->LEDGERBAL->DTASOF, true) : null;

        $bankAccount->statement = new Statement();
        $bankAccount->statement->currency = (string)$statementResponse->CURDEF;

        $bankAccount->statement->startDate = $this->createDateTimeFromStr(
            (string)$statementResponse->BANKTRANLIST->DTSTART
        );

        $bankAccount->statement->endDate = $this->createDateTimeFromStr(
            (string)$statementResponse->BANKTRANLIST->DTEND
        );

        // Pass the BANKTRANLIST parent element, let buildTransactions extract STMTTRNs
        $bankAccount->statement->transactions = $this->buildTransactions(
            $statementResponse->BANKTRANLIST
        );

        return $bankAccount;
    }

    /**
     * @param SimpleXMLElement $xml
     * @return BankAccount
     * @throws \Exception
     */
    private function buildCreditAccount(SimpleXMLElement $xml): BankAccount
    {
        $nodeName = 'CCACCTFROM';
        if (!isset($xml->CCSTMTRS->$nodeName)) {
            $nodeName = 'BANKACCTFROM';
        }

        $creditAccount = new BankAccount();
        $creditAccount->transactionUid = $xml->TRNUID;
        $creditAccount->agencyNumber = (string)$xml->CCSTMTRS->$nodeName->BRANCHID;
        $creditAccount->accountNumber = (string)$xml->CCSTMTRS->$nodeName->ACCTID;
        $creditAccount->routingNumber = (string)$xml->CCSTMTRS->$nodeName->BANKID;
        $creditAccount->accountType = (string)$xml->CCSTMTRS->$nodeName->ACCTTYPE;
        $creditAccount->balance = (float)$xml->CCSTMTRS->LEDGERBAL->BALAMT;
        $creditAccount->balanceDate = $this->createDateTimeFromStr((string)$xml->CCSTMTRS->LEDGERBAL->DTASOF, true);

        $creditAccount->statement = new Statement();
        $creditAccount->statement->currency = (string)$xml->CCSTMTRS->CURDEF;
        $creditAccount->statement->startDate = $this->createDateTimeFromStr((string)$xml->CCSTMTRS->BANKTRANLIST->DTSTART);
        $creditAccount->statement->endDate = $this->createDateTimeFromStr((string)$xml->CCSTMTRS->BANKTRANLIST->DTEND);
        // Pass the BANKTRANLIST parent element, let buildTransactions extract STMTTRNs
        $creditAccount->statement->transactions = $this->buildTransactions($xml->CCSTMTRS->BANKTRANLIST);

        return $creditAccount;
    }

    /**
     * @param SimpleXMLElement $transactionList Parent element containing STMTTRN children (typically BANKTRANLIST)
     * @return array
     * @throws \Exception
     */
    private function buildTransactions(SimpleXMLElement $transactionList): array
    {
        // Use defensive parsing if available
        if ($this->transactionBuilder !== null) {
            // Defensive parser expects BANKTRANLIST, extract STMTTRN for it
            $stmtTrnElements = $transactionList->STMTTRN;
            return $this->transactionBuilder->buildTransactions($stmtTrnElements);
        }
        
        // Original implementation (backward compatibility)
        // Expect BANKTRANLIST parent, extract all STMTTRN children
        $return = [];
        
        // Use xpath to get all STMTTRN children - works consistently for 1 or many
        $transactions = $transactionList->xpath('.//STMTTRN');
        if ($transactions === false || empty($transactions)) {
            return $return;
        }
        
        foreach ($transactions as $t) {
            $transaction = new Transaction();
            $transaction->type = (string)$t->TRNTYPE;
            $transaction->date = $this->createDateTimeFromStr((string)$t->DTPOSTED);
            if ('' !== (string)$t->DTUSER) {
                $transaction->userInitiatedDate = $this->createDateTimeFromStr((string)$t->DTUSER);
            }
            $transaction->amount = $this->createAmountFromStr((string)$t->TRNAMT);
            $transaction->uniqueId = (string)$t->FITID;
            $transaction->name = (string)$t->NAME;
			//Original
            //$transaction->memo = (string)$t->MEMO;
			//From DevCapere
			$memo = [];
            foreach ($t->MEMO as $m) {
                $memo[] = (string)$m;
            }
			$transaction->memo = implode(' ', $memo);
            $transaction->sic = (string)$t->SIC;
            $transaction->checkNumber = (string)$t->CHECKNUM;
            $transaction->refNumber = (string) $t->REFNUM;
            $transaction->nameExtended = (string) $t->EXTDNAME;
            $transaction->payeeId = (string) $t->PAYEEID;
            if(isset($t->PAYEE)) $transaction->payee = $this->buildPayee($t->PAYEE);
            if(isset($t->BANKACCTTO)) $transaction->bankAccountTo = $this->buildBankAccountTo($t->BANKACCTTO);
            if(isset($t->CCACCTTO)) $transaction->cardAccountTo = $this->buildCardAccountTo($t->CCACCTTO);
            
            // Currency information (OFX spec: multi-currency transaction support)
            if(isset($t->CURRENCY)) $transaction->currency = $this->buildCurrency($t->CURRENCY);
            if(isset($t->ORIGCURRENCY)) $transaction->originalCurrency = $this->buildCurrency($t->ORIGCURRENCY);
            
            $return[] = $transaction;
        }

        return $return;
    }

    /**
     * @param SimpleXMLElement $xml
     * @return Status
     */
    private function buildStatus(SimpleXMLElement $xml): Status
    {
        $status = new Status();
        $status->code = (int)$xml->CODE;
        $status->severity = (string)$xml->SEVERITY;
        $status->message = (string)$xml->MESSAGE;

        return $status;
    }

    /**
     * Create a DateTime object from a valid OFX date format
     *
     * Supports:
     * YYYYMMDDHHMMSS.XXX[gmt offset:tz name]
     * YYYYMMDDHHMMSS.XXX
     * YYYYMMDDHHMMSS
     * YYYYMMDD
     *
     * @param  string $dateString
     * @param  boolean $ignoreErrors
     * @return \DateTime $dateString
     * @throws \Exception
     */
    private function createDateTimeFromStr(string $dateString, bool $ignoreErrors = false): ?\DateTime
    {
        // Handle empty strings  
        if (!isset($dateString) || trim($dateString) === '') {
            if ($ignoreErrors) {
                return null;  // Return null for empty optional fields
            }
            throw new \RuntimeException('Failed to initialize DateTime for string: ');  // Throw for empty required fields
        }
        
        return Utils::createDateTimeFromStr($dateString, $ignoreErrors);
    }

    /**
     * Create a formatted number in Float according to different locale options
     *
     * From upgastao repo - more robust implementation
     * 
     * Supports:
     * 000,00 and -000,00
     * 0.000,00 and -0.000,00
     * 0,000.00 and -0,000.00
     * 000.00 and 000.00
     *
     * @param  string $amountString
     * @return float
     */
    private function createAmountFromStr(string $amountString): float
    {
        $amountString = trim($amountString);

        if (preg_match('/^(?<signal>[-\+]?)(?<integer>.*)(?<separator>[\.,])(?<decimals>[\d]+)$/', $amountString, $matches) === 1) {
            $amountString = $matches['signal'] . preg_replace('/[^\d]+/', '', $matches['integer']) . '.' . $matches['decimals'];
        }

        return (float)$amountString;
    }

    /**
     * @return Ofx New tag creation enchancement
     3..90* From upgastao repo
     */
    public function createTags(SimpleXMLElement $xml): SimpleXMLElement {

            if(!property_exists($xml, 'SIGNONMSGSRSV1')) {

                $newXml = new SimpleXMLElement('<root/>');

                $signonmsgsrsv1 = $newXml->addChild('SIGNONMSGSRSV1');
                $signonmsgsrsv1->addChild('SONRS');

                foreach ($xml->children() as $child) {
                    $newChild = $newXml->addChild($child->getName(), (string) $child);
                    foreach ($child->attributes() as $attrKey => $attrValue) {
                        $newChild->addAttribute($attrKey, $attrValue);
                    }
                    self::copyChildren($child, $newChild);
                }

                $xml = $newXml;

            }else if(!property_exists($xml->SIGNONMSGSRSV1, 'SONRS')) {

                $newXml = new SimpleXMLElement('<root/>');

                foreach ($xml->children() as $child) {
                    $newChild = $newXml->addChild($child->getName(), (string) $child);
                    foreach ($child->attributes() as $attrKey => $attrValue) {
                        $newChild->addAttribute($attrKey, $attrValue);
                    }
                    if ($child->getName() === 'SIGNONMSGSRSV1') {
                        $sonrs = $newChild->addChild('SONRS');
                    }

                    self::copyChildren($child, $newChild);
                }

                $xml = $newXml;
            }

        return $xml;
    }

       /**
     * Copy and return the new tag
     * From upgastao repo
     */
   public function copyChildren(SimpleXMLElement $from, SimpleXMLElement $to): void {
    foreach ($from->children() as $child) {
        $newChild = $to->addChild($child->getName(), (string) $child);
        foreach ($child->attributes() as $attrKey => $attrValue) {
            $newChild->addAttribute($attrKey, $attrValue);
        }
        self::copyChildren($child, $newChild);
    }
}
    /**
     * Builds payee of transaction
     * 
     *  Out of Okonst repo
     * 
     * @param SimpleXMLElement $xml
     * @return Payee
     */
    private function buildPayee(SimpleXMLElement $xml): Payee
    {
        $payee = new Payee();
        // name
        $payee->name = (string) $xml->NAME;
        // address
        $address = [];
        if((string) $xml->ADDR1) $address[] = (string) $xml->ADDR1;
        if((string) $xml->ADDR2) $address[] = (string) $xml->ADDR2;
        if((string) $xml->ADDR3) $address[] = (string) $xml->ADDR3;
        if(count($address) > 0) $payee->address = $address;

        $payee->city = (string) $xml->CITY;
        $payee->state = (string) $xml->STATE;
        $payee->postalCode = (string) $xml->POSTALCODE;
        $payee->country = (string) $xml->COUNTRY;
        $payee->phone = (string) $xml->PHONE;

        return $payee;
    }

    /**
     * Build currency information from CURRENCY or ORIGCURRENCY element
     * 
     * What: Extracts currency code (CURSYM) and exchange rate (CURRATE) from XML.
     * 
     * Why: Multi-currency transactions include currency conversion details per OFX spec.
     * Applications use this to display amounts in multiple currencies and calculate
     * original foreign currency amounts.
     * 
     * @param SimpleXMLElement $xml CURRENCY or ORIGCURRENCY element
     * @return array|null ['code' => string, 'rate' => float] or null if incomplete
     */
    private function buildCurrency(SimpleXMLElement $xml): ?array
    {
        $code = (string) $xml->CURSYM;
        $rate = (string) $xml->CURRATE;
        
        // Both fields required for valid currency information
        if ($code === '' || $rate === '') {
            return null;
        }
        
        return [
            'code' => $code,
            'rate' => (float) $rate
        ];
    }

    /**
     * Builds corresponding bank account of transaction
     * 
     *  Out of Okonst repo
     * 
     * @param SimpleXMLElement $xml
     * @return BankAccount
     */
    public function buildBankAccountTo(SimpleXMLElement $xml): BankAccount
    {
        $bankAccountTo = new BankAccount();
        $bankAccountTo->routingNumber = (string) $xml->BANKID;
        $bankAccountTo->agencyNumber = (string) $xml->BRANCHID;
        $bankAccountTo->accountNumber = (string) $xml->ACCTID;
        $bankAccountTo->accountType = (string) $xml->ACCTTYPE;

        // remove other attrs
        unset($bankAccountTo->balance, $bankAccountTo->balanceDate, $bankAccountTo->statement, $bankAccountTo->transactionUid);

        return $bankAccountTo;
    }

    /**
     * Builds corresponding credit card account of transaction
     * 
     *  Out of Okonst repo
     * 
     * @param SimpleXMLElement $xml
     * @return BankAccount
     */
    public function buildCardAccountTo(SimpleXMLElement $xml): BankAccount
    {
        $cardAccountTo = new BankAccount();
        $cardAccountTo->accountNumber = (string) $xml->ACCTID;

        // remove other attrs
        unset($cardAccountTo->routingNumber, $cardAccountTo->agencyNumber, $cardAccountTo->accountType, $cardAccountTo->balance, $cardAccountTo->balanceDate, $cardAccountTo->statement, $cardAccountTo->transactionUid);

        return $cardAccountTo;
    }
    
    /**
     * Build Security List from SECLISTMSGSRSV1
     * 
     * Implements DRY principle: Single method handles all security types (STOCK, BOND, MF, OTHER)
     * 
     * @param SimpleXMLElement $xml SECLISTMSGSRSV1 element
     * @return \OfxParser\Entities\Investment\SecurityList
     */
    private function buildSecurityList(SimpleXMLElement $xml): \OfxParser\Entities\Investment\SecurityList
    {
        $securityList = new \OfxParser\Entities\Investment\SecurityList();
        
        if (!isset($xml->SECLIST)) {
            return $securityList;
        }
        
        // Parse different security types (DRY: unified approach)
        $securityTypes = [
            'STOCKINFO' => 'STOCK',
            'DEBTINFO' => 'BOND',
            'MFINFO' => 'MUTUALFUND',
            'OPTINFO' => 'OPTION',
            'OTHERINFO' => 'OTHER'
        ];
        
        foreach ($securityTypes as $xmlTag => $securityType) {
            if (isset($xml->SECLIST->$xmlTag)) {
                foreach ($xml->SECLIST->$xmlTag as $secInfo) {
                    $security = $this->buildSecurity($secInfo, $securityType);
                    $securityList->addSecurity($security);
                }
            }
        }
        
        return $securityList;
    }
    
    /**
     * Build individual Security entity
     * 
     * Implements SOLID: Single responsibility for building one security
     * 
     * @param SimpleXMLElement $xml Security info element (STOCKINFO, DEBTINFO, etc.)
     * @param string $securityType Type of security
     * @return \OfxParser\Entities\Investment\Security
     */
    private function buildSecurity(SimpleXMLElement $xml, string $securityType): \OfxParser\Entities\Investment\Security
    {
        $security = new \OfxParser\Entities\Investment\Security();
        
        // All securities have SECINFO child
        $secInfo = $xml->SECINFO;
        
        // Required fields
        $security->securityId = (string) $secInfo->SECID->UNIQUEID;
        $security->securityIdType = (string) $secInfo->SECID->UNIQUEIDTYPE;
        $security->name = (string) $secInfo->SECNAME;
        $security->securityType = $securityType;
        
        // Optional common fields
        $security->ticker = isset($secInfo->TICKER) ? (string) $secInfo->TICKER : null;
        $security->memo = isset($secInfo->MEMO) ? (string) $secInfo->MEMO : null;
        $security->unitPrice = isset($secInfo->UNITPRICE) ? (float) $secInfo->UNITPRICE : null;
        $security->currency = isset($secInfo->CURRENCY) ? (string) $secInfo->CURRENCY : null;
        
        if (isset($secInfo->DTPRICEASOF)) {
            $security->priceDateOf = $this->createDateTimeFromStr((string) $secInfo->DTPRICEASOF);
        }
        
        // Bond-specific fields
        if ($securityType === 'BOND' && isset($xml->DEBTTYPE)) {
            $security->debtType = (string) $xml->DEBTTYPE;
            $security->debtClass = isset($xml->DEBTCLASS) ? (string) $xml->DEBTCLASS : null;
            $security->couponRate = isset($xml->COUPONRT) ? (float) $xml->COUPONRT : null;
            $security->parValue = isset($xml->PARVALUE) ? (float) $xml->PARVALUE : null;
            
            if (isset($xml->DTMAT)) {
                $security->maturityDate = $this->createDateTimeFromStr((string) $xml->DTMAT);
            }
        }
        
        // Mutual fund-specific fields
        if ($securityType === 'MUTUALFUND') {
            $security->assetClass = isset($xml->MFASSETCLASS) ? (string) $xml->MFASSETCLASS : null;
            $security->fiAssetClass = isset($xml->FIMFASSETCLASS) ? (string) $xml->FIMFASSETCLASS : null;
        }
        
        return $security;
    }
    
    /**
     * Build Loan Accounts from LOANMSGSRSV1
     * 
     * Implements SOLID: Delegates to buildLoanAccount for each account
     * 
     * @param SimpleXMLElement $xml LOANMSGSRSV1 element
     * @return \OfxParser\Entities\Loan\LoanAccount[]
     */
    private function buildLoanAccounts(SimpleXMLElement $xml): array
    {
        $loanAccounts = [];
        
        if (!isset($xml->LOANSTMTTRNRS)) {
            return $loanAccounts;
        }
        
        foreach ($xml->LOANSTMTTRNRS as $loanStatement) {
            if (isset($loanStatement->LOANSTMTRS)) {
                $loanAccount = $this->buildLoanAccount($loanStatement->LOANSTMTRS);
                $loanAccounts[] = $loanAccount;
            }
        }
        
        return $loanAccounts;
    }
    
    /**
     * Build individual Loan Account entity
     * 
     * Implements SOLID: Single responsibility for building one loan account
     * Implements DI: Uses existing Utils for date parsing
     * 
     * @param SimpleXMLElement $xml LOANSTMTRS element
     * @return \OfxParser\Entities\Loan\LoanAccount
     */
    private function buildLoanAccount(SimpleXMLElement $xml): \OfxParser\Entities\Loan\LoanAccount
    {
        $loan = new \OfxParser\Entities\Loan\LoanAccount();
        
        // Currency
        $loan->currency = (string) $xml->CURDEF;
        
        // Account identification
        $loan->accountNumber = (string) $xml->LOANACCTFROM->LOANACCTID;
        $loan->accountType = isset($xml->LOANACCTFROM->LOANACCTTYPE) 
            ? (string) $xml->LOANACCTFROM->LOANACCTTYPE 
            : 'OTHER';
        
        // Balance information (DRY: loop through balances)
        if (isset($xml->LOANBAL->BALLIST->BAL)) {
            foreach ($xml->LOANBAL->BALLIST->BAL as $bal) {
                $balType = (string) $bal->BALTYPE;
                $value = (float) $bal->VALUE;
                
                if ($balType === 'PRINCIPAL') {
                    $loan->principalBalance = $value;
                } elseif ($balType === 'AVAILABLE') {
                    $loan->availableCredit = $value;
                }
            }
        }
        
        // Interest rate
        if (isset($xml->LOANRATE)) {
            $loan->interestRate = (float) $xml->LOANRATE->LOANINTRATE;
            if (isset($xml->LOANRATE->DTASOF)) {
                $loan->interestRateAsOf = $this->createDateTimeFromStr((string) $xml->LOANRATE->DTASOF);
            }
        }
        
        // Payment information
        if (isset($xml->LOANPMTINFO)) {
            $pmtInfo = $xml->LOANPMTINFO;
            $loan->paymentAmount = isset($pmtInfo->LOANPMT) ? (float) $pmtInfo->LOANPMT : null;
            $loan->paymentFrequency = isset($pmtInfo->LOANPMTFREQ) ? (string) $pmtInfo->LOANPMTFREQ : null;
            $loan->paymentsRemaining = isset($pmtInfo->LOANPMTSREMAINING) ? (int) $pmtInfo->LOANPMTSREMAINING : null;
            
            if (isset($pmtInfo->LOANNEXTPMT)) {
                $loan->nextPaymentDate = $this->createDateTimeFromStr((string) $pmtInfo->LOANNEXTPMT);
            }
        }
        
        // Remaining amounts
        if (isset($xml->LOANREMAINING)) {
            $loan->remainingInterest = isset($xml->LOANREMAINING->LOANINTEREST) 
                ? (float) $xml->LOANREMAINING->LOANINTEREST 
                : null;
        }
        
        // Loan terms
        $loan->initialBalance = isset($xml->LOANINITBAL) ? (float) $xml->LOANINITBAL : null;
        $loan->creditLimit = isset($xml->LOANINITBAL) ? (float) $xml->LOANINITBAL : null; // For LOC
        
        if (isset($xml->LOANMATURITYDATE)) {
            $loan->maturityDate = $this->createDateTimeFromStr((string) $xml->LOANMATURITYDATE);
        }
        
        // Transaction history (reuse existing statement/transaction logic)
        if (isset($xml->LOANTRANLIST)) {
            $loan->statement = $this->buildLoanStatement($xml->LOANTRANLIST, $loan->currency);
        }
        
        return $loan;
    }
    
    /**
     * Build Statement for loan transactions
     * 
     * Implements DRY: Reuses Transaction entity and building logic
     * 
     * @param SimpleXMLElement $xml LOANTRANLIST element
     * @param string $currency Currency code
     * @return Statement
     */
    private function buildLoanStatement(SimpleXMLElement $xml, string $currency): Statement
    {
        $statement = new Statement();
        $statement->currency = $currency;
        $statement->startDate = $this->createDateTimeFromStr((string) $xml->DTSTART);
        $statement->endDate = $this->createDateTimeFromStr((string) $xml->DTEND);
        
        if (isset($xml->STMTTRN)) {
            foreach ($xml->STMTTRN as $xmlTransaction) {
                // Reuse existing transaction building logic (DRY principle)
                if ($this->transactionBuilder) {
                    $transaction = $this->transactionBuilder->buildTransaction($xmlTransaction, $statement);
                } else {
                    $transaction = $this->buildTransaction($xmlTransaction, $statement);
                }
                $statement->transactions[] = $transaction;
            }
        }
        
        return $statement;
    }

    /**
     * Build Profile from PROFRS element (XML format)
     * 
     * @param SimpleXMLElement $xml PROFRS element
     * @return \OfxParser\Entities\Profile\Profile
     */
    private function buildProfile(SimpleXMLElement $xml): \OfxParser\Entities\Profile\Profile
    {
        $profile = new \OfxParser\Entities\Profile\Profile();
        
        // FI contact information
        $profile->fiName = (string) $xml->FINAME;
        
        // Address (multi-line)
        if (isset($xml->ADDR1)) {
            $profile->address['line1'] = (string) $xml->ADDR1;
        }
        if (isset($xml->ADDR2)) {
            $profile->address['line2'] = (string) $xml->ADDR2;
        }
        if (isset($xml->ADDR3)) {
            $profile->address['line3'] = (string) $xml->ADDR3;
        }
        
        $profile->city = isset($xml->CITY) ? (string) $xml->CITY : null;
        $profile->state = isset($xml->STATE) ? (string) $xml->STATE : null;
        $profile->postalCode = isset($xml->POSTALCODE) ? (string) $xml->POSTALCODE : null;
        $profile->country = isset($xml->COUNTRY) ? (string) $xml->COUNTRY : null;
        
        // Contact numbers
        $profile->customerServicePhone = isset($xml->CSPHONE) ? (string) $xml->CSPHONE : null;
        $profile->technicalSupportPhone = isset($xml->TSPHONE) ? (string) $xml->TSPHONE : null;
        $profile->faxPhone = isset($xml->FAXPHONE) ? (string) $xml->FAXPHONE : null;
        
        $profile->url = isset($xml->URL) ? (string) $xml->URL : null;
        $profile->email = isset($xml->EMAIL) ? (string) $xml->EMAIL : null;
        
        // Profile last updated
        $profile->profileLastUpdated = $this->createDateTimeFromStr((string) $xml->DTPROFUP);
        
        // Message sets
        if (isset($xml->MSGSETLIST)) {
            $profile->messageSets = $this->buildMessageSets($xml->MSGSETLIST);
        }
        
        // Signon info
        if (isset($xml->SIGNONINFOLIST->SIGNONINFO)) {
            $profile->signonInfo = $this->buildSignonInfo($xml->SIGNONINFOLIST->SIGNONINFO);
        }
        
        return $profile;
    }

    /**
     * Build message sets from MSGSETLIST element
     * 
     * @param SimpleXMLElement $xml MSGSETLIST element
     * @return \OfxParser\Entities\Profile\MessageSetInfo[]
     */
    private function buildMessageSets(SimpleXMLElement $xml): array
    {
        $messageSets = [];
        
        $messageSetTypes = [
            'SIGNONMSGSET' => 'SIGNON',
            'BANKMSGSET' => 'BANK',
            'CREDITCARDMSGSET' => 'CREDITCARD',
            'INVSTMTMSGSET' => 'INVSTMT',
            'INTERXFERMSGSET' => 'INTERXFER',
            'WIREXFERMSGSET' => 'WIREXFER',
            'BILLPAYMSGSET' => 'BILLPAY',
            'EMAILMSGSET' => 'EMAIL',
            'SECLISTMSGSET' => 'SECLIST',
            'LOANMSGSET' => 'LOAN',
            'TAX1099MSGSET' => 'TAX1099',
        ];
        
        foreach ($messageSetTypes as $xmlTag => $type) {
            if (isset($xml->$xmlTag)) {
                $msgSetVersion = $xmlTag . 'V1';
                if (isset($xml->$xmlTag->$msgSetVersion)) {
                    $messageSet = $this->buildMessageSetInfo($xml->$xmlTag->$msgSetVersion, $type);
                    $messageSets[] = $messageSet;
                }
            }
        }
        
        return $messageSets;
    }

    /**
     * Build MessageSetInfo from message set version element
     * 
     * @param SimpleXMLElement $xml Message set version element (e.g., BANKMSGSETV1)
     * @param string $type Message set type (e.g., 'BANK')
     * @return \OfxParser\Entities\Profile\MessageSetInfo
     */
    private function buildMessageSetInfo(SimpleXMLElement $xml, string $type): \OfxParser\Entities\Profile\MessageSetInfo
    {
        $messageSet = new \OfxParser\Entities\Profile\MessageSetInfo();
        $messageSet->type = $type;
        
        if (isset($xml->MSGSETCORE)) {
            $core = $xml->MSGSETCORE;
            $messageSet->version = (int) $core->VER;
            $messageSet->url = (string) $core->URL;
            $messageSet->ofxSecurity = (string) $core->OFXSEC;
            $messageSet->transportSecurity = strtoupper((string) $core->TRANSPSEC) === 'Y';
            $messageSet->realm = (string) $core->SIGNONREALM;
            $messageSet->language = (string) $core->LANGUAGE;
        }
        
        return $messageSet;
    }

    /**
     * Build SignonInfo from SIGNONINFO element
     * 
     * @param SimpleXMLElement $xml SIGNONINFO element
     * @return \OfxParser\Entities\Profile\SignonInfo
     */
    private function buildSignonInfo(SimpleXMLElement $xml): \OfxParser\Entities\Profile\SignonInfo
    {
        $signonInfo = new \OfxParser\Entities\Profile\SignonInfo();
        
        $signonInfo->realm = (string) $xml->SIGNONREALM;
        $signonInfo->minPasswordLength = (int) $xml->MIN;
        $signonInfo->maxPasswordLength = (int) $xml->MAX;
        $signonInfo->charType = (string) $xml->CHARTYPE;
        $signonInfo->caseSensitive = strtoupper((string) $xml->CASESEN) === 'Y';
        $signonInfo->specialCharsAllowed = strtoupper((string) $xml->SPECIAL) === 'Y';
        $signonInfo->spacesAllowed = strtoupper((string) $xml->SPACES) === 'Y';
        $signonInfo->pinChangeSupported = strtoupper((string) $xml->PINCH) === 'Y';
        
        if (isset($xml->CHGPINFIRST)) {
            $signonInfo->changePasswordOnFirstSignon = strtoupper((string) $xml->CHGPINFIRST) === 'Y';
        }
        
        return $signonInfo;
    }

    /**
     * Build interbank transfers from INTERXFERMSGSRSV1
     * 
     * Parses INTERXFERTRNRS elements containing INTERXFERRS responses
     * 
     * @param SimpleXMLElement $xml INTERXFERMSGSRSV1 element
     * @return \OfxParser\Entities\InterXfer[]
     */
    private function buildInterXfers(SimpleXMLElement $xml): array
    {
        $interXfers = [];
        
        // Check for transaction responses
        if (!isset($xml->INTERXFERTRNRS)) {
            return $interXfers;
        }
        
        // Handle both single and multiple INTERXFERTRNRS elements
        $responses = $xml->INTERXFERTRNRS;
        if (!is_array($responses)) {
            $responses = [$responses];
        }
        
        foreach ($responses as $trnrs) {
            // Check if response contains INTERXFERRS
            if (!isset($trnrs->INTERXFERRS)) {
                continue;
            }
            
            $xferRs = $trnrs->INTERXFERRS;
            
            // Check for XFERINFO
            if (!isset($xferRs->XFERINFO)) {
                continue;
            }
            
            $xferInfo = $xferRs->XFERINFO;
            
            // Build the InterXfer entity
            $interXfer = new \OfxParser\Entities\InterXfer();
            
            // Server transaction ID
            if (isset($xferRs->SRVRTID)) {
                $interXfer->serverTransactionId = (string) $xferRs->SRVRTID;
            }
            
            // Transfer ID
            if (isset($xferInfo->XFERID)) {
                $interXfer->transferId = (string) $xferInfo->XFERID;
            }
            
            // Amount
            if (isset($xferInfo->TRNAMT)) {
                $interXfer->amount = (float) $xferInfo->TRNAMT;
            }
            
            // Date posted
            if (isset($xferInfo->DTPOSTED)) {
                $interXfer->datePosted = $this->createDateTimeFromStr((string) $xferInfo->DTPOSTED, true);
            }
            
            // Date due
            if (isset($xferInfo->DTDUE)) {
                $interXfer->dateDue = $this->createDateTimeFromStr((string) $xferInfo->DTDUE, true);
            }
            
            // Date available (DTXFERPRJ)
            if (isset($xferInfo->DTXFERPRJ)) {
                $interXfer->dateAvailable = $this->createDateTimeFromStr((string) $xferInfo->DTXFERPRJ, true);
            }
            
            // From account information
            if (isset($xferInfo->FROMACCTINFO->BANKACCTFROM)) {
                $fromAcct = $xferInfo->FROMACCTINFO->BANKACCTFROM;
                $interXfer->fromBankId = (string) $fromAcct->BANKID;
                $interXfer->fromAccountId = (string) $fromAcct->ACCTID;
                $interXfer->fromAccountType = (string) $fromAcct->ACCTTYPE;
            }
            
            // To account information
            if (isset($xferInfo->TOACCTINFO->BANKACCTTO)) {
                $toAcct = $xferInfo->TOACCTINFO->BANKACCTTO;
                $interXfer->toBankId = (string) $toAcct->BANKID;
                $interXfer->toAccountId = (string) $toAcct->ACCTID;
                $interXfer->toAccountType = (string) $toAcct->ACCTTYPE;
            }
            
            $interXfers[] = $interXfer;
        }
        
        return $interXfers;
    }
}
