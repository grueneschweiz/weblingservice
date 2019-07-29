<?php

namespace App\Http\Controllers\RestApi;

use App\Exceptions\BadRequestException;
use App\Exceptions\IllegalFieldUpdateMode;
use App\Repository\Group\GroupRepository;
use App\Repository\Member\Member;
use App\Repository\Member\MemberMatch;
use Illuminate\Http\Request;

/**
 * Class RestApiMember
 *
 * Manages all API resources connected to the Member
 */
class RestApiMember {
	private const MODE_REPLACE = 'replace';
	private const MODE_REPLACE_EMPTY = 'replaceEmpty';
	private const MODE_APPEND = 'append';
	private const MODE_ADD_IF_NEW = 'addIfNew';

	/**
	 * Return a json with the member fields
	 *
	 * @param $request - the http Request
	 * @param $member_id - the id of the member that we should get
	 * @param bool $is_admin - is the call by an admin resource (i.e. should we return
	 *                     all information about the member)
	 *
	 * @return string  the JSON
	 *
	 * @throws \App\Exceptions\GroupNotFoundException
	 * @throws \App\Exceptions\IllegalArgumentException
	 * @throws \App\Exceptions\InvalidFixedValueException
	 * @throws \App\Exceptions\MemberNotFoundException
	 * @throws \App\Exceptions\MemberUnknownFieldException
	 * @throws \App\Exceptions\MultiSelectOverwriteException
	 * @throws \App\Exceptions\ValueTypeException
	 * @throws \App\Exceptions\WeblingAPIException
	 * @throws \App\Exceptions\WeblingFieldMappingConfigException
	 * @throws \Webling\API\ClientException
	 */
	public function getMember( Request $request, $member_id, $is_admin = false ) {
		ApiHelper::checkIntegerInput( $member_id );
		$memberRepo = ApiHelper::createMemberRepo( $request->header( $key = 'db_key' ) );

		$allowedGroups = ApiHelper::getAllowedGroups( $request );

		$member = $memberRepo->get( $member_id );
		ApiHelper::assertAllowedMember( $allowedGroups, $member );

		$data = ApiHelper::getMemberAsArray( $member, $allowedGroups, $is_admin );

		return json_encode( $data );
	}

	/**
	 * Return a json with the member fields of the main record (!) determined
	 * by {@link MemberRepository::getMaster()}.
	 *
	 * @param $request - the http Request
	 * @param $member_id - the id of the member that we should get
	 * @param $group_ids - string with commaseparated list of ids of root group
	 *                     below which the main record should be searched
	 *                     {@see MemberRepository::getMaster()}
	 * @param bool $is_admin - is the call by an admin resource (i.e. should we return
	 *                     all information about the member)
	 *
	 * @return string  the JSON
	 *
	 * @throws \App\Exceptions\GroupNotFoundException
	 * @throws \App\Exceptions\IllegalArgumentException
	 * @throws \App\Exceptions\InvalidFixedValueException
	 * @throws \App\Exceptions\MemberNotFoundException
	 * @throws \App\Exceptions\MemberUnknownFieldException
	 * @throws \App\Exceptions\MultiSelectOverwriteException
	 * @throws \App\Exceptions\ValueTypeException
	 * @throws \App\Exceptions\WeblingAPIException
	 * @throws \App\Exceptions\WeblingFieldMappingConfigException
	 * @throws \Webling\API\ClientException
	 */
	public function getMainMember( Request $request, $member_id, $group_ids = null, $is_admin = false ) {
		$allowedGroups = ApiHelper::getAllowedGroups( $request );

		if ( $group_ids ) {
			$group_ids       = explode( ',', $group_ids );
			$requestedGroups = [];

			/** @var GroupRepository $groupRepository */
			$groupRepository = ApiHelper::createGroupRepo( $request->header( $key = 'db_key' ) );

			foreach ( $group_ids as $groupId ) {
				ApiHelper::checkIntegerInput( $groupId );

				$group                             = $groupRepository->get( (int) $groupId );
				$requestedGroups[ (int) $groupId ] = $group;

				ApiHelper::assertAllowedGroup( $allowedGroups, $group );
			}
		} else {
			$requestedGroups = $allowedGroups;
		}

		ApiHelper::checkIntegerInput( $member_id );
		$memberRepo = ApiHelper::createMemberRepo( $request->header( $key = 'db_key' ) );

		$member = $memberRepo->getMaster( $member_id, $requestedGroups );

		$data = ApiHelper::getMemberAsArray( $member, $allowedGroups, $is_admin );

		return json_encode( $data );
	}

