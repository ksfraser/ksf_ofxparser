<?php

namespace OfxParser\Entities;

/**//*******************************************************
* OFX Spec page 208 chapter 11.3.3 Banking Account Info   
	https://financialdataexchange.org/common/Uploaded%20files/OFX%20files/OFX%20Banking%20Specification%20v2.3.pdf
*
*	CCACCTINFO
*
***********************************************************/
class CreditCardAccountInfo extends AbstractEntity
{
	/**
	* @var class
	*/
	public $ccAcctFrom;
	
	/**
	* @var class
	*/
	public $otherAcctInfo;
	
	/**
	* @var string
	*	A22
	*/
	public $parentAcctId;
	
	/**
	* @var bool
	* 	Suports transaction downloads (Y) or Balance Only
	*/
	public $suptxdl;

	/**
	* @var bool  
	*	Enabled as an interbank source
	*/
	public $xferSrc;
	
	/**
	* @var bool  
	*	Enabled as an interbank destination
	*/
	public $xferDest;

        /**
        * @var string
        *       See 11.3.3.1
        *               PERSONAL, BUSINESS, CORPORATE, OTHER
        */
        public $AcctClassification;
	
	/**
	* @var string
	*	AVAIL, PEND, ACTIVE
	*/
	public $svcStatus;
	


}
