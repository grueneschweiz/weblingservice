<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 14.10.18
 * Time: 21:27
 */

namespace App\Repository\Member\Field;

use App\Exceptions\DateParsingException;
use App\Exceptions\ValueTypeException;
use Tests\TestCase;

class DateFieldTest extends TestCase {
	private $key = 'internal';
	private $weblingKey = 'webling key';
	private $value = '2018-02-01';
	
	private function getField() {
		/** @noinspection PhpUnhandledExceptionInspection */
		return new DateField($this->key, $this->weblingKey, $this->value);
	}
	
	public function testSetValue() {
		$field = $this->getField();
		
		// test mysql date format
		/** @noinspection PhpUnhandledExceptionInspection */
		$field->setValue('2018-02-01');
		$this->assertEquals($this->value, $field->getValue());
		
		// test european date format
		/** @noinspection PhpUnhandledExceptionInspection */
		$field->setValue('01.02.2018');
		$this->assertEquals($this->value, $field->getValue());
	}
	
	public function testSetValueDateParsingException() {
		$field = $this->getField();
		$this->expectException(DateParsingException::class);
		/** @noinspection PhpUnhandledExceptionInspection */
		$field->setValue('lirum larum');
	}
}