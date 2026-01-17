<?php

namespace OfxParser\Entities\Profile;

/**
 * MessageSetInfo - Information about a supported OFX message set
 * 
 * **What:**
 * Details about a specific message set supported by the FI, including
 * version, service URL, and security requirements.
 * 
 * **Why:**
 * Clients need to know:
 * - Which message sets are available (BANK, CREDITCARD, INVSTMT, etc.)
 * - What version to use in requests
 * - Where to send requests (URL may differ by service)
 * - Security/authentication requirements
 * 
 * **Message Set Types:**
 * - SIGNON: Authentication
 * - BANK: Bank accounts
 * - CREDITCARD: Credit cards
 * - INVSTMT: Investment accounts
 * - LOAN: Loan accounts
 * - SECLIST: Security master list
 * - INTERXFER: Inter-bank transfers
 * - BILLPAY: Bill payment
 * - WIREXFER: Wire transfers
 */
class MessageSetInfo
{
    /**
     * @var string Message set type (SIGNON, BANK, CREDITCARD, INVSTMT, etc.)
     */
    public $type;

    /**
     * @var int Version number (typically 1)
     */
    public $version;

    /**
     * @var string Service URL for this message set
     */
    public $url;

    /**
     * @var string OFX security level (NONE, TYPE1, etc.)
     */
    public $ofxSecurity;

    /**
     * @var bool Transport security required (HTTPS)
     */
    public $transportSecurity;

    /**
     * @var string Sign-on realm/domain
     */
    public $realm;

    /**
     * @var string Language code (ENG, FRA, etc.)
     */
    public $language;
}
