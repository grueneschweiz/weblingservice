<?php
/**
 * Created by PhpStorm.
 * User: adrian
 * Date: 18.11.18
 * Time: 22:34
 */

namespace App\Repository\Group;


use App\Exceptions\GroupNotFoundException;
use App\Exceptions\WeblingAPIException;
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
        $group = $this->groupRepository->get(1081, false);

        $this->assertEquals('Unit Group 1', $group->getName());
        $this->assertEquals(100, $group->getParent());
        $this->assertEquals([1084, 1086], $group->getChildren());
        $this->assertEquals([5469, 5470], $group->getMembers());
    }

    public function testGet()
    {
        $group = $this->groupRepository->get(1081);

        $this->assertEquals('Unit Group 1', $group->getName());
        $this->assertEquals(100, $group->getParent());
        $this->assertEquals([1084, 1086], $group->getChildren());
        $this->assertEquals([5469, 5470], $group->getMembers());
    }

    public function testUpdateCache($cacheDeleteAfter = 'PT1M')
    {
        $directory = rtrim(config('app.cache_directory'), '/') . '/group/';
        $newestFileMTime = 0;
        $files = scandir($directory, SCANDIR_SORT_NONE);
        foreach ($files as $file) {
            $file = $directory . $file;

            if(is_file($file) && filemtime($file) > $newestFileMTime) {
                $newestFileMTime = filemtime($file);
            }
        }

        \config(['app.cache_delete_after' => $cacheDeleteAfter]);
        $this->groupRepository->updateCache();

        //assert that all files in cache were created after starting this test
        $files = scandir($directory, SCANDIR_SORT_NONE);
        foreach ($files as $file) {
            $file = $directory . $file;

            if(is_file($file)) {
                $this->assertGreaterThanOrEqual($newestFileMTime, filemtime($file), $file . ' seems to be too new.');
            }
        }
    }

    public function testGetAllMembers() {
        $group = $this->groupRepository->get(100);

        $allMembers = $group->getAllMembers();

        foreach([5469, 5470, 5471] as $needle) {
            $this->assertContains($needle, $allMembers);
        }
    }

    public function testGetNonExisting() {
        $this->expectException(GroupNotFoundException::class);
        $this->groupRepository->get(1);
    }

    /**
     * Triggers a ClientException which is to be caught and replaced with a WeblingAPIException by GroupRepository
     * @throws GroupNotFoundException
     * @throws WeblingAPIException
     * @throws \Webling\API\ClientException
     */
    public function testClientException() {
        $repository = new GroupRepository('notActualKey_notActualKey_789012', 'https://lorem.ipsum');

        $this->expectException(WeblingAPIException::class);
        $repository->get(1081, false);
    }

    /**
     * Triggers Exceptions when parsing the DateIntervals for caching.
     * The functionality must still work if these Exceptions occur.
     */
    public function testInvalidDateStrings() {
        //backup config
        $cacheMaxAge = config('app.cache_max_age');
        $cacheDeleteAfter = config('app.cache_delete_after');

        //make config invalid
        config(['app.cache_max_age' => 'not_a_valid_time_interval']);
        config(['app.cache_delete_after' => 'not_a_valid_time_interval']);

        // redo tests with invalid config
        $this->testGet();
        $this->testUpdateCache(config('app.cache_delete_after'));

        //reset config
        config(['app.cache_max_age' => $cacheMaxAge]);
        config(['app.cache_delete_after' => $cacheDeleteAfter]);
    }

    /**
     * Tests if the GroupRepository constructor can create the missing group directory in the cache directory.
     * @throws \Webling\API\ClientException
     */
    public function testCreateGroupCacheDirectory() {
        $directory = realpath(config('app.cache_directory') . '/group');

        $files = scandir($directory, SCANDIR_SORT_NONE);
        foreach ($files as $file) {
            $file = $directory . '/' . $file;
            if(is_file($file)) {
                unlink($file);
            }
        }

        rmdir($directory);
        new GroupRepository(config('app.webling_api_key'));
        $this->assertFileExists($directory);
    }
}
