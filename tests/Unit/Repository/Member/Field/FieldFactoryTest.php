<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 27.10.18
 * Time: 17:02
 */

namespace App\Repository\Member\Field;

use App\Exceptions\MemberUnknownFieldException;
use App\Exceptions\MultiSelectOverwriteException;
use App\Exceptions\WeblingFieldMappingConfigException;
use App\Exceptions\WeblingFieldMappingException;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class FieldFactoryTest extends TestCase {
	const INTERNAL_FIELD_NAME = 'firstName';
	const WEBLING_FIELD_NAME = 'Vorname / prénom';
	const DATE_FIELD = 'birthday';
	const LONG_TEXT_FIELD = 'notesCountry';
	const MULTI_SELECT_FIELD = 'interests';
	const SELECT_FIELD = 'recordCategory';
	const SELECT_FIELD_VALUE_KEY = 'private';
	const SELECT_FIELD_VALUE_WEBLING_KEY = 'Privatperson / particulier';
	const TEXT_FIELD = 'lastName';
	
	public function test__constructConfigNotFound() {
		Config::set( 'app.webling_field_mappings_config_path', 'unknown' );
		
		$this->expectException( WeblingFieldMappingConfigException::class );
		$this->expectExceptionMessage( 'The Webling field mappings config file was not found.' );
		/** @noinspection PhpUnhandledExceptionInspection */
		FieldFactory::getInstance();
	}
	
	public function test__constructParseException() {
		Config::set( 'app.webling_field_mappings_config_path',
			$this->getFileRelPath() . DIRECTORY_SEPARATOR . 'webling-field-mappings-parse-error.yml' );
		
		$this->expectException( WeblingFieldMappingConfigException::class );
		$this->expectExceptionMessageRegExp( "/^YAML pase error:/" );
		/** @noinspection PhpUnhandledExceptionInspection */
		FieldFactory::getInstance();
	}
	
	private function getFileRelPath() {
		return str_replace( base_path() . '/', '', dirname( __FILE__ ) );
	}
	
	public function test__constructMappingsException() {
		Config::set( 'app.webling_field_mappings_config_path',
			$this->getFileRelPath() . DIRECTORY_SEPARATOR . 'webling-field-mappings-mappings-not-found.yml' );
		
		$this->expectException( WeblingFieldMappingConfigException::class );
		$this->expectExceptionMessage( 'The entry point ("mappings") was not found or empty.' );
		/** @noinspection PhpUnhandledExceptionInspection */
		FieldFactory::getInstance();
	}
	
	public function test__constructInvalidConfigException() {
		Config::set( 'app.webling_field_mappings_config_path',
			$this->getFileRelPath() . DIRECTORY_SEPARATOR . 'webling-field-mappings-invalid-config.yml' );
		
		$this->expectException( WeblingFieldMappingConfigException::class );
		$this->expectExceptionMessageRegExp( "/^Invalid Webling field mapping config:/" );
		/** @noinspection PhpUnhandledExceptionInspection */
		FieldFactory::getInstance();
	}
	
	public function test__constructReservedFieldNameException() {
		Config::set( 'app.webling_field_mappings_config_path',
			$this->getFileRelPath() . DIRECTORY_SEPARATOR . 'webling-field-mappings-reserved-field-key.yml' );
		
		$this->expectException( WeblingFieldMappingConfigException::class );
		$this->expectExceptionMessageRegExp( "/^Reserved field key:/" );
		/** @noinspection PhpUnhandledExceptionInspection */
		FieldFactory::getInstance();
	}
	
	public function testCreateByInternalKey() {
		/** @noinspection PhpUnhandledExceptionInspection */
		$fieldFactory = FieldFactory::getInstance();
		/** @noinspection PhpUnhandledExceptionInspection */
		$field = $fieldFactory->create( self::INTERNAL_FIELD_NAME );
		$this->assertEquals( self::WEBLING_FIELD_NAME, $field->getWeblingKey() );
	}
	
	public function testCreateByWeblingKey() {
		/** @noinspection PhpUnhandledExceptionInspection */
		$fieldFactory = FieldFactory::getInstance();
		/** @noinspection PhpUnhandledExceptionInspection */
		$field = $fieldFactory->create( self::WEBLING_FIELD_NAME );
		$this->assertEquals( self::INTERNAL_FIELD_NAME, $field->getKey() );
	}
	
	public function testCreateMemberUnknownFieldException() {
		/** @noinspection PhpUnhandledExceptionInspection */
		$fieldFactory = FieldFactory::getInstance();
		$this->expectException( MemberUnknownFieldException::class );
		/** @noinspection PhpUnhandledExceptionInspection */
		$fieldFactory->create( 'unknown' );
	}
	
	public function testCreateWithValue() {
		/** @noinspection PhpUnhandledExceptionInspection */
		$fieldFactory = FieldFactory::getInstance();
		/** @noinspection PhpUnhandledExceptionInspection */
		$field = $fieldFactory->create( self::WEBLING_FIELD_NAME, 'Hans Muster' );
		$this->assertFalse( $field->isDirty() );
	}
	
	public function testCreateDateField() {
		/** @noinspection PhpUnhandledExceptionInspection */
		$fieldFactory = FieldFactory::getInstance();
		/** @noinspection PhpUnhandledExceptionInspection */
		$field = $fieldFactory->create( self::DATE_FIELD );
		$this->assertTrue( $field instanceof DateField );
	}
	
	public function testCreateLongTextField() {
		/** @noinspection PhpUnhandledExceptionInspection */
		$fieldFactory = FieldFactory::getInstance();
		/** @noinspection PhpUnhandledExceptionInspection */
		$field = $fieldFactory->create( self::LONG_TEXT_FIELD );
		$this->assertTrue( $field instanceof LongTextField );
	}
	
	public function testCreateMultiSelectField() {
		/** @noinspection PhpUnhandledExceptionInspection */
		$fieldFactory = FieldFactory::getInstance();
		/** @noinspection PhpUnhandledExceptionInspection */
		$field = $fieldFactory->create( self::MULTI_SELECT_FIELD );
		$this->assertTrue( $field instanceof MultiSelectField );
	}
	
	public function testCreateMultiSelectFieldOverwriteException() {
		/** @noinspection PhpUnhandledExceptionInspection */
		$fieldFactory = FieldFactory::getInstance();
		$this->expectException( MultiSelectOverwriteException::class );
		/** @noinspection PhpUnhandledExceptionInspection */
		$fieldFactory->create( self::MULTI_SELECT_FIELD, 'anything' );
	}
	
	public function testCreateSelectField() {
		/** @noinspection PhpUnhandledExceptionInspection */
		$fieldFactory = FieldFactory::getInstance();
		/** @noinspection PhpUnhandledExceptionInspection */
		$field = $fieldFactory->create( self::SELECT_FIELD, self::SELECT_FIELD_VALUE_KEY );
		$this->assertTrue( $field instanceof SelectField );
		$this->assertEquals( self::SELECT_FIELD_VALUE_KEY, $field->getValue() );
		/** @noinspection PhpUndefinedMethodInspection */
		$this->assertEquals( self::SELECT_FIELD_VALUE_WEBLING_KEY, $field->getWeblingValue() );
	}
	
	public function testCreateTextField() {
		/** @noinspection PhpUnhandledExceptionInspection */
		$fieldFactory = FieldFactory::getInstance();
		/** @noinspection PhpUnhandledExceptionInspection */
		$field = $fieldFactory->create( self::TEXT_FIELD );
		$this->assertTrue( $field instanceof TextField );
	}
	
	public function testAllConfigMappings() {
		/** @noinspection PhpUnhandledExceptionInspection */
		$fieldFactory = FieldFactory::getInstance();
		/** @noinspection PhpUnhandledExceptionInspection */
		$mappings  = $this->getPrivateProperty( FieldFactory::class, 'mappings' );
		$fieldKeys = array_keys( $mappings->getValue( $fieldFactory ) );
		
		foreach ( $fieldKeys as $key ) {
			/** @noinspection PhpUnhandledExceptionInspection */
			$field = $fieldFactory->create( $key );
			$this->assertTrue( $field instanceof Field );
		}
	}
	
	/**
	 * getPrivateProperty
	 *
	 * @author    Joe Sexton <joe@webtipblog.com>
	 *
	 * @param    string $className
	 * @param    string $propertyName
	 *
	 * @return    \ReflectionProperty
	 * @throws \ReflectionException
	 */
	public function getPrivateProperty( $className, $propertyName ) {
		/** @noinspection PhpUnhandledExceptionInspection */
		$reflector = new \ReflectionClass( $className );
		$property  = $reflector->getProperty( $propertyName );
		$property->setAccessible( true );
		
		return $property;
	}
}
