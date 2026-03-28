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
        
        // Test that parser can at least load it
        $ofx = $this->parser->loadFromString($ofxContent);
        $this->assertNotNull($ofx);
    }
}
