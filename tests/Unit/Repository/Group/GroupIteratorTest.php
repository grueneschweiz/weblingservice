<?php
/**
 * Created by PhpStorm.
 * User: adrian
 * Date: 26.12.18
 * Time: 20:04
 */

use Tests\TestCase;

class GroupIteratorTest extends TestCase {
    /**
     * @var \App\Repository\Group\GroupRepository
     */
    private $repository;
    
    public function setUp(): void
    {
        parent::setUp();

        $this->repository = new \App\Repository\Group\GroupRepository(config('app.webling_api_key'));
    }

    public function test_GroupIterator() {
        $rootGroup = $this->repository->get(100);

        $iterator = new \App\Repository\Group\GroupIterator(array($rootGroup), $this->repository, true);
        $recursiveIterator = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);

        $unitGroup3reached = false;
        foreach ($recursiveIterator as $key => $value) {
            if($key === 1086) {
                $unitGroup3reached = true;
            }
        }

        self::assertTrue($unitGroup3reached);
    }

}