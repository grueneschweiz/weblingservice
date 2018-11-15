<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 12.11.18
 * Time: 15:41
 */

namespace App\Repository\Group;


use App\Exceptions\GroupNotFoundException;
use App\Exceptions\WeblingAPIException;
use App\Repository\Repository;
use Webling\API\ClientException;

class GroupRepository extends Repository {
	
	/**
	 * Get group by id. Serve from cache if not specified otherwise.
	 *
	 * @param int $id
	 * @param bool $cached
	 *
	 * @return Group
	 *
	 * @throws GroupNotFoundException
	 * @throws ClientException on connection error
	 * @throws WeblingAPIException
	 *
	 * @see https://gruenesandbox.webling.ch/api#header-error-status-codes
	 */
	public function get(int $id, bool $cached = true): Group {
		// todo: implement this
	}
	
	/**
	 * Update the groups cache.
	 *
	 * @throws ClientException on connection error
	 * @throws WeblingAPIException
	 *
	 * @see https://gruenesandbox.webling.ch/api#header-error-status-codes
	 */
	public function updateCache() {
		// todo: implement this
		// note: we have to handle php timeouts
	}
}
