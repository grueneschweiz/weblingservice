<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace App\Repository\Member\Field;

use Tests\TestCase;

class LongTextFieldTest extends TestCase
{
    private $key = 'internal';
    private $weblingKey = 'webling key';
    private $value = 'first value';
    private $secondValue = 'second value';
    private $separator = "\n";
    
    public function test__constructValueLength()
    {
        $field = $this->getField();
        $value = str_repeat('a', 500);
        $field->setValue($value);
        
        $this->assertEquals($value, $field->getValue());
    }
    
    private function getField()
    {
        return new LongTextField($this->key, $this->weblingKey, $this->value);
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
    
    public function testRemove__notPresent()
    {
        $field = $this->getField();
        $field->remove($this->secondValue, true, $this->separator);
        self::assertEquals($this->value, $field->getValue());
        self::assertFalse($field->isDirty());
    }
    
    public function testRemove__present()
    {
        $field = $this->getField();
        $field->remove($this->value);
        self::assertEmpty($field->getValue());
        self::assertTrue($field->isDirty());
        
    }
    
    public function testRemove__empty()
    {
        $field = $this->getField();
        $field->setValue(null, false);
        
        $field->remove($this->value);
        self::assertNull($field->getValue());
        self::assertFalse($field->isDirty());
    }
    
    public function testRemove__nothing()
    {
        $field = $this->getField();
        
        $field->remove('');
        self::assertEquals($this->value, $field->getValue());
        self::assertFalse($field->isDirty());
    }
    
    public function testRemove__cut()
    {
        $field = $this->getField();
        $field->setValue("some text\ntagtagtag \n tagtagtag asdf other");
        $field->remove('tagtagtag');
        self::assertEquals("some text\nasdf other", $field->getValue());
        self::assertTrue($field->isDirty());
        
    }
    
    public function testRemove__noWordFractions1()
    {
        $field = $this->getField();
        $field->setValue("some text\nKtagtagtag", false);
        $field->remove('tagtagtag');
        self::assertEquals("some text\nKtagtagtag", $field->getValue());
        self::assertFalse($field->isDirty());
    }
    
    public function testRemove__noWordFractions2()
    {
        $field = $this->getField();
        $field->setValue("some text\ntagtagtagK", false);
        $field->remove('tagtagtag');
        self::assertEquals("some text\ntagtagtagK", $field->getValue());
        self::assertFalse($field->isDirty());
    }
}
