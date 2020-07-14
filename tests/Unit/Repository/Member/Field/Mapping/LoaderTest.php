<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 09.12.18
 * Time: 11:53
 */

namespace App\Repository\Member\Field\Mapping;


use App\Exceptions\WeblingFieldMappingConfigException;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

class LoaderTest extends TestCase
{
    const INTERNAL_FIELD_NAME = 'firstName';
    const WEBLING_FIELD_NAME = 'Vorname / prénom';
    
    public function test__constructConfigNotFound()
    {
        $this->resetLoader();
        
        Config::set('app.webling_field_mappings_config_path', 'unknown');
        
        $this->expectException(WeblingFieldMappingConfigException::class);
        $this->expectExceptionMessage('The Webling field mappings config file was not found.');
        /** @noinspection PhpUnhandledExceptionInspection */
        Loader::getInstance();
    }
    
    private function resetLoader()
    {
        // remove existing instances, else getInstance will return the existing instance
        /** @noinspection PhpUnhandledExceptionInspection */
        $instanceField = $this->getPrivateProperty(Loader::class, 'instance');
        $instanceField->setValue(null);
    }
    
    /**
     * getPrivateProperty
     *
     * @param string $className
     * @param string $propertyName
     *
     * @return \ReflectionProperty
     * @throws \ReflectionException
     * @author Joe Sexton <joe@webtipblog.com>
     *
     */
    public function getPrivateProperty($className, $propertyName)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $reflector = new \ReflectionClass($className);
        $property = $reflector->getProperty($propertyName);
        $property->setAccessible(true);
        
        return $property;
    }
    
    public function test__constructParseException()
    {
        $this->resetLoader();
        
        Config::set('app.webling_field_mappings_config_path',
            $this->getFileRelPath() . DIRECTORY_SEPARATOR . 'webling-field-mappings-parse-error.yml');
        
        $this->expectException(WeblingFieldMappingConfigException::class);
        $this->expectExceptionMessageMatches("/^YAML parse error:/");
        /** @noinspection PhpUnhandledExceptionInspection */
        Loader::getInstance();
    }
    
    private function getFileRelPath()
    {
        return str_replace(base_path() . '/', '', dirname(__FILE__));
    }
    
    public function test__constructMappingsException()
    {
        $this->resetLoader();
        
        Config::set('app.webling_field_mappings_config_path',
            $this->getFileRelPath() . DIRECTORY_SEPARATOR . 'webling-field-mappings-mappings-not-found.yml');
        
        $this->expectException(WeblingFieldMappingConfigException::class);
        $this->expectExceptionMessage('The entry point ("mappings") was not found or empty.');
        /** @noinspection PhpUnhandledExceptionInspection */
        Loader::getInstance();
    }
    
    public function test__constructInvalidConfigException()
    {
        $this->resetLoader();
        
        Config::set('app.webling_field_mappings_config_path',
            $this->getFileRelPath() . DIRECTORY_SEPARATOR . 'webling-field-mappings-invalid-config.yml');
        
        $this->expectException(WeblingFieldMappingConfigException::class);
        $this->expectExceptionMessageMatches("/^Invalid Webling field mapping config:/");
        /** @noinspection PhpUnhandledExceptionInspection */
        Loader::getInstance();
    }
    
    public function test__constructReservedFieldNameException()
    {
        $this->resetLoader();
        
        Config::set('app.webling_field_mappings_config_path',
            $this->getFileRelPath() . DIRECTORY_SEPARATOR . 'webling-field-mappings-reserved-field-key.yml');
        
        $this->expectException(WeblingFieldMappingConfigException::class);
        $this->expectExceptionMessageMatches("/^Reserved field key:/");
        /** @noinspection PhpUnhandledExceptionInspection */
        Loader::getInstance();
    }
    
    public function testGetMapping()
    {
        $this->resetLoader();
        /** @noinspection PhpUnhandledExceptionInspection */
        $loader = Loader::getInstance();
        /** @noinspection PhpUnhandledExceptionInspection */
        $mapping = $loader->getMapping(self::INTERNAL_FIELD_NAME);
        $this->assertEquals(self::WEBLING_FIELD_NAME, $mapping->getWeblingKey());
        
        /** @noinspection PhpUnhandledExceptionInspection */
        $mapping = $loader->getMapping(self::WEBLING_FIELD_NAME);
        $this->assertEquals(self::INTERNAL_FIELD_NAME, $mapping->getKey());
    }
    
    public function testGetInstance()
    {
        $this->resetLoader();
        /** @noinspection PhpUnhandledExceptionInspection */
        $loader = Loader::getInstance();
        $this->assertInstanceOf(Loader::class, $loader);
    }
    
    public function testGetFieldKeys()
    {
        $this->resetLoader();
        /** @noinspection PhpUnhandledExceptionInspection */
        $loader = Loader::getInstance();
        
        $path = base_path(config('app.webling_field_mappings_config_path'));
        $mappings = Yaml::parseFile($path);
        $expected = [];
        foreach ($mappings['mappings'] as $entry) {
            $expected[$entry['weblingKey']] = $entry['key'];
        }
        
        $actual = $loader->getFieldKeys();
        
        $this->assertEquals($expected, $actual);
    }
}
