<?php
/**
 * Created by PhpStorm.
 * User: cyrill.bolliger
 * Date: 2019-03-06
 * Time: 11:12
 */

namespace Tests\Feature\Http\Controllers\RestApi;


use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AuthHelper
{
    private $token;
    private $id;
    private $testClass;
    private $secret;
    
    public function __construct(TestCase $testClass)
    {
        $this->testClass = $testClass;
    }
    
    public function getAuthHeader(array $allowedGroups = [100], string $scope = '')
    {
        return ['Authorization' => 'Bearer ' . $this->getToken($allowedGroups, $scope)];
    }
    
    public function getToken(array $allowedGroups, string $scope)
    {
        if ($this->token) {
            return $this->token;
        }
        
        $this->addClient($allowedGroups);
        
        $auth = $this->testClass->post('/oauth/token', [
                'grant_type' => 'client_credentials',
                'client_id' => $this->id,
                'client_secret' => $this->secret,
                'scope' => $scope,
            ]
        );
        
        $this->token = json_decode($auth->getContent())->access_token;
        
        return $this->token;
    }
    
    private function addClient(array $rootGroups)
    {
        Artisan::call('client:add', [
            'name' => 'Unit Test',
            '--root-group' => $rootGroups
        ]);
        
        $output = Artisan::output();
        
        preg_match("/Client ID: (\d+)/", $output, $clientId);
        preg_match("/Client secret: (\w+)/", $output, $secret);
        
        $this->id = (int)$clientId[1];
        $this->secret = $secret[1];
    }
    
    public function deleteToken()
    {
        if ($this->id) {
            Artisan::call('client:delete', ['id' => [$this->id]]);
        }
    }
}