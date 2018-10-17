<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 14.10.18
 * Time: 21:27
 */

namespace App\Repository\Member\Field;

use App\Exceptions\InputLengthException;
use Tests\TestCase;

class TextFieldTest extends TestCase {
	private $key = 'internal';
	private $weblingKey = 'webling key';
	private $value = 'short enough';
	
	private function getField() {
		/** @noinspection PhpUnhandledExceptionInspection */
		return new TextField($this->key, $this->weblingKey, $this->value);
	}
	
	public function testSetValueInputLengthException() {
		$twoHundredFiftySixChars = str_repeat( 'a', 256 );
		$this->expectException( InputLengthException::class );
		$field = $this->getField();
		/** @noinspection PhpUnhandledExceptionInspection */
		$field->setValue( $twoHundredFiftySixChars );
	}
	
	public function testSetValueInputLength() {
		$twoHundredFiftyFiveChars = str_repeat( 'a', 255 );
		$field                    = $this->getField();
		/** @noinspection PhpUnhandledExceptionInspection */
		$field->setValue( $twoHundredFiftyFiveChars );
		$this->assertEquals( $twoHundredFiftyFiveChars, $field->getValue() );
	}
}
