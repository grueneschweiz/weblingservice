<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace App\Http\Controllers\RestApi\RestApiMember;

use App\Exceptions\MemberNotFoundException;
use App\Repository\Debtor\DebtorRepository;
use App\Repository\Group\GroupRepository;
use App\Repository\Member\Member;
use App\Repository\Member\MemberRepository;
use Illuminate\Support\Str;
use Tests\Feature\Http\Controllers\RestApi\AuthHelper;
use Tests\TestCase;

class RestApiMemberTest extends TestCase
{
    const EMAIL_FIELD = 'email1';
    const MERGE_MEMBER_DEBTOR_ID = 63376;
    
    /**
     * @var AuthHelper
     */
    private $auth;
    
    public function setUp(): void
    {
        parent::setUp();
        
        $this->auth = new AuthHelper($this);
    }
    
    public function tearDown(): void
    {
        $this->auth->deleteToken();
        
        parent::tearDown();
    }
    
    public function testGetMember_WrongApiKeyFormat()
    {
        $headers = $this->auth->getAuthHeader();
        $headers['db-key'] = 'WrongFormat';
        
        $response = $this->json('GET', '/api/v1/member/1', [], $headers);
        $response->assertStatus(400);
        self::assertMatchesRegularExpression('/the apikey must be 32 chars/', $response->getContent());
    }
    
    public function testGetMember_InvalidApiKey()
    {
        $headers = $this->auth->getAuthHeader();
        $headers['db-key'] = str_repeat('a', 32);
        
        $response = $this->json('GET', '/api/v1/member/1', [], $headers);
        
        $response->assertStatus(401);
        self::assertMatchesRegularExpression('/Get request to Webling failed:.*Not authenticated/', $response->getContent());
    }
    
    public function testGetMember_401()
    {
        $response = $this->json('GET', '/api/v1/member/1');
        
        $response->assertStatus(401);
    }
    
    public function testGetMember_404()
    {
        $response = $this->json('GET', '/api/v1/member/1', [], $this->auth->getAuthHeader());
        
        $response->assertStatus(404);
    }
    
    public function testGetMember_200()
    {
        $member = $this->addMember();
        
        $response = $this->json('GET', '/api/v1/member/' . $member->id, [], $this->auth->getAuthHeader());
        
        // call this before asserting anything so it gets also
        // deleted if assertions fail.
        $this->deleteMember($member);
        
        $m = json_decode($response->getContent());
        
        $response->assertStatus(200);
        $this->assertEquals($member->email1->getValue(), $m->email1);
        $this->assertEquals($member->getGroupIds(), $m->groups);
        $this->assertEquals($member->id, $m->id);
        $this->assertObjectNotHasAttribute('iban', $m);
    }
    
    private function addMember()
    {
        $member = $this->getMember();
        
        return $this->saveMember($member);
    }
    
    private function getMember()
    {
        $member = new Member();
        $member->firstName->setValue('Unit');
        $member->lastName->setValue('Test');
        $member->email1->setValue('unittest+' . Str::random() . '@unittest.ut');
        $member->iban->setValue('12345678');
        $member->birthday->setValue('2000-01-01');
        
        $groupRepository = new GroupRepository(config('app.webling_api_key'));
        $rootGroup = $groupRepository->get(100);
        $member->addGroups($rootGroup);
        
        return $member;
    }
    
    private function saveMember(Member $member)
    {
        $memberRepository = new MemberRepository(config('app.webling_api_key'));
        
        return $memberRepository->save($member);
    }
    
    private function deleteMember($member)
    {
        $memberRepository = new MemberRepository(config('app.webling_api_key'));
        $memberRepository->delete($member);
    }
    
    public function testGetMember_200_subgroup()
    {
        $member = $this->getMember();
        
        $groupRepository = new GroupRepository(config('app.webling_api_key'));
        $rootGroup = $groupRepository->get(1084);
        $member->addGroups($rootGroup);
        
        $member = $this->saveMember($member);
        
        $response = $this->json('GET', '/api/v1/member/' . $member->id, [], $this->auth->getAuthHeader([1081]));
        
        // call this before asserting anything so it gets also
        // deleted if assertions fail.
        $this->deleteMember($member);
        
        $response->assertStatus(200);
    }
    
    public function testGetMember_200_multigroup()
    {
        $member = $this->getMember();
        
        $groupRepository = new GroupRepository(config('app.webling_api_key'));
        $rootGroup = $groupRepository->get(1084);
        $member->addGroups($rootGroup);
        
        $member = $this->saveMember($member);
        
        $response = $this->json('GET', '/api/v1/member/' . $member->id, [], $this->auth->getAuthHeader([
            1086,
            1081
        ]));
        
        // call this before asserting anything so it gets also
        // deleted if assertions fail.
        $this->deleteMember($member);
        
        $response->assertStatus(200);
    }
    
    public function testGetMember_403()
    {
        $member = $this->addMember();
        
        $response = $this->json('GET', '/api/v1/member/' . $member->id, [], $this->auth->getAuthHeader([1081]));
        
        // call this before asserting anything so it gets also
        // deleted if assertions fail.
        $this->deleteMember($member);
        
        $response->assertStatus(403);
    }
    
    public function testGetAdminMember_200()
    {
        $member = $this->addMember();
        
        $response = $this->json('GET', '/api/v1/admin/member/' . $member->id, [], $this->auth->getAuthHeader());
        
        // call this before asserting anything so it gets also
        // deleted if assertions fail.
        $this->deleteMember($member);
        
        $m = json_decode($response->getContent());
        
        $response->assertStatus(200);
        $this->assertEquals($member->email1->getValue(), $m->email1);
        $this->assertEquals($member->iban->getValue(), $m->iban);
    }
    
    public function testGetMainMember_200()
    {
        $member1 = $this->addMember();
        $member2 = $this->getMember();
        $member2->email1->setValue($member1->email1->getValue());
        $member2->memberStatusCountry->setValue('member');
        $member2 = $this->saveMember($member2);
        
        $response = $this->json('GET', '/api/v1/member/' . $member1->id . '/main/100', [], $this->auth->getAuthHeader());
        
        // call this before asserting anything so it gets also
        // deleted if assertions fail.
        $this->deleteMember($member1);
        $this->deleteMember($member2);
        
        $m = json_decode($response->getContent());
        
        $response->assertStatus(200);
        $this->assertEquals($member1->email1->getValue(), $m->email1);
        $this->assertEquals($member2->email1->getValue(), $m->email1);
        $this->assertEquals($member2->id, $m->id);
        $this->assertObjectNotHasAttribute('iban', $m);
    }
    