	/**
	 * Return a json with the all member that changed since the $revisionId
	 *
	 * @param $request - the http Request
	 * @param $revisionId - the id of the revision we want to get changes since
	 * @param $limit - how many records should be retrieved at most
	 * @param $offset - how many records should be skipped
	 * @param bool $is_admin - is the call by an admin resource (i.e. should we return
	 *                     all information about the member)
	 *
	 * @return string the JSON
	 *
	 * @throws \App\Exceptions\GroupNotFoundException
	 * @throws \App\Exceptions\IllegalArgumentException
	 * @throws \App\Exceptions\InvalidFixedValueException
	 * @throws \App\Exceptions\InvalidRevisionArgumentsException
	 * @throws \App\Exceptions\MemberNotFoundException
	 * @throws \App\Exceptions\MemberUnknownFieldException
	 * @throws \App\Exceptions\MultiSelectOverwriteException
	 * @throws \App\Exceptions\RevisionNotFoundException
	 * @throws \App\Exceptions\ValueTypeException
	 * @throws \App\Exceptions\WeblingAPIException
	 * @throws \App\Exceptions\WeblingFieldMappingConfigException
	 * @throws \Webling\API\ClientException
	 */
	public function getChanged( Request $request, $revisionId, $limit, $offset, $is_admin = false ) {
		$allowedGroups = ApiHelper::getAllowedGroups( $request );

		ApiHelper::checkIntegerInput( $revisionId );
		ApiHelper::checkIntegerInput( $limit );
		ApiHelper::checkIntegerInput( $offset );

		$memberRepo = ApiHelper::createMemberRepo( $request->header( $key = 'db_key' ) );
		$memberRepo->setLimit( (int) $limit );
		$memberRepo->setOffset( (int) $offset );

		if ( - 1 === (int) $revisionId ) {
			$members = $memberRepo->getAll( $allowedGroups );
		} else {
			$members = $memberRepo->getUpdated( $revisionId, $allowedGroups );
		}

		$data = [];
		foreach ( $members as $id => $member ) {
			if ( empty( $member ) ) {
				$data[ $id ] = null;
			} else {
				$data[ $id ] = ApiHelper::getMemberAsArray( $member, $allowedGroups, $is_admin );
			}
		}

		return json_encode( $data );
	}

	/**
	 * Update the given member.
	 *
	 * @param Request $request - the http Request with the member data
	 * @param number $memberId
	 *
	 * @return int member id
	 *
	 * @throws IllegalFieldUpdateMode
	 * @throws \App\Exceptions\GroupNotFoundException
	 * @throws \App\Exceptions\IllegalArgumentException
	 * @throws \App\Exceptions\InvalidFixedValueException
	 * @throws \App\Exceptions\MemberNotFoundException
	 * @throws \App\Exceptions\MemberUnknownFieldException
	 * @throws \App\Exceptions\MultiSelectOverwriteException
	 * @throws \App\Exceptions\NoGroupException
	 * @throws \App\Exceptions\ValueTypeException
	 * @throws \App\Exceptions\WeblingAPIException
	 * @throws \App\Exceptions\WeblingFieldMappingConfigException
	 * @throws \Webling\API\ClientException
	 * @throws BadRequestException
	 */
	public function updateMember( Request $request, $memberId ) {
		ApiHelper::checkIntegerInput( $memberId );
		$memberRepo = ApiHelper::createMemberRepo( $request->header( $key = 'db_key' ) );

		$member = $memberRepo->get( $memberId );
		ApiHelper::assertAllowedMember( ApiHelper::getAllowedGroups( $request ), $member );

		$memberData = $this->extractMemberData( $request );
		$patched    = $this->patchMember( $request, $member, $memberData );

		return $memberRepo->save( $patched )->id;
	}

