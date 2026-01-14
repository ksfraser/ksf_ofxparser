<?php declare(strict_types=1);

namespace OfxParserTest\DefensiveParsing;

use PHPUnit\Framework\TestCase;
use OfxParser\Parser;
use OfxParser\Config\DefensiveParsingConfig;
use OfxParser\Metrics\ParsingResult;
use OfxParser\Ofx;

class ParserIntegrationTest extends TestCase
{
    public function testParserWithoutDefensiveParsing(): void
    {
        $parser = new Parser();
        
        $this->assertFalse($parser->isDefensiveParsingEnabled());
    }
    
    public function testParserWithDefensiveParsingEnabled(): void
    {
        $parser = new Parser();
        $parser->withDefensiveParsing();
        
        $this->assertTrue($parser->isDefensiveParsingEnabled());
    }
    
    public function testParserWithDefensiveParsingCustomConfig(): void
    {
        $parser = new Parser();
        $config = DefensiveParsingConfig::createStrict();
        $parser->withDefensiveParsing($config);
        
        $this->assertTrue($parser->isDefensiveParsingEnabled());
    }
}
