<?php

namespace OfxParser\Sgml;

use OfxParser\Sgml\Elements\Element;
use OfxParser\Sgml\Elements\ValueElement;
use OfxParser\Sgml\Elements\ContainerElement;
use OfxParser\Sgml\Elements\UnknownElement;

/**
 * Factory for creating appropriate Element subclass based on tag name
 * Encapsulates OFX schema knowledge
 */
class ElementFactory
{
    /**
     * Known value elements with their data types
     * Format: 'TAGNAME' => ['type' => 'datatype', 'required' => bool]
     */
    private static array $valueElements = [
        // Transaction fields
        'TRNTYPE' => ['type' => 'string', 'required' => true],
        'DTPOSTED' => ['type' => 'datetime', 'required' => true],
        'DTUSER' => ['type' => 'datetime', 'required' => false],
        'DTAVAIL' => ['type' => 'datetime', 'required' => false],
        'TRNAMT' => ['type' => 'amount', 'required' => true],
        'FITID' => ['type' => 'string', 'required' => true],
        'CORRECTFITID' => ['type' => 'string', 'required' => false],
        'CORRECTACTION' => ['type' => 'string', 'required' => false],
        'CHECKNUM' => ['type' => 'string', 'required' => false],
        'REFNUM' => ['type' => 'string', 'required' => false],
        'SIC' => ['type' => 'string', 'required' => false],
        'PAYEEID' => ['type' => 'string', 'required' => false],
        'NAME' => ['type' => 'string', 'required' => false],
        'DESC' => ['type' => 'string', 'required' => false],
        'EXTDNAME' => ['type' => 'string', 'required' => false],
        'MEMO' => ['type' => 'string', 'required' => false],
        
        // Account fields
        'BANKID' => ['type' => 'string', 'required' => false],
        'BRANCHID' => ['type' => 'string', 'required' => false],
        'ACCTID' => ['type' => 'string', 'required' => true],
        'ACCTTYPE' => ['type' => 'string', 'required' => false],
        'ACCTKEY' => ['type' => 'string', 'required' => false],
        
        // Balance fields
        'BALAMT' => ['type' => 'amount', 'required' => true],
        'DTASOF' => ['type' => 'datetime', 'required' => true],
        
        // Statement fields
        'CURDEF' => ['type' => 'string', 'required' => true],
        'DTSTART' => ['type' => 'datetime', 'required' => true],
        'DTEND' => ['type' => 'datetime', 'required' => true],
        
        // Sign-on fields
        'DTSERVER' => ['type' => 'datetime', 'required' => true],
        'LANGUAGE' => ['type' => 'string', 'required' => false],
        'DTPROFUP' => ['type' => 'datetime', 'required' => false],
        'DTACCTUP' => ['type' => 'datetime', 'required' => false],
        
        // Status fields
        'CODE' => ['type' => 'int', 'required' => true],
        'SEVERITY' => ['type' => 'string', 'required' => true],
        'MESSAGE' => ['type' => 'string', 'required' => false],
        
        // Institution fields
        'ORG' => ['type' => 'string', 'required' => false],
        'FID' => ['type' => 'string', 'required' => false],
        
        // Payee fields
        'ADDR1' => ['type' => 'string', 'required' => false],
        'ADDR2' => ['type' => 'string', 'required' => false],
        'ADDR3' => ['type' => 'string', 'required' => false],
        'CITY' => ['type' => 'string', 'required' => false],
        'STATE' => ['type' => 'string', 'required' => false],
        'POSTALCODE' => ['type' => 'string', 'required' => false],
        'COUNTRY' => ['type' => 'string', 'required' => false],
        'PHONE' => ['type' => 'string', 'required' => false],
        
        // Investment fields
        'BROKERID' => ['type' => 'string', 'required' => true],
        'UNIQUEID' => ['type' => 'string', 'required' => false],
        'UNIQUEIDTYPE' => ['type' => 'string', 'required' => false],
        'HELDINACCT' => ['type' => 'string', 'required' => false],
        'POSTYPE' => ['type' => 'string', 'required' => false],
        'UNITS' => ['type' => 'decimal', 'required' => false],
        'UNITPRICE' => ['type' => 'amount', 'required' => false],
        'MKTVAL' => ['type' => 'amount', 'required' => false],
        'DTPRICEASOF' => ['type' => 'datetime', 'required' => false],
        'MEMO2' => ['type' => 'string', 'required' => false],
        'DTTRADE' => ['type' => 'datetime', 'required' => false],
        'DTSETTLE' => ['type' => 'datetime', 'required' => false],
        'TOTAL' => ['type' => 'amount', 'required' => false],
        'SUBACCTSEC' => ['type' => 'string', 'required' => false],
        'SUBACCTFUND' => ['type' => 'string', 'required' => false],
        'BUYTYPE' => ['type' => 'string', 'required' => false],
        'SELLTYPE' => ['type' => 'string', 'required' => false],
        'INCOMETYPE' => ['type' => 'string', 'required' => false],
        'COMMISSION' => ['type' => 'amount', 'required' => false],
        'FEES' => ['type' => 'amount', 'required' => false],
        'LOAD' => ['type' => 'amount', 'required' => false],
        'TAXES' => ['type' => 'amount', 'required' => false],
        'PENALTY' => ['type' => 'amount', 'required' => false],
        'WITHHOLDING' => ['type' => 'amount', 'required' => false],
        'TAXEXEMPT' => ['type' => 'boolean', 'required' => false],
        
        // Security List fields (SECLISTMSGSRSV1)
        'SECNAME' => ['type' => 'string', 'required' => true],
        'TICKER' => ['type' => 'string', 'required' => false],
        'DEBTTYPE' => ['type' => 'string', 'required' => false],
        'DEBTCLASS' => ['type' => 'string', 'required' => false],
        'COUPONRT' => ['type' => 'decimal', 'required' => false],
        'DTMAT' => ['type' => 'datetime', 'required' => false],
        'PARVALUE' => ['type' => 'amount', 'required' => false],
        'MFASSETCLASS' => ['type' => 'string', 'required' => false],
        'FIMFASSETCLASS' => ['type' => 'string', 'required' => false],
        
        // Loan Account fields (LOANMSGSRSV1)
        'LOANACCTID' => ['type' => 'string', 'required' => true],
        'LOANACCTTYPE' => ['type' => 'string', 'required' => false],
        'LOANINTRATE' => ['type' => 'decimal', 'required' => false],
        'LOANPMT' => ['type' => 'amount', 'required' => false],
        'LOANNEXTPMT' => ['type' => 'datetime', 'required' => false],
        'LOANPMTFREQ' => ['type' => 'string', 'required' => false],
        'LOANPMTSREMAINING' => ['type' => 'int', 'required' => false],
        'LOANPRINCIPAL' => ['type' => 'amount', 'required' => false],
        'LOANINTEREST' => ['type' => 'amount', 'required' => false],
        'LOANINITBAL' => ['type' => 'amount', 'required' => false],
        'LOANMATURITYDATE' => ['type' => 'datetime', 'required' => false],
        'BALTYPE' => ['type' => 'string', 'required' => false],
        'VALUE' => ['type' => 'amount', 'required' => false],
        
        // Currency fields
        'CURSYM' => ['type' => 'string', 'required' => false],
        'CURRATE' => ['type' => 'decimal', 'required' => false],
        
        // Marketing info
        'MKTGINFO' => ['type' => 'string', 'required' => false],
        
        // Transaction UID
        'TRNUID' => ['type' => 'string', 'required' => false],
        
        // Profile fields (PROFMSGSRSV1)
        'FINAME' => ['type' => 'string', 'required' => true],
        'ADDR1' => ['type' => 'string', 'required' => false],
        'ADDR2' => ['type' => 'string', 'required' => false],
        'ADDR3' => ['type' => 'string', 'required' => false],
        'CSPHONE' => ['type' => 'string', 'required' => false],
        'TSPHONE' => ['type' => 'string', 'required' => false],
        'FAXPHONE' => ['type' => 'string', 'required' => false],
        'DTPROFUP' => ['type' => 'datetime', 'required' => true],
        'VER' => ['type' => 'int', 'required' => true],
        'URL' => ['type' => 'string', 'required' => false],
        'EMAIL' => ['type' => 'string', 'required' => false],
        'OFXSEC' => ['type' => 'string', 'required' => true],
        'TRANSPSEC' => ['type' => 'boolean', 'required' => true],
        'SIGNONREALM' => ['type' => 'string', 'required' => true],
        'MIN' => ['type' => 'int', 'required' => true],
        'MAX' => ['type' => 'int', 'required' => true],
        'CHARTYPE' => ['type' => 'string', 'required' => true],
        'CASESEN' => ['type' => 'boolean', 'required' => true],
        'SPECIAL' => ['type' => 'boolean', 'required' => true],
        'SPACES' => ['type' => 'boolean', 'required' => true],
        'PINCH' => ['type' => 'boolean', 'required' => true],
        'CHGPINFIRST' => ['type' => 'boolean', 'required' => false],
    ];

