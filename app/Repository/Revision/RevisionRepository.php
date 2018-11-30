<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 30.11.18
 * Time: 17:31
 */

namespace App\Repository\Revision;


use App\Exceptions\InvalidRevisionIdException;
use App\Exceptions\WeblingAPIException;
use App\Repository\Repository;

class RevisionRepository extends Repository {
	
	/**
	 * Return revision form given id.
	 *
	 * @param int $revisionId
	 *
	 * @return Revision
	 * @throws InvalidRevisionIdException
	 * @throws WeblingAPIException
	 * @throws \Webling\API\ClientException
	 */
	public function get( int $revisionId ): Revision {
		$resp = $this->apiGet( "replicate/$revisionId" );
		
		if ( $resp->getStatusCode() !== 200 ) {
			throw new WeblingAPIException( "Get request to Webling failed with status code {$resp->getStatusCode()}" );
		}
		
		$data            = $resp->getData();
		$currentRevision = $data['revision'];
		
		if ( 0 >= $currentRevision ) {
			throw new InvalidRevisionIdException( "Webling didn't find a revision with the id {$revisionId}" );
		}
		
		$memberIds = ! empty( $data['objects']['member'] ) ? $data['objects']['member'] : [];
		
		return new Revision(
			$revisionId,
			$currentRevision,
			$memberIds
		);
	}
	
	/**
	 * Return the latest revision id of Webling.
	 *
	 * @return int
	 * @throws WeblingAPIException
	 * @throws \Webling\API\ClientException
	 */
	public function getCurrentRevisionId(): int {
		$resp = $this->apiGet( "replicate" );
		
		if ( $resp->getStatusCode() !== 200 ) {
			throw new WeblingAPIException( "Get request to Webling failed with status code {$resp->getStatusCode()}" );
		}
		
		return $resp->getData()['revision'];
	}
}
