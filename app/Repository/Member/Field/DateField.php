<?php

namespace App\Repository\Member\Field;

use App\Exceptions\DateParsingException;
use App\Exceptions\ValueTypeException;

class DateField extends FreeField
{
    
    /**
     * Make sure to bring the date in the right format, before setting it.
     *
     * @param string|null $value
     * @param boolean $dirty
     *
     * @throws DateParsingException if the date format was not recognized
     * @throws ValueTypeException if given value is neither null nor a string
     */
    public function setValue($value, bool $dirty = true)
    {
        $this->assertOptionalStringType($value);
        
        parent::setValue(self::parseDate($value), $dirty);
    }
    
    /**
     * Return date in YYYY-MM-DD format from given string or null if string is empty.
     * Throws exception if given date string could not be parsed.
     *
     * @param string $value
     *
     * @return null|string
     * @throws DateParsingException if the date format was not recognized by PHPs date_parse()
     */
    public static function parseDate($value)
    {
        $value = self::clean($value);
        
        if (null === $value) {
            return $value;
        }
        
        $tmp1 = date_parse($value);
        $tmp2 = date_parse_from_format('d.m.y', $value);
        $tmp3 = date_parse_from_format('Y-m-d', $value);
        
        if ($tmp1['error_count'] && $tmp2['error_count'] && $tmp3['error_count']) {
            throw new DateParsingException("Unable to parse date from given string: $value");
        }
        
        if (!$tmp1['error_count'] && $tmp1['year']) {
            $date = $tmp1;
        } elseif (!$tmp2['error_count']) {
            $date = $tmp2;
        } else {
            $date = $tmp3;
        }
        
        $datestring = $date['year'] . "-" . $date['month'] . "-" . $date['day'];
        
        return date('Y-m-d', strtotime($datestring));
    }
}
