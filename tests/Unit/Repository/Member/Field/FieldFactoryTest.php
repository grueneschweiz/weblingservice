<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 27.10.18
 * Time: 17:02
 */

namespace App\Repository\Member\Field;

use App\Exceptions\WeblingFieldMappingConfigException;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class FieldFactoryTest extends TestCase {
	
	public function test__constructConfigNotFound() {
		Config::set( 'app.webling_field_mappings_config_path', 'unknown' );
		
		$this->expectException( WeblingFieldMappingConfigException::class );
		/** @noinspection PhpUnhandledExceptionInspection */
		new FieldFactory();
	}
}
