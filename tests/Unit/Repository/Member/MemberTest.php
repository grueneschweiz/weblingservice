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
use App\Repository\Group\GroupRepository;
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
	private $groups = [];
	private $firstLevelRootGroups = [];
	private $rootGroup = 100;
	
	public function setUp() {
		parent::setUp();
		
		$this->data = [
			$this->someKey          => $this->someValue,
			$this->someWeblingKey   => $this->someValue,
			$this->multiSelectField => $this->multiSelectValue,
		];
		
		/** @noinspection PhpUnhandledExceptionInspection */
		$groupRepository = new GroupRepository( config( 'app.webling_api_key' ) );
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->groups = [
			100 => $groupRepository->get( 100 ),
			207 => $groupRepository->get( 207 ),
			201 => $groupRepository->get( 201 ),
		];
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->firstLevelRootGroups = [
			201 => $groupRepository->get( 201 ),
			202 => $groupRepository->get( 202 ),
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
		$this->assertEquals( $this->groups, $member->groups );
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
		$this->assertEquals( $this->id, $member->id );
		$this->assertEquals( $this->groups, $member->groups );
	}
	
	public function test__getMemberUnknownFieldException() {
		$member = $this->getMember();
		$this->expectException( MemberUnknownFieldException::class );
		/** @noinspection PhpUnhandledExceptionInspection */
		$member->{$this->noneExistingField}->getValue();
	}
	
	public function test__getField() {
		$member = $this->getMember();
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->assertEquals( $this->someValue, $member->getField( $this->someKey )->getValue() );
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->assertEquals( $this->someValue, $member->getField( $this->someWeblingKey )->getValue() );
	}
	
	public function test__getFirstLevelGroupIds() {
		$member   = $this->getMember();
		$expected = array_keys( $this->firstLevelRootGroups );
		/** @noinspection PhpUnhandledExceptionInspection */
		$actual = $member->getFirstLevelGroupIds( $this->rootGroup );
		$this->assertEmpty( array_diff( $expected, $actual ) );
		$this->assertEmpty( array_diff( $actual, $expected ) );
		
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->assertEquals( [ 207 ], $member->getFirstLevelGroupIds( 202 ) );
	}
}
