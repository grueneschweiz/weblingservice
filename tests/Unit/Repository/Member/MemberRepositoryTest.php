<?php /** @noinspection PhpUnhandledExceptionInspection */

/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 15.11.18
 * Time: 14:38
 */

namespace App\Repository\Member;


use App\Exceptions\MemberNotFoundException;
use App\Exceptions\NoGroupException;
use App\Exceptions\RevisionNotFoundException;
use App\Repository\Group\GroupRepository;
use App\Repository\Revision\RevisionRepository;
use Illuminate\Support\Str;
use Tests\TestCase;

class MemberRepositoryTest extends TestCase
{
    const REVISION_LAG = 500;
    const MEMBER_STATUS = 'member';
    const EXISTING_MEMBER_WITH_DEBTOR = 5469;
    const DEBTOR_OF_EXISTING_MEMBER = 63332;
    
    /**
     * @var MemberRepository
     */
    private $repository;
    
    /**
     * @var Member
     */
    private $member;
    
    /**
     * @var int
     */
    private $oldRevisionId;
    
    public function setUp(): void
    {
        parent::setUp();
        
        $this->repository = new MemberRepository(config('app.webling_api_key'));
    }

    public function tearDown(): void
    {
        parent::tearDown();

        if(isset($this->member)) {
            try {
                $this->repository->delete($this->member);
            } catch (\Exception){
                // ignore
            }

        }
    }
    
    public function testGetMaster()
    {
        $member1 = $this->getNewLocalMember(__METHOD__);
        $member2 = clone $member1;
        $member2->memberStatusCountry->setValue(self::MEMBER_STATUS);
        $member1 = $this->repository->save($member1);
        $member2 = $this->repository->save($member2);
        $master1 = $this->repository->getMaster($member1, $member1->groups);
        $master2 = $this->repository->getMaster($member1->id, $member1->groups);
        $this->repository->delete($member1);
        $this->repository->delete($member2);
        $this->assertEquals($member2, $master1);
        $this->assertEquals($member2, $master2);
    }
    
    private function getNewLocalMember(string $firstName)
    {
        $member = new Member();
        $member->firstName->setValue($firstName);
        $member->lastName->setValue('Test');
        $member->email1->setValue('unittest+' . Str::random() . '@unittest.ut');
        
        $groupRepository = new GroupRepository(config('app.webling_api_key'));
        $rootGroup = $groupRepository->get(100);
        $member->addGroups($rootGroup);
        
        return $member;
    }
    
    public function testGet()
    {
        $this->addMember(__METHOD__);
        $member = $this->repository->get($this->member->id);
        $this->assertEquals($this->member->id, $member->id);
        $this->removeMember();
    }
    
    public function testGetWithDebtor()
    {
        $member = $this->repository->get(self::EXISTING_MEMBER_WITH_DEBTOR);
        $this->assertContains(self::DEBTOR_OF_EXISTING_MEMBER, $member->getDebtorIds());
    }
    
    private function addMember(string $firstName)
    {
        $this->member = $this->repository->save($this->getNewLocalMember($firstName));
    }
    
    private function removeMember()
    {
        $this->repository->delete($this->member);
        unset($this->member);
    }
    
    public function testGetMemberNotFoundException()
    {
        $this->expectException(MemberNotFoundException::class);
        $this->repository->get(1);
    }
    
    public function testSaveUpdate()
    {
        $this->addMember(__METHOD__);
        $member = &$this->member;
        
        $member->interests->append('energy');
        $this->repository->save($member);
        
        $member2 = $this->repository->get($member->id);
        $this->assertTrue($member2->interests->hasValue('energy'));
        
        $this->removeMember();
    }
    
    public function testSaveCreate()
    {
        $member = $this->getNewLocalMember(__METHOD__);
        $member = $this->repository->save($member);
        
        $this->assertNotEmpty($member->id);
        
        $member2 = $this->repository->get($member->id);
        $this->assertEquals($member->email1->getValue(), $member2->email1->getValue());
        
        $this->repository->delete($member);
    }
    
    public function testSaveNoGroupException()
    {
        $this->addMember(__METHOD__);
        $member = &$this->member;
        
        $member->removeGroups($member->groups);
        
        $this->expectException(NoGroupException::class);
        $this->repository->save($member);
        
        $this->removeMember();
    }
    
