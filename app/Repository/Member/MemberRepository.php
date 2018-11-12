<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 14.10.18
 * Time: 20:30
 */

/** @noinspection PhpSignatureMismatchDuringInheritanceInspection */

namespace App\Repository\Member;


use App\Exceptions\InvalidFixedValueException;
use App\Exceptions\MemberNotFoundException;
use App\Exceptions\MemberSaveException;
use App\Exceptions\MemberUnknownFieldException;
use App\Exceptions\MultiSelectOverwriteException;
use App\Exceptions\ValueTypeException;
use App\Exceptions\WeblingAPIException;
use App\Exceptions\WeblingFieldMappingConfigException;
use App\Repository\Repository;
use Webling\API\ClientException;

class MemberRepository extends Repository {
	
	/**
	 * Get member from webling by id
	 *
	 * @param int $id
	 *
	 * @return Member
	 *
	 * @throws ClientException on connection error
	 * @throws InvalidFixedValueException
	 * @throws MemberUnknownFieldException
	 * @throws MultiSelectOverwriteException
	 * @throws ValueTypeException
	 * @throws WeblingFieldMappingConfigException
	 * @throws MemberNotFoundException
	 * @throws WeblingAPIException
	 *
	 * @see https://gruenesandbox.webling.ch/api#header-error-status-codes
	 */
	public function get( int $id ): Member {
		$data = parent::get( "member/$id" );
		
		if ( $data->getStatusCode() === 200 ) {
			$memberData = $data->getData();
			$fieldData  = $memberData['properties'];
			$groups     = $this->getGroups( $memberData['parents'] );
			
			return new Member( $fieldData, $id, $groups, true );
		}
		
		if ( $data->getStatusCode() === 404 ) {
			throw new MemberNotFoundException();
		} else {
			throw new WeblingAPIException( "Request to Webling failed with status code {$data->getStatusCode()}" );
		}
	}
	
	/**
	 * Convert group ids in array into Groups
	 *
	 * @param int[] $groupIds
	 *
	 * @return Group[]
	 */
	private function getGroups( array $groupIds ): array {
		// todo: implement this
//		$groupRepository = new GroupRepository();
//
//		$groups = [];
//		foreach ( $groupIds as $groupId ) {
//			$groups[] = $groupRepository->get( $groupId );
//		}
//
//		return $groups;
	}
	
	/**
	 * Find the master record of a given member (or member id)
	 *
	 * @param int|Member $input
	 *
	 * @return Member
	 */
	public function getMaster( $input ): Member {
		// todo: implement this
	}
	
	/**
	 * Return array of members that have changed since the given revision
	 *
	 * @param int $revisionId
	 *
	 * @return Member[]
	 *
	 * @see https://gruenesandbox.webling.ch/api#replicate
	 */
	public function getUpdated( int $revisionId ): array {
		// todo: implement this
	}
	
	/**
	 * Find members using a webling query string.
	 *
	 * Use the query syntax as documented by webling. You may use the internal
	 * field names and values.
	 *
	 * Note: The query string must not be encoded.
	 *
	 * @param string $query
	 *
	 * @return Member[]
	 *
	 * @see https://gruenesandbox.webling.ch/api#header-query-language
	 */
	public function find( string $query ): array {
		// todo: implement this
	}
	
	/**
	 * Save member in Webling.
	 *
	 * @param Member $member
	 *
	 * @return Member On insert, it conatins the id after saving.
	 *
	 * @throws MemberSaveException
	 */
	public function save( Member $member ) {
		// todo: implement this
	}
	
	/**
	 * Check if the given member does already exists in Webling somewhere below
	 * the given root group.
	 *
	 * @param Member $member
	 * @param Group[] $rootGroups
	 *
	 * @return MemberMatch unambiguous matches return the
	 * matched Member else a MemberMatch object is returned.
	 */
	public function findExisting( Member $member, array $rootGroups ): MemberMatch {
		// todo: implement this
	}
}