    public function testGetMainMember_noGroupIds_200()
    {
        $member1 = $this->addMember();
        $member2 = $this->getMember();
        $member2->email1->setValue($member1->email1->getValue());
        $member2->memberStatusCountry->setValue('member');
        $member2 = $this->saveMember($member2);
        
        $response = $this->json('GET', '/api/v1/member/' . $member1->id . '/main', [], $this->auth->getAuthHeader());
        
        // call this before asserting anything so it gets also
        // deleted if assertions fail.
        $this->deleteMember($member1);
        $this->deleteMember($member2);
        
        $m = json_decode($response->getContent());
        
        $response->assertStatus(200);
        $this->assertEquals($member1->email1->getValue(), $m->email1);
        $this->assertEquals($member2->email1->getValue(), $m->email1);
        $this->assertEquals($member2->id, $m->id);
        $this->assertObjectNotHasAttribute('iban', $m);
    }
    
    public function testGetMainMember_403()
    {
        $member1 = $this->addMember();
        
        $response = $this->json('GET', '/api/v1/member/' . $member1->id . '/main/100', [], $this->auth->getAuthHeader([1081]));
        
        // call this before asserting anything so it gets also
        // deleted if assertions fail.
        $this->deleteMember($member1);
        
        $response->assertStatus(403);
    }
    
    public function testGetMainMember_200_subgroup()
    {
        $member1 = $this->getMember();
        $groupRepository = new GroupRepository(config('app.webling_api_key'));
        $rootGroup = $groupRepository->get(1084);
        $member1->addGroups($rootGroup);
        $member1 = $this->saveMember($member1);
        
        $member2 = $this->getMember();
        $member2->email1->setValue($member1->email1->getValue());
        $member2->memberStatusCountry->setValue('member');
        $member2 = $this->saveMember($member2);
        
        $response = $this->json('GET', '/api/v1/member/' . $member1->id . '/main/1081', [], $this->auth->getAuthHeader([1081]));
        
        // call this before asserting anything so it gets also
        // deleted if assertions fail.
        $this->deleteMember($member1);
        $this->deleteMember($member2);
        
        $m = json_decode($response->getContent());
        
        $response->assertStatus(200);
        $this->assertEquals($member1->id, $m->id);
    }
    
    public function testGetAdminMainMember_200()
    {
        $member1 = $this->addMember();
        $member2 = $this->getMember();
        $member2->email1->setValue($member1->email1->getValue());
        $member2->memberStatusCountry->setValue('member');
        $member2 = $this->saveMember($member2);
        
        $response = $this->json('GET', '/api/v1/admin/member/' . $member1->id . '/main/100', [], $this->auth->getAuthHeader());
        
        // call this before asserting anything so it gets also
        // deleted if assertions fail.
        $this->deleteMember($member1);
        $this->deleteMember($member2);
        
        $m = json_decode($response->getContent());
        
        $response->assertStatus(200);
        $this->assertEquals($member1->email1->getValue(), $m->email1);
        $this->assertEquals($member2->email1->getValue(), $m->email1);
        $this->assertEquals($member2->id, $m->id);
        $this->assertEquals($member1->iban->getValue(), $m->iban);
    }
    
    public function testGetChanged_all()
    {
        $response = $this->json('GET', '/api/v1/member/changed/-1', [], $this->auth->getAuthHeader());
        
        $response->assertStatus(200);
        $members = json_decode($response->getContent());
        
        $this->assertNotEmpty($members);
        $this->assertTrue(property_exists(reset($members), self::EMAIL_FIELD));
    }
    
    public function testGetChanged_all_limited()
    {
        $response = $this->json('GET', '/api/v1/member/changed/-1/2/0', [], $this->auth->getAuthHeader());
        
        $response->assertStatus(200);
        $members = json_decode($response->getContent(), true);
        
        $this->assertEquals(2, count($members));
        
        $response = $this->json('GET', '/api/v1/member/changed/-1/2/2', [], $this->auth->getAuthHeader());
        $members2 = json_decode($response->getContent(), true);
        
        $this->assertNotEquals($members, $members2);
    }
    
    public function testGetChanged_changed()
    {
        $response = $this->json('GET', '/api/v1/revision', [], $this->auth->getAuthHeader());
        $response->assertStatus(200);
        $lastRevisionId = json_decode($response->getContent());
        
        $member = $this->addMember();
        
        $response = $this->json('GET', '/api/v1/member/changed/' . $lastRevisionId, [], $this->auth->getAuthHeader());
        $response->assertStatus(200);
        $members = json_decode($response->getContent());
        
        // call this before asserting anythins so it gets also
        // deleted if assertions fail.
        $this->deleteMember($member);
        
        $this->assertCount(1, get_object_vars($members));
        
        $m = reset($members);
        
        $this->assertEquals($member->email1->getValue(), $m->email1);
        $this->assertObjectNotHasAttribute('iban', $m);
    }
    
    public function testGetAdminChanged_changed()
    {
        $response = $this->json('GET', '/api/v1/revision', [], $this->auth->getAuthHeader());
        $response->assertStatus(200);
        $lastRevisionId = json_decode($response->getContent());
        
        $member = $this->addMember();
        
        $response = $this->json('GET', '/api/v1/admin/member/changed/' . $lastRevisionId, [], $this->auth->getAuthHeader());
        $response->assertStatus(200);
        $members = json_decode($response->getContent());
        
        // call this before asserting anythins so it gets also
        // deleted if assertions fail.
        $this->deleteMember($member);
        
        $this->assertCount(1, get_object_vars($members));
        
        $m = reset($members);
        
        $this->assertEquals($member->email1->getValue(), $m->email1);
        $this->assertEquals($member->iban->getValue(), $m->iban);
    }
    
    public function testPutMember_replace_201()
    {
        $email = 'unittest_replace+' . Str::random() . '@unittest.ut';
        
        $member = $this->addMember();
        
        $m = [
            'email1' => [
                'value' => $email,
                'mode' => 'replace'
            ]
        ];
        
        $put = $this->json(
            'PUT',
            '/api/v1/member/' . $member->id,
            $m,
            $this->auth->getAuthHeader()
        );
        
        $getUpdated = $this->json('GET', '/api/v1/member/' . $member->id, [], $this->auth->getAuthHeader());
        $m2 = json_decode($getUpdated->getContent());
        
        // call this before asserting anything so it gets also
        // deleted if assertions fail.
        $this->deleteMember($member);
        
        $this->assertEquals(201, $put->getStatusCode());
        $this->assertEquals($email, $m2->email1);
        $this->assertEquals($member->id, $put->getContent());
    }
    
