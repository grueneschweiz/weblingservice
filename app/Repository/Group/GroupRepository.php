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
     * Directory where the json files are cached
     * @var string
     */
    private $cacheDirectory;

    /**
     * GroupRepository constructor.
     * @param string $api_key
     * @param string|null $api_url
     * @throws ClientException
     */
    public function __construct(string $api_key, ?string $api_url = null)
    {
        parent::__construct($api_key, $api_url);
        $this->cacheDirectory = rtrim(config('app.cache_directory'), '/');
    }

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
        $groupJson = null;

        if($cached) {
            $groupJson = $this->getFromCache($id);
        }

        if($groupJson === null) {
            $groupJson = $this->getFromApi($id);
            $this->putToCache($id, $groupJson);
        }

        return $this->groupFromWeblingJson($id, $groupJson);
	}

    /**
     * Loads a Group from the API (webling in this case).
     * @param int $id
     * @return string
     *
     * @throws GroupNotFoundException
     * @throws ClientException on connection error
     * @throws WeblingAPIException
     */
	private function getFromApi(int $id): ?string
    {
	    //ToDo

        $endpoint = "membergroup/$id";
        $data = $this->apiGet($endpoint);
        /** @noinspection TypeUnsafeComparisonInspection */
        if($data->getStatusCode() == 200) {
            return $data->getRawData();
        }

        if ( $data->getStatusCode() === 404 ) {
            throw new GroupNotFoundException($endpoint);
        } else {
            throw new WeblingAPIException( "Get request to Webling failed with status code {$data->getStatusCode()}" );
        }
    }

    /**
     * Loads a Group from the cache
     * @param int $id
     *
     * @return string|null
     */
    private function getFromCache(int $id): ?string
    {
        try {
            $maxAge = new \DateInterval(config('app.cache_max_age'));
            $fileNewerThan = (new \DateTime('now'))->sub($maxAge);
        } catch (\Exception $e) {
            //ToDo: log Format error
            return null;
        }

        $fileName = $this->generateCacheFileName($id);

        if (file_exists($fileName) && $fileNewerThan->getTimestamp() < filemtime($fileName)) {
            return file_get_contents($fileName);
        } else {
            return null;
        }
    }

    /**
     * Stores a group in the cache,
     * updates the record in the cache if there is already an older record of this group in the cache
     * @param $id int
     * @param $jsonString string
     */
    private function putToCache(int $id, string $jsonString): void
    {
        file_put_contents($this->generateCacheFileName($id), $jsonString);
    }

    /**
     * @param $id
     * @return string
     */
    private function generateCacheFileName(int $id): string
    {
        return $this->cacheDirectory . '/group/' . $id . '.json';
    }
	
	/**
	 * Update the groups cache.
	 *
     *
	 * @see https://gruenesandbox.webling.ch/api#header-error-status-codes
	 */
	public function updateCache(): void
    {
		// todo: implement this
		// note: we have to handle php timeouts

        //Todo: delete json files older than eg. 36h
        $this->deleteCacheOlderThan(rtrim(config('app.cache_directory'), '/') . '/group/', config('app.cache_delete_after'));
	}

    /**
     * @param string $directory
     * @param string $intervalString
     */
	private function deleteCacheOlderThan(string $directory, string $intervalString): void
    {
	    try {
    	    $interval = new \DateInterval($intervalString);
    	    $timestamp = (new \DateTime('now'))->sub($interval)->getTimestamp();
	    } catch (\Exception $e) {
	        //ToDo: log format error
            return;
        }
	    $files = scandir($directory, SCANDIR_SORT_NONE);
	    foreach ($files as $file) {
	        $file = $directory . $file;
	        if(is_file($file) && filemtime($file) < $timestamp) {
	            unlink($file);
            }
        }
    }

    /**
     * @param int $id
     * @param $jsonString string
     * @return Group
     * @throws ClientException
     * @throws GroupNotFoundException
     * @throws WeblingAPIException
     */
	private function groupFromWeblingJson(int $id, string $jsonString): ?Group
    {
	    $group = new Group();
	    $group->setId($id);

        $data = json_decode($jsonString);
        /** @noinspection TypeUnsafeComparisonInspection */
        if(json_last_error() == JSON_ERROR_NONE) {
            if(isset($data->properties, $data->properties->title)) {
                $group->setName($data->properties->title);
            }

            if(isset($data->children)) {
                if(isset($data->children->membergroup)) {
                    $group->setChildren($data->children->membergroup);
                }
                if(isset($data->children->member)) {
                    $group->setMembers($data->children->member);
                }
            }

            if(isset($data->parents[0])) {
                $group->setParent($data->parents[0]);
            }

            $group->calculateRootPath($this);

            return $group;
        } else {
            throw new WeblingAPIException('Invalid JSON from WeblingAPI. JSON_ERROR: ' .json_last_error());
        }
    }
}
