<?php

namespace App\Http\Controllers\RestApi\RestApiMember;

use Tests\TestCase;
use Illuminate\Http\Request as Request;
use App\Http\Controllers\RestApi\ApiHelper;
use App\Repository\Member\Member;
use App\Exceptions\MemberNotFoundException;
use App\Exceptions\IllegalArgumentException;
use Webling\API\ClientException;

class ApiHelperTest extends TestCase {

  private $id = 123;
	private $someKey = 'firstName';
	private $someValue = 'Hugo';
	private $someAdminKey = 'roleCountry';
  private $someAdminValue = 'president';
  private $groups;


  private function getMember() {
		return new Member( $this->data, $this->id, $this->groups, true );
	}

  public function setUp() {
		parent::setUp();

		$this->data = [
			$this->someKey          => $this->someValue,
      $this->someAdminKey     => $this->someAdminValue,
		];
	}

  /**
   * @doesNotPerformAssertions - we check for the happy case
   */
  public function testCheckIntegerInput() {
      ApiHelper::checkIntegerInput('11');
      ApiHelper::checkIntegerInput(11);
    }

  public function testCheckIntegerInput_Exception() {
      $this->expectException(IllegalArgumentException::class);
      ApiHelper::checkIntegerInput('11d');
    }

  public function testGetMemberAsArray_nonAdmin() {
    $memberArray = ApiHelper::getMemberAsArray($this->getMember());

    $this->assertTrue(is_array($memberArray));
    $this->assertArrayHasKey($this->someKey, $memberArray);
    $this->assertArrayNotHasKey($this->someAdminKey, $memberArray);
  }

  public function testGetMemberAsArray_admin() {
    $memberArray = ApiHelper::getMemberAsArray($this->getMember(), true);

    $this->assertTrue(is_array($memberArray));
    $this->assertArrayHasKey($this->someKey, $memberArray);
    $this->assertArrayHasKey($this->someAdminKey, $memberArray);
  }

}
