<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace App\Http\Controllers\RestApi\RestApiMember;

use App\Exceptions\IllegalArgumentException;
use App\Http\Controllers\RestApi\ApiHelper;
use App\Repository\Group\GroupRepository;
use App\Repository\Member\Member;
use Tests\TestCase;

class ApiHelperTest extends TestCase
{
    
    private $id = 123;
    private $someKey = 'firstName';
    private $someValue = 'Hugo';
    private $someAdminKey = 'roleCountry';
    private $someAdminValue = 'president';
    private $groups;
    private $data;
    private $rootGroup;
    
    public function setUp(): void
    {
        parent::setUp();
        
        $this->data = [
            $this->someKey => $this->someValue,
            $this->someAdminKey => $this->someAdminValue,
        ];
        
        $groupRepo = new GroupRepository(config('app.webling_api_key'));
        
        $this->rootGroup = $groupRepo->get(100);
        $this->groups = [$groupRepo->get(1081)];
    }
    
    /**
     * @doesNotPerformAssertions - we check for the happy case
     */
    public function testCheckIntegerInput()
    {
        ApiHelper::checkIntegerInput('11');
        ApiHelper::checkIntegerInput(11);
    }
    
    public function testCheckIntegerInput_Exception()
    {
        $this->expectException(IllegalArgumentException::class);
        ApiHelper::checkIntegerInput('11d');
    }
    
    public function testGetMemberAsArray_nonAdmin()
    {
        $memberArray = ApiHelper::getMemberAsArray($this->getMember(), [$this->rootGroup]);
        
        $this->assertTrue(is_array($memberArray));
        $this->assertArrayHasKey($this->someKey, $memberArray);
        $this->assertArrayNotHasKey($this->someAdminKey, $memberArray);
        $this->assertEquals($this->id, $memberArray['id']);
        $this->assertEquals('Unit Group 1', $memberArray['firstLevelGroupNames']);
    }
    
    private function getMember()
    {
        return new Member($this->data, $this->id, $this->groups, true);
    }
    
    public function testGetMemberAsArray_admin()
    {
        $memberArray = ApiHelper::getMemberAsArray($this->getMember(), [$this->rootGroup], true);
        
        $this->assertTrue(is_array($memberArray));
        $this->assertArrayHasKey($this->someKey, $memberArray);
        $this->assertArrayHasKey($this->someAdminKey, $memberArray);
    }
    
}
