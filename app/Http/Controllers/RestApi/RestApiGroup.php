<?php
/**
 * Created by PhpStorm.
 * User: adrian
 * Date: 27.11.18
 * Time: 20:36
 */

namespace App\Http\Controllers\RestApi;


use App\Repository\Group\GroupRepository;
use Illuminate\Http\Request;

class RestApiGroup
{
    public function getGroup(Request $request, int $id)
    {
        $groupRepository = new GroupRepository(config('app.webling_api_key'), config('app.webling_base_url'));
        $group = $groupRepository->get($id);
        
        $allowedGroups = ApiHelper::getAllowedGroups($request);
        ApiHelper::assertAllowedGroup($allowedGroups, $group);
        
        return json_encode($group);
    }
}