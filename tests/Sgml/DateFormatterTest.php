<?php

namespace OfxParserTest\Sgml;

use OfxParser\Sgml\DateFormatter;
use PHPUnit\Framework\TestCase;

class DateFormatterTest extends TestCase
{
    public function testGetRaw()
    {
        $this->assertEquals('20250101120000.000[-5:EST]', DateFormatter::getRaw('20250101120000.000[-5:EST]'));
        $this->assertEquals('20250101', DateFormatter::getRaw('20250101'));
        $this->assertNull(DateFormatter::getRaw(null));
        $this->assertNull(DateFormatter::getRaw(''));
    }

    public function testGetNormalized()
    {
        // Full format with timezone
        $this->assertEquals('20250101120000', DateFormatter::getNormalized('20250101120000.000[-5:EST]'));
        
        // Date only (should pad with zeros)
        $this->assertEquals('20250101000000', DateFormatter::getNormalized('20250101'));
        
        // Date and time without timezone
        $this->assertEquals('20250101120000', DateFormatter::getNormalized('20250101120000'));
        
        // With milliseconds but no timezone
        $this->assertEquals('20250101120000', DateFormatter::getNormalized('20250101120000.000'));
        
        // Different timezone format
        $this->assertEquals('20250101120000', DateFormatter::getNormalized('20250101120000[-5:EST]'));
        
        // Null and empty
        $this->assertNull(DateFormatter::getNormalized(null));
        $this->assertNull(DateFormatter::getNormalized(''));
    }

    public function testGetYMD()
    {
        $this->assertEquals('2025-01-01', DateFormatter::getYMD('20250101120000.000[-5:EST]'));
        $this->assertEquals('2025-01-01', DateFormatter::getYMD('20250101'));
        $this->assertEquals('2025-01-01', DateFormatter::getYMD('20250101120000'));
        $this->assertEquals('2025-12-31', DateFormatter::getYMD('20251231235959'));
        $this->assertNull(DateFormatter::getYMD(null));
        $this->assertNull(DateFormatter::getYMD(''));
    }

    public function testParseToDateTime()
    {
        // Full date with time
        $dt = DateFormatter::parseToDateTime('20250101120000.000[-5:EST]');
        $this->assertInstanceOf(\DateTime::class, $dt);
        $this->assertEquals('2025-01-01 12:00:00', $dt->format('Y-m-d H:i:s'));
        
        // Date only (time should be 00:00:00)
        $dt = DateFormatter::parseToDateTime('20250101');
        $this->assertInstanceOf(\DateTime::class, $dt);
        $this->assertEquals('2025-01-01 00:00:00', $dt->format('Y-m-d H:i:s'));
        
        // Date and time
        $dt = DateFormatter::parseToDateTime('20250101153045');
        $this->assertInstanceOf(\DateTime::class, $dt);
        $this->assertEquals('2025-01-01 15:30:45', $dt->format('Y-m-d H:i:s'));
        
        // Invalid
        $this->assertNull(DateFormatter::parseToDateTime(null));
        $this->assertNull(DateFormatter::parseToDateTime(''));
        $this->assertNull(DateFormatter::parseToDateTime('invalid'));
    }

    public function testIsValid()
    {
        // Valid formats
        $this->assertTrue(DateFormatter::isValid('20250101'));
        $this->assertTrue(DateFormatter::isValid('20250101120000'));
        $this->assertTrue(DateFormatter::isValid('20250101120000.000'));
        $this->assertTrue(DateFormatter::isValid('20250101120000.000[-5:EST]'));
        $this->assertTrue(DateFormatter::isValid('20250101120000[-5:EST]'));
        $this->assertTrue(DateFormatter::isValid('20250101120000[+5:EST]'));
        
        // Invalid formats
        $this->assertFalse(DateFormatter::isValid(null));
        $this->assertFalse(DateFormatter::isValid(''));
        $this->assertFalse(DateFormatter::isValid('2025-01-01'));
        $this->assertFalse(DateFormatter::isValid('20250'));
        $this->assertFalse(DateFormatter::isValid('invalid'));
    }

    public function testFormat()
    {
        // Default format
        $this->assertEquals('2025-01-01 12:00:00', DateFormatter::format('20250101120000'));
        
        // Custom format
        $this->assertEquals('01/01/2025', DateFormatter::format('20250101', 'm/d/Y'));
        $this->assertEquals('2025-01-01', DateFormatter::format('20250101', 'Y-m-d'));
        $this->assertEquals('12:00:00', DateFormatter::format('20250101120000', 'H:i:s'));
        
        // Null
        $this->assertNull(DateFormatter::format(null));
    }

    public function testEdgeCases()
    {
        // Leap year
        $this->assertEquals('2024-02-29', DateFormatter::getYMD('20240229'));
        
        // End of year
        $this->assertEquals('2025-12-31', DateFormatter::getYMD('20251231'));
        
        // Start of day
        $this->assertEquals('20250101000000', DateFormatter::getNormalized('20250101000000'));
        
        // End of day
        $this->assertEquals('20250101235959', DateFormatter::getNormalized('20250101235959'));
    }
}
