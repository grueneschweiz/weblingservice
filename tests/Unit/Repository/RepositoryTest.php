<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 07.10.18
 * Time: 18:26
 */

namespace App\Repository;

use Tests\TestCase;

class RepositoryTest extends TestCase
{
    private $repository;
    
    public function testGet()
    {
        /** @var \Webling\API\IResponse|\Webling\API\Response $test */
        $test = $this->apiGet('member/5471');
        $this->assertEquals(200, $test->getStatusCode());
        
        $query = 'member?filter= (`Name / nom` = "Der Testmann" OR `Name / nom` = "Nèrvén") AND `Vorname / prénom` = "Ümläüts" AND `Datensatzkategorie / type d’entrée` = "Privatperson / particulier"';
        $test = $this->apiGet($query);
        $this->assertEquals(200, $test->getStatusCode());
        
        $data = $test->getData();
        $this->assertArrayHasKey('objects', $data);
        $this->assertEquals([298], $data['objects']);
    }
    
    private function apiGet($endpoint)
    {
        return self::callProtectedMethod($this->repository, 'apiGet', array($endpoint));
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
    public static function callProtectedMethod($object, $method, array $args = array())
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $class = new \ReflectionClass(get_class($object));
        $method = $class->getMethod($method);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }
    
    public function testPut()
    {
        $this->testPost();
    }
    
    /**
     * This test tests also put and delete
     */
    public function testPost()
    {
        $data = [
            'properties' => [
                'Name / nom' => 'automated test',
                'Vorname / prénom' => 'will be deleted after'
            ],
            'parents' => [100]
        ];
        
        /** @var \Webling\API\IResponse|\Webling\API\Response $post */
        $post = $this->apiPost('member', $data);
        $this->assertEquals(201, $post->getStatusCode());
        
        $data = [
            'properties' => [
                'Strasse / rue' => 'Deadend 99',
            ]
        ];
        /** @var \Webling\API\IResponse|\Webling\API\Response $update */
        $update = $this->apiPut("member/{$post->getData()}", $data);
        $this->assertEquals(204, $update->getStatusCode());
        
        /** @var \Webling\API\IResponse|\Webling\API\Response $delete */
        $delete = $this->apiDelete("member/{$post->getData()}");
        $this->assertEquals(204, $delete->getStatusCode());
    }
    
    private function apiPost($endpoint, $data)
    {
        return self::callProtectedMethod($this->repository, 'apiPost', array($endpoint, $data));
    }
    
    private function apiPut($endpoint, $data)
    {
        return self::callProtectedMethod($this->repository, 'apiPut', array($endpoint, $data));
    }
    
    private function apiDelete($endpoint)
    {
        return self::callProtectedMethod($this->repository, 'apiDelete', array($endpoint));
    }
    
    public function testDelete()
    {
        $this->testPost();
    }
    
    /**
     * this runs before the tests
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = $this->getMockBuilder(Repository::class)
            ->setConstructorArgs(array(config('app.webling_api_key')))
            ->getMockForAbstractClass();
    }
}
