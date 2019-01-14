<?php

use Clue\Core\Utils\Helpers;

class TestUtilHelpers extends \WP_Mock\Tools\TestCase {


    public function setUp() {
        \WP_Mock::setUp();
    }


    public function tearDown() {
        \WP_Mock::tearDown();
    }


    public function test_format_date() {
        $date = new \DateTime();

        $this->assertEquals( $date->format( 'Y-m-d h:m:s' ),Helpers::format_date( gmdate( 'Y-m-d H:i:s' ) ), 'should format date to Y-m-d h:m:s' );
    }


    public function test_get_localtime() {
        $date = gmdate( 'Y-m-d H:i:s' );
        $this->assertEquals( $date, Helpers::get_localtime(), 'should return date in Y-m-d H:i:s format' );
    }


    public function test_validate_ip () {
        $ip = '64.198.12.1';
        $bad_ip = '00.0000.0.0';
        $bad_local_ip = '127.0.0.1';

        $this->assertTrue( Helpers::validate_ip( $ip ), 'valid ip should return true' );
        $this->assertFalse( Helpers::validate_ip( $bad_ip ), 'invalid ip should return false' );
        $this->assertFalse( Helpers::validate_ip( $bad_local_ip ), 'should return false for private ranges' );
    }


    public function test_get_wp_version() {
        $this->assertEquals( '0.0.0', Helpers::get_wp_version(), 'should return 0.0.0 as version if version.php can\'t be loaded');
    }
}
