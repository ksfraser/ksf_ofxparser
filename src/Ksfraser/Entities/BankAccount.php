<?php

namespace OfxParser\Entities;

/**//********************************************************************************
*
* Parser sets the following:
        $bankAccount->transactionUid = $transactionUid;
        $bankAccount->agencyNumber = $statementResponse->BANKACCTFROM->BRANCHID;
        $bankAccount->accountNumber = $statementResponse->BANKACCTFROM->ACCTID;
        $bankAccount->routingNumber = $statementResponse->BANKACCTFROM->BANKID;
        $bankAccount->accountType = $statementResponse->BANKACCTFROM->ACCTTYPE;
        $bankAccount->balance = $statementResponse->LEDGERBAL->BALAMT;

It's too bad the author didn't follow the spec e.g. agencyNumber.
*
**********************************************************************************/
   

class BankAccount extends AbstractEntity
{
    /**
     * @var string
     */
    public $accountNumber;

    /**
     * @var string
     */
    public $accountType;

    /**
     * @var string
     */
    public $balance;

    /**
     * @var \DateTimeInterface
     */
    public $balanceDate;

    /**
     * @var string
     */
    public $routingNumber;

    /**
     * @var Statement
     */
    public $statement;

    /**
     * @var string
     */
    public $transactionUid;

    /**
     * @var string
     */
    public $agencyNumber;
}
