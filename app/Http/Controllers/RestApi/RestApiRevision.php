<?php
/**
 * Created by PhpStorm.
 * User: cyrill
 * Date: 27.11.18
 * Time: 20:36
 */

namespace App\Http\Controllers\RestApi;


use App\Repository\Revision\RevisionRepository;

class RestApiRevision
{
	/**
	 * Return a json with the current revision id
	 *
	 * @return string JSON with the revision id
	 */
	public function getRevision() {
        $repository = new RevisionRepository(config('app.webling_api_key'), config('app.webling_base_url'));
        $data = $repository->getCurrentRevisionId();
        return json_encode($data);
    }
}
