<?php /** @noinspection PhpUnhandledExceptionInspection */

/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 30.11.18
 * Time: 18:21
 */

namespace App\Repository\Revision;


use App\Exceptions\InvalidRevisionArgumentsException;
use App\Exceptions\RevisionNotFoundException;
use App\Repository\Group\GroupRepository;
use App\Repository\Member\Member;
use App\Repository\Member\MemberRepository;
use Tests\TestCase;

class RevisionRepositoryTest extends TestCase {
	const INVALID_REVISION_ID = 0;
	const EXCEEDING_REVISION_ID = 1000000;
	const REVISION_LAG = 100;
	
	/**
	 * @var RevisionRepository
	 */
	private $repository;
	
	public function setUp() {
		parent::setUp();
		
		$this->repository = new RevisionRepository( config( 'app.webling_api_key' ) );
	}
	
	public function testGet() {
		// add a member so we can be sure we have changes
		$member = new Member();
		$member->firstName->setValue( 'Revision' );
		$member->lastName->setValue( 'Unit Test' );
		
		$groupRepository = new GroupRepository( config( 'app.webling_api_key' ) );
		$member->addGroups( $groupRepository->get( 100 ) );
		
		$memberRepository = new MemberRepository( config( 'app.webling_api_key' ) );
		$member           = $memberRepository->save( $member );
		
		// get revision
		$revisionId = $this->repository->getCurrentRevisionId() - self::REVISION_LAG;
		$revision   = $this->repository->get( $revisionId );
		
		// delete member before assertions so it also be will be deleted if asserts fail
		$memberRepository->delete( $member );
		
		$this->assertEquals( $revisionId, $revision->getQueriedRevisionId() );
		$this->assertGreaterThan( $revisionId, $revision->getCurrentRevisionId() );
		$this->assertNotEmpty( $revision->getMemberIds() );
		$this->assertTrue( is_int( $revision->getMemberIds()[0] ) );
	}
	
	public function testGet__RevisionNotFoundException() {
		$this->expectException( RevisionNotFoundException::class );
		$this->repository->get( self::INVALID_REVISION_ID );
	}
	
	public function testGet__InvalidRevisionArgumentsException() {
		$this->expectException( InvalidRevisionArgumentsException::class );
		$this->repository->get( self::EXCEEDING_REVISION_ID );
	}
	
	public function testGetCurrentRevisionId() {
		$revisionId = $this->repository->getCurrentRevisionId();
		$this->assertTrue( is_int( $revisionId ) );
	}
}
