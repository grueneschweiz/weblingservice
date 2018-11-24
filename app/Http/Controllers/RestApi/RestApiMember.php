<?php

namespace App\Http\Controllers\RestApi;

use App\Repository\Member\Member;
use App\Repository\Member\MemberRepository;
use App\Repository\Member\Field\FieldFactory;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use App\Exceptions\IllegalArgumentException;

use Illuminate\Http\Request;

/**
 * Class RestApiMember
 *
 * Manages all API resources connected to the Member
 */
class RestApiMember
{

  /**
  * Return a json with the member fields
  *
  * @param $request - the http Request
  * @param $member_id - the id of the member that we should get
  * @param $is_admin - is the call by an admin resource (i.e. should we return
  *                     all information about the member)
  * @return string  the JSON
  */
  public function getMember($request, $member_id, $is_admin = false) {
    $this->checkIntegerInput($member_id);
    $memberRepo = $this->createMemberRepo($request->header($key = 'db_key'));

    $member = $memberRepo->get($member_id);

    $data = $this->getMemberAsArray($member, $is_admin);

    return json_encode($data);
  }

  /**
  * Return a json with the all member that changed since the $revisionId
  *
  * @return string the JSON
  */
  public function getChanged($request, $revisionId) {
    $this->checkIntegerInput($revisionId);
    $memberRepo = $this->createMemberRepo($request->header($key = 'db_key'));

    $members = $memberRepo->getUpdated($revisionId);

    $data = [];
    foreach ($members as $member) {
      $data[$member->id] = $this->getMemberAsArray($member);
    }

    return json_encode($data);
  }


  /**********************************************************
  ********************** Helper Functions *******************
  **********************************************************/

  /**
  *
  * @param Member the member object
  * @param boolean [optional] is the array for an admin or not?
  * @return the MemberRepository
  */
  private function getMemberAsArray(Member $member, $is_admin = false): array {

    if ($is_admin) {
      //for admin return all fields
      foreach ($member->getFields() as $field) {
        $data[$field->getKey()] = $field->getValue();
      }
    } else {
      //reduce visible fields according to yml file:
      $path = base_path( config('app.member_json_fields_config_path'));
      $mappings = Yaml::parseFile( $path );

      foreach ($mappings['mappings'] as $key) {
        $data[$key] = $member->$key->getValue();
      }
    }

    return $data;
  }
  /**
  * We check the input here because we want to wrap the error in an Exception
  *
  * @param mixed the input we want to test to be an int
  */
  private function checkIntegerInput($input) {
    if (!is_numeric($input)) {
      throw new IllegalArgumentException("Input " . $input . " is not a number.");
    }
  }

  /**
  * Creates a MemberRepository to deal with Member entities.
  *
  * @param string [optional] the db key (as of 24.11.2018 the webling api key) for the repo
  * @return MemberRepository
  */
  private function createMemberRepo(String $api_key = null) {
    if (!$api_key) {
      $api_key = config('app.webling_api_key');// default on server
    }
      return new MemberRepository($api_key);
  }


}
