<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 14.10.18
 * Time: 21:27
 */

namespace App\Repository\Member\Field;

use Tests\TestCase;

class MultiSelectFieldTest extends TestCase {
	private $key = 'internal';
	private $weblingKey = 'webling key';
	private $possibleValues = [
		'yes' => 'Ja / Oui',
		'no'  => 'Nein / No',
	];
	
	public function test__construct() {
		$field = $this->getField();
		$this->assertEquals( $this->key, $field->getKey() );
		$this->assertEquals( $this->weblingKey, $field->getWeblingKey() );
		$this->assertEquals( array(), $field->getValue() );
	}
	
	private function getField() {
		/** @noinspection PhpUnhandledExceptionInspection */
		return new MultiSelectField( $this->key, $this->weblingKey, $this->possibleValues );
	}
	
	public function testSetValue() {
		$field = $this->getField();
		
		// test not dirty change
		/** @noinspection PhpUnhandledExceptionInspection */
		$field->setValue( array_keys( $this->possibleValues ), false );
		$this->assertFalse( $field->isDirty() );
		
		// test value unchanged
		/** @noinspection PhpUnhandledExceptionInspection */
		$field->setValue( array_keys( $this->possibleValues ) );
		$this->assertFalse( $field->isDirty() );
		
		// set by internal keys
		$internal_keys = array_keys( $this->possibleValues );
		/** @noinspection PhpUnhandledExceptionInspection */
		$field->setValue( $internal_keys );
		$this->assertEquals( $internal_keys, $field->getValue() );
		
		// set empty by empty array
		/** @noinspection PhpUnhandledExceptionInspection */
		$field->setValue( array() );
		$this->assertEmpty( $field->getValue() );
		
		// set by webling keys
		$webling_keys  = array_values( $this->possibleValues );
		$internal_keys = array_keys( $this->possibleValues );
		/** @noinspection PhpUnhandledExceptionInspection */
		$field->setValue( $webling_keys );
		$this->assertEquals( $internal_keys, $field->getValue() );
		
		// set empty with null
		/** @noinspection PhpUnhandledExceptionInspection */
		$field->setValue( null );
		$this->assertEmpty( $field->getValue() );
		
		// set by string
		/** @noinspection PhpUnhandledExceptionInspection */
		$field->setValue( $this->possibleValues['yes'] );
		$this->assertTrue( in_array( 'yes', $field->getValue() ) );
	}
	
	public function testHasValue() {
		$field = $this->getField();
		
		// test empty string
		$this->assertFalse( $field->hasValue( '' ) );
		
		// test missing element on empty value
		$this->assertFalse( $field->hasValue( 'missing' ) );
		
		// test existing element
		/** @noinspection PhpUnhandledExceptionInspection */
		$field->setValue( 'yes' );
		$this->assertTrue( $field->hasValue( 'yes' ) );
		$this->assertTrue( $field->hasValue( $this->possibleValues['yes'] ) );
		
		// test missing element
		$this->assertFalse( $field->hasValue( 'no' ) );
		$this->assertFalse( $field->hasValue( $this->possibleValues['no'] ) );
		
		// test impossible element
		$this->assertFalse( $field->hasValue( 'impossible' ) );
	}
	
	public function testAppend() {
		$field = $this->getField();
		
		/** @noinspection PhpUnhandledExceptionInspection */
		$field->append( $this->possibleValues['yes'] );
		$this->assertTrue( $field->hasValue( $this->possibleValues['yes'] ) );
		
		/** @noinspection PhpUnhandledExceptionInspection */
		$field->append( $this->possibleValues['no'] );
		$this->assertEquals( array_keys( $this->possibleValues ), $field->getValue() );
	}
	
	public function testRemove() {
		$field = $this->getField();
		
		/** @noinspection PhpUnhandledExceptionInspection */
		$field->append( $this->possibleValues );
		
		/** @noinspection PhpUnhandledExceptionInspection */
		$field->remove( $this->possibleValues['no'] );
		$this->assertFalse( $field->hasValue( $this->possibleValues['no'] ) );
		$this->assertTrue( $field->hasValue( $this->possibleValues['yes'] ) );
	}
	
	public function testGetWeblingValue() {
		$field = $this->getField();
		
		/** @noinspection PhpUnhandledExceptionInspection */
		$field->setValue( $this->possibleValues );
		$this->assertEquals( array_values( $this->possibleValues ), $field->getWeblingValue() );
	}
}
