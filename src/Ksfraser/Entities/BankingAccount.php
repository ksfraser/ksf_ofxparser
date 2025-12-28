<?php

namespace OfxParser\Entities;

/**//*******************************************************
* OFX Spec page 203 chapter 11.3.1 Banking Account from/to
	https://financialdataexchange.org/common/Uploaded%20files/OFX%20files/OFX%20Banking%20Specification%20v2.3.pdf
*
***********************************************************/
class BankingAccount extends AbstractEntity
{
    /**
     * @var string
  	* In Canada - Routing and Transit
	* A9
     */
    public $bankId;

    /**
     * @var string
	* Can - Not Present
	* A22
     */
    public $branchId;

    /**
     * @var string
	* A22 
     */
    public $acctId;

    /**
     * @var string
	* 11.3.1.1
	*	Checking, Savings, Moneymrkt, creditline, cd

     */
    public $accountType;
    
	/**
     	* @var string
	* A22 
	* Used as a checksum
	* Can - Not Present
     	*/
    	public $accountKey;


}
