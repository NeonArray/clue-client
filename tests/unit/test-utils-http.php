<?php

use Clue\Core\Utils\Http;

class TestUtilsHttp extends \WP_Mock\Tools\TestCase {


    public function setUp() {
        \WP_Mock::setUp();
    }


    public function tearDown() {
        \WP_Mock::tearDown();
    }


    public function test_post_success() {
        \WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );
        \WP_Mock::userFunction( 'get_option' )->andReturn( array( 'clue_api_key' => 'abc123' ) );
        \WP_Mock::userFunction( 'wp_remote_post', array(
            'return' => array( 'success' => true, 'response' => array( 'code' => 200 ) ),
        ) );

        $http = new Http();

        $this->assertTrue( $http->post( array() ), 'should return true if success' );
    }


    public function test_post_error() {
        \WP_Mock::userFunction( 'is_wp_error' )->andReturn( true );
        \WP_Mock::userFunction( 'get_option' )->andReturn( array( 'clue_api_key' => 'abc123' ) );
        \WP_Mock::userFunction( 'wp_remote_post')->andReturn( array( 'response' => array( 'code' => 401 )) );

        $http = new Http();

        $this->assertFalse( $http::post( array() ), 'should return false if error' );
    }


    public function test_get_endpoint() {
        $http = new Http();

        $this->assertEquals( 'localhost', $http::get_endpoint(), 'should default to localhost if CLUE_API_ENDPOINT isn\'t set' );
    }


    public function test_get_api_key() {
        \WP_Mock::userFunction( 'get_option' )->andReturn( array( 'api_key' => 'abc123' ) );

        $http = new Http();

        $this->assertEquals( 'abc123', $http->get_api_key(), 'should return abc123 as api key' );
    }
}
