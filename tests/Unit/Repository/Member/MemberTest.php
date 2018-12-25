<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 04.11.18
 * Time: 16:39
 */

namespace App\Repository\Member;


use App\Exceptions\MemberUnknownFieldException;
use App\Exceptions\MultiSelectOverwriteException;
use App\Exceptions\WeblingFieldMappingException;
use Tests\TestCase;

class MemberTest extends TestCase {
	private $id = 123;
	private $someKey = 'firstName';
	private $someValue = 'Hugo';
	private $someWeblingKey = 'Name / nom';
	private $someOtherField = 'zip';
	private $noneExistingField = 'nonExistingField';
	private $multiSelectField = 'interests';
	private $multiSelectValue = 'digitisation';
	private $data = [];
	private $groups; // todo: test groups as soon as they are implemented
	
	public function setUp() {
		parent::setUp();
		
		$this->data = [
			$this->someKey          => $this->someValue,
			$this->someWeblingKey   => $this->someValue,
			$this->multiSelectField => $this->multiSelectValue,
		];
	}
	
	public function testGetDirtyFields() {
		$member = $this->getMember();
		$this->assertEmpty( $member->getDirtyFields() );
		$member->{$this->someKey}->setValue( 'this makes me dirty' );
		$this->assertTrue( in_array( $member->{$this->someKey}, $member->getDirtyFields() ) );
	}
	
	private function getMember() {
		/** @noinspection PhpUnhandledExceptionInspection */
		return new Member( $this->data, $this->id, $this->groups, true );
	}
	
	public function test__construct() {
		/** @noinspection PhpUnhandledExceptionInspection */
		$member = new Member( $this->data, $this->id, $this->groups, true );
		$this->assertEquals( $this->someValue, $member->{$this->someKey}->getValue() );
		$this->assertEquals( null, $member->{$this->someOtherField}->getValue() );
	}
	
	public function test__constructemberUnknownFieldException() {
		$data[ $this->noneExistingField ] = 'asdf';
		
		$this->expectException( MemberUnknownFieldException::class );
		/** @noinspection PhpUnhandledExceptionInspection */
		new Member( $data );
	}
	
	public function test__constructMultiSelectOverwriteException() {
		$this->expectException( MultiSelectOverwriteException::class );
		/** @noinspection PhpUnhandledExceptionInspection */
		new Member( $this->data );
	}
	
	public function test__get() {
		$member = $this->getMember();
		$this->assertEquals( $this->someValue, $member->{$this->someKey}->getValue() );
		$this->assertEquals($this->id, $member->id);
		// todo: test groups and rootGroup
	}
	
	public function test__getMemberUnknownFieldException() {
		$member = $this->getMember();
		$this->expectException( MemberUnknownFieldException::class );
		/** @noinspection PhpUnhandledExceptionInspection */
		$member->{$this->noneExistingField}->getValue();
	}
	
	public function testGetField() {
		$member = $this->getMember();
		$this->assertEquals( $this->someValue, $member->getField( $this->someKey )->getValue() );
		$this->assertEquals( $this->someValue, $member->getField( $this->someWeblingKey )->getValue() );
	}
	
	public function getFirstLevelGroupIds() {
		// todo implement this test
	}
}
