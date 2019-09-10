<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 16.10.18
 * Time: 21:27
 */

namespace App\Repository\Member\Field;


use App\Exceptions\ValueTypeException;
use Tests\TestCase;

class FreeFieldTest extends TestCase
{
    private $key = 'internal';
    private $weblingKey = 'webling key';
    private $value = 'short enough';
    
    public function test__construct()
    {
        $field = $this->getField();
        $this->assertEquals($this->key, $field->getKey());
        $this->assertEquals($this->weblingKey, $field->getWeblingKey());
        $this->assertEquals($this->value, $field->getValue());
        $this->assertFalse($field->isDirty());
    }
    
    private function getField()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return new TextField($this->key, $this->weblingKey, $this->value);
    }
    
    public function testGetWeblingValue()
    {
        $field = $this->getField();
        $this->assertEquals($this->value, $field->getWeblingValue());
    }
    
    public function testSetValue()
    {
        $field = $this->getField();
        /** @noinspection PhpUnhandledExceptionInspection */
        $field->setValue($this->value); // unchanged
        $this->assertFalse($field->isDirty());
        
        /** @noinspection PhpUnhandledExceptionInspection */
        $field->setValue('not dirty', false);
        $this->assertFalse($field->isDirty());
        
        /** @noinspection PhpUnhandledExceptionInspection */
        $field->setValue('changed');
        $this->assertTrue($field->isDirty());
        
        $untrimmed = ' asdf ';
        /** @noinspection PhpUnhandledExceptionInspection */
        $field->setValue($untrimmed);
        $this->assertEquals(trim($untrimmed), $field->getValue());
        
        /** @noinspection PhpUnhandledExceptionInspection */
        $field->setValue('');
        $this->assertEquals(null, $field->getValue());
        
        /** @noinspection PhpUnhandledExceptionInspection */
        $field->setValue('   ');
        $this->assertEquals(null, $field->getValue());
    }
    
    public function testSetValueValueTypeException()
    {
        $field = $this->getField();
        $this->expectException(ValueTypeException::class);
        /** @noinspection PhpUnhandledExceptionInspection */
        /** @noinspection PhpParamsInspection */
        $field->setValue(['array text']);
    }
}
