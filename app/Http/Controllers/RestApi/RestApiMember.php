<?php

namespace App\Http\Controllers\RestApi;

use App\Repository\Member\Member;
use App\Repository\Member\MemberRepository;
use App\Repository\Member\Field\FieldFactory;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use App\Exceptions\IllegalArgumentException;

use App\Http\Controllers\RestApi;

use Illuminate\Http\Request;

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
	 * Return a json with the all member that changed since the $revisionId
	 *
	 * @return string the JSON
	 */
	public function getChanged( $request, $revisionId ) {
		ApiHelper::checkIntegerInput( $revisionId );
		$memberRepo = ApiHelper::createMemberRepo( $request->header( $key = 'db_key' ) );

		if ( - 1 === $revisionId ) {
			$members = $memberRepo->getAll();
		} else {
			$members = $memberRepo->getUpdated( $revisionId );
		}

		$data = [];
		foreach ( $members as $member ) {
			$data[ $member->id ] = ApiHelper::getMemberAsArray( $member );
		}

		return json_encode( $data );
	}

}
