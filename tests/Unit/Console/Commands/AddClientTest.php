<?php
/**
 * Created by PhpStorm.
 * User: cyrill.bolliger
 * Date: 2019-03-21
 * Time: 10:00
 */

namespace App\Console\Commands;


use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AddClientTest extends TestCase {

	public function testHandle_successful() {
		$exitCode = Artisan::call( 'client:add', [
			'name'         => 'Unit Test',
			'--root-group' => [ 10 ]
		] );

		$output = Artisan::output();

		preg_match( "/Client ID: (\d+)/", $output, $clientId );
		preg_match( "/Root groups: (\d+)/", $output, $rootGroups );

		// clean up before asserting
		Artisan::call( 'client:delete', [ 'id' => [ (int) $clientId[1] ] ] );

		$this->assertEquals( 0, $exitCode );
		$this->assertTrue( 1 === preg_match( "/Client secret: \w+/", $output ) );
		$this->assertTrue( 1 === preg_match( "/New client created successfully./", $output ) );
		$this->assertEquals( '10', $rootGroups[1] );
		$this->assertNotEmpty( $clientId[1] );
	}

	public function testHandle_successfulMultipleGroupsShorthand() {
		$exitCode = Artisan::call( 'client:add', [
			'name'         => 'Unit Test',
			'-g' => [ 10, 20 ]
		] );

		$output = Artisan::output();

		preg_match( "/Client ID: (\d+)/", $output, $clientId );
		preg_match( "/Root groups: (\d+), (\d+)/", $output, $rootGroups );

		// clean up before asserting
		Artisan::call( 'client:delete', [ 'id' => [ (int) $clientId[1] ] ] );

		$this->assertEquals( 0, $exitCode );
		$this->assertTrue( 1 === preg_match( "/Client secret: \w+/", $output ) );
		$this->assertTrue( 1 === preg_match( "/New client created successfully./", $output ) );
		$this->assertEquals( '10', $rootGroups[1] );
		$this->assertEquals( '20', $rootGroups[2] );
		$this->assertNotEmpty( $clientId[1] );
	}

	public function testHandle_missingName() {
		$this->expectException(\Symfony\Component\Console\Exception\RuntimeException::class);

		$this->artisan( 'client:add', [
			'--root-group' => [ 10 ]
		] );
	}

	public function testHandle_noGroup() {
		$this->artisan( 'client:add', [
			'name' => 'asdf'
		] )->assertExitCode(1);
	}

	public function testHandle_invalidGroup() {
		$this->artisan( 'client:add', [
			'name' => 'asdf',
			'-g' => ['asdf']
		] )->assertExitCode(1);
	}
}
