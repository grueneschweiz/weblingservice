<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class ListClient extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'client:list';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List API oAuth Clients';
    
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Print a table with all clients and their root groups
     *
     * @return int
     */
    public function handle(): int
    {
        $clients = DB::table('oauth_clients')->get()->toArray();
        
        $data = [];
        foreach ($clients as $client) {
            $groups = \App\ClientGroup::where('client_id', $client->id)->pluck('root_group')->toArray();
            $keyModel = \App\WeblingKey::where('client_id', $client->id)->first();
            
            $data[] = [
                $client->id,
                $client->name,
                implode(' ', $groups),
                $keyModel ? Crypt::decryptString($keyModel->api_key) : '',
                $client->updated_at,
                $client->created_at,
            ];
        }
    
        $headers = ['ID', 'Name', 'Root Groups', 'Webling Key', 'Updated', 'Created'];
        
        $this->table($headers, $data);
        
        return 0;
    }
}
