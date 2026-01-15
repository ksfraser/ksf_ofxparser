<?php declare(strict_types=1);

namespace OfxParser\Entities;

class Transaction extends AbstractEntity
{
    private static $types = [
        'CREDIT' => 'Generic credit',
        'DEBIT' => 'Generic debit',
        'INT' => 'Interest earned or paid',
        'DIV' => 'Dividend',
        'FEE' => 'FI fee',
        'SRVCHG' => 'Service charge',
        'DEP' => 'Deposit',
        'ATM' => 'ATM debit or credit',
        'POS' => 'Point of sale debit or credit',
        'XFER' => 'Transfer',
        'CHECK' => 'Cheque',
        'PAYMENT' => 'Electronic payment',
        'CASH' => 'Cash withdrawal',
        'DIRECTDEP' => 'Direct deposit',
        'DIRECTDEBIT' => 'Merchant initiated debit',
        'REPEATPMT' => 'Repeating payment/standing order',
        'OTHER' => 'Other',
        'UNKNOWN' => 'Unknown',
    ];

    /**
     * @var string
     * <TRNTYPE>
     */
    public $type;

    /**
     * Date the transaction was posted <DTPOSTED>
     * @var \DateTimeInterface
     */
    public $date;

    /**
     * Date the user initiated the transaction, if known  <DTUSER>
     * @var \DateTimeInterface|null
     */
    public $userInitiatedDate;

    /**
     * @var float
     * <TRNAMT>
     */
    public $amount;

    /**
     * @var string
     * <FITID>
     */
    public $uniqueId;

    /**
     * @var string
     * <NAME>
     */
    public $name;

    /**
     * @var string
     * <MEMO>
     */
    public $memo;

    /**
     * @var string
     * <SIC>
     */
    public $sic;

    /**
     * @var string
     * <CHECKNUM>
     */
    public $checkNumber;
       /**
     * <REFNUM>
     * @var string
     */
    public $refNumber;

    /**
     * Extended name or description <EXTDNAME>
     * @var string
     */
    public $nameExtended;

    /**
     * Payee Id <PAYEEID>
     * @var string
     */
    Public $payeeId;

    /**
     * Payee requisites <PAYEE>
     * @var 
     */
    public $payee;

    /**
     * Bank account of counterparty
     * @var 
     */
    public $bankAccountTo;

    /**
     * Credit card account of counterparty
     * @var 
     */
    public $cardAccountTo;

    /**
     * Currency information for the transaction amount
     * 
     * What: Contains the currency code (e.g., 'USD', 'EUR') and optionally the
     * exchange rate used when the transaction amount differs from the account's
     * default currency (CURDEF).
     * 
     * Why: Multi-currency transactions are common in international banking. When
     * a transaction occurs in a different currency than the account's base currency,
     * OFX provides CURRENCY to show:
     * - The actual currency the transaction was in (CURSYM)
     * - The exchange rate applied (CURRATE)
     * This allows applications to display both converted and original amounts.
     * 
     * @var array|null ['code' => string, 'rate' => float] or null if same as account currency
     */
    public $currency;

    /**
     * Original currency information before any conversion
     * 
     * What: Contains the original currency details when a transaction has been
     * converted through multiple currencies.
     * 
     * Why: Some financial institutions convert foreign transactions through an
     * intermediate currency before the final account currency. ORIGCURRENCY
     * preserves the true original transaction currency:
     * - Original amount was in ORIGCURRENCY
     * - Converted to CURRENCY (if different)
     * - Finally converted to account CURDEF
     * This maintains the complete audit trail for currency conversions.
     * 
     * Example: A USD purchase on a EUR account via GBP processing:
     * - ORIGCURRENCY: USD at original rate
     * - CURRENCY: GBP at intermediate rate  
     * - CURDEF: EUR (account's base currency)
     * 
     * @var array|null ['code' => string, 'rate' => float] or null if no intermediate conversion
     */
    public $originalCurrency;

    /**
     * Get the associated type description
     *
     * @return string
     */
    public function typeDesc(): string
    {
        // Cast SimpleXMLObject to string
        $type = (string)$this->type;
        return array_key_exists($type, self::$types) ? self::$types[$type] : '';
    }
}
