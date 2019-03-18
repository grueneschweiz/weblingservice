<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace App\Http\Controllers\RestApi\RestApiMember;

use App\Repository\Group\GroupRepository;
use App\Repository\Member\Member;
use App\Repository\Member\MemberRepository;
use Tests\TestCase;
use Tests\Unit\Http\Controllers\RestApi\AuthHelper;

class RestApiMemberTest extends TestCase {
	const EMAIL_FIELD = 'email1';

	/**
	 * @var AuthHelper
	 */
	private $auth;

	public function setUp() {
		parent::setUp();

		$this->auth = new AuthHelper( $this );
	}

	public function tearDown() {
		$this->auth->deleteToken();

		parent::tearDown();
	}

	public function testGetMember_WrongApiKeyFormat() {
		$headers = $this->auth->getAuthHeader();
		$headers['db-key'] = 'WrongFormat';

		$response = $this->json( 'GET', '/api/v1/member/1', [], $headers );
		$response->assertStatus(400);
		$this->assertRegExp('/the apikey must be 32 chars/', $response->getContent());
	}

	public function testGetMember_InvalidApiKey() {
		$headers = $this->auth->getAuthHeader();
		$headers['db-key'] = str_repeat( 'a', 32 );

		$response = $this->json( 'GET', '/api/v1/member/1', [], $headers );

		$response->assertStatus(500);
		$this->assertRegExp('/Get request to Webling failed with status code 401/', $response->getContent());
	}

	public function testGetMember_401() {
		$response = $this->json( 'GET', '/api/v1/member/1' );

		$response->assertStatus( 401 );
	}

	public function testGetMember_404() {
		$response = $this->json( 'GET', '/api/v1/member/1', [], $this->auth->getAuthHeader() );

		$response->assertStatus( 404 );
	}

	public function testGetMember_200() {
		$member = $this->addMember();

		$response = $this->json( 'GET', '/api/v1/member/' . $member->id, [], $this->auth->getAuthHeader() );

		// call this before asserting anything so it gets also
		// deleted if assertions fail.
		$this->deleteMember( $member );

		$m = json_decode( $response->getContent() );

		$response->assertStatus( 200 );
		$this->assertEquals( $member->email1->getValue(), $m->email1 );
		$this->assertObjectNotHasAttribute( 'iban', $m );
	}

	public function testGetAdminMember_200() {
		$member = $this->addMember();

		$response = $this->json( 'GET', '/api/v1/admin/member/' . $member->id, [], $this->auth->getAuthHeader() );

		// call this before asserting anything so it gets also
		// deleted if assertions fail.
		$this->deleteMember( $member );

		$m = json_decode( $response->getContent() );

		$response->assertStatus( 200 );
		$this->assertEquals( $member->email1->getValue(), $m->email1 );
		$this->assertEquals( $member->iban->getValue(), $m->iban );
	}

	public function testGetMainMember_200() {
		$member1 = $this->addMember();
		$member2 = $this->getMember();
		$member2->email1->setValue( $member1->email1->getValue() );
		$member2->memberStatusCountry->setValue( 'member' );
		$member2 = $this->saveMember( $member2 );

		$response = $this->json( 'GET', '/api/v1/member/' . $member1->id . '/main/100', [], $this->auth->getAuthHeader() );

		// call this before asserting anything so it gets also
		// deleted if assertions fail.
		$this->deleteMember( $member1 );
		$this->deleteMember( $member2 );

		$m = json_decode( $response->getContent() );

		$response->assertStatus( 200 );
		$this->assertEquals( $member1->email1->getValue(), $m->email1 );
		$this->assertEquals( $member2->email1->getValue(), $m->email1 );
		$this->assertEquals( $member2->id, $m->id );
		$this->assertObjectNotHasAttribute( 'iban', $m );
	}

	public function testGetAdminMainMember_200() {
		$member1 = $this->addMember();
		$member2 = $this->getMember();
		$member2->email1->setValue( $member1->email1->getValue() );
		$member2->memberStatusCountry->setValue( 'member' );
		$member2 = $this->saveMember( $member2 );

		$response = $this->json( 'GET', '/api/v1/admin/member/' . $member1->id . '/main/100', [], $this->auth->getAuthHeader() );

		// call this before asserting anything so it gets also
		// deleted if assertions fail.
		$this->deleteMember( $member1 );
		$this->deleteMember( $member2 );

		$m = json_decode( $response->getContent() );

		$response->assertStatus( 200 );
		$this->assertEquals( $member1->email1->getValue(), $m->email1 );
		$this->assertEquals( $member2->email1->getValue(), $m->email1 );
		$this->assertEquals( $member2->id, $m->id );
		$this->assertEquals( $member1->iban->getValue(), $m->iban );
	}

	public function testGetChanged_all() {
		$response = $this->json( 'GET', '/api/v1/member/changed/-1', [], $this->auth->getAuthHeader() );

		$response->assertStatus( 200 );
		$members = json_decode( $response->getContent() );

		$this->assertNotEmpty( $members );
		$this->assertTrue( property_exists( reset( $members ), self::EMAIL_FIELD ) );
	}

	public function testGetChanged_changed() {
		$response = $this->json( 'GET', '/api/v1/revision', [], $this->auth->getAuthHeader() );
		$response->assertStatus( 200 );
		$lastRevisionId = json_decode( $response->getContent() );

		$member = $this->addMember();

		$response = $this->json( 'GET', '/api/v1/member/changed/' . $lastRevisionId, [], $this->auth->getAuthHeader() );
		$response->assertStatus( 200 );
		$members = json_decode( $response->getContent() );

		// call this before asserting anythins so it gets also
		// deleted if assertions fail.
		$this->deleteMember( $member );

		$this->assertCount( 1, get_object_vars( $members ) );
		$this->assertEquals( $member->email1->getValue(), reset( $members )->email1 );
	}

	private function addMember() {
		$member = $this->getMember();

		return $this->saveMember( $member );
	}

	private function saveMember( Member $member ) {
		$memberRepository = new MemberRepository( config( 'app.webling_api_key' ) );

		return $memberRepository->save( $member );
	}

	private function getMember() {
		$member = new Member();
		$member->firstName->setValue( 'Unit' );
		$member->lastName->setValue( 'Test' );
		$member->email1->setValue( 'unittest+' . str_random() . '@unittest.ut' );
		$member->iban->setValue( '12345678' );

		$groupRepository = new GroupRepository( config( 'app.webling_api_key' ) );
		$rootGroup       = $groupRepository->get( 100 );
		$member->addGroups( $rootGroup );

		return $member;
	}

	private function deleteMember( $member ) {
		$memberRepository = new MemberRepository( config( 'app.webling_api_key' ) );
		$memberRepository->delete( $member );
	}

}
