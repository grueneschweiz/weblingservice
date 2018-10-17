<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 14.10.18
 * Time: 21:27
 */

namespace App\Repository\Member\Field;

use App\Exceptions\InvalidFixedValueException;
use Tests\TestCase;

class SelectFieldTest extends TestCase {
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
		$this->assertEquals( 'yes', $field->getValue() );
		$this->assertFalse( $field->isDirty() );
	}
	
	private function getField() {
		/** @noinspection PhpUnhandledExceptionInspection */
		return new SelectField( $this->key, $this->weblingKey, $this->possibleValues, 'yes' );
	}
	
	public function testGetWeblingValue() {
		$field = $this->getField();
		$this->assertEquals( $this->possibleValues['yes'], $field->getWeblingValue() );
	}
	
	public function testSetValue() {
		$field = $this->getField();
		
		// test value unchanged
		/** @noinspection PhpUnhandledExceptionInspection */
		$field->setValue( $this->possibleValues['yes'] );
		$this->assertFalse( $field->isDirty() );
		
		// test not dirty change
		/** @noinspection PhpUnhandledExceptionInspection */
		$field->setValue( $this->possibleValues['no'], false );
		$this->assertFalse( $field->isDirty() );
		
		// test set by internal value
		/** @noinspection PhpUnhandledExceptionInspection */
		$field->setValue( 'yes' );
		$this->assertEquals( 'yes', $field->getValue() );
		
		// test set by webling value
		/** @noinspection PhpUnhandledExceptionInspection */
		$field->setValue( $this->possibleValues['no'] );
		$this->assertEquals( 'no', $field->getValue() );
		
		// test dirty change
		/** @noinspection PhpUnhandledExceptionInspection */
		$field->setValue( $this->possibleValues['yes'] );
		$this->assertTrue( $field->isDirty() );
	}
	
	public function testSetValueInvalidFixedValueException() {
		$field = $this->getField();
		$this->expectException( InvalidFixedValueException::class );
		/** @noinspection PhpUnhandledExceptionInspection */
		$field->setValue( 'else' );
	}
}
