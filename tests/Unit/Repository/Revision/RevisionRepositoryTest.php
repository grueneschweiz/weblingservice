<?php /** @noinspection PhpUnhandledExceptionInspection */

/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 30.11.18
 * Time: 18:21
 */

namespace App\Repository\Revision;


use App\Exceptions\InvalidRevisionIdException;
use Tests\TestCase;

class RevisionRepositoryTest extends TestCase {
	const INVALID_REVISION_ID = 0;
	const VALID_REVISION_ID = 2000;
	
	/**
	 * @var RevisionRepository
	 */
	private $repository;
	
	public function setUp() {
		parent::setUp();
		
		$this->repository = new RevisionRepository( config( 'app.webling_api_key' ) );
	}
	
	public function testGet() {
		$revision = $this->repository->get( self::VALID_REVISION_ID );
		
		$this->assertEquals( self::VALID_REVISION_ID, $revision->getQueriedRevisionId() );
		$this->assertGreaterThan( self::VALID_REVISION_ID, $revision->getCurrentRevisionId() );
		$this->assertNotEmpty( $revision->getMemberIds() );
		$this->assertTrue( is_int( $revision->getMemberIds()[0] ) );
	}
	
	public function testGet__InvalidRevisionIdException() {
		$this->expectException( InvalidRevisionIdException::class );
		$this->repository->get( self::INVALID_REVISION_ID );
	}
	
	public function testGetCurrentRevisionId() {
		$revisionId = $this->repository->getCurrentRevisionId();
		$this->assertTrue( is_int( $revisionId ) );
		$this->assertGreaterThan( self::VALID_REVISION_ID, $revisionId );
	}
}
