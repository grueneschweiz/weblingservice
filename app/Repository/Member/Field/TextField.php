<?php


namespace App\Repository\Member\Field;


use App\Exceptions\InputLengthException;
use App\Exceptions\ValueTypeException;

class TextField extends FreeField
{
    const MAX_LEN = 1023; // the limit of webling is unknown, but higher
    
    /**
     * Append value, if it's not already in the the field.
     *
     * @param $value
     * @param bool $dirty
     * @param string $separator
     *
     * @throws InputLengthException
     * @throws ValueTypeException
     */
    public function append($value, bool $dirty = true, string $separator = ', ')
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
     * Make sure we don't exceed the length limit
     *
     * @param string|null $value
     * @param boolean $dirty
     *
     * @throws InputLengthException if the input was longer MAX_LEN
     * @throws ValueTypeException if input was other than null or a string
     */
    public function setValue($value, bool $dirty = true)
    {
        $this->assertOptionalStringType($value);
        
        if (null !== $value && self::MAX_LEN < strlen($value)) {
            throw new InputLengthException('Max length of input (' . self::MAX_LEN . ' characters) exceeded');
        }
        
        parent::setValue($value, $dirty);
    }
}
