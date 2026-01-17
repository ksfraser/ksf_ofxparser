<?php

namespace OfxParser\Entities;

use DateTimeInterface;

/**
 * Represents an interbank transfer (INTERXFERMSGSRSV1)
 * 
 * Describes a transfer of funds between accounts at different financial institutions.
 * Common in scenarios where customers have accounts at multiple banks and want to move
 * money between them.
 */
class InterXfer
{
    /**
     * @var string Server transaction ID - FI's unique identifier for this transfer
     */
    public $serverTransactionId;

    /**
     * @var string Transfer ID - unique identifier for the transfer
     */
    public $transferId;

    /**
     * @var float Amount transferred
     */
    public $amount;

    /**
     * @var DateTimeInterface|null Date when transfer was posted
     */
    public $datePosted;

    /**
     * @var DateTimeInterface|null Date when transfer is due to process
     */
    public $dateDue;

    /**
     * @var DateTimeInterface|null Date when funds will be available in destination account
     */
    public $dateAvailable;

    /**
     * FROM ACCOUNT INFORMATION
     */

    /**
     * @var string Bank ID (routing number) of the source account
     */
    public $fromBankId;

    /**
     * @var string Account ID of the source account
     */
    public $fromAccountId;

    /**
     * @var string Account type of source account (CHECKING, SAVINGS, MONEYMRKT, CREDITLINE)
     */
    public $fromAccountType;

    /**
     * TO ACCOUNT INFORMATION
     */

    /**
     * @var string Bank ID (routing number) of the destination account
     */
    public $toBankId;

    /**
     * @var string Account ID of the destination account
     */
    public $toAccountId;

    /**
     * @var string Account type of destination account (CHECKING, SAVINGS, MONEYMRKT, CREDITLINE)
     */
    public $toAccountType;
}
