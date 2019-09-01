<?php

namespace App\Repository\Member\Field;


use App\Exceptions\ValueTypeException;

abstract class FreeField extends Field {
	/**
	 * FreeField constructor.
	 *
	 * @param string $key
	 * @param string $weblingKey
	 * @param string|null $value
	 *
	 * @throws ValueTypeException
	 */
	public function __construct( string $key, string $weblingKey, $value ) {
		$this->key        = $key;
		$this->weblingKey = $weblingKey;
		$this->setValue( $value, false );
	}

	/**
	 * @param string|null $value
	 * @param boolean $dirty
	 *
	 * @throws ValueTypeException if given value is neither null nor a string
	 */
	public function setValue( $value, bool $dirty = true ) {
		$this->assertOptionalStringType( $value );
		$value = self::clean( $value );

		if ( $value !== $this->getValue() ) {
			$this->value = $value;
			$this->setDirty( $dirty );
		}
	}

	/**
	 * @return string|array
	 */
	public function getWeblingValue() {
		return $this->getValue();
	}

	/**
	 * Check if the given needle is contained in the value.
	 *
	 * Returns only true, if the match is bounded, not if it is in the middle of a word.
	 *
	 * @param string $needle
	 *
	 * @return bool
	 */
	protected function inValue( string $needle ) {
	    $needle = preg_quote( $needle, '/' );
		return 1 === preg_match( "/\b$needle\b/", $this->getValue() );
	}
}
