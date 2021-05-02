<?php

namespace App\Http\Middleware;

use Closure;
use Laravel\Passport\ClientRepository;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;

/**
 * Add the allowed root groups to the request.
 *
 * @package App\Http\Middleware
 */
class AddAllowedRootGroups
{
    protected $clientRepository = null;
    
    public function __construct(ClientRepository $clientRepository)
    {
        $this->clientRepository = $clientRepository;
    }
    
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /**
         * get client from token
         *
         * @see https://github.com/laravel/passport/issues/143
         */
        $jwt = (new Parser(new JoseEncoder()))->parse($request->bearerToken());
        $client_id = $jwt->claims()->get('aud')[0];
        
        // add root groups to header
        $rootGroups = \App\ClientGroup::where('client_id', (int)$client_id)->get();
        
        $groupIds = [];
        foreach ($rootGroups as $group) {
            $groupIds[] = $group->root_group;
        }
        
        $request->merge(['allowed_groups' => $groupIds]);
        
        return $next($request);
    }
}
