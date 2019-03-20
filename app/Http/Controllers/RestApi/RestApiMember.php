<?php

namespace App\Http\Controllers\RestApi;

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
		$group_ids = explode( ',', $group_ids );
		$groups    = [];

		$groupRepository = ApiHelper::createGroupRepo( $request->header( $key = 'db_key' ) );

		foreach ( $group_ids as $groupId ) {
			ApiHelper::checkIntegerInput( $groupId );
			$groups[] = $groupRepository->get( (int) $groupId );
		}

		ApiHelper::checkIntegerInput( $member_id );
		$memberRepo = ApiHelper::createMemberRepo( $request->header( $key = 'db_key' ) );

		$member = $memberRepo->getMaster( $member_id, $groups );

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
		ApiHelper::checkIntegerInput( $revisionId );
		$memberRepo = ApiHelper::createMemberRepo( $request->header( $key = 'db_key' ) );

		if ( - 1 === (int) $revisionId ) {
			$members = $memberRepo->getAll();
		} else {
			$members = $memberRepo->getUpdated( $revisionId );
		}

		$data = [];
		foreach ( $members as $member ) {
			$data[ $member->id ] = ApiHelper::getMemberAsArray( $member, $is_admin );
		}

		return json_encode( $data );
	}

}
