<?php
/**
 * Created by PhpStorm.
 * User: adrian
 * Date: 18.11.18
 * Time: 22:36
 */

namespace App\Repository\Group;

use Tests\TestCase;

class GroupTest extends TestCase
{
    private $group;

    public function test__construct()
    {
        $jsonData = "{   \"type\": \"membergroup\",   \"meta\": {     \"created\": null,     \"lastmodified\": \"2018-11-18 11:13:42\"   },   \"readonly\": false,   \"properties\": {     \"title\": \"Mitglieder\"   },   \"children\": {     \"membergroup\": [       201 /* CH */ ,       202 /* ZH */ ,       242 /* GE */     ],     \"member\": [       494 /* Unit Test */ ,       495 /* Unit Test */ ,       496 /* Unit Test */ ,       502 /* Unit Test */ ,       506 /* Unit Test */ ,       508 /* Unit Test */ ,       509 /* Unit Test */     ]   },   \"links\": [],   \"parents\": [] }";
        $this->group = new Group();


        $this->assertEquals("Mitglieder", $this->group->getName());
        $this->assertEquals(null, $this->group->getParent());
        $this->assertEquals([201, 202, 242], $this->group->getChildren());
        $this->assertEquals([494, 495, 496, 502, 506, 508, 509], $this->group->getMembers());
    }

    public function testGetRootPath()
    {

    }

    public function testGetAllMembers()
    {

    }
}
