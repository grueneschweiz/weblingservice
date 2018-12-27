<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 09.12.18
 * Time: 12:26
 */

namespace App\Repository\Member\Field\Mapping;


use App\Exceptions\InvalidFixedValueException;
use Tests\TestCase;

class MappingTest extends TestCase {
	const SELECT_FIELD_KEY_INTERNAL = 'recordCategory';
	const SELECT_FIELD_KEY_WEBLING = 'Datensatzkategorie / type d’entrée';
	const SELECT_FIELD_TYPE = 'SelectField';
	const SELECT_POSSIBLE_VALUES = [
		'private' => 'Privatperson / particulier',
		'media'   => 'Medien / média'
	];
	const SELECT_INVALID_VALUE = 'invalid';
	
	const MULTI_SELECT_FIELD_KEY_INTERNAL = 'mandateCountry';
	const MULTI_SELECT_FIELD_KEY_WEBLING = 'Mandate National / mandats nationaux';
	const MULTI_SELECT_FIELD_TYPE = 'MultiSelectField';
	const MULTI_SELECT_POSSIBLE_VALUES = [
		'legislativeActive' => 'Legislative aktiv / législatif actuel',
		'legislativePast'   => 'Legislative ehemals / législatif passé'
	];
	const MULTI_SELECT_INVALID_VALUE = 'invalid';
	
	const TEXT_FIELD_KEY_INTERNAL = 'firstName';
	const TEXT_FIELD_KEY_WEBLING = 'Vorname / prénom';
	const TEXT_FIELD_TYPE = 'TextField';
	const SOME_TEXT = 'Some text';
	
	const DATE_FIELD_KEY_INTERNAL = 'birthday';
	const DATE_FIELD_KEY_WEBLING = 'Geburtstag / anniversaire';
	const DATE_FIELD_TYPE = 'DateField';
	const VALID_DATE = '25.12.2018';
	const INVALID_DATE = '12.25.2018';
	
	public function testMakeWeblingValue() {
		$mapping = $this->getMapping();
		
		$internalValue = array_keys( self::SELECT_POSSIBLE_VALUES )[0];
		$weblingValue  = array_values( self::SELECT_POSSIBLE_VALUES )[0];
		
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->assertEquals( $weblingValue, $mapping->makeWeblingValue( $internalValue ) );
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->assertEquals( $weblingValue, $mapping->makeWeblingValue( $weblingValue ) );
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->assertEquals( null, $mapping->makeWeblingValue( null ) );
		
		$freeFieldMapping = new Mapping(
			self::SELECT_FIELD_KEY_INTERNAL,
			self::SELECT_FIELD_KEY_WEBLING,
			self::TEXT_FIELD_TYPE
		);
		
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->assertEquals( self::SELECT_INVALID_VALUE,
			$freeFieldMapping->makeWeblingValue( self::SELECT_INVALID_VALUE ) );
	}
	
	private function getMapping() {
		return new Mapping(
			self::SELECT_FIELD_KEY_INTERNAL,
			self::SELECT_FIELD_KEY_WEBLING,
			self::SELECT_FIELD_TYPE,
			self::SELECT_POSSIBLE_VALUES
		);
	}
	
	public function testMakeWeblingValue_InvalidFixedValueException() {
		$mapping = $this->getMapping();
		
		$this->expectException( InvalidFixedValueException::class );
		/** @noinspection PhpUnhandledExceptionInspection */
		$mapping->makeWeblingValue( self::SELECT_INVALID_VALUE );
	}
	
	public function testIsPossibleValue() {
		$mapping = $this->getMapping();
		
		$internalValue = array_keys( self::SELECT_POSSIBLE_VALUES )[0];
		$weblingValue  = array_values( self::SELECT_POSSIBLE_VALUES )[0];
		
		$this->assertTrue( $mapping->isPossibleValue( $internalValue ) );
		$this->assertTrue( $mapping->isPossibleValue( $weblingValue ) );
		$this->assertTrue( $mapping->isPossibleValue( null ) );
		$this->assertFalse( $mapping->isPossibleValue( self::SELECT_INVALID_VALUE ) );
	}
	
	public function testIsPossibleValue_freeField() {
		$mapping = new Mapping(
			self::TEXT_FIELD_KEY_INTERNAL,
			self::TEXT_FIELD_KEY_WEBLING,
			self::TEXT_FIELD_TYPE
		);
		
		$this->assertTrue( $mapping->isPossibleValue( '' ) );
		$this->assertTrue( $mapping->isPossibleValue( null ) );
		$this->assertTrue( $mapping->isPossibleValue( self::SOME_TEXT ) );
	}
	
	public function testIsPossibleValue_dateField() {
		$mapping = new Mapping(
			self::DATE_FIELD_KEY_INTERNAL,
			self::DATE_FIELD_KEY_WEBLING,
			self::DATE_FIELD_TYPE
		);
		
		$this->assertTrue( $mapping->isPossibleValue( '' ) );
		$this->assertTrue( $mapping->isPossibleValue( null ) );
		$this->assertTrue( $mapping->isPossibleValue( self::VALID_DATE ) );
		$this->assertFalse( $mapping->isPossibleValue( self::INVALID_DATE ) );
		$this->assertFalse( $mapping->isPossibleValue( self::SOME_TEXT ) );
	}
	
	public function testIsPossibleValue_multiSelect() {
		$mapping = new Mapping(
			self::MULTI_SELECT_FIELD_KEY_INTERNAL,
			self::MULTI_SELECT_FIELD_KEY_WEBLING,
			self::MULTI_SELECT_FIELD_TYPE,
			self::MULTI_SELECT_POSSIBLE_VALUES
		);
		
		$this->assertFalse( $mapping->isPossibleValue( '' ) );
		$this->assertTrue( $mapping->isPossibleValue( null ) );
		$this->assertTrue( $mapping->isPossibleValue( array_values( self::MULTI_SELECT_POSSIBLE_VALUES )[0] ) );
		$this->assertTrue( $mapping->isPossibleValue( array_keys( self::MULTI_SELECT_POSSIBLE_VALUES )[0] ) );
		$this->assertFalse( $mapping->isPossibleValue( self::MULTI_SELECT_INVALID_VALUE ) );
	}
	
	public function test__construct() {
		$mapping = new Mapping(
			self::SELECT_FIELD_KEY_INTERNAL,
			self::SELECT_FIELD_KEY_WEBLING,
			self::SELECT_FIELD_TYPE,
			self::SELECT_POSSIBLE_VALUES
		);
		
		$this->assertEquals( self::SELECT_FIELD_KEY_INTERNAL, $mapping->getKey() );
		$this->assertEquals( self::SELECT_FIELD_KEY_WEBLING, $mapping->getWeblingKey() );
		$this->assertEquals( self::SELECT_FIELD_TYPE, $mapping->getType() );
		$this->assertEquals( self::SELECT_POSSIBLE_VALUES, $mapping->getWeblingValues() );
		$this->assertEquals( array_flip( self::SELECT_POSSIBLE_VALUES ), $mapping->getInternalValues() );
	}
}
