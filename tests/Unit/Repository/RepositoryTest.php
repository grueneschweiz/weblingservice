<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 07.10.18
 * Time: 18:26
 */

namespace App\Repository;

use Tests\TestCase;

class RepositoryTest extends TestCase {
	private $repository;
	
	public function testGet() {
		$test = $this->repository->get( 'member/210' );
		$this->assertAttributeEquals( 200, 'code', $test );
		
		$query = 'member?filter= (`Name / nom` = "Der Testmann" OR `Name / nom` = "Nèrvén") AND `Vorname / prénom` = "Ümläüts" AND `Datensatzkategorie / type d’entrée` = "Privatperson / particulier"';
		$test  = $this->repository->get( $query );
		$this->assertAttributeEquals( 200, 'code', $test );
		
		$data = $test->getData();
		$this->assertArrayHasKey( 'objects', $data );
		$this->assertEquals( [ 298 ], $data['objects'] );
	}
	
	public function testPut() {
		$this->testPost();
	}
	
	/**
	 * This test tests also put and delete
	 */
	public function testPost() {
		$data = [
			'properties' => [
				'Name / nom'       => 'automated test',
				'Vorname / prénom' => 'will be deleted after'
			],
			'parents'    => [ 100 ]
		];
		
		$post = $this->repository->post( 'member', $data );
		$this->assertAttributeEquals( 201, 'code', $post );
		
		$data   = [
			'properties' => [
				'Strasse / rue' => 'Deadend 99',
			]
		];
		$update = $this->repository->put( "member/{$post->getData()}", $data );
		$this->assertAttributeEquals( 204, 'code', $update );
		
		$delete = $this->repository->delete( "member/{$post->getData()}" );
		$this->assertAttributeEquals( 204, 'code', $delete );
	}
	
	public function testDelete() {
		$this->testPost();
	}
	
	protected function setUp() {
		parent::setUp();
		
		$this->repository = $this->getMockBuilder( Repository::class )
		                         ->setConstructorArgs( array( config( 'app.webling_api_key' ) ) )
		                         ->getMockForAbstractClass();
	}
}
