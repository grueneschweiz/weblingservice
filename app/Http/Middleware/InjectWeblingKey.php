<?php

namespace App\Http\Middleware;

use App\WeblingKey;
use Closure;
use Illuminate\Support\Facades\Crypt;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;

class InjectWeblingKey
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
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
        $clientId = $jwt->claims()->get('aud')[0];
        
        $keyModel = WeblingKey::where('client_id', $clientId)->first();
        $key = Crypt::decryptString($keyModel->api_key);
    
        $request->headers->set('db_key', $key, false);
        
        return $next($request);
    }
}
