<?php

namespace OfxParser;

use SimpleXMLElement;

/**
 * Utilities and helpers for conversion and parsing.
 */
class Utils
{
    /**
     * Set this to define your own DateTime creation function.
     * function('YYYY-MM-DD HH:ii:ss') : \DateTime compatible object
     * @static callable
     */
    public static $fnDateTimeFactory;

    /**
     * Create a DateTime object from a valid OFX date format
     *
     * Supports:
     * YYYYMMDDHHMMSS.XXX[gmt offset:tz name]
     * YYYYMMDDHHMMSS.XXX
     * YYYYMMDDHHMMSS
     * YYYYMMDD
     *
     * @param  string $dateString
     * @param  boolean $ignoreErrors
     * @return \DateTime $dateString
     * @throws \Exception
     */
    public static function createDateTimeFromStr($dateString, $ignoreErrors = false)
    {
        if (!isset($dateString) || trim($dateString) === '') {
            return null;
        }
        
        $regex = '/'
            . "(\d{4})(\d{2})(\d{2})?"     // YYYYMMDD             1,2,3
            . "(?:(\d{2})(\d{2})(\d{2}))?" // HHMMSS   - optional  4,5,6
            . "(?:\.(\d{3}))?"             // .XXX     - optional  7
            . "(?:\[(-?\d+)\:(\w{3}\]))?"  // [-n:TZ]  - optional  8,9
            . '/';

        if (preg_match($regex, $dateString, $matches)) {
            $year = (int)$matches[1];
            $month = (int)$matches[2];
            $day = (int)$matches[3];
            $hour = isset($matches[4]) ? $matches[4] : 0;
            $min = isset($matches[5]) ? $matches[5] : 0;
            $sec = isset($matches[6]) ? $matches[6] : 0;

            $format = $year . '-' . $month . '-' . $day . ' ' . $hour . ':' . $min . ':' . $sec;

            try {
                $dt = null;
                if (is_callable(static::$fnDateTimeFactory)) {
                    $dt = call_user_func(static::$fnDateTimeFactory, $format);
                } else {
                    $dt = new \DateTime($format);
                }

                return $dt;
            } catch (\Exception $e) {
                if ($ignoreErrors) {
                    return null;
                }

                throw $e;
            }
        }

        throw new \RuntimeException('Failed to initialize DateTime for string: ' . $dateString);
    }

    /**
     * Create a formatted number in Float according to different locale options
     *
     * Supports:
     * 000,00 and -000,00
     * 0.000,00 and -0.000,00
     * 0,000.00 and -0,000.00
     * 000.00 and 000.00
     *
     * @param  string $amountString
     * @return float
     */
    public static function createAmountFromStr($amountString)
    {
        // This assumes that all supported currency will have no more than
        // 2 decimal places! The tell is the thousands separator, followed
        // by three digits. If no thousands separator present, the only
        // differentiator is number of decimal places.

        // Decimal mark style (UK/US): 000.00 or 0,000.00
        if (preg_match('/(\d,\d{3}|\.\d{1,2}$)/', $amountString) === 1) {
            return (float) str_replace(',', '', $amountString);
        }

        // European style: 000,00 or 0.000,00
        if (preg_match('/(\d\.\d{3}|,\d{1,2}$)/', $amountString) === 1) {
            return (float) str_replace(
                array('.', ','),
                array('', '.'),
                $amountString
            );
        }

        return (float) $amountString;
    }
}
