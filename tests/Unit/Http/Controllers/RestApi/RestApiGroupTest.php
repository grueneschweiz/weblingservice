<?php
namespace App\Http\Controllers\RestApi\RestApiGroup;

use App\Exceptions\GroupNotFoundException;
use App\Http\Controllers\RestApi\RestApiGroup;
use Tests\TestCase;

class RestApiGroupTest extends TestCase {

    private $api;
    public function setUp()
    {
        parent::setUp();

        $this->api = new RestApiGroup();
    }


    public function test_getGroup() {
        $json = $this->api->getGroup(1081);

        //ToDo: replace with checking json content
        $this->assertTrue(json_decode($json) !== false);
    }

    public function test_getGroupNotFound() {
        $this->expectException(GroupNotFoundException::class);
        $this->api->getGroup(1);
    }
}
