<?php /** @noinspection PhpUnhandledExceptionInspection */

/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 15.11.18
 * Time: 18:36
 */

namespace App\Repository\Member;


use App\Exceptions\WeblingAPIException;
use App\Repository\Group\Group;
use App\Repository\Group\GroupRepository;
use Tests\TestCase;

class MemberMatchTest extends TestCase
{
    /**
     * @var MemberRepository
     */
    private $memberRepo;
    
    /**
     * @var Group
     */
    private $group;
    
    /**
     * @var Member[]
     */
    private $members = [];
    
    public function setUp(): void
    {
        parent::setUp();
        
        $this->memberRepo = new MemberRepository(config('app.webling_api_key'));
        
        $groupRepo = new GroupRepository(config('app.webling_api_key'));
        $this->group = $groupRepo->get(100);
    }
    
    public function tearDown(): void
    {
        parent::tearDown();
        
        foreach ($this->members as $member) {
            try {
                $this->memberRepo->delete($member);
            } catch (WeblingAPIException $e) {
                // the member was already deleted.
            }
        }
    }
    
    public function test__match()
    {
        $member = $this->getNewMember();
        
        // precondition: make sure we start clean
        $match = MemberMatch::match($member, [$this->group], $this->memberRepo);
        if ($match->count()) {
            foreach ($match->getMatches() as $match) {
                $this->memberRepo->delete($match);
            }
        }
        
        /**
         * no email, incomplete name
         */
        // empty first name
        $member1 = $this->saveMember(clone $member);
        $member1->email1->setValue('');
        $member1->firstName->setValue('');
        $match = MemberMatch::match($member1, [$this->group], $this->memberRepo);
        $this->assertEquals(MemberMatch::NO_MATCH, $match->getStatus());
        $this->assertEmpty($match->getMatches());
        $this->assertEquals(0, $match->count());
        
        // empty last name
        $member1->firstName->setValue($member->firstName->getValue());
        $member1->lastName->setValue('');
        $match = MemberMatch::match($member1, [$this->group], $this->memberRepo);
        $this->assertEquals(MemberMatch::NO_MATCH, $match->getStatus());
        $this->assertEmpty($match->getMatches());
        $this->assertEquals(0, $match->count());
        $this->memberRepo->delete($member1);
        
        /**
         * get by email
         */
        // no match
        $match = MemberMatch::match($member, [$this->group], $this->memberRepo);
        $this->assertEquals(MemberMatch::NO_MATCH, $match->getStatus());
        $this->assertEmpty($match->getMatches());
        $this->assertEquals(0, $match->count());
        
        // single match
        $member1 = $this->saveMember(clone $member);
        $match = MemberMatch::match($member, [$this->group], $this->memberRepo);
        $this->assertEquals(MemberMatch::MATCH, $match->getStatus());
        $this->assertEquals($member1->id, $match->getMatches()[0]->id);
        $this->assertEquals(1, $match->count());
        
        // email 2
        $member1->email2->setValue($member1->email1->getValue());
        $member1->email1->setValue('');
        $member1 = $this->saveMember($member1);
        $match = MemberMatch::match($member1, [$this->group], $this->memberRepo);
        $this->assertEquals(MemberMatch::MATCH, $match->getStatus());
        $this->assertEquals($member1->id, $match->getMatches()[0]->id);
        $this->assertEquals(1, $match->count());
        
        // no first name
        $member1->firstName->setValue('');
        $this->memberRepo->save($member1);
        $match = MemberMatch::match($member, [$this->group], $this->memberRepo);
        $this->assertEquals(MemberMatch::MATCH, $match->getStatus());
        $this->assertEquals($member1->id, $match->getMatches()[0]->id);
        $this->assertEquals(1, $match->count());
        
        // extended first name with -
        $member1->firstName->setValue($member->firstName->getValue() . '-hyphen');
        $this->memberRepo->save($member1);
        $match = MemberMatch::match($member, [$this->group], $this->memberRepo);
        $this->assertEquals(MemberMatch::MATCH, $match->getStatus());
        $this->assertEquals($member1->id, $match->getMatches()[0]->id);
        $this->assertEquals(1, $match->count());
        
        // extended first name with space
        $member1->firstName->setValue($member->firstName->getValue() . ' space');
        $this->memberRepo->save($member1);
        $match = MemberMatch::match($member, [$this->group], $this->memberRepo);
        $this->assertEquals(MemberMatch::MATCH, $match->getStatus());
        $this->assertEquals($member1->id, $match->getMatches()[0]->id);
        $this->assertEquals(1, $match->count());
        
        // longer first name
        $member1->firstName->setValue($member->firstName->getValue() . 'nospace');
        $this->memberRepo->save($member1);
        $match = MemberMatch::match($member, [$this->group], $this->memberRepo);
        $this->assertEquals(MemberMatch::NO_MATCH, $match->getStatus());
        $this->assertEmpty($match->getMatches());
        $this->assertEquals(0, $match->count());
        
        // multiple match
        $this->memberRepo->delete($member1);
        $member1 = $this->saveMember($member);
        $member2 = $this->saveMember($member);
        $match = MemberMatch::match($member, [$this->group], $this->memberRepo);
        $this->assertEquals(MemberMatch::MULTIPLE_MATCHES, $match->getStatus());
        $matchIds = array_map(function (Member $member) {
            return $member->id;
        }, $match->getMatches());
        $this->assertContains($member1->id, $matchIds);
        $this->assertContains($member2->id, $matchIds);
        $this->assertEquals(2, $match->count());
        
        // cleanup
        $this->memberRepo->delete($member1);
        $this->memberRepo->delete($member2);
        
        /**
         * match by name & zip
         */
        $member = $this->getNewMember();
        $member->email1->setValue('');
        
        // no match
        $member1 = $this->saveMember(clone $member);
        $member1->firstName->setValue('unknown');
        $match = MemberMatch::match($member1, [$this->group], $this->memberRepo);
        $this->assertEquals(MemberMatch::NO_MATCH, $match->getStatus());
        $this->assertEmpty($match->getMatches());
        $this->assertEquals(0, $match->count());
        $this->memberRepo->delete($member1);
        
        // ambiguous match
        $member1 = $this->saveMember(clone $member);
        $member1->zip->setValue('');
        $match = MemberMatch::match($member1, [$this->group], $this->memberRepo);
        $this->assertEquals(MemberMatch::AMBIGUOUS_MATCH, $match->getStatus());
        $this->assertEquals($member1->id, $match->getMatches()[0]->id);
        $this->assertEquals(1, $match->count());
        $this->memberRepo->delete($member1);
        
        // single match
        $member1 = $this->saveMember($member);
        $match = MemberMatch::match($member, [$this->group], $this->memberRepo);
        $this->assertEquals(MemberMatch::MATCH, $match->getStatus());
        $this->assertEquals($member1->id, $match->getMatches()[0]->id);
        $this->assertEquals(1, $match->count());
        $this->memberRepo->delete($member1);
        
        // striped zip
        $member1 = $this->saveMember(clone $member);
        $member1->zip->setValue('D-' . $member->zip->getValue());
        $match = MemberMatch::match($member1, [$this->group], $this->memberRepo);
        $this->assertEquals(MemberMatch::MATCH, $match->getStatus());
        $this->assertEquals($member1->id, $match->getMatches()[0]->id);
        $this->assertEquals(1, $match->count());
        $this->memberRepo->delete($member1);
        
        // wrong zip
        $member1 = $this->saveMember(clone $member);
        $member1->zip->setValue('9999');
        $match = MemberMatch::match($member1, [$this->group], $this->memberRepo);
        $this->assertEquals(MemberMatch::NO_MATCH, $match->getStatus());
        $this->assertEmpty($match->getMatches());
        $this->assertEquals(0, $match->count());
        $this->memberRepo->delete($member1);
        
        // multiple matches
        $member1 = $this->saveMember($member);
        $member2 = $this->saveMember($member);
        $match = MemberMatch::match($member, [$this->group], $this->memberRepo);
        $this->assertEquals(MemberMatch::MULTIPLE_MATCHES, $match->getStatus());
        $matchIds = array_map(function (Member $member) {
            return $member->id;
        }, $match->getMatches());
        $this->assertContains($member1->id, $matchIds);
        $this->assertContains($member2->id, $matchIds);
        $this->assertEquals(2, $match->count());
    }
    
    // wrapper function so remaining members will be deleted on tear down
    private function getNewMember()
    {
        $member = new Member();
        $member->firstName->setValue('Unit');
        $member->lastName->setValue('Test');
        $member->zip->setValue("1234");
        $member->email1->setValue('unittest@unittest.ut');
        
        $member->addGroups($this->group);
        
        return $member;
    }
    
    private function saveMember(Member $member)
    {
        $member = $this->memberRepo->save($member);
        $this->members[] = $member;
        
        return $member;
    }
}
