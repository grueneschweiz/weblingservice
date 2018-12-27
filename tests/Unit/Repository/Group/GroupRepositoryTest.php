<?php
/**
 * Created by PhpStorm.
 * User: adrian
 * Date: 18.11.18
 * Time: 22:34
 */

namespace App\Repository\Group;


use Tests\TestCase;

class GroupRepositoryTest extends TestCase
{
    /**
     * @var GroupRepository
     */
    private $groupRepository;

    public function setUp() {
        parent::setUp();

        $this->groupRepository = new GroupRepository(config( 'app.webling_api_key' ));
    }

    public function testGetUncached()
    {
        $this->repository = new GroupRepository(config( 'app.webling_api_key' ));
        $group = $this->groupRepository->get(1081, false);

        $this->assertEquals("Unit Group 1", $group->getName());
        $this->assertEquals(100, $group->getParent());
        $this->assertEquals([1084, 1086], $group->getChildren());
        $this->assertEquals([1082, 1083], $group->getMembers());
    }

    public function testGet()
    {
        $this->repository = new GroupRepository(config( 'app.webling_api_key' ));
        $group = $this->groupRepository->get(1081);

        $this->assertEquals("Unit Group 1", $group->getName());
        $this->assertEquals(100, $group->getParent());
        $this->assertEquals([1084, 1086], $group->getChildren());
        $this->assertEquals([1082, 1083], $group->getMembers());
    }

    public function testUpdateCache()
    {
        $timestamp = time();

        \config(['app.cache_delete_after' => 'PT1M']);
        $this->groupRepository->updateCache();

        //assert that all files in cache were created after starting this test
        $directory = rtrim(config('app.cache_directory'), '/') . '/group/';
        $files = scandir($directory, SCANDIR_SORT_NONE);
        foreach ($files as $file) {
            $file = $directory . $file;

            if(is_file($file)) {
                $this->assertGreaterThanOrEqual($timestamp, filemtime($file), $file . ' seems to be too new.');
            }
        }
    }
}
