<?php

namespace App\Repository\Member\Field;

use Tests\TestCase;

class LongTextFieldTest extends TestCase {
	
	public function test__constructValueLength() {
		$key        = 'internal';
		$weblingKey = 'webling key';
		$value      = str_repeat( 'a', 500 );
		
		/** @noinspection PhpUnhandledExceptionInspection */
		$field = new LongTextField( $key, $weblingKey, $value );
		$this->assertEquals( $value, $field->getValue() );
	}
}
