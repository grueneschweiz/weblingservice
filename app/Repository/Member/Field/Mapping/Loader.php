<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 08.12.18
 * Time: 18:00
 */

namespace App\Repository\Member\Field\Mapping;

use App\Exceptions\MemberUnknownFieldException;
use App\Exceptions\WeblingFieldMappingConfigException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads and holds the field mappings (internal to Webling). Singleton
 *
 * @package App\Repository\Member\Field\Mapping
 */
class Loader {
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
	 * @var Loader|null
	 */
	private static $instance;
	
	/**
	 * Cache the field mappings
	 *
	 * @var Mapping[]
	 */
	private $mappings = [];
	
	/**
	 * Array with the internal field keys as value and the webling field
	 * keys as keys
	 *
	 * @var array
	 */
	private $fieldKeys = [];
	
	/**
	 * Flipped array of the field keys
	 *
	 * @var array
	 */
	private $flippedFieldKeys = [];
	
	/**
	 * Loader constructor.
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
			throw new WeblingFieldMappingConfigException( "YAML parse error: {$e->getMessage()}" );
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
		
		if ( empty( $array['type'] ) ) {
			throw new WeblingFieldMappingConfigException( 'Invalid Webling field mapping config: Every mapping element must provide a non-empty type property. Given key: "' . $array['key'] . '"' );
		}
		
		$values = [];
		switch ( $array['type'] ) {
			case 'DateField':
			case 'LongTextField':
			case 'TextField':
			case 'Skip':
				break;
			
			case 'SelectField':
			case 'MultiSelectField':
				if ( empty( $array['values'] ) ) {
					throw new WeblingFieldMappingConfigException( 'Invalid Webling field mapping config: All mappings of type "' . $array['type'] . '" must provide a property "values" that contains all possible values for this field. Given key: "' . $array['key'] . '"' );
				}
				$values = $this->preparePossibleValues( $array['values'] );
				break;
			
			default:
				throw new WeblingFieldMappingConfigException( 'Invalid Webling field mapping config: The given type does not match a field class. Given key: "' . $array['type'] . '"' );
		}
		
		// add mapping by its internal key
		$this->mappings[ $array['key'] ] = new Mapping(
			$array['key'],
			$array['weblingKey'],
			$array['type'],
			$values
		);
		
		// populate the internal field keys array
		$this->fieldKeys[ $array['weblingKey'] ] = $array['key'];
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
	 * Get instance.
	 *
	 * @return Loader|null
	 * @throws WeblingFieldMappingConfigException
	 */
	public static function getInstance() {
		if ( ! self::$instance ) {
			self::$instance = new Loader();
		}
		
		return self::$instance;
	}
	
	/**
	 * Return the mapping with the given key
	 *
	 * @param string $key the internal key or the webling key
	 *
	 * @return Mapping
	 * @throws MemberUnknownFieldException
	 */
	public function getMapping( string $key ): Mapping {
		$internalKey = $this->makeInternalKey( $key );
		
		return $this->mappings[ $internalKey ];
	}
	
	/**
	 * Turns the given field key into the internal key
	 *
	 * @param string $key webling key or internal key
	 *
	 * @return string
	 * @throws MemberUnknownFieldException
	 */
	private function makeInternalKey( string $key ): string {
		if ( in_array( $key, $this->fieldKeys ) ) {
			return $key;
		}
		
		if ( empty( $this->flippedFieldKeys ) ) {
			$this->flippedFieldKeys = array_flip( $this->getFieldKeys() );
		}
		
		$internal = array_search( $key, $this->flippedFieldKeys );
		if ( $internal ) {
			return $internal;
		}
		
		throw new MemberUnknownFieldException( 'The given key "' . $key . '" was not found in the webling field mapping config.' );
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