    public function testGetUpdated()
    {
        $updated = $this->repository->getUpdated($this->getOldRevisionId());
        foreach ($updated as $member) {
            $this->assertTrue($member instanceof Member || null === $member);
        }
        
        $rervisionRepository = new RevisionRepository(config('app.webling_api_key'));
        $revision = $rervisionRepository->get($this->getOldRevisionId());
        foreach ($revision->getMemberIds() as $id) {
            $this->assertTrue(array_key_exists($id, $updated));
        }
    }
    
    private function getOldRevisionId()
    {
        if ($this->oldRevisionId) {
            return $this->oldRevisionId;
        }
        
        // get revision id
        $rervisionRepository = new RevisionRepository(config('app.webling_api_key'));
        $current = $rervisionRepository->getCurrentRevisionId();
        $oldRevisionId = $current - self::REVISION_LAG;
        
        // test if it is valid
        // search until one valid found
        for ($i = 1; $i < 50; $i++) {
            try {
                $rervisionRepository->get($oldRevisionId);
                $this->oldRevisionId = $oldRevisionId;
                break;
            } catch (RevisionNotFoundException $e) {
                $oldRevisionId += $i * 10;
            }
        }
        
        // this line asserts we get an exception if the search was not successful
        $rervisionRepository->get($oldRevisionId);
        
        return $this->oldRevisionId;
    }
    
    public function testGetUpdated_ofSubgroup()
    {
        $groupRepository = new GroupRepository(config('app.webling_api_key'));
        $group = $groupRepository->get(1081);
        
        $updated = $this->repository->getUpdated($this->getOldRevisionId(), [$group]);
        foreach ($updated as $member) {
            $this->assertTrue($member instanceof Member || null === $member);
        }
    }
    
    public function testFind()
    {
        $this->addMember(__METHOD__);
        
        $query = '`' . $this->member->email1->getWeblingKey() . '` = "' . $this->member->email1->getValue() . '"';
        $found = $this->repository->find($query);
        
        $this->assertEquals(1, count($found));
        $this->assertEquals($this->member->id, array_values($found)[0]->id);
        
        $this->removeMember();
    }
    
    public function testFind_all()
    {
        $this->addMember(__METHOD__);
        
        $found = $this->repository->find('');
        $this->assertTrue(in_array($this->member, $found));
        
        $this->removeMember();
    }
    
    public function testGetAll()
    {
        $this->addMember(__METHOD__);
        
        $found = $this->repository->getAll();
        $this->assertTrue(in_array($this->member, $found));
        
        $this->removeMember();
    }
    
    public function testGetAll_limited()
    {
        $this->addMember(__METHOD__);
        
        $this->repository->setLimit(1);
        $offset = 0;
        
        $found = [];
        while (true) {
            $this->repository->setOffset($offset);
            $tmp = $this->repository->getAll();
            
            if (empty($tmp)) {
                break;
            }
            
            $found = array_merge($found, $tmp);
            
            $offset++;
        }
        
        $this->assertTrue(in_array($this->member, $found));
        
        $this->removeMember();
    }
    
    public function testGetAll_limited_offset()
    {
        $this->repository->setLimit(1);
        $this->repository->setOffset(PHP_INT_MAX);
        $this->assertEmpty($this->repository->getAll());
    }
    
    public function testFindWithRootGroups()
    {
        $this->addMember(__METHOD__);
        
        $query = '`' . $this->member->email1->getWeblingKey() . '` = "' . $this->member->email1->getValue() . '"';
        $groupRepository = new GroupRepository(config('app.webling_api_key'));
        
        $found = $this->repository->find($query, [$groupRepository->get(100), $groupRepository->get(203)]);
        $this->assertEquals(1, count($found));
        $this->assertEquals($this->member->id, array_values($found)[0]->id);
        
        $found = $this->repository->find($query, [$groupRepository->get(203)]);
        $this->assertEmpty($found);
        
        $this->removeMember();
    }
    
    public function testDelete()
    {
        $this->addMember(__METHOD__);
        
        $this->repository->delete($this->member);
        
        $this->expectException(MemberNotFoundException::class);
        $this->repository->get($this->member->id);
    }
}
