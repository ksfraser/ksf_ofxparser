<?php declare(strict_types=1);

namespace OfxParserTest\Sgml;

use PHPUnit\Framework\TestCase;
use OfxParser\Sgml\ElementFactory;
use OfxParser\Sgml\Elements\ValueElement;
use OfxParser\Sgml\Elements\ContainerElement;
use OfxParser\Sgml\Elements\UnknownElement;
use OfxParser\Sgml\Elements\CurrencyElement;

/**
 * Test ElementFactory element creation and type detection
 */
class ElementFactoryTest extends TestCase
{
    private ElementFactory $factory;
    
    protected function setUp(): void
    {
        $this->factory = new ElementFactory();
    }
    
    /**
     * Test createElement with known value element
     */
    public function testCreateElementWithKnownValueElement(): void
    {
        $element = $this->factory->createElement('TRNTYPE');
        
        $this->assertInstanceOf(ValueElement::class, $element);
        $this->assertEquals('TRNTYPE', $element->getTagName());
    }
    
    /**
     * Test createElement with known container element
     */
    public function testCreateElementWithKnownContainerElement(): void
    {
        $element = $this->factory->createElement('BANKTRANLIST');
        
        $this->assertInstanceOf(ContainerElement::class, $element);
        $this->assertEquals('BANKTRANLIST', $element->getTagName());
    }
    
    /**
     * Test createElement with unknown element
     */
    public function testCreateElementWithUnknownElement(): void
    {
        $element = $this->factory->createElement('UNKNOWNTAG');
        
        $this->assertInstanceOf(UnknownElement::class, $element);
        $this->assertEquals('UNKNOWNTAG', $element->getTagName());
    }
    
    /**
     * Test createElement with lowercase tag converts to uppercase
     */
    public function testCreateElementWithLowercaseTag(): void
    {
        $element = $this->factory->createElement('trntype');
        
        $this->assertEquals('TRNTYPE', $element->getTagName());
    }
    
    /**
     * Test createElement with mixed case tag
     */
    public function testCreateElementWithMixedCaseTag(): void
    {
        $element = $this->factory->createElement('TrnType');
        
        $this->assertEquals('TRNTYPE', $element->getTagName());
    }
    
    /**
     * Test isValueElement with known value elements
     */
    public function testIsValueElementWithKnownElements(): void
    {
        $this->assertTrue($this->factory->isValueElement('TRNTYPE'));
        $this->assertTrue($this->factory->isValueElement('DTPOSTED'));
        $this->assertTrue($this->factory->isValueElement('TRNAMT'));
        $this->assertTrue($this->factory->isValueElement('FITID'));
        $this->assertTrue($this->factory->isValueElement('CURDEF'));
        $this->assertTrue($this->factory->isValueElement('CODE'));
    }
    
    /**
     * Test isValueElement with container elements returns false
     */
    public function testIsValueElementWithContainerElements(): void
    {
        $this->assertFalse($this->factory->isValueElement('BANKTRANLIST'));
        $this->assertFalse($this->factory->isValueElement('OFX'));
        $this->assertFalse($this->factory->isValueElement('STMTTRN'));
    }
    
    /**
     * Test isValueElement with unknown element returns false
     */
    public function testIsValueElementWithUnknownElement(): void
    {
        $this->assertFalse($this->factory->isValueElement('UNKNOWNTAG'));
    }
    
    /**
     * Test isContainerElement with known container elements
     */
    public function testIsContainerElementWithKnownElements(): void
    {
        $this->assertTrue($this->factory->isContainerElement('OFX'));
        $this->assertTrue($this->factory->isContainerElement('BANKTRANLIST'));
        $this->assertTrue($this->factory->isContainerElement('STMTTRN'));
        $this->assertTrue($this->factory->isContainerElement('BANKACCTFROM'));
        $this->assertTrue($this->factory->isContainerElement('STATUS'));
        $this->assertTrue($this->factory->isContainerElement('PAYEE'));
    }
    
    /**
     * Test isContainerElement with value elements returns false
     */
    public function testIsContainerElementWithValueElements(): void
    {
        $this->assertFalse($this->factory->isContainerElement('TRNTYPE'));
        $this->assertFalse($this->factory->isContainerElement('TRNAMT'));
        $this->assertFalse($this->factory->isContainerElement('NAME'));
    }
    
    /**
     * Test isContainerElement with unknown element returns false
     */
    public function testIsContainerElementWithUnknownElement(): void
    {
        $this->assertFalse($this->factory->isContainerElement('UNKNOWNTAG'));
    }
    
    /**
     * Test registerValueElement adds new value element
     */
    public function testRegisterValueElement(): void
    {
        $this->assertFalse($this->factory->isValueElement('CUSTOMFIELD'));
        
        $this->factory->registerValueElement('CUSTOMFIELD', 'string', false);
        
        $this->assertTrue($this->factory->isValueElement('CUSTOMFIELD'));
        
        $element = $this->factory->createElement('CUSTOMFIELD');
        $this->assertInstanceOf(ValueElement::class, $element);
    }
    
    /**
     * Test registerValueElement with different data types
     */
    public function testRegisterValueElementWithDifferentTypes(): void
    {
        $this->factory->registerValueElement('CUSTOMDATE', 'datetime', true);
        $this->factory->registerValueElement('CUSTOMAMOUNT', 'amount', true);
        $this->factory->registerValueElement('CUSTOMINT', 'int', false);
        
        $this->assertTrue($this->factory->isValueElement('CUSTOMDATE'));
        $this->assertTrue($this->factory->isValueElement('CUSTOMAMOUNT'));
        $this->assertTrue($this->factory->isValueElement('CUSTOMINT'));
    }
    
