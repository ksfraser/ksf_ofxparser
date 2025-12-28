<?php

namespace OfxParser\Entities;

/**//*******************************************************
* OFX Spec page 206 chapter 11.3.2 Credit Card Account aggregate
	https://financialdataexchange.org/common/Uploaded%20files/OFX%20files/OFX%20Banking%20Specification%20v2.3.pdf
*
*	CCACCTFROM and CCACCTO
*
***********************************************************/
class CreditCardAccount extends AbstractEntity
{
	/**
	* @var string
	* A22 
    	*/
    	public $acctId;

	/**
     	* @var string
	* A22 
     	*/
    	public $acctKey;


}
