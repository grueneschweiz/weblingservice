<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 27.10.18
 * Time: 16:29
 */

namespace App\Repository\Member\Field;

use App\Exceptions\MemberUnknownFieldException;
use App\Exceptions\MultiSelectOverwriteException;
use App\Exceptions\WeblingFieldMappingConfigException;
use App\Repository\Member\Field\Mapping\Loader;
use App\Repository\Member\Field\Mapping\Mapping;

/**
 * Class FieldFactory
 *
 * Creates the right fields from given key and value.
 *
 * @package App\Repository\Member\Field
 */
class FieldFactory {
	/**
	 * Create correct field with all needed presets from given key and optional value.
	 *
	 * NOTE: MultiSelectFields must not be created with a initial value to prevent
	 * accidental overwrite (append != setValue).
	 *
	 * NOTE: Fields of type 'Skip' will return null.
	 *
	 * @param string $key
	 * @param null|string $value
	 *
	 * @return Field|null null if the type was 'Skip'
	 * @throws MultiSelectOverwriteException
	 * @throws WeblingFieldMappingConfigException
	 * @throws MemberUnknownFieldException
	 * @throws \App\Exceptions\InvalidFixedValueException
	 * @throws \App\Exceptions\ValueTypeException
	 */
	public static function create( string $key, $value = null ) {
		$mapper  = Loader::getInstance();
		$mapping = $mapper->getMapping( $key );
		
		switch ( $mapping->getType() ) {
			case 'DateField':
				return new DateField( $mapping->getKey(), $mapping->getWeblingKey(), $value );
			case 'LongTextField':
				return new LongTextField( $mapping->getKey(), $mapping->getWeblingKey(), $value );
			case 'TextField':
				return new TextField( $mapping->getKey(), $mapping->getWeblingKey(), $value );
			case 'SelectField':
			case 'MultiSelectField':
				return self::createFixedField( $mapping, $value );
			case 'Skip':
				return null;
			default:
				throw new WeblingFieldMappingConfigException( 'Invalid Webling field mapping config: The given type does not match a field class. Given key: "' . $mapping->getType() . '"' );
		}
	}
	
	/**
	 * As {@see App\Repository\Member\Field\FixedField}s are slightly more
	 * complicated to create, we've separated the logic from the
	 * {@see self::create()} method.
	 *
	 * @param Mapping $mapping
	 * @param null|string $value
	 *
	 * @return FixedField
	 * @throws MultiSelectOverwriteException
	 * @throws \App\Exceptions\InvalidFixedValueException
	 * @throws \App\Exceptions\ValueTypeException
	 */
	private static function createFixedField( Mapping $mapping, $value ): FixedField {
		if ( 'SelectField' === $mapping->getType() ) {
			
			return new SelectField( $mapping->getKey(), $mapping->getWeblingKey(), $mapping->getWeblingValues(),
				$value );
		}
		
		if ( ! empty( $value ) ) {
			throw new MultiSelectOverwriteException( 'The value of MultiSelectFields must be set explicitly to prevent accidental overwrite of existing values.' );
		}
		
		return new MultiSelectField( $mapping->getKey(), $mapping->getWeblingKey(), $mapping->getWeblingValues() );
	}
}