    public function testPutMember_replaceMultiselect_201()
    {
        $member = $this->getMember();
        $member->mandateCountry->setValue(['legislativeActive', 'legislativePast']);
        
        $member = $this->saveMember($member);
        
        $m = [
            'mandateCountry' => [
                'value' => ['legislativePast'],
                'mode' => 'replace'
            ]
        ];
        
        $put = $this->json(
            'PUT',
            '/api/v1/member/' . $member->id,
            $m,
            $this->auth->getAuthHeader()
        );
        
        $getUpdated = $this->json('GET', '/api/v1/admin/member/' . $member->id, [], $this->auth->getAuthHeader());
        $m2 = json_decode($getUpdated->getContent());
        
        // call this before asserting anything so it gets also
        // deleted if assertions fail.
        $this->deleteMember($member);
        
        $this->assertEquals(201, $put->getStatusCode());
        $this->assertEquals(['legislativePast'], $m2->mandateCountry);
        $this->assertEquals($member->id, $put->getContent());
    }
    
    public function testPutMember_append_201()
    {
        $initial = 'climate';
        $appended = 'energy';
        
        $member = $this->getMember();
        $member->interests->append($initial);
        $member = $this->saveMember($member);
        
        $m = [
            'interests' => [
                'value' => $appended,
                'mode' => 'append'
            ]
        ];
        
        $put = $this->json(
            'PUT',
            '/api/v1/member/' . $member->id,
            $m,
            $this->auth->getAuthHeader()
        );
        
        $getUpdated = $this->json('GET', '/api/v1/admin/member/' . $member->id, [], $this->auth->getAuthHeader());
        $m2 = json_decode($getUpdated->getContent());
        
        // call this before asserting anything so it gets also
        // deleted if assertions fail.
        $this->deleteMember($member);
        
        $this->assertEquals(201, $put->getStatusCode());
        $this->assertTrue(in_array($initial, $m2->interests));
        $this->assertTrue(in_array($appended, $m2->interests));
    }
    
    public function testPutMember_removeMultiSelect_201(): void
    {
        $remove = 'energy';
        $initial = 'climate';
        
        $member = $this->getMember();
        $member->interests->append([$initial, $remove]);
        $member = $this->saveMember($member);
        
        $m = [
            'interests' => [
                'value' => $remove,
                'mode' => 'remove'
            ]
        ];
        
        $put = $this->json(
            'PUT',
            '/api/v1/member/' . $member->id,
            $m,
            $this->auth->getAuthHeader()
        );
        
        $getUpdated = $this->json('GET', '/api/v1/admin/member/' . $member->id, [], $this->auth->getAuthHeader());
        $m2 = json_decode($getUpdated->getContent(), false, 512, JSON_THROW_ON_ERROR);
        
        // call this before asserting anything so it gets also
        // deleted if assertions fail.
        $this->deleteMember($member);
        
        self::assertEquals(201, $put->getStatusCode());
        self::assertContains($initial, $m2->interests);
        self::assertNotContains($remove, $m2->interests);
    }
    
    public function testPutMember_removeText_201(): void
    {
        $remove = 'tagtagtag';
        $initial = "this is a note\nanothertag";
        
        $member = $this->getMember();
        $member->notesCountry->append("$initial\n$remove");
        $member = $this->saveMember($member);
        
        $m = [
            'notesCountry' => [
                'value' => $remove,
                'mode' => 'remove'
            ]
        ];
        
        $put = $this->json(
            'PUT',
            '/api/v1/member/' . $member->id,
            $m,
            $this->auth->getAuthHeader()
        );
        
        $getUpdated = $this->json('GET', '/api/v1/admin/member/' . $member->id, [], $this->auth->getAuthHeader());
        $m2 = json_decode($getUpdated->getContent(), false, 512, JSON_THROW_ON_ERROR);
        
        // call this before asserting anything so it gets also
        // deleted if assertions fail.
        $this->deleteMember($member);
        
        self::assertEquals(201, $put->getStatusCode());
        self::assertEquals($initial, $m2->notesCountry);
    }
    
    public function testPutMember_removeAndAppendText_201(): void
    {
        $remove = 'tagtagtag';
        $append = 'anewtag';
        $initial = "this is a note\nanothertag\n$remove";
        $final = "this is a note\nanothertag\n$append";
        
        $member = $this->getMember();
        $member->notesCountry->append($initial);
        $member = $this->saveMember($member);
        
        $m = [
            'notesCountry' => [
                [
                    'value' => $remove,
                    'mode' => 'remove'
                ],
                [
                    'value' => $append,
                    'mode' => 'append'
                ]
            ]
        ];
        
        $put = $this->json(
            'PUT',
            '/api/v1/member/' . $member->id,
            $m,
            $this->auth->getAuthHeader()
        );
        
        $getUpdated = $this->json('GET', '/api/v1/admin/member/' . $member->id, [], $this->auth->getAuthHeader());
        $m2 = json_decode($getUpdated->getContent(), false, 512, JSON_THROW_ON_ERROR);
        
        // call this before asserting anything so it gets also
        // deleted if assertions fail.
        $this->deleteMember($member);
        
        self::assertEquals(201, $put->getStatusCode());
        self::assertEquals($final, $m2->notesCountry);
    }
    
    public function testPutMember_addIfNew_notNew_201()
    {
        $initial = 'already in the database';
        $add = 'this should not be appended';
        
        $member = $this->getMember();
        $member->entryChannel->setValue($initial);
        $member = $this->saveMember($member);
        
        $m = [
            'entryChannel' => [
                'value' => $add,
                'mode' => 'addIfNew'
            ]
        ];
        
        $put = $this->json(
            'PUT',
            '/api/v1/member/' . $member->id,
            $m,
            $this->auth->getAuthHeader()
        );
        
        $getUpdated = $this->json('GET', '/api/v1/admin/member/' . $member->id, [], $this->auth->getAuthHeader());
        $m2 = json_decode($getUpdated->getContent());
        
        // call this before asserting anything so it gets also
        // deleted if assertions fail.
        $this->deleteMember($member);
        
        $this->assertEquals(201, $put->getStatusCode());
        $this->assertTrue(0 === strpos($initial, $m2->entryChannel));
        $this->assertTrue(false === strpos($add, $m2->entryChannel));
    }
    