    /**
     * Known container elements
     */
    private static array $containerElements = [
        'OFX',
        'SIGNONMSGSRSV1',
        'SIGNONMSGSRQV1',
        'SONRS',
        'SONRQ',
        'STATUS',
        'FI',
        'BANKMSGSRSV1',
        'BANKMSGSRQV1',
        'STMTTRNRS',
        'STMTTRNRQ',
        'STMTRS',
        'STMTRQ',
        'BANKACCTFROM',
        'BANKACCTTO',
        'BANKTRANLIST',
        'STMTTRN',
        'LEDGERBAL',
        'AVAILBAL',
        'BALLIST',
        'BAL',
        'CREDITCARDMSGSRSV1',
        'CREDITCARDMSGSRQV1',
        'CCSTMTTRNRS',
        'CCSTMTTRNRQ',
        'CCSTMTRS',
        'CCSTMTRQ',
        'CCACCTFROM',
        'CCACCTTO',
        'INVSTMTMSGSRSV1',
        'INVSTMTMSGSRQV1',
        'INVSTMTTRNRS',
        'INVSTMTTRNRQ',
        'INVSTMTRS',
        'INVSTMTRQ',
        'INVACCTFROM',
        'INVTRANLIST',
        'INVBANKTRAN',
        'INVPOS',
        'INVPOSLIST',
        'SECLIST',
        'SECINFO',
        'SECID',
        'SECLISTMSGSRSV1',  // Security List message set
        'STOCKINFO',        // Stock security
        'DEBTINFO',         // Bond/Debt security
        'MFINFO',           // Mutual fund security
        'OPTINFO',          // Option security
        'OTHERINFO',        // Other security type
        'LOANMSGSRSV1',     // Loan message set
        'LOANSTMTTRNRS',    // Loan statement response
        'LOANSTMTRS',       // Loan statement
        'LOANACCTFROM',     // Loan account identifier
        'LOANBAL',          // Loan balance
        'LOANRATE',         // Loan interest rate
        'LOANPMTINFO',      // Loan payment information
        'LOANREMAINING',    // Loan remaining amounts
        'LOANTRANLIST',     // Loan transaction list
        'PAYEE',
        // Note: CURRENCY is NOT listed here - it can be either value OR container
        // When it's <CURRENCY>USD, it's an UnknownElement with textValue
        // When it's <CURRENCY><CURSYM>USD</CURSYM>, it's an UnknownElement with children
        'ORIGCURRENCY',
        'BANKACCTINFO',
        'CCACCTINFO',
        'INVACCTINFO',
        'PROFMSGSRSV1',     // Profile message set
        'PROFRS',           // Profile response
        'MSGSETLIST',       // Message set list
        'SIGNONMSGSET',     // Signon message set
        'SIGNONMSGSETV1',   // Signon message set version 1
        'BANKMSGSET',       // Bank message set
        'BANKMSGSETV1',     // Bank message set version 1
        'CREDITCARDMSGSET', // Credit card message set
        'CREDITCARDMSGSETV1', // Credit card message set version 1
        'INVSTMTMSGSET',    // Investment message set
        'INVSTMTMSGSETV1',  // Investment message set version 1
        'INTERXFERMSGSET',  // Interbank transfer message set
        'INTERXFERMSGSETV1', // Interbank transfer message set version 1
        'WIREXFERMSGSET',   // Wire transfer message set
        'WIREXFERMSGSETV1', // Wire transfer message set version 1
        'BILLPAYMSGSET',    // Bill payment message set
        'BILLPAYMSGSETV1',  // Bill payment message set version 1
        'EMAILMSGSET',      // Email message set
        'EMAILMSGSETV1',    // Email message set version 1
        'SECLISTMSGSET',    // Security list message set
        'SECLISTMSGSETV1',  // Security list message set version 1
        'LOANMSGSET',       // Loan message set
        'LOANMSGSETV1',     // Loan message set version 1
        'TAX1099MSGSET',    // Tax 1099 message set
        'TAX1099MSGSETV1',  // Tax 1099 message set version 1
        'MSGSETCORE',       // Message set core info
        'SIGNONINFOLIST',   // Signon info list
        'SIGNONINFO',       // Signon info (individual entry - though it contains fields)
    ];

