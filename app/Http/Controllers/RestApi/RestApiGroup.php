<?php
/**
 * Created by PhpStorm.
 * User: adrian
 * Date: 27.11.18
 * Time: 20:36
 */

namespace App\Http\Controllers\RestApi;


use App\Repository\Group\GroupRepository;

class RestApiGroup
{
    public function getGroup($id) {
        $groupRepository = new GroupRepository(config('app.webling_api_key'), config('app.webling_base_url'));
        $data = $groupRepository->get($id);
        return json_encode($data);
    }
}