    public function testPutMember_addIfNew_new_201()
    {
        $m = [
            'email1' => [
                'value' => 'unittest_' . Str::random() . '@mail.com',
                'mode' => 'replace',
            ],
            'entryChannel' => [
                'value' => 'I am new here',
                'mode' => 'addIfNew'
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
        
        $this->assertEquals(201, $put->getStatusCode());
        $id = $put->getContent();
        
        $getNew = $this->json('GET', '/api/v1/admin/member/' . $id, [], $this->auth->getAuthHeader());
        $m2 = json_decode($getNew->getContent());
        
        // call this before asserting anything so it gets also
        // deleted if assertions fail.
        $this->deleteMember($id);
        
        $this->assertTrue(0 === strpos($m['entryChannel']['value'], $m2->entryChannel));
    }
    
    public function testPutMember_replaceEmpty_notEmpty_201()
    {
        $initial = 'already in the database';
        $replace = 'this should not be replaced';
        
        $member = $this->getMember();
        $member->entryChannel->setValue($initial);
        $member = $this->saveMember($member);
        
        $m = [
            'entryChannel' => [
                'value' => $replace,
                'mode' => 'replaceEmpty'
            ]
        ];
        
        $put = $this->json(
            'PUT',
            '/api/v1/member/' . $member->id,
            $m,
            $this->auth->getAuthHeader()
        );
        
        $getUpdated = $this->json('GET', '/api/v1/admin/member/' . $member->id, [], $this->auth->getAuthHeader());
        $m2 = json_decode($getUpdated->getContent());
        
        // call this before asserting anything so it gets also
        // deleted if assertions fail.
        $this->deleteMember($member);
        
        $this->assertEquals(201, $put->getStatusCode());
        $this->assertTrue(0 === strpos($initial, $m2->entryChannel));
        $this->assertTrue(false === strpos($replace, $m2->entryChannel));
    }
    
    public function testPutMember_replaceEmpty_empty_201()
    {
        $initial = '';
        $replace = 'this should not be replaced';
        
        $member = $this->getMember();
        $member->entryChannel->setValue($initial);
        $member = $this->saveMember($member);
        
        $m = [
            'entryChannel' => [
                'value' => $replace,
                'mode' => 'replaceEmpty'
            ]
        ];
        
        $put = $this->json(
            'PUT',
            '/api/v1/member/' . $member->id,
            $m,
            $this->auth->getAuthHeader()
        );
        
        $getUpdated = $this->json('GET', '/api/v1/admin/member/' . $member->id, [], $this->auth->getAuthHeader());
        $m2 = json_decode($getUpdated->getContent());
        
        // call this before asserting anything so it gets also
        // deleted if assertions fail.
        $this->deleteMember($member);
        
        $this->assertEquals(201, $put->getStatusCode());
        $this->assertEquals($replace, $m2->entryChannel);
    }
    
    public function testPutMember_append_500()
    {
        $date = '2019-07-27';
        $member = $this->addMember();
        
        $m = [
            'birthday' => [
                'value' => $date,
                'mode' => 'append'
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
        $this->deleteMember($member);
        
        $this->assertEquals(500, $put->getStatusCode());
    }
    
    public function testPutMember_skipId_201()
    {
        $member = $this->addMember();
        
        $m = [
            'id' => [
                'value' => 1,
                'mode' => 'replace'
            ]
        ];
        
        $put = $this->json(
            'PUT',
            '/api/v1/member/' . $member->id,
            $m,
            $this->auth->getAuthHeader()
        );
        
        $getUpdated = $this->json('GET', '/api/v1/member/' . $member->id, [], $this->auth->getAuthHeader());
        $m2 = json_decode($getUpdated->getContent());
        
        // call this before asserting anything so it gets also
        // deleted if assertions fail.
        $this->deleteMember($member);
        
        $this->assertEquals(201, $put->getStatusCode());
        $this->assertEquals($member->id, $m2->id);
    }
    
    public function testPutMember_replaceGroup_201()
    {
        $group = 1081;
        $member = $this->addMember();
        
        $m = [
            'groups' => [
                'value' => $group,
                'mode' => 'replace'
            ]
        ];
        
        $put = $this->json(
            'PUT',
            '/api/v1/member/' . $member->id,
            $m,
            $this->auth->getAuthHeader()
        );
        
        $getUpdated = $this->json('GET', '/api/v1/member/' . $member->id, [], $this->auth->getAuthHeader());
        $m2 = json_decode($getUpdated->getContent());
        
        // call this before asserting anything so it gets also
        // deleted if assertions fail.
        $this->deleteMember($member);
        
        $this->assertEquals(201, $put->getStatusCode());
        $this->assertEquals([$group], $m2->groups);
    }
    
    public function testPutMember_appendGroup_201()
    {
        $group = 1081;
        $member = $this->addMember();
        
        $m = [
            'groups' => [
                'value' => $group,
                'mode' => 'append'
            ]
        ];
        
        $put = $this->json(
            'PUT',
            '/api/v1/member/' . $member->id,
            $m,
            $this->auth->getAuthHeader()
        );
        
        $getUpdated = $this->json('GET', '/api/v1/member/' . $member->id, [], $this->auth->getAuthHeader());
        $m2 = json_decode($getUpdated->getContent());
        
        // call this before asserting anything so it gets also
        // deleted if assertions fail.
        $this->deleteMember($member);
        
        $this->assertEquals(201, $put->getStatusCode());
        $this->assertTrue(in_array($group, $m2->groups));
        $this->assertTrue(in_array(100, $m2->groups));
    }
    
    public function testPutMember_removeGroup_201(): void
    {
        $groupId = 1081;
        $groupRepository = new GroupRepository(config('app.webling_api_key'));
        $group = $groupRepository->get($groupId);
        
        $member = $this->getMember();
        $member->addGroups($group);
        
        $member = $this->saveMember($member);
        
        $m = [
            'groups' => [
                'value' => $groupId,
                'mode' => 'remove'
            ]
        ];
        
        $put = $this->json(
            'PUT',
            '/api/v1/member/' . $member->id,
            $m,
            $this->auth->getAuthHeader()
        );
        
        $getUpdated = $this->json('GET', '/api/v1/member/' . $member->id, [], $this->auth->getAuthHeader());
        $m2 = json_decode($getUpdated->getContent(), false, 512, JSON_THROW_ON_ERROR);
        
        // call this before asserting anything so it gets also
        // deleted if assertions fail.
        $this->deleteMember($member);
        
        self::assertEquals(201, $put->getStatusCode());
        self::assertNotContains($groupId, $m2->groups);
        self::assertContains(100, $m2->groups);
    }
    
    public function testPostMember_insert_201()
    {
        $m = [
            'firstName' => [
                'value' => 'Unit Post Create',
                'mode' => 'replace'
            ],
            'lastName' => [
                'value' => 'Test',
                'mode' => 'append'
            ],
            'email1' => [
                'value' => 'unittest+' . Str::random() . '@unittest.ut',
                'mode' => 'replace'
            ],
            'groups' => [
                'value' => [100],
                'mode' => 'append',
            ]
        ];
        
        $post = $this->json(
            'POST',
            '/api/v1/member',
            $m,
            $this->auth->getAuthHeader()
        );
        
        $id = $post->getContent();
        
        $getUpdated = $this->json('GET', "/api/v1/member/$id", [], $this->auth->getAuthHeader());
        $m2 = json_decode($getUpdated->getContent());
        
        // call this before asserting anything so it gets also
        // deleted if assertions fail.
        $memberRepository = new MemberRepository(config('app.webling_api_key'));
        $this->deleteMember($memberRepository->get((int)$id));
        
        $this->assertEquals(201, $post->getStatusCode());
        $this->assertEquals($m['email1']['value'], $m2->email1);
        $this->assertEquals($m['lastName']['value'], $m2->lastName);
    }
    
    public function testPostMember_upsert_id_201()
    {
        $member = $this->addMember();
        $this->assertEmpty($member->email2->getValue());
        
        $m = [
            'email2' => [
                'value' => $member->email1->getValue(),
                'mode' => 'replace'
            ],
            'id' => [
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
        
        $getUpdated = $this->json('GET', "/api/v1/admin/member/$id", [], $this->auth->getAuthHeader());
        $m2 = json_decode($getUpdated->getContent());
        
        // call this before asserting anything so it gets also
        // deleted if assertions fail.
        $memberRepository = new MemberRepository(config('app.webling_api_key'));
        $this->deleteMember($memberRepository->get((int)$id));
        
        $this->assertEquals(201, $post->getStatusCode());
        $this->assertEquals($id, $member->id);
        $this->assertEquals($m['email2']['value'], $m2->email2);
    }
    
    public function testPostMember_upsert_email_single_201()
    {
        $member = $this->addMember();
        $this->assertEmpty($member->email2->getValue());
        
        $m = [
            'email1' => [
                'value' => $member->email1->getValue(),
                'mode' => 'replace'
            ],
            'email2' => [
                'value' => $member->email1->getValue(),
                'mode' => 'replace'
            ]
        ];
        
        $post = $this->json(
            'POST',
            '/api/v1/member',
            $m,
            $this->auth->getAuthHeader()
        );
        
        $id = $post->getContent();
        
        $getUpdated = $this->json('GET', "/api/v1/admin/member/$id", [], $this->auth->getAuthHeader());
        $m2 = json_decode($getUpdated->getContent());
        
        // call this before asserting anything so it gets also
        // deleted if assertions fail.
        $memberRepository = new MemberRepository(config('app.webling_api_key'));
        $this->deleteMember($memberRepository->get((int)$id));
        
        $this->assertEquals(201, $post->getStatusCode());
        $this->assertEquals($member->id, $id);
        $this->assertEquals($m['email2']['value'], $m2->email2);
    }
    
    public function testPostMember_upsert_email_multiple_201()
    {
        $member = $this->getMember();
        $member1 = $this->saveMember($member);
        $member2 = $this->saveMember($member);
        
        $this->assertNotEquals($member1->id, $member2->id);
        
        $m = [
            'email1' => [
                'value' => $member1->email1->getValue(),
                'mode' => 'replace'
            ],
            'email2' => [
                'value' => $member1->email1->getValue(),
                'mode' => 'replace'
            ],
            'groups' => [
                'value' => [100],
                'mode' => 'append',
            ]
        ];
        
        $post = $this->json(
            'POST',
            '/api/v1/member',
            $m,
            $this->auth->getAuthHeader()
        );
        
        $id = $post->getContent();
        
        $getUpdated = $this->json('GET', "/api/v1/admin/member/$id", [], $this->auth->getAuthHeader());
        $m2 = json_decode($getUpdated->getContent());
        
        // call this before asserting anything so it gets also
        // deleted if assertions fail.
        $memberRepository = new MemberRepository(config('app.webling_api_key'));
        $this->deleteMember($memberRepository->get((int)$id));
        $this->deleteMember($member1);
        $this->deleteMember($member2);
        
        $this->assertEquals(201, $post->getStatusCode());
        $this->assertNotEquals($member1->id, $id);
        $this->assertNotEquals($member2->id, $id);
        $this->assertEquals($m['email2']['value'], $m2->email2);
    }
    
    public function testPostMatch_200_no_match()
    {
        $member = $this->getMember();
        
        $m = [
            'email1' => [
                'value' => $member->email1->getValue(),
            ]
        ];
        
        $response = $this->json('POST', '/api/v1/member/match', $m, $this->auth->getAuthHeader());
        
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'matches']);
        $response->assertJsonCount(0, 'matches');
        $response->assertJsonFragment(['status' => 'no_match']);
    }
    
    public function testPostMatch_200_match()
    {
        $member = $this->addMember();
        
        $m = [
            'email1' => [
                'value' => $member->email1->getValue(),
            ]
        ];
        
        $response = $this->json('POST', '/api/v1/member/match', $m, $this->auth->getAuthHeader());
        
        $this->deleteMember($member);
        
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'matches']);
        $response->assertJsonCount(1, 'matches');
        $response->assertJsonFragment(['status' => 'match']);
    }
    
    public function testPostMatch_200_match_apostrophe()
    {
        $member = $this->getMember();
        $member->firstName->setValue("d'apostrophique");
        $member = $this->saveMember($member);
        
        $m = [
            'firstName' => [
                'value' => $member->firstName->getValue(),
            ],
            'lastName' => [
                'value' => $member->lastName->getValue(),
            ]
        ];
        
        $response = $this->json('POST', '/api/v1/member/match', $m, $this->auth->getAuthHeader());
        
        $this->deleteMember($member);
        
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'matches']);
        $response->assertJsonCount(1, 'matches');
    }
    
