<?php

namespace App\Repository\Member\Field;

use App\Exceptions\MemberUnknownFieldException;
use App\Exceptions\MultiSelectOverwriteException;
use App\Repository\Member\Field\Mapping\Loader;
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
	const SKIP_FIELD = 'dontUse';
	
	public function testCreateByInternalKey() {
		$fieldFactory = FieldFactory::getInstance();
		/** @noinspection PhpUnhandledExceptionInspection */
		$field = $fieldFactory->create( self::INTERNAL_FIELD_NAME );
		$this->assertEquals( self::WEBLING_FIELD_NAME, $field->getWeblingKey() );
	}
	
	public function testCreateByWeblingKey() {
		$fieldFactory = FieldFactory::getInstance();
		/** @noinspection PhpUnhandledExceptionInspection */
		$field = $fieldFactory->create( self::WEBLING_FIELD_NAME );
		$this->assertEquals( self::INTERNAL_FIELD_NAME, $field->getKey() );
	}
	
	public function testCreateMemberUnknownFieldException() {
		$fieldFactory = FieldFactory::getInstance();
		$this->expectException( MemberUnknownFieldException::class );
		/** @noinspection PhpUnhandledExceptionInspection */
		$fieldFactory->create( 'unknown' );
	}
	
	public function testCreateWithValue() {
		$fieldFactory = FieldFactory::getInstance();
		/** @noinspection PhpUnhandledExceptionInspection */
		$field = $fieldFactory->create( self::WEBLING_FIELD_NAME, 'Hans Muster' );
		$this->assertFalse( $field->isDirty() );
	}
	
	public function testCreateDateField() {
		$fieldFactory = FieldFactory::getInstance();
		/** @noinspection PhpUnhandledExceptionInspection */
		$field = $fieldFactory->create( self::DATE_FIELD );
		$this->assertTrue( $field instanceof DateField );
	}
	
	public function testCreateLongTextField() {
		$fieldFactory = FieldFactory::getInstance();
		/** @noinspection PhpUnhandledExceptionInspection */
		$field = $fieldFactory->create( self::LONG_TEXT_FIELD );
		$this->assertTrue( $field instanceof LongTextField );
	}
	
	public function testCreateMultiSelectField() {
		$fieldFactory = FieldFactory::getInstance();
		/** @noinspection PhpUnhandledExceptionInspection */
		$field = $fieldFactory->create( self::MULTI_SELECT_FIELD );
		$this->assertTrue( $field instanceof MultiSelectField );
	}
	
	public function testCreateMultiSelectFieldOverwriteException() {
		$fieldFactory = FieldFactory::getInstance();
		$this->expectException( MultiSelectOverwriteException::class );
		/** @noinspection PhpUnhandledExceptionInspection */
		$fieldFactory->create( self::MULTI_SELECT_FIELD, 'anything' );
	}
	
	public function testCreateSelectField() {
		$fieldFactory = FieldFactory::getInstance();
		/** @noinspection PhpUnhandledExceptionInspection */
		$field = $fieldFactory->create( self::SELECT_FIELD, self::SELECT_FIELD_VALUE_KEY );
		$this->assertTrue( $field instanceof SelectField );
		$this->assertEquals( self::SELECT_FIELD_VALUE_KEY, $field->getValue() );
		/** @noinspection PhpUndefinedMethodInspection */
		$this->assertEquals( self::SELECT_FIELD_VALUE_WEBLING_KEY, $field->getWeblingValue() );
	}
	
	public function testCreateTextField() {
		$fieldFactory = FieldFactory::getInstance();
		/** @noinspection PhpUnhandledExceptionInspection */
		$field = $fieldFactory->create( self::TEXT_FIELD );
		$this->assertTrue( $field instanceof TextField );
	}
	
	public function testCreateSkipField() {
		$fieldFactory = FieldFactory::getInstance();
		/** @noinspection PhpUnhandledExceptionInspection */
		$field = $fieldFactory->create( self::SKIP_FIELD );
		$this->assertEmpty( $field );
	}
	
	public function testAllConfigMappings() {
		/** @noinspection PhpUnhandledExceptionInspection */
		$loader    = Loader::getInstance();
		$fieldKeys = $loader->getFieldKeys();
		
		$fieldFactory = FieldFactory::getInstance();
		
		foreach ( $fieldKeys as $key ) {
			/** @noinspection PhpUnhandledExceptionInspection */
			$field = $fieldFactory->create( $key );
			if ( $field ) {
				// handle Skip fields
				$this->assertTrue( $field instanceof Field );
			}
		}
	}
}
