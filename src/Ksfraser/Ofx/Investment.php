<?php declare(strict_types=1);

namespace OfxParser\Ofx;

use SimpleXMLElement;
use OfxParser\Ofx;
use OfxParser\Utils;
use OfxParser\Entities\Statement;
use OfxParser\Entities\Investment\Account as InvestmentAccount;
use OfxParser\Entities\Investment\Transaction\Banking;
use OfxParser\Entities\Investment\Transaction\BuyMutualFund;
use OfxParser\Entities\Investment\Transaction\BuySecurity;
use OfxParser\Entities\Investment\Transaction\BuyStock;
use OfxParser\Entities\Investment\Transaction\Income;
use OfxParser\Entities\Investment\Transaction\Reinvest;
use OfxParser\Entities\Investment\Transaction\SellMutualFund;
use OfxParser\Entities\Investment\Transaction\SellSecurity;
use OfxParser\Entities\Investment\Transaction\SellStock;
use OfxParser\Builder\TransactionBuilder;
use OfxParser\Extraction\FieldExtractor;
use OfxParser\Metrics\ParsingMetrics;

class Investment extends Ofx
{
    /**
     * @param SimpleXMLElement $xml
     * @param TransactionBuilder|null $transactionBuilder Optional defensive transaction builder
     * @param FieldExtractor|null $fieldExtractor Optional defensive field extractor
     * @param ParsingMetrics|null $metrics Optional metrics tracker
     * @throws \Exception
     */
    public function __construct(
        SimpleXMLElement $xml,
        ?TransactionBuilder $transactionBuilder = null,
        ?FieldExtractor $fieldExtractor = null,
        ?ParsingMetrics $metrics = null
    ) {
        // Call parent constructor to set defensive parsing dependencies
        parent::__construct($xml, $transactionBuilder, $fieldExtractor, $metrics);
        
        // Investment-specific initialization (override parent's bank account setup)
        $this->bankAccounts = [];
        $this->bankAccount = null;
        
        $this->signOn = $this->buildSignOn($xml->SIGNONMSGSRSV1->SONRS);

        if (isset($xml->INVSTMTMSGSRSV1)) {
            $this->bankAccounts = $this->buildAccounts($xml);
        }

        // Set a helper if only one bank account
        if (count($this->bankAccounts) === 1) {
            $this->bankAccount = $this->bankAccounts[0];
        }
    }

    /**
     * @param SimpleXMLElement $xml
     * @return array Array of InvestmentAccount enities
     * @throws \Exception
     */
    protected function buildAccounts(SimpleXMLElement $xml): array
    {
        // Loop through the bank accounts
        $accounts = [];
        foreach ($xml->INVSTMTMSGSRSV1->INVSTMTTRNRS as $accountStatement) {
            foreach ($accountStatement->INVSTMTRS as $statementResponse) {
                $accounts[] = $this->buildAccount(trim((string)$accountStatement->TRNUID), $statementResponse);
            }
        }
        return $accounts;
    }

    /**
     * @param string $transactionUid
     * @param SimpleXMLElement $statementResponse
     * @return InvestmentAccount
     * @throws \Exception
     */
    protected function buildAccount(string $transactionUid, SimpleXMLElement $statementResponse): InvestmentAccount
    {
        $account = new InvestmentAccount();
        $account->transactionUid = (string) $transactionUid;
        $account->brokerId = trim((string) $statementResponse->INVACCTFROM->BROKERID);
        $account->accountNumber = trim((string) $statementResponse->INVACCTFROM->ACCTID);

        $account->statement = new Statement();
        $account->statement->currency = (string) $statementResponse->CURDEF;

        if (@count($statementResponse->INVTRANLIST)) {
            $account->statement->startDate = Utils::createDateTimeFromStr(
                (string)$statementResponse->INVTRANLIST->DTSTART
            );

            $account->statement->endDate = Utils::createDateTimeFromStr(
                (string)$statementResponse->INVTRANLIST->DTEND
            );

            $account->statement->transactions = $this->buildTransactions(
                $statementResponse->INVTRANLIST->children()
            );
        } else {
            // Shouldn't this just be the default value in the Entity?
            $account->statement->transactions = [];
        }

        return $account;
    }

    /**
     * Processes multiple types of investment transactions, ignoring many
     * others.
     *
     * @param SimpleXMLElement $transactions
     * @return array
     * @throws \Exception
     */
    protected function buildTransactions(SimpleXMLElement $transactions): array
    {
        $activity = [];

        foreach ($transactions as $t) {
            $item = null;

            switch ($t->getName()) {
                case 'BUYMF':
                    $item = new BuyMutualFund();
                    break;
                case 'BUYOTHER':
                    $item = new BuySecurity();
                    break;
                case 'BUYSTOCK':
                    $item = new BuyStock();
                    break;
                case 'INCOME':
                    $item = new Income();
                    break;
                case 'INVBANKTRAN':
                    $item = new Banking();
                    break;
                case 'REINVEST':
                    $item = new Reinvest();
                    break;
                case 'SELLMF':
                    $item = new SellMutualFund();
                    break;
                case 'SELLOTHER':
                    $item = new SellSecurity();
                    break;
                case 'SELLSTOCK':
                    $item = new SellStock();
                    break;
                case 'DTSTART':
                    // already processed
                    break;
                case 'DTEND':
                    // already processed
                    break;
                default:
                    // Log: ignored node....
                    break;
            }

            if (!is_null($item)) {
                $item->loadOfx($t);
                $activity[] = $item;
            }
        }

        return $activity;
    }
}
