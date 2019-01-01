<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 15.11.18
 * Time: 14:38
 */

namespace App\Repository\Member;


use App\Exceptions\MemberNotFoundException;
use App\Exceptions\NoGroupException;
use App\Repository\Group\GroupRepository;
use App\Repository\Revision\RevisionRepository;
use Tests\TestCase;

class MemberRepositoryTest extends TestCase {
	const REVISION_ID = 2000;
	
	/**
	 * @var MemberRepository
	 */
	private $repository;
	
	/**
	 * @var Member
	 */
	private $member;
	
	
	public function setUp() {
		parent::setUp();
		
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->repository = new MemberRepository( config( 'app.webling_api_key' ) );
	}
	
	public function testGetMaster() {
		// todo: implement this
		$this->assertTrue( true );
	}
	
	public function testGet() {
		$this->addMember();
		/** @noinspection PhpUnhandledExceptionInspection */
		$member = $this->repository->get( $this->member->id );
		$this->assertEquals( $this->member->id, $member->id );
		$this->removeMember();
	}
	
	private function addMember() {
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->member = $this->repository->save( $this->getNewLocalMember() );
	}
	
	private function getNewLocalMember() {
		/** @noinspection PhpUnhandledExceptionInspection */
		$member = new Member();
		/** @noinspection PhpUnhandledExceptionInspection */
		$member->firstName->setValue( 'Unit' );
		/** @noinspection PhpUnhandledExceptionInspection */
		$member->lastName->setValue( 'Test' );
		/** @noinspection PhpUnhandledExceptionInspection */
		$member->email1->setValue( 'unittest+' . str_random() . '@unittest.ut' );
		
		/** @noinspection PhpUnhandledExceptionInspection */
		$groupRepository = new GroupRepository( config( 'app.webling_api_key' ) );
		/** @noinspection PhpUnhandledExceptionInspection */
		$rootGroup = $groupRepository->get( 100 );
		$member->addGroups( $rootGroup );
		
		return $member;
	}
	
	private function removeMember() {
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->repository->delete( $this->member );
	}
	
	public function testGetMemberNotFoundException() {
		$this->expectException( MemberNotFoundException::class );
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->repository->get( 1 );
	}
	
	public function testSaveUpdate() {
		$this->addMember();
		$member = &$this->member;
		
		/** @noinspection PhpUnhandledExceptionInspection */
		$member->interests->append( 'energy' );
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->repository->save( $member );
		
		/** @noinspection PhpUnhandledExceptionInspection */
		$member2 = $this->repository->get( $member->id );
		$this->assertTrue( $member2->interests->hasValue( 'energy' ) );
		
		$this->removeMember();
	}
	
	public function testSaveCreate() {
		$member = $this->getNewLocalMember();
		/** @noinspection PhpUnhandledExceptionInspection */
		$member = $this->repository->save( $member );
		
		$this->assertNotEmpty( $member->id );
		
		/** @noinspection PhpUnhandledExceptionInspection */
		$member2 = $this->repository->get( $member->id );
		$this->assertEquals( $member->email1->getValue(), $member2->email1->getValue() );
		
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->repository->delete( $member );
	}
	
	public function testSaveNoGroupException() {
		$this->addMember();
		$member = &$this->member;
		
		/** @noinspection PhpUnhandledExceptionInspection */
		$member->removeGroups( $member->groups );
		
		$this->expectException( NoGroupException::class );
		$this->repository->save( $member );
		
		$this->removeMember();
	}
	
	public function testFindExisting() {
		// todo: implement this
		$this->assertTrue( true );
	}
	
	public function testGetUpdated() {
		/** @noinspection PhpUnhandledExceptionInspection */
		$updated = $this->repository->getUpdated( self::REVISION_ID );
		foreach ( $updated as $member ) {
			$this->assertTrue( $member instanceof Member || null === $member );
		}
		
		/** @noinspection PhpUnhandledExceptionInspection */
		$rervisionRepository = new RevisionRepository( config( 'app.webling_api_key' ) );
		$revision            = $rervisionRepository->get( self::REVISION_ID );
		foreach ( $revision->getMemberIds() as $id ) {
			$this->assertTrue( array_key_exists( $id, $updated ) );
		}
	}
	
	public function testFind() {
		$this->addMember();
		
		$query = '`' . $this->member->email1->getWeblingKey() . '` = "' . $this->member->email1->getValue() . '"';
		/** @noinspection PhpUnhandledExceptionInspection */
		$found = $this->repository->find( $query );
		
		$this->assertEquals( 1, count( $found ) );
		$this->assertEquals( $this->member->id, array_values( $found )[0]->id );
		
		$this->removeMember();
	}
	
	public function testFindWithRootGroups() {
		$this->addMember();
		
		$query = '`' . $this->member->email1->getWeblingKey() . '` = "' . $this->member->email1->getValue() . '"';
		/** @noinspection PhpUnhandledExceptionInspection */
		$groupRepository = new GroupRepository( config( 'app.webling_api_key' ) );
		
		/** @noinspection PhpUnhandledExceptionInspection */
		$found = $this->repository->find( $query, [ $groupRepository->get( 100 ), $groupRepository->get( 203 ) ] );
		$this->assertEquals( 1, count( $found ) );
		$this->assertEquals( $this->member->id, array_values( $found )[0]->id );
		
		/** @noinspection PhpUnhandledExceptionInspection */
		$found = $this->repository->find( $query, [ $groupRepository->get( 203 ) ] );
		$this->assertEmpty( $found );
		
		$this->removeMember();
	}
	
	public function testDelete() {
		$this->addMember();
		
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->repository->delete( $this->member );
		
		$this->expectException( MemberNotFoundException::class );
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->repository->get( $this->member->id );
	}
}
