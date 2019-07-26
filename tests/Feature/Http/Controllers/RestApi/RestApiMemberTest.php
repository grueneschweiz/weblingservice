<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace App\Http\Controllers\RestApi\RestApiMember;

use App\Repository\Group\GroupRepository;
use App\Repository\Member\Member;
use App\Repository\Member\MemberRepository;
use Tests\Feature\Http\Controllers\RestApi\AuthHelper;
use Tests\TestCase;

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
		$headers           = $this->auth->getAuthHeader();
		$headers['db-key'] = 'WrongFormat';

		$response = $this->json( 'GET', '/api/v1/member/1', [], $headers );
		$response->assertStatus( 400 );
		$this->assertRegExp( '/the apikey must be 32 chars/', $response->getContent() );
	}

	public function testGetMember_InvalidApiKey() {
		$headers           = $this->auth->getAuthHeader();
		$headers['db-key'] = str_repeat( 'a', 32 );

		$response = $this->json( 'GET', '/api/v1/member/1', [], $headers );

		$response->assertStatus( 500 );
		$this->assertRegExp( '/Get request to Webling failed with status code 401/', $response->getContent() );
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
		$this->assertEquals( $member->getGroupIds(), $m->groups );
		$this->assertEquals( $member->id, $m->id );
		$this->assertObjectNotHasAttribute( 'iban', $m );
	}

	public function testGetMember_200_subgroup() {
		$member = $this->getMember();

		$groupRepository = new GroupRepository( config( 'app.webling_api_key' ) );
		$rootGroup       = $groupRepository->get( 1084 );
		$member->addGroups( $rootGroup );

		$member = $this->saveMember( $member );

		$response = $this->json( 'GET', '/api/v1/member/' . $member->id, [], $this->auth->getAuthHeader( [ 1081 ] ) );

		// call this before asserting anything so it gets also
		// deleted if assertions fail.
		$this->deleteMember( $member );

		$response->assertStatus( 200 );
	}

	public function testGetMember_200_multigroup() {
		$member = $this->getMember();

		$groupRepository = new GroupRepository( config( 'app.webling_api_key' ) );
		$rootGroup       = $groupRepository->get( 1084 );
		$member->addGroups( $rootGroup );

		$member = $this->saveMember( $member );

		$response = $this->json( 'GET', '/api/v1/member/' . $member->id, [], $this->auth->getAuthHeader( [
			1086,
			1081
		] ) );

		// call this before asserting anything so it gets also
		// deleted if assertions fail.
		$this->deleteMember( $member );

		$response->assertStatus( 200 );
	}

	public function testGetMember_403() {
		$member = $this->addMember();

		$response = $this->json( 'GET', '/api/v1/member/' . $member->id, [], $this->auth->getAuthHeader( [ 1081 ] ) );

		// call this before asserting anything so it gets also
		// deleted if assertions fail.
		$this->deleteMember( $member );

		$response->assertStatus( 403 );
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

	public function testGetMainMember_noGroupIds_200() {
		$member1 = $this->addMember();
		$member2 = $this->getMember();
		$member2->email1->setValue( $member1->email1->getValue() );
		$member2->memberStatusCountry->setValue( 'member' );
		$member2 = $this->saveMember( $member2 );

		$response = $this->json( 'GET', '/api/v1/member/' . $member1->id . '/main', [], $this->auth->getAuthHeader() );

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

	public function testGetMainMember_403() {
		$member1 = $this->addMember();

		$response = $this->json( 'GET', '/api/v1/member/' . $member1->id . '/main/100', [], $this->auth->getAuthHeader( [ 1081 ] ) );

		// call this before asserting anything so it gets also
		// deleted if assertions fail.
		$this->deleteMember( $member1 );

		$response->assertStatus( 403 );
	}

	public function testGetMainMember_200_subgroup() {
		$member1         = $this->getMember();
		$groupRepository = new GroupRepository( config( 'app.webling_api_key' ) );
		$rootGroup       = $groupRepository->get( 1084 );
		$member1->addGroups( $rootGroup );
		$member1 = $this->saveMember( $member1 );

		$member2 = $this->getMember();
		$member2->email1->setValue( $member1->email1->getValue() );
		$member2->memberStatusCountry->setValue( 'member' );
		$member2 = $this->saveMember( $member2 );

		$response = $this->json( 'GET', '/api/v1/member/' . $member1->id . '/main/1081', [], $this->auth->getAuthHeader( [ 1081 ] ) );

		// call this before asserting anything so it gets also
		// deleted if assertions fail.
		$this->deleteMember( $member1 );
		$this->deleteMember( $member2 );

		$m = json_decode( $response->getContent() );

		$response->assertStatus( 200 );
		$this->assertEquals( $member1->id, $m->id );
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

	public function testGetChanged_all_limited() {
		$response = $this->json( 'GET', '/api/v1/member/changed/-1/2/0', [], $this->auth->getAuthHeader() );

		$response->assertStatus( 200 );
		$members = json_decode( $response->getContent(), true );

		$this->assertEquals( 2, count( $members ) );

		$response = $this->json( 'GET', '/api/v1/member/changed/-1/2/2', [], $this->auth->getAuthHeader() );
		$members2 = json_decode( $response->getContent(), true );

		$this->assertNotEquals( $members, $members2 );
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

		$m = reset( $members );

		$this->assertEquals( $member->email1->getValue(), $m->email1 );
		$this->assertObjectNotHasAttribute( 'iban', $m );
	}

	public function testGetAdminChanged_changed() {
		$response = $this->json( 'GET', '/api/v1/revision', [], $this->auth->getAuthHeader() );
		$response->assertStatus( 200 );
		$lastRevisionId = json_decode( $response->getContent() );

		$member = $this->addMember();

		$response = $this->json( 'GET', '/api/v1/admin/member/changed/' . $lastRevisionId, [], $this->auth->getAuthHeader() );
		$response->assertStatus( 200 );
		$members = json_decode( $response->getContent() );

		// call this before asserting anythins so it gets also
		// deleted if assertions fail.
		$this->deleteMember( $member );

		$this->assertCount( 1, get_object_vars( $members ) );

		$m = reset( $members );

		$this->assertEquals( $member->email1->getValue(), $m->email1 );
		$this->assertEquals( $member->iban->getValue(), $m->iban );
	}

	public function testPutMember_replace_201() {
		$email = 'unittest_replace+' . str_random() . '@unittest.ut';

		$member = $this->addMember();

		$m = [
			'email1' => [
				'value' => $email,
				'mode'  => 'replace'
			]
		];

		$put = $this->json(
			'PUT',
			'/api/v1/member/' . $member->id,
			$m,
			$this->auth->getAuthHeader()
		);

		$getUpdated = $this->json( 'GET', '/api/v1/member/' . $member->id, [], $this->auth->getAuthHeader() );
		$m2         = json_decode( $getUpdated->getContent() );

		// call this before asserting anything so it gets also
		// deleted if assertions fail.
		$this->deleteMember( $member );

		$this->assertEquals( 201, $put->getStatusCode() );
		$this->assertEquals( $email, $m2->email1 );
		$this->assertEquals( $member->id, $put->getContent() );
	}

	public function testPutMember_append_201() {
		$initial  = 'climate';
		$appended = 'energy';

		$member = $this->getMember();
		$member->interests->append( $initial );
		$member = $this->saveMember( $member );

		$m = [
			'interests' => [
				'value' => $appended,
				'mode'  => 'append'
			]
		];

		$put = $this->json(
			'PUT',
			'/api/v1/member/' . $member->id,
			$m,
			$this->auth->getAuthHeader()
		);

		$getUpdated = $this->json( 'GET', '/api/v1/admin/member/' . $member->id, [], $this->auth->getAuthHeader() );
		$m2         = json_decode( $getUpdated->getContent() );

		// call this before asserting anything so it gets also
		// deleted if assertions fail.
		$this->deleteMember( $member );

		$this->assertEquals( 201, $put->getStatusCode() );
		$this->assertTrue( in_array( $initial, $m2->interests ) );
		$this->assertTrue( in_array( $appended, $m2->interests ) );
	}

	public function testPutMember_addIfNew_notNew_201() {
		$initial = 'already in the database';
		$add     = 'this should not be appended';

		$member = $this->getMember();
		$member->entryChannel->setValue( $initial );
		$member = $this->saveMember( $member );

		$m = [
			'entryChannel' => [
				'value' => $add,
				'mode'  => 'addIfNew'
			]
		];

		$put = $this->json(
			'PUT',
			'/api/v1/member/' . $member->id,
			$m,
			$this->auth->getAuthHeader()
		);

		$getUpdated = $this->json( 'GET', '/api/v1/admin/member/' . $member->id, [], $this->auth->getAuthHeader() );
		$m2         = json_decode( $getUpdated->getContent() );

		// call this before asserting anything so it gets also
		// deleted if assertions fail.
		$this->deleteMember( $member );

		$this->assertEquals( 201, $put->getStatusCode() );
		$this->assertTrue( 0 === strpos( $initial, $m2->entryChannel ) );
		$this->assertTrue( false === strpos( $add, $m2->entryChannel ) );
	}

	public function testPutMember_addIfNew_new_201() {
		$m = [
			'email1' => [
				'value' => 'unittest_'.str_random().'@mail.com',
				'mode' => 'replace',
			],
			'entryChannel' => [
				'value' => 'I am new here',
				'mode'  => 'addIfNew'
			],
			'groups' => [
				'value' => 100,
				'mode' => 'append',
			]
		];

		$put = $this->json(
			'POST',
			'/api/v1/member',
			$m,
			$this->auth->getAuthHeader()
		);

		$this->assertEquals( 201, $put->getStatusCode() );
		$id = $put->getContent();

		$getNew = $this->json( 'GET', '/api/v1/admin/member/' . $id, [], $this->auth->getAuthHeader() );
		$m2     = json_decode( $getNew->getContent() );

		// call this before asserting anything so it gets also
		// deleted if assertions fail.
		$this->deleteMember( $id );

		$this->assertTrue( 0 === strpos( $m['entryChannel']['value'], $m2->entryChannel ) );
	}

	public function testPutMember_replaceEmpty_notEmpty_201() {
		$initial = 'already in the database';
		$replace     = 'this should not be replaced';

		$member = $this->getMember();
		$member->entryChannel->setValue( $initial );
		$member = $this->saveMember( $member );

		$m = [
			'entryChannel' => [
				'value' => $replace,
				'mode'  => 'replaceEmpty'
			]
		];

		$put = $this->json(
			'PUT',
			'/api/v1/member/' . $member->id,
			$m,
			$this->auth->getAuthHeader()
		);

		$getUpdated = $this->json( 'GET', '/api/v1/admin/member/' . $member->id, [], $this->auth->getAuthHeader() );
		$m2         = json_decode( $getUpdated->getContent() );

		// call this before asserting anything so it gets also
		// deleted if assertions fail.
		$this->deleteMember( $member );

		$this->assertEquals( 201, $put->getStatusCode() );
		$this->assertTrue( 0 === strpos( $initial, $m2->entryChannel ) );
		$this->assertTrue( false === strpos( $replace, $m2->entryChannel ) );
	}


	public function testPutMember_replaceEmpty_empty_201() {
		$initial = '';
		$replace     = 'this should not be replaced';

		$member = $this->getMember();
		$member->entryChannel->setValue( $initial );
		$member = $this->saveMember( $member );

		$m = [
			'entryChannel' => [
				'value' => $replace,
				'mode'  => 'replaceEmpty'
			]
		];

		$put = $this->json(
			'PUT',
			'/api/v1/member/' . $member->id,
			$m,
			$this->auth->getAuthHeader()
		);

		$getUpdated = $this->json( 'GET', '/api/v1/admin/member/' . $member->id, [], $this->auth->getAuthHeader() );
		$m2         = json_decode( $getUpdated->getContent() );

		// call this before asserting anything so it gets also
		// deleted if assertions fail.
		$this->deleteMember( $member );

		$this->assertEquals( 201, $put->getStatusCode() );
		$this->assertEquals( $replace, $m2->entryChannel );
	}


	public function testPutMember_append_500() {
		$email = 'unittest_replace+' . str_random() . '@unittest.ut';

		$member = $this->addMember();

		$m = [
			'email1' => [
				'value' => $email,
				'mode'  => 'append'
			]
		];

		$put = $this->json(
			'PUT',
			'/api/v1/member/' . $member->id,
			$m,
			$this->auth->getAuthHeader()
		);

		// call this before asserting anything so it gets also
		// deleted if assertions fail.
		$this->deleteMember( $member );

		$this->assertEquals( 500, $put->getStatusCode() );
	}

	public function testPutMember_skipId_201() {
		$member = $this->addMember();

		$m = [
			'id' => [
				'value' => 1,
				'mode'  => 'replace'
			]
		];

		$put = $this->json(
			'PUT',
			'/api/v1/member/' . $member->id,
			$m,
			$this->auth->getAuthHeader()
		);

		$getUpdated = $this->json( 'GET', '/api/v1/member/' . $member->id, [], $this->auth->getAuthHeader() );
		$m2         = json_decode( $getUpdated->getContent() );

		// call this before asserting anything so it gets also
		// deleted if assertions fail.
		$this->deleteMember( $member );

		$this->assertEquals( 201, $put->getStatusCode() );
		$this->assertEquals( $member->id, $m2->id );
	}

	public function testPutMember_replaceGroup_201() {
		$group  = 1081;
		$member = $this->addMember();

		$m = [
			'groups' => [
				'value' => $group,
				'mode'  => 'replace'
			]
		];

		$put = $this->json(
			'PUT',
			'/api/v1/member/' . $member->id,
			$m,
			$this->auth->getAuthHeader()
		);

		$getUpdated = $this->json( 'GET', '/api/v1/member/' . $member->id, [], $this->auth->getAuthHeader() );
		$m2         = json_decode( $getUpdated->getContent() );

		// call this before asserting anything so it gets also
		// deleted if assertions fail.
		$this->deleteMember( $member );

		$this->assertEquals( 201, $put->getStatusCode() );
		$this->assertEquals( [ $group ], $m2->groups );
	}

	public function testPutMember_appendGroup_201() {
		$group  = 1081;
		$member = $this->addMember();

		$m = [
			'groups' => [
				'value' => $group,
				'mode'  => 'append'
			]
		];

		$put = $this->json(
			'PUT',
			'/api/v1/member/' . $member->id,
			$m,
			$this->auth->getAuthHeader()
		);

		$getUpdated = $this->json( 'GET', '/api/v1/member/' . $member->id, [], $this->auth->getAuthHeader() );
		$m2         = json_decode( $getUpdated->getContent() );

		// call this before asserting anything so it gets also
		// deleted if assertions fail.
		$this->deleteMember( $member );

		$this->assertEquals( 201, $put->getStatusCode() );
		$this->assertTrue( in_array( $group, $m2->groups ) );
		$this->assertTrue( in_array( 100, $m2->groups ) );
	}

	public function testPostMember_insert_201() {
		$m = [
			'firstName' => [
				'value' => 'Unit Post Create',
				'mode'  => 'replace'
			],
			'lastName'  => [
				'value' => 'Test',
				'mode'  => 'append'
			],
			'email1'    => [
				'value' => 'unittest+' . str_random() . '@unittest.ut',
				'mode'  => 'replace'
			],
			'groups'    => [
				'value' => [ 100 ],
				'mode'  => 'append',
			]
		];

		$post = $this->json(
			'POST',
			'/api/v1/member',
			$m,
			$this->auth->getAuthHeader()
		);

		$id = $post->getContent();

		$getUpdated = $this->json( 'GET', "/api/v1/member/$id", [], $this->auth->getAuthHeader() );
		$m2         = json_decode( $getUpdated->getContent() );

		// call this before asserting anything so it gets also
		// deleted if assertions fail.
		$memberRepository = new MemberRepository( config( 'app.webling_api_key' ) );
		$this->deleteMember( $memberRepository->get( (int) $id ) );

		$this->assertEquals( 201, $post->getStatusCode() );
		$this->assertEquals( $m['email1']['value'], $m2->email1 );
		$this->assertEquals( $m['lastName']['value'], $m2->lastName );
	}

	public function testPostMember_upsert_id_201() {
		$member = $this->addMember();
		$this->assertEmpty( $member->email2->getValue() );

		$m = [
			'email2' => [
				'value' => $member->email1->getValue(),
				'mode'  => 'replace'
			],
			'id'     => [
				'value' => $member->id
			]
		];

		$post = $this->json(
			'POST',
			'/api/v1/member',
			$m,
			$this->auth->getAuthHeader()
		);

		$id = $post->getContent();

		$getUpdated = $this->json( 'GET', "/api/v1/admin/member/$id", [], $this->auth->getAuthHeader() );
		$m2         = json_decode( $getUpdated->getContent() );

		// call this before asserting anything so it gets also
		// deleted if assertions fail.
		$memberRepository = new MemberRepository( config( 'app.webling_api_key' ) );
		$this->deleteMember( $memberRepository->get( (int) $id ) );

		$this->assertEquals( 201, $post->getStatusCode() );
		$this->assertEquals( $id, $member->id );
		$this->assertEquals( $m['email2']['value'], $m2->email2 );
	}

	public function testPostMember_upsert_email_single_201() {
		$member = $this->addMember();
		$this->assertEmpty( $member->email2->getValue() );

		$m = [
			'email1' => [
				'value' => $member->email1->getValue(),
				'mode'  => 'replace'
			],
			'email2' => [
				'value' => $member->email1->getValue(),
				'mode'  => 'replace'
			]
		];

		$post = $this->json(
			'POST',
			'/api/v1/member',
			$m,
			$this->auth->getAuthHeader()
		);

		$id = $post->getContent();

		$getUpdated = $this->json( 'GET', "/api/v1/admin/member/$id", [], $this->auth->getAuthHeader() );
		$m2         = json_decode( $getUpdated->getContent() );

		// call this before asserting anything so it gets also
		// deleted if assertions fail.
		$memberRepository = new MemberRepository( config( 'app.webling_api_key' ) );
		$this->deleteMember( $memberRepository->get( (int) $id ) );

		$this->assertEquals( 201, $post->getStatusCode() );
		$this->assertEquals( $member->id, $id );
		$this->assertEquals( $m['email2']['value'], $m2->email2 );
	}

	public function testPostMember_upsert_email_multiple_201() {
		$member  = $this->getMember();
		$member1 = $this->saveMember( $member );
		$member2 = $this->saveMember( $member );

		$this->assertNotEquals( $member1->id, $member2->id );

		$m = [
			'email1' => [
				'value' => $member1->email1->getValue(),
				'mode'  => 'replace'
			],
			'email2' => [
				'value' => $member1->email1->getValue(),
				'mode'  => 'replace'
			],
			'groups' => [
				'value' => [ 100 ],
				'mode'  => 'append',
			]
		];

		$post = $this->json(
			'POST',
			'/api/v1/member',
			$m,
			$this->auth->getAuthHeader()
		);

		$id = $post->getContent();

		$getUpdated = $this->json( 'GET', "/api/v1/admin/member/$id", [], $this->auth->getAuthHeader() );
		$m2         = json_decode( $getUpdated->getContent() );

		// call this before asserting anything so it gets also
		// deleted if assertions fail.
		$memberRepository = new MemberRepository( config( 'app.webling_api_key' ) );
		$this->deleteMember( $memberRepository->get( (int) $id ) );
		$this->deleteMember( $member1 );
		$this->deleteMember( $member2 );

		$this->assertEquals( 201, $post->getStatusCode() );
		$this->assertNotEquals( $member1->id, $id );
		$this->assertNotEquals( $member2->id, $id );
		$this->assertEquals( $m['email2']['value'], $m2->email2 );
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
