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
        /**
         * @var Group
         */
        $group = null;

        if($cached) {
            $group = $this->getFromCache($id);
        }

        if($group != null) {
            //todo: check how long Group has been in cache, eventually reload from api
            return $group;
        }

        $group = $this->getFromApi($id);
        $this->putToCache($group);

        return $group;
	}

    /**
     * Loads a Group from the API (webling in this case).
     * @param int $id
     * @return Group
     *
     * @throws GroupNotFoundException
     * @throws ClientException on connection error
     * @throws WeblingAPIException
     */
	private function getFromApi(int $id) {
	    //ToDo
        $data = $this->apiGet("membergroup/$id");
        if($data->getStatusCode() == 200) {
            return new Group($data->getRawData());
        }

        if ( $data->getStatusCode() === 404 ) {
            throw new GroupNotFoundException();
        } else {
            throw new WeblingAPIException( "Get request to Webling failed with status code {$data->getStatusCode()}" );
        }
    }

    /**
     * Loads a Group from the cache
     * @param int $id
     *
     * @return Group
     *
     * @throws GroupNotFoundException if the group is not in the cache
     */
    private function getFromCache(int $id) {
        //Cache is not yet implemented, therefore Groups can't be found in it.
        //throw new GroupNotFoundException("Cache not yet implemented");
	    //ToDo: implement caching

        return null;
    }

    /**
     * Stores a group in the cache,
     * updates the record in the cache if there is already an older record of this group in the cache
     * @param Group $group
     */
    private function putToCache(Group $group) {
        //todo: implement caching
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
