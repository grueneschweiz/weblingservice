<?php

namespace App\Repository\Member\Field\Mapping;

use App\Exceptions\InvalidFixedValueException;

/**
 * Holds the configuration to map a field to Webling.
 *
 * @package App\Repository\Member\Field\Mappin
 */
class Mapping {
	/**
	 * The internal field key
	 *
	 * @var string
	 */
	private $key;
	
	/**
	 * The Webling field key
	 *
	 * @var string
	 */
	private $weblingKey;
	
	/**
	 * The type (matches to field class)
	 *
	 * @var string
	 */
	private $type;
	
	/**
	 * The possible values
	 *
	 * @var array the key represents the internal value, the value the webling
	 * value
	 */
	private $values;
	
	/**
	 * Mapping constructor.
	 *
	 * @param string $key
	 * @param string $weblingKey
	 * @param string $type
	 * @param array $values
	 */
	public function __construct( string $key, string $weblingKey, string $type, array $values = [] ) {
		$this->key        = $key;
		$this->weblingKey = $weblingKey;
		$this->type       = $type;
		$this->values     = $values;
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
	 * @return string
	 */
	public function getType(): string {
		return $this->type;
	}
	
	/**
	 * Tests if the given value is a possible value
	 *
	 * @param string? $value
	 *
	 * @return bool
	 */
	public function isPossibleValue( $value ): bool {
		if ( empty( $this->values ) || null === $value ) {
			return true;
		}
		
		if ( in_array( $value, $this->getWeblingValues() ) ) {
			return true;
		}
		
		if ( in_array( $value, $this->getInternalValues() ) ) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Return array with all webling values as values and the internal values
	 * as keys.
	 *
	 * @return array
	 */
	public function getWeblingValues(): array {
		return $this->values;
	}
	
	/**
	 * Return array with all internal values as values and the webling values
	 * as keys.
	 *
	 * @return array
	 */
	public function getInternalValues(): array {
		return array_flip( $this->values );
	}
	
	/**
	 * Transform the given value in the webling value.
	 *
	 * @param null|string $value
	 *
	 * @return null|string
	 *
	 * @throws InvalidFixedValueException
	 */
	public function makeWeblingValue( $value ) {
		if ( null === $value ) {
			return null;
		}
		
		if ( empty( $this->values ) ) {
			return $value;
		}
		
		if ( in_array( $value, $this->getInternalValues() ) ) {
			return array_search( $value, $this->getInternalValues() );
		}
		
		if ( in_array( $value, $this->getWeblingValues() ) ) {
			return $value;
		}
		
		throw new InvalidFixedValueException( "'$value' is not a a valid fixed field value for '{$this->key}" );
	}
}