	/**
	 * Update or insert the given member.
	 *
	 * @param Request $request - the http Request with the member data
	 *
	 * @return int member id
	 *
	 * @throws BadRequestException
	 * @throws IllegalFieldUpdateMode
	 * @throws \App\Exceptions\GroupNotFoundException
	 * @throws \App\Exceptions\IllegalArgumentException
	 * @throws \App\Exceptions\InvalidFixedValueException
	 * @throws \App\Exceptions\MemberNotFoundException
	 * @throws \App\Exceptions\MemberUnknownFieldException
	 * @throws \App\Exceptions\MultiSelectOverwriteException
	 * @throws \App\Exceptions\NoGroupException
	 * @throws \App\Exceptions\ValueTypeException
	 * @throws \App\Exceptions\WeblingAPIException
	 * @throws \App\Exceptions\WeblingFieldMappingConfigException
	 * @throws \Webling\API\ClientException
	 */
	public function upsertMember( Request $request ) {
		$memberData = $this->extractMemberData( $request );

		// update the given member, if it contains an id
		if ( ! empty( $memberData[ Member::KEY_ID ] ) ) {
			$idField  = $memberData[ Member::KEY_ID ];
			$memberId = isset( $idField['value'] ) ? $idField['value'] : $idField;

			return $this->updateMember( $request, $memberId );
		}

		$member  = new Member();
		$patched = $this->patchMember( $request, $member, $memberData, true );

		$memberRepo = ApiHelper::createMemberRepo( $request->header( $key = 'db_key' ) );
		$match      = $memberRepo->findExisting( $member, ApiHelper::getAllowedGroups( $request ) );

		switch ( $match->getStatus() ) {
			case MemberMatch::NO_MATCH:
				return $memberRepo->save( $patched )->id;

			case MemberMatch::MATCH:
				$matches       = $match->getMatches();
				$matchedMember = reset( $matches );
				$patched       = $this->patchMember( $request, $matchedMember, $memberData );

				return $memberRepo->save( $patched )->id;

			case MemberMatch::MULTIPLE_MATCHES:
			case MemberMatch::AMBIGUOUS_MATCH:
				// todo: notify the responsible person
				//       (use the api client to get the email to notify.
				//       the client doesn't yet have an email field.)
				return $memberRepo->save( $patched )->id;

			default:
				throw new IllegalFieldUpdateMode( $match->getStatus() . ' is not defined' );
		}
	}

	/**
	 * Merge $data into $member
	 *
	 * @param Request $request
	 * @param Member $member
	 * @param array $data
	 * @param bool $forceReplace disrespect the field's mode and use replace mode if true
	 *
	 * @return Member
	 *
	 * @throws BadRequestException
	 * @throws \App\Exceptions\GroupNotFoundException
	 * @throws \App\Exceptions\InvalidFixedValueException
	 * @throws \App\Exceptions\MemberNotFoundException
	 * @throws \App\Exceptions\MemberUnknownFieldException
	 * @throws \App\Exceptions\MultiSelectOverwriteException
	 * @throws \App\Exceptions\ValueTypeException
	 * @throws \App\Exceptions\WeblingAPIException
	 * @throws \App\Exceptions\WeblingFieldMappingConfigException
	 * @throws \Webling\API\ClientException
	 * @throws IllegalFieldUpdateMode
	 */
	private function patchMember( Request &$request, Member &$member, array $data, $forceReplace = false ): Member {
		foreach ( $data as $fieldKey => $field ) {
			if ( Member::KEY_ID === $fieldKey ) {
				continue;
			}

			if ( $forceReplace && is_array( $field ) ) {
				$field['mode'] = self::MODE_REPLACE;
			}

			if ( ! is_array( $field ) || ! array_key_exists( 'mode', $field ) || ! array_key_exists( 'value', $field ) ) {
				throw new BadRequestException( 'Malformed data in field: ' . $fieldKey );
			}

			if ( Member::KEY_GROUPS === $fieldKey ) {
				$this->patchGroups( $request, $member, $field );
			} else {
				$this->patchField( $member, $field, $fieldKey );
			}
		}

		return $member;
	}

