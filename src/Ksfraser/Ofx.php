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
}
