<?php declare(strict_types=1);

namespace OfxParserTest\DefensiveParsing;

use PHPUnit\Framework\TestCase;
use OfxParser\Metrics\ParsingResult;
use OfxParser\Metrics\ParsingMetrics;
use OfxParser\Ofx;

class ParsingResultTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $xml = simplexml_load_string('<?xml version="1.0"?><OFX><SIGNONMSGSRSV1><SONRS><STATUS><CODE>0</CODE><SEVERITY>INFO</SEVERITY></STATUS><DTSERVER>20240101</DTSERVER><LANGUAGE>ENG</LANGUAGE><FI><ORG>Test</ORG><FID>123</FID></FI></SONRS></SIGNONMSGSRSV1></OFX>');
        $ofx = new Ofx($xml);
        $metrics = new ParsingMetrics();
        
        $result = new ParsingResult($ofx, $metrics);
        
        $this->assertSame($ofx, $result->getOfx());
        $this->assertSame($metrics, $result->getMetrics());
    }
    
    public function testHasErrorsWhenNoErrors(): void
    {
        $xml = simplexml_load_string('<?xml version="1.0"?><OFX><SIGNONMSGSRSV1><SONRS><STATUS><CODE>0</CODE><SEVERITY>INFO</SEVERITY></STATUS><DTSERVER>20240101</DTSERVER><LANGUAGE>ENG</LANGUAGE><FI><ORG>Test</ORG><FID>123</FID></FI></SONRS></SIGNONMSGSRSV1></OFX>');
        $ofx = new Ofx($xml);
        $metrics = new ParsingMetrics();
        $metrics->incrementSuccessfulTransaction();
        $metrics->incrementSuccessfulTransaction();
        
        $result = new ParsingResult($ofx, $metrics);
        
        $this->assertFalse($result->hasErrors());
    }
    
    public function testHasErrorsWhenCorruptTransactions(): void
    {
        $xml = simplexml_load_string('<?xml version="1.0"?><OFX><SIGNONMSGSRSV1><SONRS><STATUS><CODE>0</CODE><SEVERITY>INFO</SEVERITY></STATUS><DTSERVER>20240101</DTSERVER><LANGUAGE>ENG</LANGUAGE><FI><ORG>Test</ORG><FID>123</FID></FI></SONRS></SIGNONMSGSRSV1></OFX>');
        $ofx = new Ofx($xml);
        $metrics = new ParsingMetrics();
        $metrics->incrementSuccessfulTransaction();
        $metrics->incrementCorruptTransaction();
        
        $result = new ParsingResult($ofx, $metrics);
        
        $this->assertTrue($result->hasErrors());
    }
    
    public function testHasErrorsWhenUnexpectedErrors(): void
    {
        $xml = simplexml_load_string('<?xml version="1.0"?><OFX><SIGNONMSGSRSV1><SONRS><STATUS><CODE>0</CODE><SEVERITY>INFO</SEVERITY></STATUS><DTSERVER>20240101</DTSERVER><LANGUAGE>ENG</LANGUAGE><FI><ORG>Test</ORG><FID>123</FID></FI></SONRS></SIGNONMSGSRSV1></OFX>');
        $ofx = new Ofx($xml);
        $metrics = new ParsingMetrics();
        $metrics->incrementSuccessfulTransaction();
        $metrics->incrementUnexpectedError();
        
        $result = new ParsingResult($ofx, $metrics);
        
        $this->assertTrue($result->hasErrors());
    }
    
    public function testIsCompleteWhenAllSuccessful(): void
    {
        $xml = simplexml_load_string('<?xml version="1.0"?><OFX><SIGNONMSGSRSV1><SONRS><STATUS><CODE>0</CODE><SEVERITY>INFO</SEVERITY></STATUS><DTSERVER>20240101</DTSERVER><LANGUAGE>ENG</LANGUAGE><FI><ORG>Test</ORG><FID>123</FID></FI></SONRS></SIGNONMSGSRSV1></OFX>');
        $ofx = new Ofx($xml);
        $metrics = new ParsingMetrics();
        $metrics->incrementSuccessfulTransaction();
        $metrics->incrementSuccessfulTransaction();
        
        $result = new ParsingResult($ofx, $metrics);
        
        $this->assertTrue($result->isComplete());
    }
    
    public function testIsCompleteWhenIncompleteTransactions(): void
    {
        $xml = simplexml_load_string('<?xml version="1.0"?><OFX><SIGNONMSGSRSV1><SONRS><STATUS><CODE>0</CODE><SEVERITY>INFO</SEVERITY></STATUS><DTSERVER>20240101</DTSERVER><LANGUAGE>ENG</LANGUAGE><FI><ORG>Test</ORG><FID>123</FID></FI></SONRS></SIGNONMSGSRSV1></OFX>');
        $ofx = new Ofx($xml);
        $metrics = new ParsingMetrics();
        $metrics->incrementSuccessfulTransaction();
        $metrics->incrementIncompleteTransaction();
        
        $result = new ParsingResult($ofx, $metrics);
        
        $this->assertFalse($result->isComplete());
    }
    
    public function testGetSuccessRate(): void
    {
        $xml = simplexml_load_string('<?xml version="1.0"?><OFX><SIGNONMSGSRSV1><SONRS><STATUS><CODE>0</CODE><SEVERITY>INFO</SEVERITY></STATUS><DTSERVER>20240101</DTSERVER><LANGUAGE>ENG</LANGUAGE><FI><ORG>Test</ORG><FID>123</FID></FI></SONRS></SIGNONMSGSRSV1></OFX>');
        $ofx = new Ofx($xml);
        $metrics = new ParsingMetrics();
        $metrics->incrementSuccessfulTransaction();
        $metrics->incrementSuccessfulTransaction();
        $metrics->incrementSuccessfulTransaction();
        $metrics->incrementCorruptTransaction();
        
        $result = new ParsingResult($ofx, $metrics);
        
        $this->assertEquals(75.0, $result->getSuccessRate());
    }
    
    public function testGetCountMethods(): void
    {
        $xml = simplexml_load_string('<?xml version="1.0"?><OFX><SIGNONMSGSRSV1><SONRS><STATUS><CODE>0</CODE><SEVERITY>INFO</SEVERITY></STATUS><DTSERVER>20240101</DTSERVER><LANGUAGE>ENG</LANGUAGE><FI><ORG>Test</ORG><FID>123</FID></FI></SONRS></SIGNONMSGSRSV1></OFX>');
        $ofx = new Ofx($xml);
        $metrics = new ParsingMetrics();
        $metrics->incrementSuccessfulTransaction();
        $metrics->incrementSuccessfulTransaction();
        $metrics->incrementIncompleteTransaction();
        $metrics->incrementCorruptTransaction();
        
        $result = new ParsingResult($ofx, $metrics);
        
        $this->assertEquals(4, $result->getTotalTransactions());
        $this->assertEquals(2, $result->getSuccessfulTransactions());
        $this->assertEquals(1, $result->getIncompleteTransactions());
        $this->assertEquals(1, $result->getCorruptTransactions());
    }
    
    public function testToArray(): void
    {
        $xml = simplexml_load_string('<?xml version="1.0"?><OFX><SIGNONMSGSRSV1><SONRS><STATUS><CODE>0</CODE><SEVERITY>INFO</SEVERITY></STATUS><DTSERVER>20240101</DTSERVER><LANGUAGE>ENG</LANGUAGE><FI><ORG>Test</ORG><FID>123</FID></FI></SONRS></SIGNONMSGSRSV1></OFX>');
        $ofx = new Ofx($xml);
        $metrics = new ParsingMetrics();
        $metrics->incrementSuccessfulTransaction();
        $metrics->incrementCorruptTransaction();
        
        $result = new ParsingResult($ofx, $metrics);
        
        $array = $result->toArray();
        
        $this->assertArrayHasKey('success', $array);
        $this->assertArrayHasKey('complete', $array);
        $this->assertArrayHasKey('metrics', $array);
        $this->assertFalse($array['success']); // Has errors
        $this->assertFalse($array['complete']); // Has corrupt
    }
}
