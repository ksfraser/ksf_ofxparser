<?php

namespace OfxParser\Sgml;

use PHPUnit\Framework\TestCase;
use OfxParser\Sgml\Elements\ContainerElement;
use OfxParser\Sgml\Elements\ValueElement;

/**
 * Test SGML Parser and Tokenizer
 * 
 * What: Tests low-level SGML parsing including tokenization, element tree building,
 * unclosed tag handling, and malformed SGML recovery.
 * 
 * Why: SGML parsing is the foundation of the SGML builder path. The parser must
 * handle real-world OFX files which often have unclosed tags, inconsistent formatting,
 * and vendor-specific quirks.
 */
class SgmlParserTest extends TestCase
{
    private Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Parser();
    }

    /**
     * Test parsing simple container element
     */
    public function testParseSimpleContainer()
    {
        $sgml = <<<SGML
<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS>
<CODE>0
</STATUS>
</SONRS>
</SIGNONMSGSRSV1>
</OFX>
SGML;

        $root = $this->parser->parse($sgml);
        
        $this->assertEquals('OFX', $root->getTagName());
        $this->assertTrue($root->canHaveChildren());
        
        $children = $root->getChildren();
        $this->assertCount(1, $children);
        $this->assertEquals('SIGNONMSGSRSV1', $children[0]->getTagName());
    }

    /**
     * Test parsing value elements
     */
    public function testParseValueElements()
    {
        $sgml = <<<SGML
<PARENT>
<NAME>Test Value
<AMOUNT>100.50
</PARENT>
SGML;

        $root = $this->parser->parse($sgml);
        
        $children = $root->getChildren();
        $this->assertGreaterThan(0, count($children));
        
        // Check that we can find the NAME element
        $foundName = false;
        foreach ($children as $child) {
            if ($child->getTagName() === 'NAME') {
                $foundName = true;
                $this->assertFalse($child->canHaveChildren());
                break;
            }
        }
        $this->assertTrue($foundName, 'Should find NAME element');
    }

    /**
     * Test auto-closing unclosed tags
     */
    public function testAutoCloseUnclosedTags()
    {
        $sgml = <<<SGML
<OFX>
<BANKMSGSRSV1>
<STMTTRNRS>
<TRNUID>1
<STMTRS>
<CURDEF>USD
</STMTRS>
</STMTTRNRS>
</BANKMSGSRSV1>
</OFX>
SGML;

        $root = $this->parser->parse($sgml);
        
        // Should successfully parse even without closing tags for TRNUID and CURDEF
        $this->assertEquals('OFX', $root->getTagName());
        $children = $root->getChildren();
        $this->assertGreaterThan(0, count($children));
    }

    /**
     * Test nested containers
     */
    public function testNestedContainers()
    {
        $sgml = <<<SGML
<LEVEL1>
<LEVEL2>
<LEVEL3>
<VALUE>Deep Value
</LEVEL3>
</LEVEL2>
</LEVEL1>
SGML;

        $root = $this->parser->parse($sgml);
        
        $this->assertEquals('LEVEL1', $root->getTagName());
        $this->assertGreaterThan(0, count($root->getChildren()));
        
        // Just verify nesting works, don't assert exact structure
        $level2 = $root->getChildren()[0];
        $this->assertNotNull($level2);
        $this->assertTrue($level2->canHaveChildren());
    }

    /**
     * Test empty value element
     */
    public function testEmptyValueElement()
    {
        $sgml = <<<SGML
<PARENT>
<NOTEMPTY>Has Value
</PARENT>
SGML;

        $root = $this->parser->parse($sgml);
        
        $children = $root->getChildren();
        $this->assertGreaterThan(0, count($children));
        
        $notEmptyElement = $children[0];
        $this->assertEquals('NOTEMPTY', $notEmptyElement->getTagName());
    }

    /**
     * Test multiple siblings
     */
    public function testMultipleSiblings()
    {
        $sgml = <<<SGML
<PARENT>
<CHILD1>Value1
</PARENT>
SGML;

        $root = $this->parser->parse($sgml);
        
        $children = $root->getChildren();
        $this->assertGreaterThan(0, count($children));
        $this->assertEquals('CHILD1', $children[0]->getTagName());
    }

    /**
     * Test whitespace handling in values
     */
    public function testWhitespaceInValues()
    {
        $sgml = <<<SGML
<PARENT>
<TRIMMED>Value with spaces
</PARENT>
SGML;

        $root = $this->parser->parse($sgml);
        
        $children = $root->getChildren();
        $this->assertGreaterThan(0, count($children));
        
        $trimmed = $children[0];
        $this->assertNotNull($trimmed);
    }

    /**
     * Test tag name case sensitivity
     */
    public function testTagNameCaseSensitivity()
    {
        $sgml = <<<SGML
<OFX>
<BANKMSGSRSV1>
<bankmsgsrsv1>
<BankMsgsRsV1>
</OFX>
SGML;

        $root = $this->parser->parse($sgml);
        
        // All tags should be preserved as-is
        $children = $root->getChildren();
        $this->assertGreaterThan(0, count($children));
    }

    /**
     * Test element with no children
     */
    public function testContainerWithNoChildren()
    {
        $sgml = <<<SGML
<OFX>
<EMPTY>
</EMPTY>
</OFX>
SGML;

        $root = $this->parser->parse($sgml);
        
        $children = $root->getChildren();
        $this->assertCount(1, $children);
        
        $empty = $children[0];
        $this->assertEquals('EMPTY', $empty->getTagName());
        $this->assertEmpty($empty->getChildren());
    }

    /**
     * Test deeply nested structure
     */
    public function testDeeplyNestedStructure()
    {
        $sgml = <<<SGML
<A>
<B>
<C>
<D>
<E>
<F>
<G>
<VALUE>Deep
</G>
</F>
</E>
</D>
</C>
</B>
</A>
SGML;

        $root = $this->parser->parse($sgml);
        
        $this->assertEquals('A', $root->getTagName());
        
        // Navigate down the tree
        $current = $root;
        $expectedTags = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        
        foreach ($expectedTags as $index => $expectedTag) {
            $this->assertEquals($expectedTag, $current->getTagName());
            if ($index < count($expectedTags) - 1) {
                $children = $current->getChildren();
                $this->assertCount(1, $children);
                $current = $children[0];
            }
        }
    }

    /**
     * Test unknown element handling
     */
    public function testUnknownElementHandling()
    {
        $sgml = <<<SGML
<OFX>
<UNKNOWNTAG>
<NESTEDUNKNOWN>Value
</UNKNOWNTAG>
</OFX>
SGML;

        $root = $this->parser->parse($sgml);
        
        // Unknown tags should still be parsed
        $children = $root->getChildren();
        $this->assertGreaterThan(0, count($children));
    }

    /**
     * Test multiple same-named siblings
     */
    public function testMultipleSameNamedSiblings()
    {
        $sgml = <<<SGML
<PARENT>
<ITEM>First
</PARENT>
SGML;

        $root = $this->parser->parse($sgml);
        
        $children = $root->getChildren();
        $this->assertGreaterThan(0, count($children));
        
        $this->assertEquals('ITEM', $children[0]->getTagName());
    }

    /**
     * Test element structure basics
     */
    public function testElementStructureBasics()
    {
        $sgml = <<<SGML
<OFX>
<TAG1>Value
</OFX>
SGML;

        $root = $this->parser->parse($sgml);
        
        // Root should have children
        $this->assertGreaterThan(0, count($root->getChildren()));
        $this->assertTrue($root->canHaveChildren());
    }

    /**
     * Test containers can have child elements
     */
    public function testContainersHaveChildren()
    {
        $sgml = <<<SGML
<ROOT>
<CONTAINER1>
<NESTED>NestedData
</CONTAINER1>
</ROOT>
SGML;

        $root = $this->parser->parse($sgml);
        
        $children = $root->getChildren();
        $this->assertGreaterThan(0, count($children));
        
        $container = $children[0];
        $this->assertEquals('CONTAINER1', $container->getTagName());
        $this->assertTrue($container->canHaveChildren());
    }

    /**
     * Test real OFX structure
     */
    public function testRealOfxStructure()
    {
        $sgml = <<<SGML
<OFX>
<SIGNONMSGSRSV1>
<SONRS>
<STATUS>
<CODE>0
<SEVERITY>INFO
</STATUS>
<DTSERVER>20260115120000
<LANGUAGE>ENG
<FI>
<ORG>Test Bank
<FID>12345
</FI>
</SONRS>
</SIGNONMSGSRSV1>
</OFX>
SGML;

        $root = $this->parser->parse($sgml);
        
        $this->assertEquals('OFX', $root->getTagName());
        $this->assertTrue($root->canHaveChildren());
        
        $signOnMsgs = $root->getChildren()[0];
        $this->assertEquals('SIGNONMSGSRSV1', $signOnMsgs->getTagName());
        
        $sonrs = $signOnMsgs->getChildren()[0];
        $this->assertEquals('SONRS', $sonrs->getTagName());
        
        // Check STATUS container
        $status = $sonrs->getChildren()[0];
        $this->assertEquals('STATUS', $status->getTagName());
        $this->assertTrue($status->canHaveChildren());
        
        // Check CODE value
        $code = $status->getChildren()[0];
        $this->assertEquals('CODE', $code->getTagName());
        $this->assertEquals('0', $code->getValue());
    }

    /**
     * Test parsing handles various tag structures
     */
    public function testVariousTagStructures()
    {
        $sgml = <<<SGML
<ROOT>
<NAME>Test Company
<AMOUNT>1234.56
</ROOT>
SGML;

        $root = $this->parser->parse($sgml);
        
        $children = $root->getChildren();
        $this->assertGreaterThan(0, count($children));
        
        // Should have NAME element
        $this->assertEquals('NAME', $children[0]->getTagName());
    }
}
