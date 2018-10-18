<?php

namespace App\Repository\Member\Field;


use App\Exceptions\ValueTypeException;

abstract class Field {
	/**
	 * @var mixed the value
	 */
	protected $value;
	
	/**
	 * @var string holds the internal name of the field
	 */
	protected $key;
	
	/**
	 * @var string the fieldname in webling
	 */
	protected $weblingKey;
	
	/**
	 * @var bool signals if a field value has changed
	 */
	private $dirty = false;
	
	/**
	 * @return bool
	 */
	public function isDirty(): bool {
		return $this->dirty;
	}
	
	/**
	 * @param bool $dirty
	 */
	public function setDirty( bool $dirty ) {
		$this->dirty = $dirty;
	}
	
	/**
	 * @return string
	 */
	public function getKey(): string {
		return $this->key;
	}
	
	/**
	 * @return string
	 */
	public function getWeblingKey(): string {
		return $this->weblingKey;
	}
	
	
	/**
	 * @return mixed
	 */
	public function getValue() {
		return $this->value;
	}
	
	/**
	 * set value
	 *
	 * @param string|array|null $value with the value(s)
	 * @param bool $dirty set to false if you don't want to mark the field as dirty
	 */
	public abstract function setValue( $value, bool $dirty = true );
	
	/**
	 * Throws exception if given value is neither null nor a string
	 *
	 * @param string|null $value
	 *
	 * @throws ValueTypeException if given value is neither null nor a string
	 */
	protected function assertOptionalStringType( $value ) {
		if ( null !== $value && 'string' !== gettype( $value ) ) {
			throw new ValueTypeException( "The '{$this->key}' field only accepts string values but " . gettype( $value ) . ' given.' );
		}
	}
	
	/**
	 * Trims input and transforms empty strings into null
	 *
	 * @param $value
	 *
	 * @return null|string
	 */
	protected function clean( $value ) {
		$value = trim( $value );
		
		return 0 === strlen( $value ) ? null : $value;
	}
}
