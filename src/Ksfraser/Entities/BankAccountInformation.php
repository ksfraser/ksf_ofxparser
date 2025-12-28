<?php

namespace OfxParser\Entities;

/**//*******************************************************
* OFX Spec page 208 chapter 11.3.3 Banking Account Info   
	https://financialdataexchange.org/common/Uploaded%20files/OFX%20files/OFX%20Banking%20Specification%20v2.3.pdf
*
*	BANKACCTINFO
*
***********************************************************/
class BankAccountInfo extends AbstractEntity
{
	/**
	* @var class
	*/
	public $bankAcctFrom;
	
	/**
	* @var class
	*/
	public $otherAcctInfo;
	
	/**
	* @var bool
	* 	Suports transaction downloads (Y) or Balance Only
	*/
	public $suptxdl;

	/**
	* @var string
	*/
	public $loanAcctType;
	
	/**
	* @var \DateTimeInterface
	*	CD Accounts ONLY
	*/
	public $maturityDate;
	
	/**
	* @var float
	*	CD Accounts ONLY
	*/
	public $maturityAmt;
	
	/**
	* @var float
	*	Minimum balance required to avoid fees
	*/
	public $moinBalReq;
	
	/**
	* @var string
	*	See 11.3.3.1
	*		PERSONAL, BUSINESS, CORPORATE, OTHER
	*/
	public $AcctClassification;
	
	/**
	* @var float
	*/
	public $overDraftLimit;
	
	/**
	* @var string
	*	AVAIL, PEND, ACTIVE
	*/
	public $svcStatus;
	


}
