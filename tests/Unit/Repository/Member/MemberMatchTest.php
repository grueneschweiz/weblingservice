<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 15.11.18
 * Time: 18:36
 */

namespace App\Repository\Member;


use Tests\TestCase;

class MemberMatchTest extends TestCase {
	
	public function test__construct() {
		$matchStubs = [ 'stub1', 'stub2' ];
		$match      = new MemberMatch( MemberMatch::MULTIPLE_MATCHES, $matchStubs );
		$this->assertEquals( MemberMatch::MULTIPLE_MATCHES, $match->getStatus() );
		$this->assertEquals( $matchStubs, $match->getMatches() );
	}
	
	public function testCount() {
		$matchStubs = [ 'stub1', 'stub2' ];
		$match      = new MemberMatch( MemberMatch::MULTIPLE_MATCHES, $matchStubs );
		$this->assertEquals( count( $matchStubs ), $match->count() );
		
	}
}