	/**
	 * Returns the member data form the given request
	 *
	 * @param Request $request
	 *
	 * @return array
	 * @throws BadRequestException
	 */
	private function extractMemberData( Request &$request ): array {
		$memberData = json_decode( $request->getContent(), true );
		if ( ! $memberData ) {
			throw new BadRequestException( 'Missing request content data.' );
		}

		if ( ! is_array( $memberData ) ) {
			throw new BadRequestException( 'Malformed member data.' );
		}

		return $memberData;
	}

	/**
	 * Update the groups of the given member
	 *
	 * @param Request $request
	 * @param Member $member
	 * @param array $data => [ value => [ group ids ], mode => replace/append ]
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
	 * @throws IllegalFieldUpdateMode
	 */
	private function patchGroups( Request &$request, Member &$member, array $data ) {
		$groups        = [];
		$groupRepo     = ApiHelper::createGroupRepo( $request->header( $key = 'db_key' ) );
		$allowedGroups = ApiHelper::getAllowedGroups( $request );

		foreach ( (array) $data['value'] as $group ) {
			$g = $groupRepo->get( $group );
			ApiHelper::assertAllowedGroup( $allowedGroups, $g );
			$groups[] = $g;
		}

		switch ( $data['mode'] ) {
			case self::MODE_APPEND:
				$member->addGroups( $groups );
				break;

			case self::MODE_REPLACE:
				$member->setGroups( $groups );
				break;

			case self::MODE_REPLACE_EMPTY:
				if ( empty( $member->getGroupIds() ) ) {
					$member->setGroups( $groups );
				}
				break;

			case self::MODE_ADD_IF_NEW:
				if ( null === $member->id ) {
					$member->setGroups( $groups );
				}
				break;

			default:
				throw new IllegalFieldUpdateMode( "The update mode '{$data['mode']}' for the field '" . Member::KEY_GROUPS . "' is not supported." );
		}
	}

	/**
	 * Update the given field of the given member
	 *
	 * @param Member $member
	 * @param array $data
	 * @param string $key
	 *
	 * @throws IllegalFieldUpdateMode
	 */
	private function patchField( Member &$member, array $data, string $key ) {
		$mode = $data['mode'];

		if ( self::MODE_APPEND === $mode && ! method_exists( $member->$key, 'append' ) ) {
			throw new IllegalFieldUpdateMode( "The update mode '{$data['mode']}' for the field '$key' is not supported." );
		}

		switch ( $mode ) {
			case self::MODE_APPEND:
				$member->$key->append( $data['value'] );
				break;

			case self::MODE_REPLACE:
				$member->$key->setValue( $data['value'] );
				break;

			case self::MODE_REPLACE_EMPTY:
				if ( empty( $member->$key->getValue() ) ) {
					$member->$key->setValue( $data['value'] );
				}
				break;

			case self::MODE_ADD_IF_NEW:
				if ( null === $member->id ) {
					$member->$key->setValue( $data['value'] );
				}
				break;

			default:
				throw new IllegalFieldUpdateMode( "The update mode '{$data['mode']}' for the field '$key' is not supported." );
		}
	}
}
