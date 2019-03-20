<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;

class DeleteClient extends ClientCommand {
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'client:delete {id : Client ID}';

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
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle() {
		$id = (int) $this->argument( 'id' );

		$client = $this->getClientById( $id );

		\App\ClientGroup::where( 'client_id', $id )->delete();
		DB::table( 'oauth_access_tokens' )->where( 'client_id', '=', $id )->delete();
		DB::table( 'oauth_clients' )->where( 'id', '=', $id )->delete();

		$this->info( "Successfully deleted client '{$client->name}'" );

		return 0;
	}
}
