<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 25.10.17
 * Time: 17:42
 */

namespace App\Repository\Member\Field;

use App\Exceptions\InputLengthException;
use App\Exceptions\ValueTypeException;

class LongTextField extends FreeField
{
    /**
     * Append value, if it's not already in the the field.
     *
     * @param $value
     * @param bool $dirty
     * @param string $separator
     *
     * @throws \App\Exceptions\ValueTypeException
     */
    public function append($value, bool $dirty = true, string $separator = "\n")
    {
        if (!$value || $this->inValue($value)) {
            return;
        }
        
        if (empty($this->getValue())) {
            $v = $value;
        } else {
            $v = $this->getValue() . $separator . $value;
        }
        
        $this->setValue($v, $dirty);
    }
    
    /**
     * Remove value, if it's in the the field.
     *
     * @param $value
     * @param bool $dirty
     * @param string $separator
     *
     * @throws InputLengthException
     * @throws ValueTypeException
     */
    public function remove($value, bool $dirty = true, string $separator = "\n" )
    {
        if (empty($value)
            || empty($this->getValue())
            || !$this->inValue($value)) {
            return;
        }
        
        $needle = preg_quote($value, '/');
        $v = preg_replace("/\b$needle\b/", '', $this->getValue());
        
        $preg_separator = preg_quote($separator, '/');
        $v = preg_replace("/\s*$preg_separator+\s*/", $separator, $v);
        
        $this->setValue($v, $dirty);
    }
}
