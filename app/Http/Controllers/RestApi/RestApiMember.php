<?php

namespace App\Http\Controllers\RestApi;

use App\Repository\Group\GroupRepository;

/**
 * Class RestApiMember
 *
 * Manages all API resources connected to the Member
 */
class RestApiMember {

	/**
	 * Return a json with the member fields
	 *
	 * @param $request - the http Request
	 * @param $member_id - the id of the member that we should get
	 * @param $is_admin - is the call by an admin resource (i.e. should we return
	 *                     all information about the member)
	 *
	 * @return string  the JSON
	 */
	public function getMember( $request, $member_id, $is_admin = false ) {
		ApiHelper::checkIntegerInput( $member_id );
		$memberRepo = ApiHelper::createMemberRepo( $request->header( $key = 'db_key' ) );

		$member = $memberRepo->get( $member_id );
		ApiHelper::assertAllowedMember( ApiHelper::getAllowedGroups( $request ), $member );

		$data = ApiHelper::getMemberAsArray( $member, $is_admin );

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
	 * @param $is_admin - is the call by an admin resource (i.e. should we return
	 *                     all information about the member)
	 *
	 * @return string  the JSON
	 */
	public function getMainMember( $request, $member_id, $group_ids, $is_admin = false ) {
		$allowedGroups = ApiHelper::getAllowedGroups( $request );

		$group_ids       = explode( ',', $group_ids );
		$requestedGroups = [];

		/** @var GroupRepository $groupRepository */
		$groupRepository = ApiHelper::createGroupRepo( $request->header( $key = 'db_key' ) );

		foreach ( $group_ids as $groupId ) {
			ApiHelper::checkIntegerInput( $groupId );

			$group = $groupRepository->get( (int) $groupId );
			$requestedGroups[ (int) $groupId ] = $group;

			ApiHelper::assertAllowedGroup( $allowedGroups, $group );
		}

		ApiHelper::checkIntegerInput( $member_id );
		$memberRepo = ApiHelper::createMemberRepo( $request->header( $key = 'db_key' ) );

		$member = $memberRepo->getMaster( $member_id, $requestedGroups );

		$data = ApiHelper::getMemberAsArray( $member, $is_admin );

		return json_encode( $data );
	}

	/**
	 * Return a json with the all member that changed since the $revisionId
	 *
	 * @param $request - the http Request
	 * @param $revisionId - the id of the revision we want to get changes since
	 * @param $is_admin - is the call by an admin resource (i.e. should we return
	 *                     all information about the member)
	 *
	 * @return string the JSON
	 */
	public function getChanged( $request, $revisionId, $is_admin = false ) {
		$allowedGroups = ApiHelper::getAllowedGroups( $request );

		ApiHelper::checkIntegerInput( $revisionId );
		$memberRepo = ApiHelper::createMemberRepo( $request->header( $key = 'db_key' ) );

		if ( - 1 === (int) $revisionId ) {
			$members = $memberRepo->getAll( $allowedGroups );
		} else {
			$members = $memberRepo->getUpdated( $revisionId, $allowedGroups );
		}

		$data = [];
		foreach ( $members as $member ) {
			$data[ $member->id ] = ApiHelper::getMemberAsArray( $member, $is_admin );
		}

		return json_encode( $data );
	}
}
