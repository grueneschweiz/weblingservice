<?php
/**
 * Created by PhpStorm.
 * User: cyrill.bolliger
 * Date: 2019-03-21
 * Time: 11:39
 */

namespace App\Console\Commands;


use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ListClientTest extends TestCase
{
    private $clientId;
    private $key = 'super_secret';
    
    public function setUp(): void
    {
        parent::setUp();
        
        Artisan::call('client:add', [
            'name' => 'Unit Test',
            'webling-key' => $this->key,
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
    
    public function testHandle()
    {
        $exitCode = Artisan::call('client:list');
        $output = Artisan::output();
        
        $this->assertEquals(0, $exitCode);
        self::assertMatchesRegularExpression('/^| ID\s+| Name\s+| Root Groups\s+| Webling Key\s+| Updated\s+| Created\s+|/', $output);
        self::assertMatchesRegularExpression('/| ' . $this->clientId . '\s+| Unit Test\s+| 10\s+| ' . $this->key . '\s+ | \d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} | \d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} |/', $output);
    }
}
