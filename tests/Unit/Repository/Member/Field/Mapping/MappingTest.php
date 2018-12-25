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
	const INTERNAL_FIELD_NAME = 'recordCategory';
	const WEBLING_FIELD_NAME = 'Datensatzkategorie / type d’entrée';
	const TYPE = 'SelectField';
	const POSSIBLE_VALUES = [
		'private' => 'Privatperson / particulier',
		'media'   => 'Medien / média'
	];
	const INVALID_VALUE = 'invalid';
	
	const TEXT_FIELD_TYPE = 'TextField';
	const SOME_TEXT = 'Some text';
	
	const DATE_FIELD_TYPE = 'DateField';
	const VALID_DATE = '25.12.2018';
	const INVALID_DATE = '12.25.2018';
	
	public function testMakeWeblingValue() {
		$mapping = $this->getMapping();
		
		$internalValue = array_keys( self::POSSIBLE_VALUES )[0];
		$weblingValue  = array_values( self::POSSIBLE_VALUES )[0];
		
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->assertEquals( $weblingValue, $mapping->makeWeblingValue( $internalValue ) );
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->assertEquals( $weblingValue, $mapping->makeWeblingValue( $weblingValue ) );
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->assertEquals( null, $mapping->makeWeblingValue( null ) );
		
		$freeFieldMapping = new Mapping(
			self::INTERNAL_FIELD_NAME,
			self::WEBLING_FIELD_NAME,
			self::TEXT_FIELD_TYPE
		);
		
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->assertEquals( self::INVALID_VALUE, $freeFieldMapping->makeWeblingValue( self::INVALID_VALUE ) );
	}
	
	private function getMapping() {
		return new Mapping(
			self::INTERNAL_FIELD_NAME,
			self::WEBLING_FIELD_NAME,
			self::TYPE,
			self::POSSIBLE_VALUES
		);
	}
	
	public function testMakeWeblingValue_InvalidFixedValueException() {
		$mapping = $this->getMapping();
		
		$this->expectException( InvalidFixedValueException::class );
		/** @noinspection PhpUnhandledExceptionInspection */
		$mapping->makeWeblingValue( self::INVALID_VALUE );
	}
	
	public function testIsPossibleValue() {
		$mapping = $this->getMapping();
		
		$internalValue = array_keys( self::POSSIBLE_VALUES )[0];
		$weblingValue  = array_values( self::POSSIBLE_VALUES )[0];
		
		$this->assertTrue( $mapping->isPossibleValue( $internalValue ) );
		$this->assertTrue( $mapping->isPossibleValue( $weblingValue ) );
		$this->assertTrue( $mapping->isPossibleValue( null ) );
		$this->assertFalse( $mapping->isPossibleValue( self::INVALID_VALUE ) );
	}
	
	public function testIsPossibleValue_freeField() {
		$mapping = new Mapping(
			self::INTERNAL_FIELD_NAME,
			self::WEBLING_FIELD_NAME,
			self::TEXT_FIELD_TYPE
		);
		
		$this->assertTrue( $mapping->isPossibleValue( '' ) );
		$this->assertTrue( $mapping->isPossibleValue( null ) );
		$this->assertTrue( $mapping->isPossibleValue( self::SOME_TEXT ) );
	}
	
	public function testIsPossibleValue_dateField() {
		$mapping = new Mapping(
			self::INTERNAL_FIELD_NAME,
			self::WEBLING_FIELD_NAME,
			self::DATE_FIELD_TYPE
		);
		
		$this->assertTrue( $mapping->isPossibleValue( '' ) );
		$this->assertTrue( $mapping->isPossibleValue( null ) );
		$this->assertTrue( $mapping->isPossibleValue( self::VALID_DATE ) );
		$this->assertFalse( $mapping->isPossibleValue( self::INVALID_DATE ) );
		$this->assertFalse( $mapping->isPossibleValue( self::SOME_TEXT ) );
	}
	
	public function test__construct() {
		$mapping = new Mapping(
			self::INTERNAL_FIELD_NAME,
			self::WEBLING_FIELD_NAME,
			self::TYPE,
			self::POSSIBLE_VALUES
		);
		
		$this->assertEquals( self::INTERNAL_FIELD_NAME, $mapping->getKey() );
		$this->assertEquals( self::WEBLING_FIELD_NAME, $mapping->getWeblingKey() );
		$this->assertEquals( self::TYPE, $mapping->getType() );
		$this->assertEquals( self::POSSIBLE_VALUES, $mapping->getWeblingValues() );
		$this->assertEquals( array_flip( self::POSSIBLE_VALUES ), $mapping->getInternalValues() );
	}
}
