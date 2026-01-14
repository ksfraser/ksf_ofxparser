<?php

namespace OfxParser\Sgml;

/**
 * Static utility class for formatting OFX date strings
 * Can be used by both SGML and XML parsers for consistent date handling
 */
class DateFormatter
{
    /**
     * Get raw date string as-is from OFX
     */
    public static function getRaw(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        return $value;
    }

    /**
     * Get normalized datetime string in format YYYYMMDDHHmmss
     * Strips timezone info and milliseconds, pads time with zeros if not present
     * 
     * Examples:
     *   20250101120000.000[-5:EST] => 20250101120000
     *   20250101 => 20250101000000
     *   20250101120000 => 20250101120000
     */
    public static function getNormalized(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Parse the OFX date/time format
        // Format: YYYYMMDD[HHMMSS][.XXX][[offset]:TZ]
        $regex = '/'
            . '(\d{4})(\d{2})(\d{2})'      // YYYYMMDD - required
            . '(?:(\d{2})(\d{2})(\d{2}))?' // HHMMSS - optional
            . '(?:\.(\d{3}))?'             // .XXX - optional milliseconds
            . '(?:\[(-?\d+)\:(\w{3}\]))?'  // [-n:TZ] - optional timezone
            . '/';

        if (preg_match($regex, $value, $matches)) {
            $year = $matches[1];
            $month = $matches[2];
            $day = $matches[3];
            $hour = isset($matches[4]) && $matches[4] !== '' ? $matches[4] : '00';
            $min = isset($matches[5]) && $matches[5] !== '' ? $matches[5] : '00';
            $sec = isset($matches[6]) && $matches[6] !== '' ? $matches[6] : '00';

            return $year . $month . $day . $hour . $min . $sec;
        }

        return null;
    }

    /**
     * Get date in YYYY-MM-DD format (matches old XML parser DateTime->format('Y-m-d'))
     * 
     * Examples:
     *   20250101120000.000[-5:EST] => 2025-01-01
     *   20250101 => 2025-01-01
     */
    public static function getYMD(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $dt = self::parseToDateTime($value);
        if ($dt) {
            return $dt->format('Y-m-d');
        }

        return null;
    }

    /**
     * Parse OFX datetime string to PHP DateTime object
     * 
     * @param string $value OFX date string
     * @return \DateTime|null DateTime object or null on parse error
     */
    public static function parseToDateTime(?string $value): ?\DateTime
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $regex = '/'
                . '(\d{4})(\d{2})(\d{2})'      // YYYYMMDD
                . '(?:(\d{2})(\d{2})(\d{2}))?' // HHMMSS - optional
                . '(?:\.(\d{3}))?'             // .XXX - optional milliseconds
                . '(?:\[(-?\d+)\:(\w{3}\]))?'  // [-n:TZ] - optional timezone
                . '/';

            if (preg_match($regex, $value, $matches)) {
                $year = (int)$matches[1];
                $month = (int)$matches[2];
                $day = (int)$matches[3];
                $hour = isset($matches[4]) && $matches[4] !== '' ? (int)$matches[4] : 0;
                $min = isset($matches[5]) && $matches[5] !== '' ? (int)$matches[5] : 0;
                $sec = isset($matches[6]) && $matches[6] !== '' ? (int)$matches[6] : 0;

                $format = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $min, $sec);
                return new \DateTime($format);
            }
        } catch (\Exception $e) {
            // Return null on parse error
        }

        return null;
    }

    /**
     * Validate OFX datetime format
     * 
     * @param string $value Value to validate
     * @return bool True if valid OFX date format
     */
    public static function isValid(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        // OFX date format: YYYYMMDD or YYYYMMDDHHMMSS with optional milliseconds and timezone
        // Format: YYYYMMDD[HHMMSS][.XXX][[offset]:TZ]
        $pattern = '/^\d{8}(\d{6})?(\.\d{3})?(\[[-+]?\d+:\w+\])?$/';
        return preg_match($pattern, $value) === 1;
    }

    /**
     * Get formatted date for display (human-readable)
     * 
     * @param string|null $value OFX date string
     * @param string $format PHP date format (default: 'Y-m-d H:i:s')
     * @return string|null Formatted date or null
     */
    public static function format(?string $value, string $format = 'Y-m-d H:i:s'): ?string
    {
        $dt = self::parseToDateTime($value);
        if ($dt) {
            return $dt->format($format);
        }

        return null;
    }
}
