<?php

namespace OfxParser\Entities\Profile;

/**
 * Profile - Financial Institution Profile Information (PROFMSGSRSV1)
 * 
 * **What:**
 * Contains the financial institution's profile, including contact information,
 * supported message sets, and service capabilities.
 * 
 * **Why:**
 * Profile discovery allows clients to determine:
 * - What services the FI offers (banking, credit card, investments)
 * - What message set versions are supported
 * - Service URLs and security requirements
 * - Password/authentication requirements
 * - Transaction limits and fees
 * 
 * **Usage:**
 * Typically retrieved once and cached. Use before making service requests
 * to ensure the FI supports the desired functionality.
 * 
 * **Canadian Context:**
 * Canadian FIs commonly support BANK, CREDITCARD, INVSTMT message sets.
 * Less common: BILLPAY (banks have their own systems), WIREXFER (use Interac).
 */
class Profile
{
    /**
     * @var string Financial institution name
     */
    public $fiName;

    /**
     * @var array Multi-line address (line1, line2, line3)
     */
    public $address = [];

    /**
     * @var string|null City
     */
    public $city;

    /**
     * @var string|null State/Province (e.g., 'ON', 'BC', 'QC' in Canada)
     */
    public $state;

    /**
     * @var string|null Postal/ZIP code
     */
    public $postalCode;

    /**
     * @var string|null Country code (e.g., 'CAN', 'USA')
     */
    public $country;

    /**
     * @var string|null Customer service phone number
     */
    public $customerServicePhone;

    /**
     * @var string|null Technical support phone number
     */
    public $technicalSupportPhone;

    /**
     * @var string|null Fax number
     */
    public $faxPhone;

    /**
     * @var string|null FI website URL
     */
    public $url;

    /**
     * @var string|null Customer support email
     */
    public $email;

    /**
     * @var \DateTime Date profile was last updated
     */
    public $profileLastUpdated;

    /**
     * @var MessageSetInfo[] Supported message sets (BANK, CREDITCARD, INVSTMT, etc.)
     */
    public $messageSets = [];

    /**
     * @var SignonInfo|null Sign-on requirements (password rules, etc.)
     */
    public $signonInfo;
}
