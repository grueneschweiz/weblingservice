<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 07.10.18
 * Time: 17:07
 */

namespace App\Repository;


use Webling\API\Client;

abstract class Repository {
	private $webling_client;
	
	/**
	 * Repository constructor.
	 *
	 * @param string $api_key
	 * @param string|null $api_url your webling address, e.g: "demo.webling.ch"
	 *                             the one set in the .env file will be used if
	 *                             parameter is left out.
	 *
	 * @throws \Webling\API\ClientException
	 */
	public function __construct( string $api_key, string $api_url = null ) {
		if ( ! $api_url ) {
			$api_url = config( 'app.webling_base_url' );
		}
		
		$this->webling_client = new Client( $api_url, $api_key );
	}
	
	/**
	 * Query Webling
	 *
	 * @link https://gruenesandbox.webling.ch/api for more details.
	 *
	 * @param string $endpoint this must not be encoded.
	 *
	 * @return \Webling\API\IResponse|\Webling\API\Response
	 * @throws \Webling\API\ClientException
	 */
	protected function api_get( string $endpoint ) {
		return $this->webling_client->get( $this->prepareEndpoint( $endpoint ) );
	}
	
	/**
	 * Preprocess the endpoint url: Strip api key, url encode query args
	 *
	 * @param string $endpoint
	 *
	 * @return string
	 */
	private function prepareEndpoint( string $endpoint ) {
		$endpoint = $this->removeApiKey( $endpoint );
		$encoded  = $this->urlEncodeEndpoint( $endpoint );
		
		return $encoded;
	}
	
	/**
	 * Make sure we never send the api key as url parameter
	 *
	 * @param string $endpoint
	 *
	 * @return null|string
	 */
	private function removeApiKey( string $endpoint ) {
		/** @noinspection SpellCheckingInspection */
		return preg_replace( '/&apikey=[^&]*/', '', $endpoint );
	}
	
	/**
	 * Encode the url to avoid special chars, don't encode '&' and '='
	 *
	 * @param string $endpoint
	 *
	 * @return string
	 */
	private function urlEncodeEndpoint( string $endpoint ) {
		$query_start = strpos( $endpoint, '?' );
		if ( ! $query_start ) {
			return $endpoint;
		}
		
		$query = substr( $endpoint, $query_start + 1 );
		$base  = substr( $endpoint, 0, $query_start );
		
		$encoded = urlencode( $query );
		
		// re-decode all ampersands and equal signs to keep the webling api happy
		$webling_ready = str_replace( [ '%26', '%3D' ], [ '&', '=' ], $encoded );
		
		return "$base?$webling_ready";
	}
	
	/**
	 * Update record in Webling
	 *
	 * @link https://gruenesandbox.webling.ch/api for more details.
	 *
	 * @param string $endpoint this must not be encoded.
	 * @param array $data only provide the fields to update. empty fields will be emptied.
	 *
	 * @return \Webling\API\IResponse|\Webling\API\Response
	 * @throws \Webling\API\ClientException
	 */
	protected function api_put( string $endpoint, array $data ) {
		// todo: implement history
		return $this->webling_client->put( $this->prepareEndpoint( $endpoint ), $data );
	}
	
	/**
	 * Insert record into Webling
	 *
	 * @link https://gruenesandbox.webling.ch/api for more details.
	 *
	 * @param string $endpoint this must not be encoded.
	 * @param array $data
	 *
	 * @return \Webling\API\IResponse|\Webling\API\Response
	 * @throws \Webling\API\ClientException
	 */
	protected function api_post( string $endpoint, array $data ) {
		return $this->webling_client->post( $this->prepareEndpoint( $endpoint ), $data );
	}
	
	/**
	 * Delete record in Webling
	 *
	 * @link https://gruenesandbox.webling.ch/api for more details.
	 *
	 * @param string $endpoint this must not be encoded.
	 *
	 * @return \Webling\API\IResponse|\Webling\API\Response
	 * @throws \Webling\API\ClientException
	 */
	protected function api_delete( string $endpoint ) {
		// todo: implement history
		return $this->webling_client->delete( $this->prepareEndpoint( $endpoint ) );
	}
}
