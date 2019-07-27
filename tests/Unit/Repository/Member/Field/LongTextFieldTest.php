<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace App\Repository\Member\Field;

use Tests\TestCase;

class LongTextFieldTest extends TestCase {
	private $key = 'internal';
	private $weblingKey = 'webling key';
	private $value = 'first value';
	private $secondValue = 'second value';
	private $separator = "\n";

	public function test__constructValueLength() {
		$field = $this->getField();
		$value = str_repeat( 'a', 500 );
		$field->setValue( $value );

		$this->assertEquals( $value, $field->getValue() );
	}

	private function getField() {
		return new LongTextField( $this->key, $this->weblingKey, $this->value );
	}

	public function testAppend__notInString() {
		$field = $this->getField();
		$field->append( $this->secondValue, true, $this->separator );
		$this->assertEquals( $this->value . $this->separator . $this->secondValue, $field->getValue() );
	}

	public function testAppend__alreadyInString() {
		$field = $this->getField();
		$field->append( $this->value );
		$this->assertEquals( $this->value, $field->getValue() );
	}
}
