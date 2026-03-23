<?php declare(strict_types=1);

namespace Tests\Builders;

use DateTime;
use DateTimeZone;

/**
 * Edge case date value generators
 * 
 * Provides systematically chosen boundary dates for date parsing testing.
 * Each method returns an array of DateTime objects appropriate for testing
 * that specific date edge case category.
 * 
 * Usage:
 *   $dates = EdgeCaseDates::leapYearDates();
 *   // Result: [2000-02-29, 2004-02-29, 2008-02-29, ...]
 */
class EdgeCaseDates
{
    /**
     * Unix epoch dates
     * Tests dates at the beginning of modern computing
     * 
     * @return array<DateTime> Epoch boundary dates
     */
    public static function unixEpoch(): array
    {
        return [
            new DateTime('1970-01-01', new DateTimeZone('UTC')),  // Unix epoch start
            new DateTime('1970-01-02', new DateTimeZone('UTC')),  // Day after epoch
            new DateTime('1970-12-31', new DateTimeZone('UTC')),  // End of epoch year
        ];
    }
    
    /**
     * Y2K boundary dates
     * Tests dates around the millennium and Y2K boundary
     * 
     * @return array<DateTime> Y2K boundary dates
     */
    public static function y2kBoundary(): array
    {
        return [
            new DateTime('1999-12-31', new DateTimeZone('UTC')),  // Last day of 1900s
            new DateTime('2000-01-01', new DateTimeZone('UTC')),  // Y2K - first day of 2000
            new DateTime('2000-01-02', new DateTimeZone('UTC')),  // Day after Y2K
            new DateTime('2000-12-31', new DateTimeZone('UTC')),  // End of Y2K year
        ];
    }
    
    /**
     * Leap year dates
     * Tests February 29 in leap years
     * 
     * @return array<DateTime> February 29 in leap years
     */
    public static function leapYearDates(): array
    {
        $leapYears = self::leapYears();
        $dates = [];
        
        foreach ($leapYears as $year) {
            $dates[] = new DateTime("{$year}-02-29", new DateTimeZone('UTC'));
        }
        
        return $dates;
    }
    
    /**
     * Year boundary dates
     * Tests first and last days of years
     * 
     * @return array<DateTime> Year boundaries
     */
    public static function yearBoundaries(): array
    {
        $boundaries = [];
        $testYears = [1970, 2000, 2010, 2020, 2026, 2050, 2099];
        
        foreach ($testYears as $year) {
            // First day of year
            $boundaries[] = new DateTime("{$year}-01-01", new DateTimeZone('UTC'));
            
            // Last day of year
            $boundaries[] = new DateTime("{$year}-12-31", new DateTimeZone('UTC'));
        }
        
        return $boundaries;
    }
    
    /**
     * Month boundary dates
     * Tests first and last days of months
     * 
     * @return array<DateTime> Month boundaries
     */
    public static function monthBoundaries(): array
    {
        $boundaries = [];
        
        for ($month = 1; $month <= 12; $month++) {
            // First day of month
            $monthStr = str_pad((string)$month, 2, '0', STR_PAD_LEFT);
            $boundaries[] = new DateTime("2026-{$monthStr}-01", new DateTimeZone('UTC'));
            
            // Last day of month (simplified)
            $lastDay = match($month) {
                2 => '28', // Non-leap year
                4, 6, 9, 11 => '30',
                default => '31',
            };
            $boundaries[] = new DateTime("2026-{$monthStr}-{$lastDay}", new DateTimeZone('UTC'));
        }
        
        return $boundaries;
    }
    
    /**
     * Current year boundaries
     * Tests dates at start and end of current year
     * 
     * @return array<DateTime> Current year boundaries
     */
    public static function currentYearBoundaries(): array
    {
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $year = $now->format('Y');
        
        return [
            new DateTime("{$year}-01-01", new DateTimeZone('UTC')),
            new DateTime("{$year}-12-31", new DateTimeZone('UTC')),
        ];
    }
    
    /**
     * Far future dates
     * Tests dates far in the future
     * 
     * @return array<DateTime> Future boundary dates
     */
    public static function farFuture(): array
    {
        return [
            new DateTime('2050-06-15', new DateTimeZone('UTC')),  // Mid-century
            new DateTime('2099-12-31', new DateTimeZone('UTC')),  // End of century
        ];
    }
    
    /**
     * All critical dates combined
     * 
     * @return array<DateTime> All edge case dates
     */
    public static function all(): array
    {
        return array_merge(
            self::unixEpoch(),
            self::y2kBoundary(),
            self::leapYearDates(),
            self::yearBoundaries(),
            self::farFuture()
        );
    }
    
    /**
     * Leap years from 1900 to 2100
     * Used for Feb 29 testing
     * 
     * @return array<int> Years divisible by 4 (minus exceptions for century years)
     */
    public static function leapYears(): array
    {
        $leapYears = [];
        
        for ($year = 1900; $year <= 2100; $year++) {
            // A year is a leap year if:
            // - Divisible by 4 AND
            // - (NOT divisible by 100 OR divisible by 400)
            if (($year % 4 === 0) && (($year % 100 !== 0) || ($year % 400 === 0))) {
                $leapYears[] = $year;
            }
        }
        
        return $leapYears;
    }
    
    /**
     * Non-leap years
     * Years that are NOT leap years
     * 
     * @return array<int> Years that are not leap years
     */
    public static function nonLeapYears(): array
    {
        $allYears = range(1970, 2099);
        $leapYears = self::leapYears();
        
        return array_diff($allYears, $leapYears);
    }
    
    /**
     * Century year boundary dates
     * Tests century changes (2000, 2100, etc.)
     * 
     * @return array<DateTime> Century boundary dates
     */
    public static function centuryBoundaries(): array
    {
        $boundaries = [];
        $centuries = [1900, 2000, 2100];
        
        foreach ($centuries as $year) {
            $boundaries[] = new DateTime("{$year}-01-01", new DateTimeZone('UTC'));
            $boundaries[] = new DateTime("{$year}-12-31", new DateTimeZone('UTC'));
        }
        
        return $boundaries;
    }
    
    /**
     * Dates with times
     * Tests date parsing with time components
     * 
     * @return array<DateTime> Dates with various times
     */
    public static function datesWithTimes(): array
    {
        return [
            new DateTime('2026-03-13 00:00:00', new DateTimeZone('UTC')),  // Midnight
            new DateTime('2026-03-13 12:00:00', new DateTimeZone('UTC')),  // Noon
            new DateTime('2026-03-13 23:59:59', new DateTimeZone('UTC')),  // End of day
            new DateTime('2026-03-13 14:30:45', new DateTimeZone('UTC')),  // Random time
        ];
    }
}
