<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;

class DeleteClient extends ClientCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'client:delete {id* : List of Client IDs separated by a space.}';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete API oAuth Client';
    
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
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $ids = $this->argument('id');
        
        foreach ($ids as $id) {
            $client = $this->getClientById($id);
            if (!$client) {
                if (1 === count($ids)) {
                    return 1;
                } else {
                    continue;
                }
            }
            
            \App\ClientGroup::where('client_id', $id)->delete();
            \App\WeblingKey::where('client_id', $id)->delete();
            DB::table('oauth_access_tokens')->where('client_id', '=', $id)->delete();
            DB::table('oauth_clients')->where('id', '=', $id)->delete();
            
            $this->info("<comment>Successfully deleted client:</comment> {$client->name}");
        }
        
        return 0;
    }
}