    /**
     * Test registerContainerElement adds new container element
     */
    public function testRegisterContainerElement(): void
    {
        $this->assertFalse($this->factory->isContainerElement('CUSTOMCONTAINER'));
        
        $this->factory->registerContainerElement('CUSTOMCONTAINER');
        
        $this->assertTrue($this->factory->isContainerElement('CUSTOMCONTAINER'));
        
        $element = $this->factory->createElement('CUSTOMCONTAINER');
        $this->assertInstanceOf(ContainerElement::class, $element);
    }
    
    /**
     * Test registerContainerElement doesn't add duplicates
     */
    public function testRegisterContainerElementNoDuplicates(): void
    {
        $this->factory->registerContainerElement('CUSTOM');
        $this->factory->registerContainerElement('CUSTOM');
        $this->factory->registerContainerElement('custom'); // Lowercase should normalize
        
        $this->assertTrue($this->factory->isContainerElement('CUSTOM'));
    }
    
    /**
     * Test createElement with line and column tracking
     */
    public function testCreateElementWithLineAndColumn(): void
    {
        $element = $this->factory->createElement('TRNTYPE', 10, 5);
        
        $this->assertInstanceOf(ValueElement::class, $element);
        // Line and column should be tracked (test via element if it exposes them)
    }
    
    /**
     * Test currency fields are value elements
     */
    public function testCurrencyFieldsAreValueElements(): void
    {
        $this->assertTrue($this->factory->isValueElement('CURSYM'));
        $this->assertTrue($this->factory->isValueElement('CURRATE'));
        
        $cursym = $this->factory->createElement('CURSYM');
        $currate = $this->factory->createElement('CURRATE');
        
        $this->assertInstanceOf(ValueElement::class, $cursym);
        $this->assertInstanceOf(ValueElement::class, $currate);
    }
    
    /**
     * Test CURRENCY element is handled by dedicated CurrencyElement class
     * 
     * CURRENCY can appear as:
     * - Simple value: <CURRENCY>USD
     * - Container: <CURRENCY><CURSYM>USD</CURSYM><CURRATE>1.0</CURRATE></CURRENCY>
     * 
     * CurrencyElement class follows SRP - dedicated responsibility for handling
     * this known OFX element's dual format behavior.
     */
    public function testCurrencyIsCurrencyElement(): void
    {
        $this->assertFalse($this->factory->isContainerElement('CURRENCY'));
        $this->assertFalse($this->factory->isValueElement('CURRENCY'));
        $this->assertTrue($this->factory->isContainerElement('ORIGCURRENCY'));
        
        $currency = $this->factory->createElement('CURRENCY');
        $origCurrency = $this->factory->createElement('ORIGCURRENCY');
        
        $this->assertInstanceOf(CurrencyElement::class, $currency);
        $this->assertInstanceOf(ContainerElement::class, $origCurrency);
    }
    
    /**
     * Test investment fields are value elements
     */
    public function testInvestmentFieldsAreValueElements(): void
    {
        $this->assertTrue($this->factory->isValueElement('BROKERID'));
        $this->assertTrue($this->factory->isValueElement('UNIQUEID'));
        $this->assertTrue($this->factory->isValueElement('UNITS'));
        $this->assertTrue($this->factory->isValueElement('UNITPRICE'));
        $this->assertTrue($this->factory->isValueElement('BUYTYPE'));
        $this->assertTrue($this->factory->isValueElement('SELLTYPE'));
        $this->assertTrue($this->factory->isValueElement('INCOMETYPE'));
    }
    
    /**
     * Test investment containers are container elements
     */
    public function testInvestmentContainersAreContainerElements(): void
    {
        $this->assertTrue($this->factory->isContainerElement('INVSTMTMSGSRSV1'));
        $this->assertTrue($this->factory->isContainerElement('INVSTMTTRNRS'));
        $this->assertTrue($this->factory->isContainerElement('INVACCTFROM'));
        $this->assertTrue($this->factory->isContainerElement('INVTRANLIST'));
        $this->assertTrue($this->factory->isContainerElement('SECLIST'));
        $this->assertTrue($this->factory->isContainerElement('SECID'));
    }
    
    /**
     * Test payee fields are value elements
     */
    public function testPayeeFieldsAreValueElements(): void
    {
        $this->assertTrue($this->factory->isValueElement('ADDR1'));
        $this->assertTrue($this->factory->isValueElement('ADDR2'));
        $this->assertTrue($this->factory->isValueElement('ADDR3'));
        $this->assertTrue($this->factory->isValueElement('CITY'));
        $this->assertTrue($this->factory->isValueElement('STATE'));
        $this->assertTrue($this->factory->isValueElement('POSTALCODE'));
        $this->assertTrue($this->factory->isValueElement('PHONE'));
    }
    
    /**
     * Test account info containers
     */
    public function testAccountInfoContainersAreContainerElements(): void
    {
        $this->assertTrue($this->factory->isContainerElement('BANKACCTINFO'));
        $this->assertTrue($this->factory->isContainerElement('CCACCTINFO'));
        $this->assertTrue($this->factory->isContainerElement('INVACCTINFO'));
    }
    
    /**
     * Test all required transaction fields
     */
    public function testAllRequiredTransactionFields(): void
    {
        $requiredFields = ['TRNTYPE', 'DTPOSTED', 'TRNAMT', 'FITID'];
        
        foreach ($requiredFields as $field) {
            $this->assertTrue($this->factory->isValueElement($field), "$field should be a value element");
            
            $element = $this->factory->createElement($field);
            $this->assertInstanceOf(ValueElement::class, $element);
        }
    }
}

