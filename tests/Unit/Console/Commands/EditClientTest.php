<?php
/**
 * Created by PhpStorm.
 * User: cyrill.bolliger
 * Date: 2019-03-21
 * Time: 11:23
 */

namespace App\Console\Commands;


use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class EditClientTest extends TestCase
{
    private $clientId;
    
    public function setUp(): void
    {
        parent::setUp();
        
        Artisan::call('client:add', [
            'name' => 'Unit Test',
            'webling-key' => 'secret',
            '--root-group' => [10]
        ]);
        
        $output = Artisan::output();
        preg_match("/Client ID: (\d+)/", $output, $clientId);
        
        $this->clientId = (int)$clientId[1];
    }
    
    public function tearDown(): void
    {
        Artisan::call('client:delete', [
            'id' => [$this->clientId]
        ]);
        
        parent::tearDown();
    }
    
    public function testHandle_successfulName()
    {
        $this->artisan('client:edit', [
            'id' => $this->clientId,
            '--name' => 'Edit Unit Test'
        ])->expectsOutput('Successfully changed name.')
            ->assertExitCode(0);
    }
    
    public function testHandle_nonExistingClient()
    {
        $this->artisan('client:edit', [
            'id' => PHP_INT_MAX,
            '--name' => 'Edit Unit Test'
        ])->expectsOutput('No client with id: ' . PHP_INT_MAX)
            ->assertExitCode(1);
    }
    
    public function testHandle_successfulGroup()
    {
        $this->artisan('client:edit', [
            'id' => $this->clientId,
            '--root-group' => [20]
        ])->expectsOutput('Deleted groups: 10')
            ->expectsOutput('Added groups: 20')
            ->assertExitCode(0);
    }
    
    public function testHandle_successfulWeblingKey()
    {
        $key = 'new-secret';
        
        $this->artisan('client:edit', [
            'id' => $this->clientId,
            '--webling-key' => $key
        ])->expectsOutput('Changed Webling key to: ' . $key)
            ->assertExitCode(0);
        
        $keyModel = \App\WeblingKey::where('client_id', $this->clientId)->first();
        
        $this->assertEquals($key, Crypt::decryptString($keyModel->api_key));
    }
}
