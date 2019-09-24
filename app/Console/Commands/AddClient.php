<?php

namespace App\Console\Commands;

use Laravel\Passport\ClientRepository;

class AddClient extends ClientCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'client:add
                            {name : Client name (human readable identifier)}
                            {--g|root-group=* : The root group (id) the client should have access to. Repeat the option for multiple groups.}';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add API oAuth Client';
    
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
     * @param ClientRepository $clientRepository injected by laravel
     *
     * @return mixed
     */
    public function handle(ClientRepository $clientRepository)
    {
        $groups = $this->option('root-group');
        
        if (!$this->validateGroups($groups)) {
            return 1;
        }
        
        $clientId = $this->addClient($clientRepository, $this->argument('name'));
        $this->addRootGroups($clientId, $groups);
        
        return 0;
    }
    
    /**
     * Add a Client Credentials Grant Tokens using the passport command
     *
     * @param ClientRepository $clientRepository
     * @param string $name
     *
     * @return int the client id
     */
    private function addClient(ClientRepository $clientRepository, string $name)
    {
        $client = $clientRepository->create(null, $name, '');
        
        $this->info('New client created successfully.');
        $this->line('<comment>Client ID:</comment> ' . $client->id);
        $this->line('<comment>Client secret:</comment> ' . $client->secret);
        
        return $client->id;
    }
    
    /**
     * Associate the root groups with the client
     *
     * @param int $clientId
     * @param array $rootGroups
     */
    private function addRootGroups(int $clientId, array $rootGroups)
    {
        foreach ($rootGroups as $group) {
            $g = new \App\ClientGroup();
            
            $g->client_id = $clientId;
            $g->root_group = (int)$group;
            
            $g->save();
        }
        
        $this->info('<comment>Root groups:</comment> ' . implode($rootGroups, ', '));
    }
}
