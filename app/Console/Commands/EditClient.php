<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;

class EditClient extends ClientCommand {
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'client:edit 
                            {id : Client ID}
                            {--name= : Client name (human readable identifier)}
                            {--g|root-group=* : The root group (id) the client should have access to. Repeat the option for multiple groups.}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Edit API oAuth Client';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Change name and/or root groups according to the selected options.
	 *
	 * @return mixed
	 */
	public function handle() {
		$id     = (int) $this->argument( 'id' );
		$name   = $this->option( 'name' );
		$groups = $this->option( 'root-group' );

		$client = $this->getClientById( $id );

		if ( ! $client ) {
			return 1;
		}

		if ( ! empty( $name ) ) {
			DB::table( 'oauth_clients' )->where( 'id', $id )->update( [ 'name' => $name ] );

			$this->info( 'Successfully changed name.' );
		}

		if ( ! empty( $groups ) ) {
			// delete the ones not given
			$toDelete = \App\ClientGroup::where( 'client_id', $id )
			                            ->whereNotIn( 'root_group', $groups )
			                            ->pluck( 'root_group' )
			                            ->toArray();
			\App\ClientGroup::where( 'client_id', $id )->whereNotIn( 'root_group', $groups )->delete();

			if ( ! empty( $toDelete ) ) {
				$this->info( '<comment>Deleted groups:</comment> ' . implode( $toDelete ) );
			}

			// add the new ones
			$dbGroups = \App\ClientGroup::where( 'client_id', $id )->pluck( 'root_group' )->toArray();
			$added    = [];
			foreach ( $groups as $group ) {
				$group = (int) $group;
				if ( ! in_array( $group, $dbGroups ) ) {
					$added[] = $group;
					$g       = new \App\ClientGroup();

					$g->client_id  = $id;
					$g->root_group = $group;

					$g->save();
				}
			}

			if ( ! empty( $added ) ) {
				$this->info( '<comment>Added groups:</comment> ' . implode( $added ) );
			}
		}

		return 0;
	}
}
