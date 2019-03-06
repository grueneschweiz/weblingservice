<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace App\Http\Controllers\RestApi\RestApiMember;

use App\Exceptions\WeblingAPIException;
use App\Repository\Group\GroupRepository;
use App\Repository\Member\Member;
use App\Repository\Member\MemberRepository;
use Tests\TestCase;
use App\Http\Controllers\RestApi\RestApiMember;
use Illuminate\Http\Request as Request;
use App\Exceptions\MemberNotFoundException;
use Tests\Unit\Http\Controllers\RestApi\AuthHelper;
use Webling\API\ClientException;

class RestApiMemberTest extends TestCase {
	const EMAIL_FIELD = 'email1';

	/**
	 * @var AuthHelper
	 */
	private $auth;

	public function setUp() {
		parent::setUp();

		$this->auth = new AuthHelper($this);
	}

	public function tearDown() {
		$this->auth->deleteToken();

		parent::tearDown();
	}

	private function getRestApiMember() {
		return new RestApiMember();
	}

	public function testGetMember_MemberNotFoundException() {
		$api     = $this->getRestApiMember();
		$request = new Request();

		$this->expectException( MemberNotFoundException::class );
		$api->getMember( $request, '11' );
	}

	public function testGetMember_WeblingClientException() {
		$api     = $this->getRestApiMember();
		$request = new Request();
		$request->headers->set( 'db-key', 'NotCorrect' );

		$this->expectException( ClientException::class );
		$api->getMember( $request, '1' );
	}

	public function testGetMember_noWeblingClientException() {
		$api     = $this->getRestApiMember();
		$request = new Request();
		$request->headers->set( 'db-key', str_repeat( 'a', 32 ) );

		//it still does not find the member
		$this->expectException( WeblingAPIException::class );
		$api->getMember( $request, 1 );
	}

	public function testGetChanged_all() {
		$api     = $this->getRestApiMember();
		$request = new Request();

		$resp    = $api->getChanged( $request, - 1 );
		$members = json_decode( $resp );
		$this->assertNotEmpty( $members );
		$this->assertTrue( property_exists( reset( $members ), self::EMAIL_FIELD ) );
	}

	public function testGetChanged_changed() {
		$response = $this->json( 'GET', '/api/v1/revision', [], $this->auth->getAuthHeader() );
		$response->assertStatus( 200 );
		$lastRevisionId = json_decode( $response->getContent() );

		$member = $this->addMember();

		$api     = $this->getRestApiMember();
		$request = new Request();

		$resp    = $api->getChanged( $request, $lastRevisionId );
		$members = json_decode( $resp );

		// call this before asserting anythins so it gets also
		// deleted if assertions fail.
		$this->deleteMember( $member );

		$this->assertCount( 1, get_object_vars( $members ) );
		$this->assertEquals( $member->email1->getValue(), reset( $members )->email1 );
	}

	private function addMember() {
		$member = new Member();
		$member->firstName->setValue( 'Unit' );
		$member->lastName->setValue( 'Test' );
		$member->email1->setValue( 'unittest+' . str_random() . '@unittest.ut' );

		$groupRepository = new GroupRepository( config( 'app.webling_api_key' ) );
		$rootGroup       = $groupRepository->get( 100 );
		$member->addGroups( $rootGroup );

		$memberRepository = new MemberRepository( config( 'app.webling_api_key' ) );
		$member           = $memberRepository->save( $member );

		return $member;
	}

	private function deleteMember( $member ) {
		$memberRepository = new MemberRepository( config( 'app.webling_api_key' ) );
		$memberRepository->delete( $member );
	}

}
