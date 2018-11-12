<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 12.11.18
 * Time: 15:41
 */

/** @noinspection PhpSignatureMismatchDuringInheritanceInspection */

namespace App\Repository\Group;


use App\Repository\Repository;

class GroupRepository extends Repository {
	
	/**
	 * Get group by id. Serve from cache if not specified otherwise.
	 *
	 * @param int $id
	 * @param bool $cached
	 *
	 * @return Group
	 */
	public function get(int $id, bool $cached = true) {
		// todo: implement this
	}
	
	/**
	 * Update the groups cache.
	 */
	public function updateCache() {
		// todo: implement this
		// note: we have to handle php timeouts
	}
}
