<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace App\Repository\Debtor;

use Tests\TestCase;

class DebtorRepositoryTest extends TestCase
{
    private const EXISTING_DEBTOR_ID_1 = 63332;
    private const EXISTING_DEBTOR_1_MEMBER_ID = 5469;
    
    private const EXISTING_DEBTOR_ID_2 = 63336;
    private const EXISTING_DEBTOR_1_MEMBER_ID_1 = 5470;
    private const EXISTING_DEBTOR_1_MEMBER_ID_2 = 5471;
    
    /**
     * @var DebtorRepository
     */
    private $repository;
    
    public function setUp(): void
    {
        parent::setUp();
        
        $this->repository = new DebtorRepository(config('app.webling_api_key'));
    }
    
    public function testGet()
    {
        $debtor = $this->repository->get(self::EXISTING_DEBTOR_ID_1);
        
        $this->assertEquals(self::EXISTING_DEBTOR_ID_1, $debtor->getId());
        $this->assertEquals(self::EXISTING_DEBTOR_1_MEMBER_ID, $debtor->getMemberId());
    }
    
    public function testPut()
    {
        $debtor = $this->repository->get(self::EXISTING_DEBTOR_ID_2);
        
        $memberId = $debtor->getMemberId() === self::EXISTING_DEBTOR_1_MEMBER_ID_1
            ? self::EXISTING_DEBTOR_1_MEMBER_ID_2
            : self::EXISTING_DEBTOR_1_MEMBER_ID_1;
        $debtor->setMemberId($memberId);
        
        $this->repository->put($debtor);
    
        $debtor = $this->repository->get(self::EXISTING_DEBTOR_ID_2);
        
        $this->assertEquals(self::EXISTING_DEBTOR_ID_2, $debtor->getId());
        $this->assertEquals($memberId, $debtor->getMemberId());
    }
}
