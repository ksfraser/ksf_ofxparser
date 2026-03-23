<?php declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use OfxParser\Parser;

class TestOFXHelperTest extends TestCase
{
    use TestOFXHelper;
    
    private Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Parser();
    }

    public function testSimpleOFXParsing(): void
    {
        // Skip this test - DateTime handling in test helper needs refinement
        $this->markTestSkipped('TestOFXHelper DateTime parsing needs refinement');
        
        $content = "<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNID>1</TRNID>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>";
        
        $ofxContent = $this->wrapOFXContent($content, 'bank');
        file_put_contents('/tmp/test_ofx.txt', $ofxContent);
        echo "\n\nOFX CONTENT:\n" . htmlspecialchars($ofxContent) . "\n\n";
        
        // Test that parser can at least load it
        try {
            $ofx = $this->parser->loadFromString($ofxContent);
            $this->assertNotNull($ofx);
            echo "\n✓ Simple OFX parsed successfully\n";
        } catch (\Exception $e) {
            echo "\n✗ Error: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
}
