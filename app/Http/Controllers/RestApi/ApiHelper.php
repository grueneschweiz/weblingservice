<?php

namespace App\Http\Controllers\RestApi;

use App\Exceptions\IllegalArgumentException;
use App\Repository\Group\Group;
use App\Repository\Group\GroupRepository;
use App\Repository\Member\Member;
use App\Repository\Member\MemberRepository;
use Illuminate\Http\Request;
use Symfony\Component\Yaml\Yaml;

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

    $data['id'] = $member->id;
    $data['groups'] = $member->getGroupIds();

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

	/**
	 * Creates a GroupRepository to deal with Group entities.
	 *
	 * @param string [optional] the db key (as of 24.11.2018 the webling api key) for the repo
	 *
	 * @return GroupRepository
	 */
	public static function createGroupRepo( String $api_key = null ) {
		if ( ! $api_key ) {
			$api_key = config( 'app.webling_api_key' ); // default on server
		}

		return new GroupRepository( $api_key );
	}

	/**
	 * Get the allowed groups as Group instances from the allowed_groups parameter
	 * of the request.
	 *
	 * Use the AddRootGroups middleware to add the root_groups to the request.
	 *
	 * If the request does not contain any root_groups, the action is aborted
	 * with a 403 status code.
	 *
	 * @param $request
	 *
	 * @return Group[]
	 *
	 * @throws \App\Exceptions\GroupNotFoundException
	 * @throws \App\Exceptions\InvalidFixedValueException
	 * @throws \App\Exceptions\MemberNotFoundException
	 * @throws \App\Exceptions\MemberUnknownFieldException
	 * @throws \App\Exceptions\MultiSelectOverwriteException
	 * @throws \App\Exceptions\ValueTypeException
	 * @throws \App\Exceptions\WeblingAPIException
	 * @throws \App\Exceptions\WeblingFieldMappingConfigException
	 * @throws \Webling\API\ClientException
	 */
	public static function getAllowedGroups( Request $request ) {
		$allowedGroups = $request->get( 'allowed_groups' );

		if ( empty( $allowedGroups ) ) {
			abort( 403, 'Not authorized.' );
		}

		$groupRepository = self::createGroupRepo( $request->header( $key = 'db_key' ) );

		$groups = [];
		foreach ( $allowedGroups as $groupId ) {
			$groups[ $groupId ] = $groupRepository->get( $groupId );
		}

		return $groups;
	}

	/**
	 * Asserts that the given group is a descendant of any of the given root groups.
	 * Else the request is aborted with a 403 status code.
	 *
	 * @param Group[] $allowedGroups
	 * @param Group $group
	 *
	 * @return Group
	 *
	 * @throws \App\Exceptions\GroupNotFoundException
	 * @throws \App\Exceptions\WeblingAPIException
	 */
	public static function assertAllowedGroup( array $allowedGroups, Group $group ): Group {
		/** @var GroupRepository $groupRepository */
		$groupRepository = self::createGroupRepo();

		foreach ( $allowedGroups as &$allowedGroup ) {
			if ( $group->getId() === $allowedGroup->getId() ||
			     in_array( $allowedGroup->getId(), $group->calculateRootPath( $groupRepository ) ) ) {
				return $group;
			}
		}

		abort( 403, 'Not authorized.' );
	}

	/**
	 * Asserts that the given member is in any of the given root groups.
	 * Else the request is aborted with a 403 status code.
	 *
	 * @param Group[] $allowedGroups
	 * @param Member $member
	 *
	 * @return Member
	 *
	 * @throws \App\Exceptions\GroupNotFoundException
	 * @throws \App\Exceptions\WeblingAPIException
	 * @throws \Webling\API\ClientException
	 */
	public static function assertAllowedMember( array $allowedGroups, Member $member ): Member {
		foreach ( $allowedGroups as &$rootGroup ) {
			if ( $member->isDescendantOf( $rootGroup ) ) {
				return $member;
			}
		}

		abort( 403, 'Not authorized.' );
	}
}
