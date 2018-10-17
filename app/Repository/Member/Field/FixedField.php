<?php

namespace App\Repository\Member\Field;


use App\Exceptions\InvalidFixedValueException;

abstract class FixedField extends Field {
	/**
	 * @var array with the possible values with the internal value as key
	 * and the webling value as value.
	 */
	protected $possibleValues = [];
	
	/**
	 * @return string|array
	 */
	public abstract function getWeblingValue();
	
	/**
	 * Returns the internal value for any internal or webling value
	 *
	 * @param string $value
	 *
	 * @return false|string
	 *
	 * @throws InvalidFixedValueException if the given value is neither a possible internal nor a possible webling value
	 */
	protected function makeInternalValue(string $value) {
		$internal_key = array_search($value, $this->possibleValues);
		if ($internal_key){
			$value = $internal_key;
		} else if (!in_array($value, array_keys($this->possibleValues))) {
			throw new InvalidFixedValueException("'$value' ist not a possible value for {$this->getKey()}");
		}
		
		return $value;
	}
}
