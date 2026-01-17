<?php

namespace OfxParser\Entities\Profile;

/**
 * SignonInfo - Sign-on requirements and password rules
 * 
 * **What:**
 * Defines authentication requirements including password complexity,
 * length constraints, and allowed character types.
 * 
 * **Why:**
 * Clients need to validate credentials before sending:
 * - Enforce minimum/maximum password length
 * - Check character type requirements
 * - Handle case sensitivity correctly
 * - Determine if PIN change is supported
 * 
 * **Character Types:**
 * - ALPHAONLY: Letters only
 * - NUMERICONLY: Numbers only
 * - ALPHAORNUMERIC: Letters or numbers (not both required)
 * - ALPHAANDNUMERIC: Must contain both letters and numbers
 */
class SignonInfo
{
    /**
     * @var string Sign-on realm (same as in SONRQ)
     */
    public $realm;

    /**
     * @var int Minimum password length
     */
    public $minPasswordLength;

    /**
     * @var int Maximum password length
     */
    public $maxPasswordLength;

    /**
     * @var string Character type requirement
     * Values: ALPHAONLY, NUMERICONLY, ALPHAORNUMERIC, ALPHAANDNUMERIC
     */
    public $charType;

    /**
     * @var bool Password is case sensitive
     */
    public $caseSensitive;

    /**
     * @var bool Special characters allowed (!@#$%^&*, etc.)
     */
    public $specialCharsAllowed;

    /**
     * @var bool Spaces allowed in password
     */
    public $spacesAllowed;

    /**
     * @var bool PIN change supported via OFX
     */
    public $pinChangeSupported;

    /**
     * @var bool|null User must change PIN on first signon
     */
    public $changePasswordOnFirstSignon;
}
