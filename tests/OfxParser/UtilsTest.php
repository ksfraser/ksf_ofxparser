<?php

namespace OfxParserTest;

use PHPUnit\Framework\TestCase;
use OfxParser\Utils;

/**
 * Fake class for DateTime callback.
 */
class MyDateTime extends \DateTime { }

/**
 * @covers OfxParser\Utils
 */
class UtilsTest extends TestCase
{
    public function amountConversionProvider()
    {
        return [
            '1' => ['1', 1.0],
            '10' => ['10', 10.0],
            '100' => ['100', 100.0],
            '1000.01' => ['1000.01', 1000.01],
            '1000,01' => ['1000,01', 1000.01],
            '1,000.01' => ['1,000.01', 1000.01],
            '1.000,01' => ['1.000,01', 1000.01],
            '-1' => ['-1', -1.0],
            '-10' => ['-10', -10.0],
            '-100' => ['-100', -100.0],
            '-1000.01' => ['-1000.01', -1000.01],
            '-1000,01' => ['-1000,01', -1000.01],
            '-1,000.01' => ['-1,000.01', -1000.01],
            '-1.000,01' => ['-1.000,01', -1000.01],
            '+1' => ['+1', 1.0],
            '+10' => ['+10', 10.0],
            '+100' => ['+100', 100.0],
            '+1000.01' => ['+1000.01', 1000.01],
            '+1000,01' => ['+1000,01', 1000.01],
            '+1,000.01' => ['+1,000.01', 1000.01],
            '+1.000,01' => ['+1.000,01', 1000.01],

            // Try some bigger numbers, too.
            '2,225,000' => ['2,225,000', 2225000.00],
            '2,225,000.01' => ['2,225,000.01', 2225000.01],
            '2.225.000' => ['2.225.000', 2225000.00],
            '2.225.000,01' => ['2.225.000,01', 2225000.01],

            // And some tiny numbers.
            '0.02' => ['0.02', 0.02],
            '-,03' => ['-,03', -0.03],
        ];
    }

    /**
     * @param string $input
     * @param float $output
     * @dataProvider amountConversionProvider
     */
    public function testCreateAmountFromStr($input, $output)
    {
        $actual = Utils::createAmountFromStr($input);
        self::assertSame($output, $actual);
    }

    public function testCreateDateTimeFromOFXDateFormats()
    {
        // October 5, 2008, at 1:22 and 124 milliseconds pm, Easter Standard Time
        $expectedDateTime = new \DateTime('2008-10-05 13:22:00');

        // Test OFX Date Format YYYYMMDDHHMMSS.XXX[gmt offset:tz name]
        $DateTimeOne = Utils::createDateTimeFromStr('20081005132200.124[-5:EST]');
        self::assertEquals($expectedDateTime->getTimestamp(), $DateTimeOne->getTimestamp());

        // Test YYYYMMDD
        $DateTimeTwo = Utils::createDateTimeFromStr('20081005');
        self::assertEquals($expectedDateTime->format('Y-m-d'), $DateTimeTwo->format('Y-m-d'));

        // Test YYYYMMDDHHMMSS
        $DateTimeThree = Utils::createDateTimeFromStr('20081005132200');
        self::assertEquals($expectedDateTime->getTimestamp(), $DateTimeThree->getTimestamp());

        // Test YYYYMMDDHHMMSS.XXX
        $DateTimeFour = Utils::createDateTimeFromStr('20081005132200.124');
        self::assertEquals($expectedDateTime->getTimestamp(), $DateTimeFour->getTimestamp());

        // Test empty datetime
        $DateTimeFive = Utils::createDateTimeFromStr('');
        self::assertEquals(null, $DateTimeFive);

        // Test DateTime factory callback
        Utils::$fnDateTimeFactory = function($format) { return new MyDateTime($format); };
        $DateTimeSix = Utils::createDateTimeFromStr('20081005');
        self::assertEquals($expectedDateTime->format('Y-m-d'), $DateTimeSix->format('Y-m-d'));
        self::assertEquals('OfxParserTest\\MyDateTime', get_class($DateTimeSix));
        Utils::$fnDateTimeFactory = null;
    }

    public function testCreateDateTimeFromStrWithAllFormats()
    {
        // Test YYYYMMDD
        $date1 = Utils::createDateTimeFromStr('20200115');
        self::assertEquals('2020-01-15', $date1->format('Y-m-d'));
        
        // Test YYYYMMDDHHMMSS
        $date2 = Utils::createDateTimeFromStr('20200115143025');
        self::assertEquals('2020-01-15 14:30:25', $date2->format('Y-m-d H:i:s'));
        
        // Test YYYYMMDDHHMMSS.XXX
        $date3 = Utils::createDateTimeFromStr('20200115143025.500');
        self::assertEquals('2020-01-15 14:30:25', $date3->format('Y-m-d H:i:s'));
        
        // Test YYYYMMDDHHMMSS.XXX[gmt offset:tz name]
        $date4 = Utils::createDateTimeFromStr('20200115143025.500[-5:EST]');
        self::assertEquals('2020-01-15 14:30:25', $date4->format('Y-m-d H:i:s'));
    }

    public function testCreateDateTimeFromStrWithInvalidFormat()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to initialize DateTime for string:');
        Utils::createDateTimeFromStr('invalid-date-format');
    }

    public function testCreateDateTimeFromStrWithPartialDate()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to initialize DateTime for string:');
        Utils::createDateTimeFromStr('202001');
    }

    public function testCreateDateTimeFromStrIgnoresErrors()
    {
        $result = Utils::createDateTimeFromStr('99999999', true);
        self::assertNull($result);
    }

    public function testCreateDateTimeFromStrThrowsExceptionOnInvalidDate()
    {
        $this->expectException(\Exception::class);
        Utils::createDateTimeFromStr('not-a-date', false);
    }

    public function testCreateAmountFromStrWithEdgeCases()
    {
        self::assertEquals(0.0, Utils::createAmountFromStr('0'));
        self::assertEquals(0.0, Utils::createAmountFromStr('0.00'));
        self::assertEquals(0.01, Utils::createAmountFromStr('0.01'));
        self::assertEquals(-0.01, Utils::createAmountFromStr('-0.01'));
    }

    public function testCreateAmountFromStrWithWhitespace()
    {
        self::assertEquals(100.50, Utils::createAmountFromStr('  100.50  '));
        self::assertEquals(-50.25, Utils::createAmountFromStr('  -50.25  '));
    }

    public function testCreateAmountFromStrWithLargeNumbers()
    {
        self::assertEquals(1000000.00, Utils::createAmountFromStr('1,000,000.00'));
        self::assertEquals(1000000.00, Utils::createAmountFromStr('1.000.000,00'));
        self::assertEquals(-9999999.99, Utils::createAmountFromStr('-9,999,999.99'));
    }

    public function testCreateAmountFromStrWithPositiveSign()
    {
        self::assertEquals(50.0, Utils::createAmountFromStr('+50'));
        self::assertEquals(100.50, Utils::createAmountFromStr('+100.50'));
    }

    public function testCreateAmountFromStrWithOnlyDecimalSeparator()
    {
        self::assertEquals(0.5, Utils::createAmountFromStr('.5'));
        self::assertEquals(0.99, Utils::createAmountFromStr(',99'));
    }}