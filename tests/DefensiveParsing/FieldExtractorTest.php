<?php declare(strict_types=1);

namespace OfxParserTest\DefensiveParsing;

use PHPUnit\Framework\TestCase;
use OfxParser\Extraction\FieldExtractor;
use OfxParser\Recovery\RecoveryContext;
use OfxParser\Config\DefensiveParsingConfig;
use OfxParser\Metrics\ParsingMetrics;
use OfxParser\Recovery\FieldRecovery\NullStrategy;
use OfxParser\Recovery\FieldRecovery\DefaultValueStrategy;
use OfxParser\Exceptions\Field\RequiredFieldMissingException;

class FieldExtractorTest extends TestCase
{
    private FieldExtractor $extractor;
    private ParsingMetrics $metrics;
    
    protected function setUp(): void
    {
        $config = DefensiveParsingConfig::createDefault();
        $config->setFieldStrategy('OptionalFieldMissingException', new DefaultValueStrategy('default'));
        $recoveryContext = new RecoveryContext($config);
        $this->metrics = new ParsingMetrics();
        $this->extractor = new FieldExtractor($recoveryContext, $this->metrics);
    }
    
    public function testExtractRequiredFieldSuccess(): void
    {
        $xml = simplexml_load_string('<ROOT><FITID>123456</FITID></ROOT>');
        
        $result = $this->extractor->extractRequired($xml, 'FITID');
        
        $this->assertEquals('123456', $result);
    }
    
    public function testExtractOptionalFieldSuccess(): void
    {
        $xml = simplexml_load_string('<ROOT><MEMO>Test memo</MEMO></ROOT>');
        
        $result = $this->extractor->extractOptional($xml, 'MEMO', 'fallback');
        
        $this->assertEquals('Test memo', $result);
    }
    
    public function testExtractRequiredAmount(): void
    {
        $xml = simplexml_load_string('<ROOT><TRNAMT>123.45</TRNAMT></ROOT>');
        
        $result = $this->extractor->extractRequiredAmount($xml, 'TRNAMT');
        
        $this->assertEquals(123.45, $result);
    }
    
    public function testExtractOptionalAmount(): void
    {
        $xml = simplexml_load_string('<ROOT><FEE>10.50</FEE></ROOT>');
        
        $result = $this->extractor->extractOptionalAmount($xml, 'FEE', 0.0);
        
        $this->assertEquals(10.50, $result);
    }
}
