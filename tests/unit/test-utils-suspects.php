<?php

use Clue\Core\Utils\Suspects;

class TestUtilsSuspects extends \WP_Mock\Tools\TestCase {


    public function setUp() {
        \WP_Mock::setUp();
    }


    public function tearDown() {
        \WP_Mock::tearDown();
    }


    public function test_genetics() {
        $this->assertEquals( 'wp_user', Suspects::WP_USER, 'should return wp_user' );
    }
}
