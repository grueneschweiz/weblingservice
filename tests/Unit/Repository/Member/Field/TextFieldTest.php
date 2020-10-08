<?php /** @noinspection PhpUnhandledExceptionInspection */

/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 14.10.18
 * Time: 21:27
 */

namespace App\Repository\Member\Field;

use App\Exceptions\InputLengthException;
use Tests\TestCase;

class TextFieldTest extends TestCase
{
    private $key = 'internal';
    private $weblingKey = 'webling key';
    private $value = 'short enough';
    private $secondValue = 'second value';
    private $separator = ', ';
    
    public function testSetValueInputLengthException()
    {
        $longString = str_repeat('a', 1024);
        $this->expectException(InputLengthException::class);
        $field = $this->getField();
        $field->setValue($longString);
    }
    
    private function getField()
    {
        return new TextField($this->key, $this->weblingKey, $this->value);
    }
    
    public function testSetValueInputLength()
    {
        $twoHundredFiftyFiveChars = str_repeat('a', 255);
        $field = $this->getField();
        $field->setValue($twoHundredFiftyFiveChars);
        $this->assertEquals($twoHundredFiftyFiveChars, $field->getValue());
    }
    
    public function testAppend__notInString()
    {
        $field = $this->getField();
        $field->append($this->secondValue, true, $this->separator);
        $this->assertEquals($this->value . $this->separator . $this->secondValue, $field->getValue());
    }
    
    public function testAppend__alreadyInString()
    {
        $field = $this->getField();
        $field->append($this->value);
        $this->assertEquals($this->value, $field->getValue());
    }
    
    public function testAppend__emptyString()
    {
        $field = $this->getField();
        $field->setValue(null);
        
        $field->append($this->secondValue);
        $this->assertEquals($this->secondValue, $field->getValue());
    }
}
