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
use App\Repository\Group\Group;
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

	public function test__clone() {
		$member1 = $this->getMember();
		$member2 = clone $member1;
		$this->assertFalse( $member1 === $member2 );
		$member1->{$this->someKey}->setValue( 'asdf' ); // do change the value (because of phps copy of write paradigm?)
		$this->assertFalse( $member1->{$this->someKey}->getValue() === $member2->{$this->someKey}->getValue() );
		$this->assertFalse( $member1->{$this->someKey} === $member2->{$this->someKey} );
		$this->assertFalse( $member1->{$this->someKey}->getValue() === $member2->{$this->someKey}->getValue() );
		$this->assertFalse( $member1->groups === $member2->groups );
		$this->assertEquals( $member1->groups, $member2->groups );
		$this->assertTrue( $member1->id === $member2->id );
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

		$member->removeGroups( $member->groups );
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->assertEmpty( $member->getFirstLevelGroupIds( $this->rootGroup ) );
	}

	public function test__addGroups() {
		$member = $this->getMember();

		/** @noinspection PhpUnhandledExceptionInspection */
		$groupRepository = new GroupRepository( config( 'app.webling_api_key' ) );
		/** @noinspection PhpUnhandledExceptionInspection */
		$group = $groupRepository->get( 202 );

		// single add
		$member->addGroups( $group );
		$this->assertContains( $group, $member->groups );

		// no duplicates
		$count = count( $member->groups );
		$member->addGroups( $group );
		$this->assertEquals( $count, count( $member->groups ) );

		// multiple add
		$member = $this->getMember();
		/** @noinspection PhpUnhandledExceptionInspection */
		$groups = [
			202 => $groupRepository->get( 202 ),
			203 => $groupRepository->get( 203 ),
		];
		$member->addGroups( $groups );
		$this->assertContains( $groups[202], $member->groups );
		$this->assertContains( $groups[203], $member->groups );
	}

	public function test__removeGroups() {
		$member = $this->getMember();

		/** @noinspection PhpUnhandledExceptionInspection */
		$groupRepository = new GroupRepository( config( 'app.webling_api_key' ) );
		/** @noinspection PhpUnhandledExceptionInspection */
		$group = $groupRepository->get( 203 );

		// single remove
		$member->addGroups( $group );
		$this->assertContains( $group, $member->groups );
		$member->removeGroups( $group );
		$this->assertNotContains( $group, $member->groups );

		// multiple remove
		$this->assertGreaterThan( 1, count( $member->groups ) );
		$member->removeGroups( $member->groups );
		$this->assertEmpty( $member->groups );
	}

	public function test__getRootPaths() {
		$member = $this->getMember();

		/** @noinspection PhpUnhandledExceptionInspection */
		$groupRepository = new GroupRepository( config( 'app.webling_api_key' ) );

		$rootPaths = [];
		/** @var Group $group */
		foreach ( $this->groups as $group ) {
			/** @noinspection PhpUnhandledExceptionInspection */
			$rootPaths[ $group->getId() ] = $group->getRootPath( $groupRepository );
		}

		/** @noinspection PhpUnhandledExceptionInspection */
		$this->assertEquals( $rootPaths, $member->getRootPaths() );
	}

	public function test__isDescendantOf() {
		$member = $this->getMember();

		/** @noinspection PhpUnhandledExceptionInspection */
		$groupRepository = new GroupRepository( config( 'app.webling_api_key' ) );
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->assertTrue( $member->isDescendantOf( $groupRepository->get( 100 ) ) );
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->assertTrue( $member->isDescendantOf( $groupRepository->get( 202 ) ) );
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->assertTrue( $member->isDescendantOf( $groupRepository->get( 207 ) ) );

		/** @noinspection PhpUnhandledExceptionInspection */
		$this->assertFalse( $member->isDescendantOf( $groupRepository->get( 203 ) ) );
	}

	public function test__getGroupIds() {
		$member = $this->getMember();
		$this->assertEquals( array_keys( $this->groups ), $member->getGroupIds() );
	}

	public function test__setGroups() {
		$member = $this->getMember();
		$this->assertGreaterThan( 1, count( $member->groups ) );

		$group = reset( $this->groups );
		$member->setGroups( $group );

		$this->assertEquals( [ $group ], array_values( $member->groups ) );
	}

	public function test__setGroups_noPreset() {
		$member = new Member( $this->data, $this->id, null, true );
		$this->assertEmpty( $member->groups );

		$group = reset( $this->groups );
		$member->setGroups( $group );

		$this->assertEquals( [ $group ], array_values( $member->groups ) );
	}
}
