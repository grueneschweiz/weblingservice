<?php

namespace App\Repository\Member\Field;


use App\Exceptions\InvalidFixedValueException;


class MultiSelectField extends FixedField {
	/**
	 * MultiSelectField constructor.
	 *
	 * @param string $key
	 * @param string $weblingKey
	 * @param array $possibleValues with the internal value as key and the webling value as value
	 */
	public function __construct( string $key, string $weblingKey, array $possibleValues ) {
		$this->key            = $key;
		$this->weblingKey     = $weblingKey;
		$this->possibleValues = $possibleValues;
		$this->value          = array();
	}
	
	/**
	 * Remove the given element(s)
	 *
	 * @param array|string $values internal values or webling values
	 * @param bool $dirty
	 *
	 * @throws InvalidFixedValueException
	 */
	public function remove( $values, bool $dirty = true ) {
		$new = (array) $values;
		
		foreach ( $new as &$value ) {
			$value = $this->clean( $value );
			$value = $this->makeInternalValue( $value );
			
			if ( $this->hasValue( $value ) && null !== $value ) {
				$key = array_search( $value, $this->value );
				unset( $this->value[ $key ] );
				$this->setDirty( $dirty );
			}
		}
	}
	
	/**
	 * Check if value is set
	 *
	 * @param string $value internal value or webling value
	 *
	 * @return bool
	 */
	public function hasValue( string $value ): bool {
		$value = $this->clean( $value );
		
		if ( null !== $value ) {
			try {
				$value = $this->makeInternalValue( $value );
			} catch ( InvalidFixedValueException $e ) {
				return false;
			}
		}
		
		return in_array( $value, $this->getValue() );
	}
	
	/**
	 * Set the given values
	 *
	 * @param array|string|null $values
	 * @param boolean $dirty
	 *
	 * @throws InvalidFixedValueException
	 */
	public function setValue( $values, bool $dirty = true ) {
		if ( empty( $values ) && ! empty( $this->value ) ) {
			$this->value = array();
			$this->setDirty( $dirty );
			
			return;
		}
		
		$values = (array) $values;
		
		foreach ( $values as &$value ) {
			$value = $this->clean( $value );
			$value = $this->makeInternalValue( $value );
			
			if ( ! $this->hasValue( $value ) && null !== $value ) {
				$this->value = array();
				$this->append( $values, $dirty );
				break;
			}
		}
	}
	
	/**
	 * Append an array of values or a single value given as string
	 *
	 * @param array|string $values internal value or webling value
	 * @param bool $dirty
	 *
	 * @throws InvalidFixedValueException
	 */
	public function append( $values, bool $dirty = true ) {
		$new = (array) $values;
		
		foreach ( $new as &$value ) {
			$value = $this->clean( $value );
			$value = $this->makeInternalValue( $value );
			
			if ( ! $this->hasValue( $value ) && null !== $value ) {
				array_push( $this->value, $value );
				$this->setDirty( $dirty );
			}
		}
	}
	
	/**
	 * Return the webling values
	 *
	 * @return string|array
	 */
	public function getWeblingValue() {
		$array = [];
		foreach ( $this->getValue() as $value ) {
			$array[] = $this->possibleValues[ $value ];
		}
		
		return $array;
	}
}
