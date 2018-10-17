<?php

namespace App\Repository\Member\Field;


use App\Exceptions\InvalidFixedValueException;
use App\Exceptions\ValueTypeException;

class SelectField extends FixedField
{
	/**
	 * SelectField constructor.
	 *
	 * @param string $key
	 * @param string $weblingKey
	 * @param array $possibleValues with the internal value as key and the webling value as value
	 * @param string|null $value
	 *
	 * @throws InvalidFixedValueException
	 * @throws ValueTypeException
	 */
	public function __construct( string $key, string $weblingKey, array $possibleValues, $value ) {
		$this->key            = $key;
		$this->weblingKey     = $weblingKey;
		$this->possibleValues = $possibleValues;
		$this->setValue( $value, false );
	}
	
	/**
	 * Check if the given value is either a possible internal value or possible webling value
	 *
	 * @param string|null $value
	 * @param boolean $dirty
	 *
	 * @throws InvalidFixedValueException if given value is neither a possible internal nor a possible webling value
	 * @throws ValueTypeException if given value is neither null nor a string
	 */
    public function setValue($value, bool $dirty = true)
    {
        $this->assertOptionalStringType($value);
        $value = $this->clean($value);

        if (null !== $value){
	        $value = $this->makeInternalValue($value);
        }

        if ($this->getValue() !== $value) {
        	$this->value = $value;
            $this->setDirty($dirty);
        }
    }
	
	/**
	 * @return string
	 */
	public function getWeblingValue() {
		return $this->possibleValues[$this->getValue()];
	}
}
