<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 27.10.18
 * Time: 16:29
 */

namespace App\Repository\Member\Field;

use App\Exceptions\MultiSelectOverwriteException;
use App\Exceptions\MemberUnknownFieldException;
use App\Exceptions\WeblingFieldMappingConfigException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Class FieldFactory
 *
 * Creates the right fields from given key and value. Works as a singleton.
 *
 * @package App\Repository\Member\Field
 */
class FieldFactory {
	/**
	 * Reserved field names
	 */
	const RESERVED = [
		'groups',
		'rootGroups',
		'id'
	];
	
	/**
	 * The instance
	 *
	 * @var FieldFactory|null
	 */
	private static $instance;
	
	/**
	 * Cache the field mappings
	 *
	 * @var array
	 */
	private $mappings = [];
	
	/**
	 * The internal field keys
	 *
	 * @var array
	 */
	private $fieldKeys = [];
	
	/**
	 * Get instance.
	 *
	 * @return FieldFactory|null
	 * @throws WeblingFieldMappingConfigException
	 */
	public static function getInstance() {
		if (! self::$instance)
		{
			self::$instance = new FieldFactory();
		}
		
		return self::$instance;
	}
	
	/**
	 * FieldFactory constructor.
	 *
	 * Read mappings config and populate mappings field with it.
	 *
	 * @throws WeblingFieldMappingConfigException
	 */
	private function __construct() {
		$mappings = $this->readMappings();
		
		foreach ( $mappings as $mapping ) {
			$this->addMapping( $mapping );
		}
	}
	
	/**
	 * Read the Webling field mappings config file defined in .env and return
	 * an array with the mappings.
	 *
	 * @return array
	 * @throws WeblingFieldMappingConfigException
	 */
	private function readMappings() {
		$path = base_path( config( 'app.webling_field_mappings_config_path' ) );
		
		if ( ! file_exists( $path ) ) {
			throw new WeblingFieldMappingConfigException( 'The Webling field mappings config file was not found.' );
		}
		
		try {
			$mappings = Yaml::parseFile( $path );
		} catch ( ParseException $e ) {
			throw new WeblingFieldMappingConfigException( "YAML pase error: {$e->getMessage()}" );
		}
		
		
		if ( empty( $mappings['mappings'] ) ) {
			throw new WeblingFieldMappingConfigException( 'The entry point ("mappings") was not found or empty.' );
		}
		
		return $mappings['mappings'];
	}
	
	/**
	 * Add the given mapping data to the mappings field, accessible by the
	 * internal key and with a alias using the webling key.
	 *
	 * @param array $array
	 *
	 * @throws WeblingFieldMappingConfigException
	 */
	private function addMapping( array $array ) {
		if ( empty( $array['key'] ) ) {
			throw new WeblingFieldMappingConfigException( 'Invalid Webling field mapping config: Every mapping element must provide a non-empty key property.' );
		}
		
		if ( empty( $array['weblingKey'] ) ) {
			throw new WeblingFieldMappingConfigException( 'Invalid Webling field mapping config: Every mapping element must provide a non-empty weblingKey property.' );
		}
		
		if ( in_array( $array['key'], self::RESERVED ) ) {
			throw new WeblingFieldMappingConfigException( "Reserved field key: {$array['key']}" );
		}
		
		// add mapping by its internal key
		$this->mappings[ $array['key'] ] = $array;
		
		// add alias so we can also access it by the webling key
		$this->mappings[ $array['weblingKey'] ] = &$this->mappings[ $array['key'] ];
		
		// populate the internal field keys array
		$this->fieldKeys[] = $array['key'];
	}
	
	/**
	 * Create correct field with all needed presets from given key and optional value.
	 *
	 * NOTE: MultiSelectFields must not be created with a initial value to prevent
	 * accidental overwrite (append != setValue).
	 *
	 * @param string $key
	 * @param null|string $value
	 *
	 * @return Field
	 * @throws MultiSelectOverwriteException
	 * @throws WeblingFieldMappingConfigException
	 * @throws MemberUnknownFieldException
	 * @throws \App\Exceptions\InvalidFixedValueException
	 * @throws \App\Exceptions\ValueTypeException
	 */
	public function create( string $key, $value = null ): Field {
		if ( empty( $this->mappings[ $key ] ) ) {
			throw new MemberUnknownFieldException( 'The given key "' . $key . '" was not found in the webling field mapping config.' );
		}
		
		$mapping = $this->mappings[ $key ];
		
		if ( empty( $mapping['type'] ) ) {
			throw new WeblingFieldMappingConfigException( 'Invalid Webling field mapping config: Every mapping element must provide a non-empty type property. Given key: "' . $key . '"' );
		}
		
		switch ( $mapping['type'] ) {
			case 'DateField':
				return new DateField( $mapping['key'], $mapping['weblingKey'], $value );
			case 'LongTextField':
				return new LongTextField( $mapping['key'], $mapping['weblingKey'], $value );
			case 'TextField':
				return new TextField( $mapping['key'], $mapping['weblingKey'], $value );
			case 'SelectField':
			case 'MultiSelectField':
				return $this->createFixedField( $key, $value );
		}
		
		throw new WeblingFieldMappingConfigException( 'Invalid Webling field mapping config: The given type does not match a field class. Given key: "' . $key . '"' );
	}
	
	/**
	 * Does basically as {@see App\Repository\Member\Field\FixedField}s are
	 * slightly more complicated to create, we've separated the logic from the
	 * {@see self::create()} method.
	 *
	 * @param string $key
	 * @param null|string $value
	 *
	 * @return FixedField
	 * @throws MultiSelectOverwriteException
	 * @throws WeblingFieldMappingConfigException
	 * @throws \App\Exceptions\InvalidFixedValueException
	 * @throws \App\Exceptions\ValueTypeException
	 */
	private function createFixedField( string $key, $value ): FixedField {
		$mapping = $this->mappings[ $key ];
		
		if ( empty( $mapping['values'] ) ) {
			throw new WeblingFieldMappingConfigException( 'Invalid Webling field mapping config: All mappings of type "' . $mapping['type'] . '" must provide a property "values" that contains all possible values for this field. Given key: "' . $key . '"' );
		}
		
		$possibleValues = $this->preparePossibleValues( $mapping['values'] );
		
		if ( 'SelectField' === $mapping['type'] ) {
			
			return new SelectField( $mapping['key'], $mapping['weblingKey'], $possibleValues, $value );
		}
		
		if ( ! empty( $value ) ) {
			throw new MultiSelectOverwriteException( 'The value of MultiSelectFields must be set explicitly to prevent accidental overwrite of existing values.' );
		}
		
		return new MultiSelectField( $mapping['key'], $mapping['weblingKey'], $possibleValues );
	}
	
	/**
	 * Helper function that maps the numeric two-dimensional array of the yaml
	 * parser into a one-dimensional key value paired array.
	 *
	 * @param array $array
	 *
	 * @return array
	 */
	private function preparePossibleValues( array $array ): array {
		$values = [];
		foreach ( $array as $a ) {
			$key            = array_keys( $a )[0];
			$values[ $key ] = $a[ $key ];
		}
		
		return $values;
	}
	
	/**
	 * Return an array containing all internal field keys
	 *
	 * @return array
	 */
	public function getFieldKeys(): array {
		return $this->fieldKeys;
	}
}