    public function testPostMatch_200_match_ampersand()
    {
        $member = $this->getMember();
        $member->firstName->setValue("P. & M.");
        $member = $this->saveMember($member);
        
        $m = [
            'firstName' => [
                'value' => $member->firstName->getValue(),
            ],
            'lastName' => [
                'value' => $member->lastName->getValue(),
            ]
        ];
        
        $response = $this->json('POST', '/api/v1/member/match', $m, $this->auth->getAuthHeader());
        
        $this->deleteMember($member);
        
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'matches']);
        $response->assertJsonCount(1, 'matches');
    }
    
    public function testPostMatch_200_ambiguous()
    {
        $member = $this->getMember();
        
        $m = [
            'firstName' => [
                'value' => $member->firstName->getValue(),
            ],
            'lastName' => [
                'value' => $member->lastName->getValue(),
            ],
        ];
        
        // precondition: assert we dont have any records with the same name
        $response = $this->json('POST', '/api/v1/member/match', $m, $this->auth->getAuthHeader());
        
        foreach ($response->json('matches') as $match) {
            $this->deleteMember($match['id']);
        }
        
        // the actual test
        $member = $this->addMember();
        $response = $this->json('POST', '/api/v1/member/match', $m, $this->auth->getAuthHeader());
        
        $this->deleteMember($member);
        
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'matches']);
        $response->assertJsonCount(1, 'matches');
        $response->assertJsonFragment(['status' => 'ambiguous']);
    }
    
    public function testPostMatch_200_multiple()
    {
        $member = $this->getMember();
        $member1 = $this->saveMember($member);
        $member2 = $this->saveMember($member);
        
        $m = [
            'email1' => [
                'value' => $member->email1->getValue(),
            ]
        ];
        
        $response = $this->json('POST', '/api/v1/member/match', $m, $this->auth->getAuthHeader());
        
        $this->deleteMember($member1);
        $this->deleteMember($member2);
        
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'matches']);
        $response->assertJsonCount(2, 'matches');
        $response->assertJsonFragment(['status' => 'multiple']);
    }
    
    public function testPutMerge_200_noconflicts()
    {
        $dst = $this->getMember();
        $dst->gender->setValue('n');
        $dst->salutationInformal->setValue('nD');
        $dst->address1->setValue("Rue de l'Annonciade 22");
        $dst->address2->setValue('CP 123');
        $dst->zip->setValue('1234');
        $dst->city->setValue('Entenhausen');
        $dst->entryChannel->setValue('initial value');
        $dst->interests->setValue(['finance', 'gender']);
        $dst->request->setValue('music');
        $dst->memberStatusMunicipality->setValue('sympathiser');
        $dst->memberStatusRegion->setValue('member');
        $dst->memberStatusCanton->setValue('unconfirmed');
        $dst->roleMunicipality->setValue('trouble master');
        $dst->mandateMunicipality->setValue('legislativePast');
        $dst->mandateMunicipalityDetail->setValue('parli');
        $dst = $this->saveMember($dst);
        
        $src = new Member();
        $src->recordStatus->setValue('active');
        $src->firstName->setValue(mb_strtoupper($dst->firstName->getValue()));
        $src->lastName->setValue(mb_strtolower($dst->lastName->getValue()));
        $src->recordCategory->setValue('private');
        $src->language->setValue('d');
        $src->gender->setValue('f');
        $src->salutationFormal->setValue('fD');
        $src->salutationInformal->setValue('fD');
        $src->title->setValue('Dr.');
        $src->company->setValue('Company');
        $src->address1->setValue("22 rue de l'annonciade");
        $src->address2->setValue('Case postale 123');
        $src->zip->setValue('1234');
        $src->city->setValue('entenhausen');
        $src->country->setValue('ch');
        $src->postStatus->setValue('active');
        $src->email1->setValue(ucfirst($dst->email1->getValue()));
        $src->email2->setValue('hugo@email.com');
        $src->emailStatus->setValue('active');
        $src->mobilePhone->setValue('123456');
        $src->landlinePhone->setValue('789');
        $src->workPhone->setValue('0800800800');
        $src->phoneStatus->setValue('unwanted');
        $src->entryChannel->setValue('must not be merged');
        $src->birthday->setValue('');
        $src->website->setValue('https://mysite.com');
        $src->facebook->setValue('boomer');
        $src->twitter->setValue('@nerd');
        $src->instagram->setValue('@yay');
        $src->iban->setValue('12345678');
        $src->profession->setValue('activist');
        $src->professionCategory->setValue('entrepreneurs');
        $src->networkNpo->setValue('betterWorld');
        $src->interests->setValue(['energy', 'climate']);
        $src->request->setValue('design');
        $src->coupleCategory->setValue('single');
        $src->partnerSalutationFormal->setValue('fD');
        $src->partnerSalutationInformal->setValue('fD');
        $src->partnerFirstName->setValue('Vroni');
        $src->partnerLastName->setValue('Maurer');
        $src->memberStatusMunicipality->setValue('member');
        $src->memberStatusRegion->setValue('member');
        $src->memberStatusCanton->setValue('member');
        $src->memberStatusCountry->setValue('member');
        $src->memberStatusYoung->setValue('member');
        $src->membershipStart->setValue('01.01.1970');
        $src->membershipEnd->setValue('31.12.2034');
        $src->responsibility->setValue('you');
        $src->membershipFeeMunicipality->setValue('regular');
        $src->membershipFeeRegion->setValue('reduced');
        $src->membershipFeeCanton->setValue('couple');
        $src->membershipFeeCountry->setValue('no');
        $src->membershipFeeYoung->setValue('no');
        $src->magazineMunicipality->setValue('yes');
        $src->newsletterMunicipality->setValue('no');
        $src->pressReleaseMunicipality->setValue('yes');
        $src->roleMunicipality->setValue('problem solver');
        $src->mandateMunicipality->setValue('legislativeActive');
        $src->mandateMunicipalityDetail->setValue('parli');
        $src->donorMunicipality->setValue('sponsor');
        $src->notesMunicipality->setValue('note');
        $src->roleRegion->setValue('asdf');
        $src->mandateRegion->setValue('governorActive');
        $src->mandateRegionDetail->setValue('mandateR');
        $src->donorRegion->setValue('donor');
        $src->magazineCantonD->setValue('yes');
        $src->magazineCantonF->setValue('no');
        $src->newsletterCantonD->setValue('yes');
        $src->newsletterCantonF->setValue('no');
        $src->pressReleaseCantonD->setValue('yes');
        $src->pressReleaseCantonF->setValue('no');
        $src->roleCanton->setValue('chef');
        $src->mandateCanton->setValue('commissionActive');
        $src->mandateCantonDetail->setValue('canton');
        $src->donorCanton->setValue('majorDonor');
        $src->notesCanton->setValue('my note');
        $src->magazineCountryD->setValue('yes');
        $src->magazineCountryF->setValue('no');
        $src->newsletterCountryD->setValue('yes');
        $src->newsletterCountryF->setValue('no');
        $src->pressReleaseCountryD->setValue('yes');
        $src->pressReleaseCountryF->setValue('no');
        $src->roleCountry->setValue('backer');
        $src->roleInternational->setValue('butcher');
        $src->mandateCountry->setValue('judikativeActive');
        $src->mandateCountryDetail->setValue('the judge');
        $src->donorCountry->setValue('donor');
        $src->notesCountry->setValue('long lives the judge');
        $src->notesCantonYoung->setValue('party');
        $src->notesCountryYoung->setValue('work');
        $src->legacy->setValue('old');
        $src->magazineOther->setValue('deprecatedM');
        $src->newsletterOther->setValue('deprecatedNL');
        $src->networkOther->setValue('deprecatedNW');
        $src->roleYoung->setValue('maker');
        $src->donorYoung->setValue('sponsor');
        
        $groupRepository = new GroupRepository(config('app.webling_api_key'));
        $rootGroup = $groupRepository->get(100);
        $src->addGroups($rootGroup);
        $src = $this->saveMember($src);
     
        // link existing debtor to src member
        $debtorRepository = new DebtorRepository(config('app.webling_api_key'));
        $debtor = $debtorRepository->get(self::MERGE_MEMBER_DEBTOR_ID);
        $debtor->setMemberId($src->id);
        $debtorRepository->put($debtor);
        
        $response = $this->json(
            'PUT',
            '/api/v1/admin/member/' . $dst->id . '/merge/' . $src->id,
            [],
            $this->auth->getAuthHeader()
        );
        
        $response->assertStatus(200);
        
        $body = json_decode($response->getContent(), false, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(true, $body->success);
        $this->assertEmpty($body->conflicts);
        
        $merged = $body->merged;
    
        $this->assertEquals('active', $merged->recordStatus);
        $this->assertEquals($dst->firstName->getValue(), $merged->firstName);
        $this->assertEquals($dst->lastName->getValue(), $merged->lastName);
        $this->assertEquals('private', $merged->recordCategory);
        $this->assertEquals('d', $merged->language);
        $this->assertEquals('f', $merged->gender);
        $this->assertEquals('fD', $merged->salutationFormal);
        $this->assertEquals('fD', $merged->salutationInformal);
        $this->assertEquals('Dr.', $merged->title);
        $this->assertEquals('Company', $merged->company);
        $this->assertEquals("Rue de l'Annonciade 22", $merged->address1);
        $this->assertEquals('CP 123', $merged->address2);
        $this->assertEquals('1234', $merged->zip);
        $this->assertEquals('Entenhausen', $merged->city);
        $this->assertEquals('ch', $merged->country);
        $this->assertEquals('active', $merged->postStatus);
        $this->assertEquals(strtolower($dst->email1->getValue()), $merged->email1);
        $this->assertEquals('hugo@email.com', $merged->email2);
        $this->assertEquals('active', $merged->emailStatus);
        $this->assertEquals('123456', $merged->mobilePhone);
        $this->assertEquals('789', $merged->landlinePhone);
        $this->assertEquals('0800800800', $merged->workPhone);
        $this->assertEquals('unwanted', $merged->phoneStatus);
        $this->assertEquals($dst->entryChannel->getValue(), $merged->entryChannel);
        $this->assertEquals($dst->birthday->getValue(), $merged->birthday);
        $this->assertEquals('https://mysite.com', $merged->website);
        $this->assertEquals('boomer', $merged->facebook);
        $this->assertEquals('@nerd', $merged->twitter);
        $this->assertEquals('@yay', $merged->instagram);
        $this->assertEquals('12345678', $merged->iban);
        $this->assertEquals('activist', $merged->profession);
        $this->assertEquals('entrepreneurs', $merged->professionCategory);
        $this->assertEquals('betterWorld', $merged->networkNpo);
        $this->assertContains('finance', $merged->interests);
        $this->assertContains('gender', $merged->interests);
        $this->assertContains('energy', $merged->interests);
        $this->assertContains('climate', $merged->interests);
        $this->assertContains('music', $merged->request);
        $this->assertContains('design', $merged->request);
        $this->assertEquals('single', $merged->coupleCategory);
        $this->assertEquals('fD', $merged->partnerSalutationFormal);
        $this->assertEquals('fD', $merged->partnerSalutationInformal);
        $this->assertEquals('Vroni', $merged->partnerFirstName);
        $this->assertEquals('Maurer', $merged->partnerLastName);
        $this->assertEquals('member', $merged->memberStatusMunicipality);
        $this->assertEquals('member', $merged->memberStatusRegion);
        $this->assertEquals('member', $merged->memberStatusCanton);
        $this->assertEquals('member', $merged->memberStatusCountry);
        $this->assertEquals('member', $merged->memberStatusYoung);
        $this->assertEquals('1970-01-01', $merged->membershipStart);
        $this->assertEquals('2034-12-31', $merged->membershipEnd);
        $this->assertEquals('you', $merged->responsibility);
        $this->assertEquals('regular', $merged->membershipFeeMunicipality);
        $this->assertEquals('reduced', $merged->membershipFeeRegion);
        $this->assertEquals('couple', $merged->membershipFeeCanton);
        $this->assertEquals('no', $merged->membershipFeeCountry);
        $this->assertEquals('no', $merged->membershipFeeYoung);
        $this->assertEquals('yes', $merged->magazineMunicipality);
        $this->assertEquals('no', $merged->newsletterMunicipality);
        $this->assertEquals('yes', $merged->pressReleaseMunicipality);
        $this->assertStringContainsString('problem solver', $merged->roleMunicipality);
        $this->assertStringContainsString('trouble master', $merged->roleMunicipality);
        $this->assertContains('legislativeActive', $merged->mandateMunicipality);
        $this->assertContains('legislativePast', $merged->mandateMunicipality);
        $this->assertEquals('parli', $merged->mandateMunicipalityDetail);
        $this->assertEquals('sponsor', $merged->donorMunicipality);
        $this->assertEquals('note', $merged->notesMunicipality);
        $this->assertEquals('asdf', $merged->roleRegion);
        $this->assertContains('governorActive', $merged->mandateRegion);
        $this->assertEquals('mandateR', $merged->mandateRegionDetail);
        $this->assertEquals('donor', $merged->donorRegion);
        $this->assertEquals('yes', $merged->magazineCantonD);
        $this->assertEquals('no', $merged->magazineCantonF);
        $this->assertEquals('yes', $merged->newsletterCantonD);
        $this->assertEquals('no', $merged->newsletterCantonF);
        $this->assertEquals('yes', $merged->pressReleaseCantonD);
        $this->assertEquals('no', $merged->pressReleaseCantonF);
        $this->assertEquals('chef', $merged->roleCanton);
        $this->assertContains('commissionActive', $merged->mandateCanton);
        $this->assertEquals('canton', $merged->mandateCantonDetail);
        $this->assertEquals('majorDonor', $merged->donorCanton);
        $this->assertEquals('my note', $merged->notesCanton);
        $this->assertEquals('yes', $merged->magazineCountryD);
        $this->assertEquals('no', $merged->magazineCountryF);
        $this->assertEquals('yes', $merged->newsletterCountryD);
        $this->assertEquals('no', $merged->newsletterCountryF);
        $this->assertEquals('yes', $merged->pressReleaseCountryD);
        $this->assertEquals('no', $merged->pressReleaseCountryF);
        $this->assertEquals('backer', $merged->roleCountry);
        $this->assertEquals('butcher', $merged->roleInternational);
        $this->assertContains('judikativeActive', $merged->mandateCountry);
        $this->assertEquals('the judge', $merged->mandateCountryDetail);
        $this->assertEquals('donor', $merged->donorCountry);
        $this->assertEquals('long lives the judge', $merged->notesCountry);
        $this->assertEquals('party', $merged->notesCantonYoung);
        $this->assertEquals('work', $merged->notesCountryYoung);
        $this->assertEquals('old', $merged->legacy);
        $this->assertEquals('deprecatedM', $merged->magazineOther);
        $this->assertEquals('deprecatedNL', $merged->newsletterOther);
        $this->assertEquals('deprecatedNW', $merged->networkOther);
        $this->assertEquals('maker', $merged->roleYoung);
        $this->assertEquals('sponsor', $merged->donorYoung);
        
        // check if debtor was associated with dst member
        $updatedDebtor = $debtorRepository->get(self::MERGE_MEMBER_DEBTOR_ID);
        $this->assertSame($dst->id, $updatedDebtor->getMemberId());
        
        // check if src member is deleted
        $memberRepository = new MemberRepository(config('app.webling_api_key'));
        $this->expectException(MemberNotFoundException::class);
        $memberRepository->get($src->id);
    }
    
    public function testPutMerge_200_updateInvalidEmailStateChangeEmail(): void
    {
        $dst = $this->getMember();
        $dst->email1->setValue('invalid@email.com');
        $dst->emailStatus->setValue('invalid');
        $dst = $this->saveMember($dst);
        
        $src = new Member();
        $src->email1->setValue('valid@email.com');
        $src->emailStatus->setValue('active');
        
        $groupRepository = new GroupRepository(config('app.webling_api_key'));
        $rootGroup = $groupRepository->get(100);
        $src->addGroups($rootGroup);
        $src = $this->saveMember($src);
        
        $response = $this->json(
            'PUT',
            '/api/v1/admin/member/' . $dst->id . '/merge/' . $src->id,
            [],
            $this->auth->getAuthHeader()
        );
        
        $response->assertStatus(200);
        
        $body = json_decode($response->getContent(), false, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(true, $body->success);
        $this->assertEmpty($body->conflicts);
        
        $merged = $body->merged;
        
        $this->assertEquals('valid@email.com', $merged->email1);
        $this->assertEquals('active', $merged->emailStatus);
    }
    
    public function testPutMerge_200_checkSrcDeleted()
    {
        $dst = $this->getMember();
        $src = clone $dst;
        
        $dst = $this->saveMember($dst);
        $src = $this->saveMember($src);
        
        $this->json(
            'PUT',
            '/api/v1/admin/member/' . $dst->id . '/merge/' . $src->id,
            [],
            $this->auth->getAuthHeader()
        );
        
        $response = $this->json('GET', '/api/v1/member/'.$src->id, [], $this->auth->getAuthHeader());
        $response->assertStatus(404);
    }
    
    public function testPutMerge_200_checkSrcNotDeletedAfterMergeFailed()
    {
        $dst = $this->getMember();
        $dst->zip->setValue('1324');
        $dst = $this->saveMember($dst);
    
        $src = new Member();
        $src->zip->setValue('8888');
        $groupRepository = new GroupRepository(config('app.webling_api_key'));
        $rootGroup = $groupRepository->get(100);
        $src->addGroups($rootGroup);
        $src = $this->saveMember($src);
    
        $this->json(
            'PUT',
            '/api/v1/admin/member/' . $dst->id . '/merge/' . $src->id,
            [],
            $this->auth->getAuthHeader()
        );
        
        $response = $this->json('GET', '/api/v1/member/'.$src->id, [],  $this->auth->getAuthHeader());
        $response->assertStatus(200);
    }
    
    public function testPutMerge_409_checkConflictInfoAfterMergeFailed()
    {
        $dst = $this->getMember();
        $dst->zip->setValue('1324');
        $dst->city->setValue('Musterdorf');
        $dst = $this->saveMember($dst);
    
        $src = new Member();
        $src->zip->setValue('8888');
        $src->city->setValue('Entenhausen');
        $groupRepository = new GroupRepository(config('app.webling_api_key'));
        $rootGroup = $groupRepository->get(100);
        $src->addGroups($rootGroup);
        $src = $this->saveMember($src);
        
        $response = $this->json(
            'PUT',
            '/api/v1/admin/member/' . $dst->id . '/merge/' . $src->id,
            [],
            $this->auth->getAuthHeader()
        );
        
        $response->assertStatus(409);
        
        $body = json_decode($response->getContent(), false, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(false, $body->success);
        $this->assertEmpty($body->merged);
    
        $conflicts = $body->conflicts;
        
        $this->assertContains('zip', $conflicts);
        $this->assertContains('city', $conflicts);
    }
    
    
    public function testPutMerge_409_dstNotActive()
    {
        $dst = $this->getMember();
        $dst->recordStatus->setValue('blocked');
        $dst = $this->saveMember($dst);
    
        $src = new Member();
        $src->notesCountry->setValue('this should not be appended');
        $groupRepository = new GroupRepository(config('app.webling_api_key'));
        $rootGroup = $groupRepository->get(100);
        $src->addGroups($rootGroup);
        $src = $this->saveMember($src);
    
        $response = $this->json(
            'PUT',
            '/api/v1/admin/member/' . $dst->id . '/merge/' . $src->id,
            [],
            $this->auth->getAuthHeader()
        );
    
        $response->assertStatus(409);
    
        $body = json_decode($response->getContent(), false, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(false, $body->success);
        $this->assertEmpty($body->merged);
    
        $conflicts = $body->conflicts;
    
        $this->assertContains('recordStatus', $conflicts);
    }
}
