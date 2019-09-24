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
		$test = $this->apiGet( 'member/210' );
		$this->assertEquals(200, self::getPrivateProperty($test, 'code'));
		
		$query = 'member?filter= (`Name / nom` = "Der Testmann" OR `Name / nom` = "Nèrvén") AND `Vorname / prénom` = "Ümläüts" AND `Datensatzkategorie / type d’entrée` = "Privatperson / particulier"';
		$test  = $this->apiGet( $query );
        $this->assertEquals(200, self::getPrivateProperty($test, 'code'));
		
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
		
		$post = $this->apiPost( 'member', $data );
        $this->assertEquals(201, self::getPrivateProperty($post, 'code'));
        
        
        $data   = [
			'properties' => [
				'Strasse / rue' => 'Deadend 99',
			]
		];
		$update = $this->apiPut( "member/{$post->getData()}", $data );
        $this->assertEquals(204, self::getPrivateProperty($update, 'code'));
        
        
        $delete = $this->apiDelete( "member/{$post->getData()}" );
        $this->assertEquals(204, self::getPrivateProperty($delete, 'code'));
        
    }
	
	public function testDelete() {
		$this->testPost();
	}
	
	/**
	 * this runs before the tests
	 */
	protected function setUp(): void {
		parent::setUp();
		
		$this->repository = $this->getMockBuilder( Repository::class )
		                         ->setConstructorArgs( array( config( 'app.webling_api_key' ) ) )
		                         ->getMockForAbstractClass();
	}
	
	private function apiGet($endpoint) {
		return self::callProtectedMethod($this->repository, 'apiGet', array($endpoint));
	}
	
	private function apiPost($endpoint, $data) {
		return self::callProtectedMethod($this->repository, 'apiPost', array($endpoint, $data));
	}
	
	private function apiPut($endpoint, $data) {
		return self::callProtectedMethod($this->repository, 'apiPut', array($endpoint, $data));
	}
	
	private function apiDelete($endpoint) {
		return self::callProtectedMethod($this->repository, 'apiDelete', array($endpoint));
	}
	
	/**
	 * Test protected method
	 *
	 * @param $object
	 * @param $method
	 * @param array $args
	 *
	 * @return mixed
	 * @throws \ReflectionException
	 *
	 * @see https://stackoverflow.com/a/5013441
	 */
	public static function callProtectedMethod($object, $method, array $args=array()) {
		/** @noinspection PhpUnhandledExceptionInspection */
		$class  = new \ReflectionClass(get_class($object));
		$method = $class->getMethod($method);
		$method->setAccessible(true);
		return $method->invokeArgs($object, $args);
	}
    
    /**
     * Get private property
     *
     * @param $object
     * @param $property
     * @param array $args
     *
     * @return mixed
     * @throws \ReflectionException
     *
     * @see https://stackoverflow.com/a/5013441
     */
    public static function getPrivateProperty($object, $property, array $args=array()) {
        /** @noinspection PhpUnhandledExceptionInspection */
        $class  = new \ReflectionClass(get_class($object));
        $property = $class->getProperty($property);
        $property->setAccessible(true);
        return $property->getValue($object);
    }
}
