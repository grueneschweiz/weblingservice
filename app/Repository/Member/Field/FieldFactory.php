<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 27.10.18
 * Time: 16:29
 */

namespace App\Repository\Member\Field;

use App\Exceptions\WeblingFieldMappingConfigException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class FieldFactory {
	private $mappings;
	
	public function __construct() {
		$this->mappings = $this->loadMappings();
	}
	
	/**
	 * Read the Webling field mappings config file defined in .env and return
	 * an array with the mappings.
	 *
	 * @return array
	 * @throws WeblingFieldMappingConfigException
	 */
	private function loadMappings() {
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
		
		if ( ! is_array( $mappings['mappings'] ) ) {
			throw new WeblingFieldMappingConfigException( 'The entry point ("mappings") must contain an array with the field mappings.' );
		}
		
		return $mappings['mappings'];
	}
}
