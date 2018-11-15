<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 15.11.18
 * Time: 14:38
 */

namespace App\Repository\Member;


use App\Exceptions\MemberNotFoundException;
use App\Exceptions\WeblingAPIException;
use Tests\TestCase;

class MemberRepositoryTest extends TestCase {
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
	
	public function testGetWeblingAPIException() {
		/** @noinspection PhpUnhandledExceptionInspection */
		$repository = new MemberRepository( 'invalid' . config( 'app.webling_api_key' ) );
		
		$this->expectException( WeblingAPIException::class );
		/** @noinspection PhpUnhandledExceptionInspection */
		$repository->get( 1 );
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
		$member2 = $this->repository->save( $member );
		
		$this->assertNotEmpty( $member->id );
		$this->assertEquals( $member->id, $member2->id );
		
		/** @noinspection PhpUnhandledExceptionInspection */
		$member3 = $this->repository->get( $member->id );
		$this->assertEquals( $member->email1->getValue(), $member3->email1->getValue() );
		
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->repository->delete( $member );
	}
	
	public function testFindExisting() {
		// todo: implement this
	}
	
	public function testGetUpdated() {
		// todo: implement this
	}
	
	public function testFind() {
		$this->addMember();
		
		$query = '`email1` = "' . $this->member->email1->getValue() . '"';
		$found = $this->repository->find($query);
		
		$this->assertEquals(1, count($found));
		$this->assertEquals($this->member->id, $found[0]->id);
		
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
