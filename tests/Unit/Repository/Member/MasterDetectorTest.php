<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace App\Repository\Member;


use App\Exceptions\WeblingAPIException;
use App\Repository\Group\Group;
use App\Repository\Group\GroupRepository;
use Tests\TestCase;

class MasterDetectorTest extends TestCase
{
    const MEMBER_STATUS = 'member';
    const UNCONFIRMED_STATUS = 'unconfirmed';
    const SYMPATHISER_STATUS = 'sympathiser';
    
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
    /**
     * @var MasterDetector
     */
    private $masterDetector;
    
    public function setUp(): void
    {
        parent::setUp();
        
        $this->memberRepo = new MemberRepository(config('app.webling_api_key'));
        $groupRepo = new GroupRepository(config('app.webling_api_key'));
        $this->group = $groupRepo->get(100);
        $this->masterDetector = new MasterDetector($this->memberRepo, $this->group);
        
        $this->removeExistingRecords();
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
    
    public function testGetMaster__noMatch()
    {
        $member1 = $this->getNewMember(__METHOD__);
        $this->assertEquals($member1, $this->masterDetector->getMaster($member1));
    }
    
    private function getNewMember(string $firstName)
    {
        $member = new Member();
        $member->firstName->setValue($firstName);
        $member->lastName->setValue('Test');
        $member->zip->setValue("1234");
        $member->email1->setValue('masterdetector@unittest.ut');
        
        $member->addGroups($this->group);
        
        return $member;
    }
    
    public function testGetMaster__match()
    {
        $member1 = $this->getNewMember(__METHOD__);
        $member1 = $this->saveMember($member1);
        $this->assertEquals($member1, $this->masterDetector->getMaster($member1));
        $this->memberRepo->delete($member1);
    }
    
    private function saveMember(Member $member)
    {
        $member = $this->memberRepo->save($member);
        $this->members[] = $member;
        
        return $member;
    }
    
    public function testGetMaster__ambiguousMatch()
    {
        $member1 = $this->getNewMember(__METHOD__);
        $member1->zip->setValue('');
        $member1->email1->setValue('');
        $member2 = clone $member1;
        $member1 = $this->saveMember($member1);
        $member2 = $this->saveMember($member2);
        $this->assertEquals($member1, $this->masterDetector->getMaster($member1));
        $this->memberRepo->delete($member1);
        $this->memberRepo->delete($member2);
    }
    
    public function testGetMaster__euqalRating()
    {
        // given member has equal rating to similar
        $member1 = $this->getNewMember(__METHOD__);
        $member1->memberStatusCountry->setValue(self::MEMBER_STATUS);
        $member2 = clone $member1;
        $member1 = $this->saveMember($member1);
        $member2 = $this->saveMember($member2);
        $this->assertEquals($member1, $this->masterDetector->getMaster($member1));
        $this->memberRepo->delete($member1);
        $this->memberRepo->delete($member2);
    }
    
    public function testGetMaster__memberRating()
    {
        // given has higher rating to similar
        $member1 = $this->getNewMember(__METHOD__);
        $member2 = clone $member1;
        $member1->memberStatusCountry->setValue(self::MEMBER_STATUS);
        $member1 = $this->saveMember($member1);
        $member2 = $this->saveMember($member2);
        $this->assertEquals($member1, $this->masterDetector->getMaster($member1));
        $this->memberRepo->delete($member1);
        $this->memberRepo->delete($member2);
    }
    
    public function testGetMaster__unconfirmedRating()
    {
        // given has higher rating to similar
        $member1 = $this->getNewMember(__METHOD__);
        $member2 = clone $member1;
        $member1->memberStatusCountry->setValue(self::UNCONFIRMED_STATUS);
        $member1 = $this->saveMember($member1);
        $member2 = $this->saveMember($member2);
        $this->assertEquals($member1, $this->masterDetector->getMaster($member1));
        $this->memberRepo->delete($member1);
        $this->memberRepo->delete($member2);
    }
    
    public function testGetMaster__sympathiserRating()
    {
        // given has higher rating to similar
        $member1 = $this->getNewMember(__METHOD__);
        $member2 = clone $member1;
        $member1->memberStatusCountry->setValue(self::SYMPATHISER_STATUS);
        $member1 = $this->saveMember($member1);
        $member2 = $this->saveMember($member2);
        $this->assertEquals($member1, $this->masterDetector->getMaster($member1));
        $this->memberRepo->delete($member1);
        $this->memberRepo->delete($member2);
    }
    
    public function testGetMaster__smallerRating()
    {
        // given has smaller rating to similar
        $member1 = $this->getNewMember(__METHOD__);
        $member2 = clone $member1;
        $member2->memberStatusCountry->setValue(self::MEMBER_STATUS);
        $member1 = $this->saveMember($member1);
        $member2 = $this->saveMember($member2);
        $this->assertEquals($member2, $this->masterDetector->getMaster($member1));
        $this->memberRepo->delete($member1);
        $this->memberRepo->delete($member2);
    }
    
    public function testGetMaster__similarLowerHigher()
    {
        // multiple similar, one with lower rating one with higher rating
        // expect similar with higher rating
        $member1 = $this->getNewMember(__METHOD__);
        $member2 = clone $member1;
        $member3 = clone $member1;
        $member1->memberStatusCountry->setValue(self::UNCONFIRMED_STATUS);
        $member2->memberStatusCountry->setValue(self::MEMBER_STATUS);
        $member3->memberStatusCountry->setValue(self::SYMPATHISER_STATUS);
        $member1 = $this->saveMember($member1);
        $member2 = $this->saveMember($member2);
        $member3 = $this->saveMember($member3);
        $this->assertEquals($member2, $this->masterDetector->getMaster($member1));
        $this->memberRepo->delete($member1);
        $this->memberRepo->delete($member2);
        $this->memberRepo->delete($member3);
    }
    
    public function testGetMaster__euqalRatingOfSimilar()
    {
        // multiple similar, both with higher but equal rating
        // expect one of similar (order undefined)
        $member1 = $this->getNewMember(__METHOD__);
        $member2 = clone $member1;
        $member3 = clone $member1;
        $member1->memberStatusCountry->setValue(self::SYMPATHISER_STATUS);
        $member2->memberStatusCountry->setValue(self::MEMBER_STATUS);
        $member3->memberStatusCountry->setValue(self::MEMBER_STATUS);
        $member1 = $this->saveMember($member1);
        $member2 = $this->saveMember($member2);
        $member3 = $this->saveMember($member3);
        $master = $this->masterDetector->getMaster($member1);
        $this->assertTrue(in_array($master, [$member2, $member3]));
        $this->memberRepo->delete($member1);
        $this->memberRepo->delete($member2);
        $this->memberRepo->delete($member3);
    }
    
    // wrapper function so remaining members will be deleted on tear down

    public function testGetMaster__memberSympathiser()
    {
        // one member flag wins over 5 sympathiser flags
        $member1 = $this->getNewMember(__METHOD__);
        $member1->memberStatusCountry->setValue(self::MEMBER_STATUS);
        $member2 = clone $member1;
        $member2->memberStatusCountry->setValue(self::SYMPATHISER_STATUS);
        $member2->memberStatusCanton->setValue(self::SYMPATHISER_STATUS);
        $member2->memberStatusRegion->setValue(self::SYMPATHISER_STATUS);
        $member2->memberStatusMunicipality->setValue(self::SYMPATHISER_STATUS);
        $member2->memberStatusYoung->setValue(self::SYMPATHISER_STATUS);
        $member1 = $this->saveMember($member1);
        $member2 = $this->saveMember($member2);
        $this->assertEquals($member1, $this->masterDetector->getMaster($member2));
        $this->memberRepo->delete($member1);
        $this->memberRepo->delete($member2);
    }
    
    public function testGetMaster__memberUnconfirmed()
    {
        // one member flag wins over 1 unconfirmed and 5 sympathiser flags
        $member1 = $this->getNewMember(__METHOD__);
        $member1->memberStatusCountry->setValue(self::MEMBER_STATUS);
        $member2 = clone $member1;
        $member2->memberStatusCountry->setValue(self::UNCONFIRMED_STATUS);
        $member2->memberStatusCanton->setValue(self::SYMPATHISER_STATUS);
        $member2->memberStatusRegion->setValue(self::SYMPATHISER_STATUS);
        $member2->memberStatusMunicipality->setValue(self::SYMPATHISER_STATUS);
        $member2->memberStatusYoung->setValue(self::SYMPATHISER_STATUS);
        $member1 = $this->saveMember($member1);
        $member2 = $this->saveMember($member2);
        $this->assertEquals($member1, $this->masterDetector->getMaster($member2));
        $this->memberRepo->delete($member1);
        $this->memberRepo->delete($member2);
    }
    
    private function removeExistingRecords() {
        $member1 = $this->getNewMember(__METHOD__);
    
        // precondition
        $members = $this->memberRepo->find(
            '`E-Mail / courriel 1` = "' . $member1->email1->getValue() . '"'
            . 'OR (`Vorname / prénom` = "' . $member1->firstName->getValue() . '"'
            . ' AND `Name / nom` = "' . $member1->lastName->getValue() . '")'
        );
    
        if ($members) {
            foreach ($members as $member) {
                $this->memberRepo->delete($member);
            }
        }
    }
}
