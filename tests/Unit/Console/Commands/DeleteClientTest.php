<?php
/**
 * Created by PhpStorm.
 * User: cyrill.bolliger
 * Date: 2019-03-21
 * Time: 11:10
 */

namespace App\Console\Commands;


use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DeleteClientTest extends TestCase
{
    
    public function testHandle_successful()
    {
        $exitCode = Artisan::call('client:delete', [
            'id' => [$this->addClient()]
        ]);
        
        $output = Artisan::output();
        
        $this->assertEquals(0, $exitCode);
        $this->assertTrue(1 === preg_match("/Successfully deleted client/", $output));
    }
    
    private function addClient()
    {
        Artisan::call('client:add', [
            'name' => 'Unit Test',
            'webling-key' => 'secret',
            '--root-group' => [10]
        ]);
        
        $output = Artisan::output();
        preg_match("/Client ID: (\d+)/", $output, $clientId);
        
        return (int)$clientId[1];
    }
    
    public function testHandle_successfulMultiple()
    {
        $exitCode = Artisan::call('client:delete', [
            'id' => [$this->addClient(), $this->addClient()]
        ]);
        
        $output = Artisan::output();
        
        $this->assertEquals(0, $exitCode);
        $this->assertTrue(1 === preg_match("/Successfully deleted client.*\nSuccessfully deleted client/", $output));
    }
    
    public function testHandle_successfulMultiplePartiallyInvalid()
    {
        $exitCode = Artisan::call('client:delete', [
            'id' => [$this->addClient(), -1]
        ]);
        
        $output = Artisan::output();
        
        $this->assertEquals(0, $exitCode);
        $this->assertTrue(1 === preg_match("/Successfully deleted client.*\nInvalid id/", $output));
    }
    
    public function testHandle_missingId()
    {
        $this->expectException(\Symfony\Component\Console\Exception\RuntimeException::class);
        
        $this->artisan('client:delete');
    }
    
    public function testHandle_invalidId()
    {
        $this->artisan('client:delete', [
            'id' => [-1]
        ])->assertExitCode(1);
    }
}
