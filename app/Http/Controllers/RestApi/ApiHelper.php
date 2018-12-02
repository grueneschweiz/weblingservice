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
 * Class ApiHelper
 *
 * Helper class for ApiControllers
 */
class ApiHelper
{
  /**
  *
  * @param Member the member object
  * @param boolean [optional] is the array for an admin or not?
  * @return the MemberRepository
  */
  public static function getMemberAsArray(Member $member, $is_admin = false): array {

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
  * Note: We do not use parameter constraints in routing because this would give a 404
  * but we want to return the that the id is of the wrong format
  *
  * @param mixed the input we want to test to be an int
  */
  public static function checkIntegerInput($input) {
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
  public static function createMemberRepo(String $api_key = null) {
    if (!$api_key) {
      $api_key = config('app.webling_api_key');// default on server
    }
      return new MemberRepository($api_key);
  }

}