    /**
     * Create appropriate element based on tag name
     */
    public function createElement(string $tagName, int $line = 0, int $column = 0): Element
    {
        $tagUpper = strtoupper($tagName);

        // Check if it's a known value element
        if (isset(self::$valueElements[$tagUpper])) {
            $config = self::$valueElements[$tagUpper];
            return new ValueElement(
                $tagUpper,
                $config['type'],
                $config['required'],
                $line,
                $column
            );
        }

        // Check if it's a known container element
        if (in_array($tagUpper, self::$containerElements)) {
            return new ContainerElement($tagUpper, $line, $column);
        }

        // Unknown element - allow forward compatibility
        return new UnknownElement($tagUpper, $line, $column);
    }

    /**
     * Check if tag is a known value element
     */
    public function isValueElement(string $tagName): bool
    {
        return isset(self::$valueElements[strtoupper($tagName)]);
    }

    /**
     * Check if tag is a known container element
     */
    public function isContainerElement(string $tagName): bool
    {
        return in_array(strtoupper($tagName), self::$containerElements);
    }

    /**
     * Add custom value element definition (for extensibility)
     */
    public function registerValueElement(string $tagName, string $dataType = 'string', bool $required = false): void
    {
        self::$valueElements[strtoupper($tagName)] = [
            'type' => $dataType,
            'required' => $required,
        ];
    }

    /**
     * Add custom container element (for extensibility)
     */
    public function registerContainerElement(string $tagName): void
    {
        $tagUpper = strtoupper($tagName);
        if (!in_array($tagUpper, self::$containerElements)) {
            self::$containerElements[] = $tagUpper;
        }
    }
}
