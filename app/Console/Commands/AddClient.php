<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Artisan;

class AddClient extends ClientCommand {
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
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle() {
		$groups = $this->option( 'root-group' );

		if ( ! $this->validateGroups( $groups ) ) {
			return 1;
		}

		$clientId = $this->addClient( $this->argument( 'name' ) );
		$this->addRootGroups( $clientId, $groups );

		return 0;
	}

	/**
	 * Associate the root groups with the client
	 *
	 * @param int $clientId
	 * @param array $rootGroups
	 */
	private function addRootGroups( int $clientId, array $rootGroups ) {
		foreach ( $rootGroups as $group ) {
			$g = new \App\ClientGroup();

			$g->client_id  = $clientId;
			$g->root_group = (int) $group;

			$g->save();
		}

		$this->info( 'Root groups: ' . implode( $rootGroups, ', ' ) );
	}

	/**
	 * Add a Client Credentials Grant Tokens using the passport command
	 *
	 * @param string $name
	 *
	 * @return int the client id
	 */
	private function addClient( string $name ) {
		Artisan::call( 'passport:client', [ '--client' => true, '--name' => $name ] );
		$output = trim( Artisan::output() );

		$this->info( $output );

		preg_match( "/Client ID: (\d+)/", $output, $matches );

		return (int) $matches[1]; // client id
	}
}
