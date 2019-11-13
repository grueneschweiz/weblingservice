<?php
/**
 * Created by PhpStorm.
 * User: cyrill.bolliger
 * Date: 2019-03-21
 * Time: 10:00
 */

namespace App\Console\Commands;


use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class AddClientTest extends TestCase
{
    
    public function testHandle_successful()
    {
        $key = 'secret';
        
        $exitCode = Artisan::call('client:add', [
            'name' => 'Unit Test',
            'webling-key' => $key,
            '--root-group' => [10]
        ]);
        
        $output = Artisan::output();
        
        preg_match("/Client ID: (\d+)/", $output, $clientId);
        preg_match("/Root groups: (\d+)/", $output, $rootGroups);
    
        $clientId = (int)$clientId[1];
        $rootGroups = $rootGroups[1];
    
        $keyModel = \App\WeblingKey::where('client_id', $clientId)->first();
        
        // clean up before asserting
        Artisan::call('client:delete', ['id' => [$clientId]]);
        
        $this->assertEquals(0, $exitCode);
        $this->assertTrue(1 === preg_match("/Client secret: \w+/", $output));
        $this->assertTrue(1 === preg_match("/Webling Key: \w+/", $output));
        $this->assertTrue(1 === preg_match("/New client created successfully./", $output));
        $this->assertEquals('10', $rootGroups);
        $this->assertNotEmpty($clientId);
        $this->assertEquals($key, Crypt::decryptString($keyModel->api_key));
    }
    
    public function testHandle_successfulMultipleGroupsShorthand()
    {
        $exitCode = Artisan::call('client:add', [
            'name' => 'Unit Test',
            'webling-key' => 'secret',
            '-g' => [10, 20]
        ]);
        
        $output = Artisan::output();
        
        preg_match("/Client ID: (\d+)/", $output, $clientId);
        preg_match("/Root groups: (\d+), (\d+)/", $output, $rootGroups);
        
        // clean up before asserting
        Artisan::call('client:delete', ['id' => [(int)$clientId[1]]]);
        
        $this->assertEquals(0, $exitCode);
        $this->assertTrue(1 === preg_match("/Client secret: \w+/", $output));
        $this->assertTrue(1 === preg_match("/New client created successfully./", $output));
        $this->assertEquals('10', $rootGroups[1]);
        $this->assertEquals('20', $rootGroups[2]);
        $this->assertNotEmpty($clientId[1]);
    }
    
    public function testHandle_missingName()
    {
        $this->expectException(\Symfony\Component\Console\Exception\RuntimeException::class);
        
        $this->artisan('client:add', [
            '--root-group' => [10]
        ]);
    }
    
    public function testHandle_missingKey()
    {
        $this->expectException(\Symfony\Component\Console\Exception\RuntimeException::class);
        
        $this->artisan('client:add', [
            'name' => 'Unit Test',
            '--root-group' => [10]
        ]);
    }
    
    public function testHandle_noGroup()
    {
        $this->artisan('client:add', [
            'name' => 'asdf',
            'webling-key' => 'secret',
        ])->assertExitCode(1);
    }
    
    public function testHandle_invalidGroup()
    {
        $this->artisan('client:add', [
            'name' => 'asdf',
            'webling-key' => 'secret',
            '-g' => ['asdf']
        ])->assertExitCode(1);
    }
}
