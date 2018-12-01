<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 14.10.18
 * Time: 20:30
 */


namespace App\Repository\Member;


use App\Exceptions\InvalidFixedValueException;
use App\Exceptions\InvalidRevisionIdException;
use App\Exceptions\MemberNotFoundException;
use App\Exceptions\MemberUnknownFieldException;
use App\Exceptions\MultiSelectOverwriteException;
use App\Exceptions\ValueTypeException;
use App\Exceptions\WeblingAPIException;
use App\Exceptions\WeblingFieldMappingConfigException;
use App\Repository\Member\Field\Field;
use App\Repository\Repository;
use App\Repository\Revision\RevisionRepository;
use Webling\API\ClientException;

class MemberRepository extends Repository {
	/**
	 * The maximum of members that should be queried in one turn.
	 *
	 * If more members are queried, it is split up in multiple requests.
	 */
	const QUERY_MEMBER_MAX = 100;
	
	/**
	 * Find the master record of a given member (or member id)
	 *
	 * @param int|Member $input id or member instance
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
	 * @throws ClientException
	 * @throws InvalidFixedValueException
	 * @throws InvalidRevisionIdException
	 * @throws MemberNotFoundException
	 * @throws MemberUnknownFieldException
	 * @throws MultiSelectOverwriteException
	 * @throws ValueTypeException
	 * @throws WeblingAPIException
	 * @throws WeblingFieldMappingConfigException
	 *
	 * @see https://gruenesandbox.webling.ch/api#replicate
	 */
	public function getUpdated( int $revisionId ): array {
		$repository = new RevisionRepository( $this->api_key, $this->api_url );
		$revision   = $repository->get( $revisionId );
		
		return $this->getMultiple( $revision->getMemberIds() );
	}
	
	/**
	 * Get multiple members by id.
	 *
	 * If more than self::QUERY_MEMBER_MAX ids are queried, Webling ist queried
	 * multiple times with at most members self::QUERY_MEMBER_MAX per request.
	 *
	 * @param array $memberIds
	 *
	 * @return Member[]
	 *
	 * @throws ClientException
	 * @throws InvalidFixedValueException
	 * @throws MemberNotFoundException
	 * @throws MemberUnknownFieldException
	 * @throws MultiSelectOverwriteException
	 * @throws ValueTypeException
	 * @throws WeblingAPIException
	 * @throws WeblingFieldMappingConfigException
	 */
	private function getMultiple( array $memberIds ): array {
		$blocks = array_chunk( $memberIds, self::QUERY_MEMBER_MAX );
		
		$members = [];
		foreach ( $blocks as $block ) {
			$ids = implode( ',', $block );
			
			$resp = $this->apiGet( "member/$ids" );
			
			if ( $resp->getStatusCode() === 200 ) {
				$newMembers = $this->getMembersFromWeblingPayload( $resp->getData(), $block );
				$members    += $newMembers;
			} else if ( $resp->getStatusCode() === 404 ) {
				throw new MemberNotFoundException();
			} else {
				throw new WeblingAPIException( "Get request to Webling failed with status code {$resp->getStatusCode()}" );
			}
		}
		
		return $members;
	}
	
	/**
	 * Convert the payload of a webling member response into an array of members
	 *
	 * @param array $payload the data from the Webling response
	 * @param int[] $ids the webling ids that were requested
	 *
	 * @return Member[] with the webling ids as key and the member as value
	 *
	 * @throws InvalidFixedValueException
	 * @throws MemberUnknownFieldException
	 * @throws MultiSelectOverwriteException
	 * @throws ValueTypeException
	 * @throws WeblingFieldMappingConfigException
	 */
	private function getMembersFromWeblingPayload( array $payload, array $ids ): array {
		/*
		 * Normalize webling payload
		 *
		 * Put single member response payload into array, so it has the same
		 * form as a multi member response payload.
		 */
		if ( 1 === count( $ids ) ) {
			$payload['id'] = $ids[0];
			$payload       = [ $payload ];
		}
		
		$members = [];
		foreach ( $payload as $memberData ) {
			$fieldData = $memberData['properties'];
			$groups    = $this->getGroups( $memberData['parents'] );
			$id        = $memberData['id'];
			
			$members[ $id ] = new Member( $fieldData, $id, $groups, true );
		}
		
		return $members;
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
		return [ 100 ]; // todo: remove this mock
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
	 * Find members using a webling query string.
	 *
	 * Use the query syntax as documented by webling (without the '?filter=').
	 * You may use the internal field names and values.
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
	 * @return Member On insert, it contains the id after saving.
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
	 * @see https://gruenesandbox.webling.ch/api#member-member-list-post
	 * @see https://gruenesandbox.webling.ch/api#member-member-put
	 */
	public function save( Member $member ): Member {
		// only save dirty fields
		$dirtyFields = $member->getDirtyFields();
		
		// get array of fields formed for the webling api
		$fields = $this->makeWeblingFieldArray( $dirtyFields );
		
		// get array of groups formed for the webling api
		// todo: implement this
		$groups = [ '100' ]; // todo: remove this mock
		
		// bring data into the form, webling wants
		$data = [
			'properties' => $fields,
			'parents'    => $groups
		];
		
		$id = $member->id;
		
		if ( $id ) {
			// update
			if ( $data ) { // only send request, if data has changed
				$resp = $this->apiPut( "member/$id", $data );
				if ( $resp->getStatusCode() !== 204 ) {
					throw new WeblingAPIException( "Put request to Webling failed with status code {$resp->getStatusCode()}" );
				}
			}
			
		} else {
			// create
			$resp = $this->apiPost( 'member', $data );
			if ( $resp->getStatusCode() !== 201 ) {
				throw new WeblingAPIException( "Post request to Webling failed with status code {$resp->getStatusCode()}" );
			}
			$id = $resp->getData();
		}
		
		return $this->get( $id );
	}
	
	/**
	 * Transform fields into an array the webling api understands
	 *
	 * @param Field[] $fields
	 *
	 * @return array
	 */
	private function makeWeblingFieldArray( array $fields ): array {
		$apiData = [];
		
		foreach ( $fields as $field ) {
			$apiData[ $field->getWeblingKey() ] = $field->getWeblingValue();
		}
		
		return $apiData;
	}
	
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
		$result = $this->getMultiple( [ $id ] );
		
		return array_pop( $result );
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
	
	/**
	 * Delete member in Webling.
	 *
	 * Note: Think twice, if you want to delete this member. There might be some
	 * accounting data left over without a linked member.
	 *
	 * @param int|Member $input id or member instance
	 *
	 * @throws WeblingAPIException
	 * @throws ClientException
	 */
	public function delete( $input ) {
		$id = $input instanceof Member ? $input->id : $input;
		
		$data = $this->apiDelete( "member/$id" );
		
		if ( $data->getStatusCode() !== 204 ) {
			throw new WeblingAPIException( "Delete request to Webling failed with status code {$data->getStatusCode()}" );
		}
	}
}
