<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 12.11.18
 * Time: 14:46
 */

namespace App\Repository\Member;


class MemberMatch {
	/**
	 * No member in webling matched the given member
	 */
	const NO_MATCH = 0;
	
	/**
	 * It's an unambiguous match of exaclty one member
	 */
	const MATCH = 1;
	
	/**
	 * There was a match, but it wasn't unique enough (not enough unambiguous
	 * fields matched)
	 */
	const AMBIGUOUS_MATCH = 2;
	
	/**
	 * There were multiple matches
	 */
	const MULTIPLE_MATCHES = 3;
	
	/**
	 * The matching status
	 *
	 * @var int
	 */
	private $status;
	
	/**
	 * The matches
	 *
	 * @var array
	 */
	private $matches;
	
	/**
	 * MemberMatch constructor.
	 *
	 * @param int $status
	 * @param Member[] $matches
	 */
	public function __construct( $status, array $matches ) {
		$this->status = $status;
		$this->matches = $matches;
	}
	
	/**
	 * Return the matches status
	 *
	 * @return int
	 */
	public function getStatus(): int {
		return $this->status;
	}
	
	/**
	 * Return the matches
	 *
	 * @return array empty on no match
	 */
	public function getMatches(): array {
		return $this->matches;
	}
	
	/**
	 * Return the number of matches
	 *
	 * @return int
	 */
	public function count(): int {
		return count($this->matches);
	}
}
