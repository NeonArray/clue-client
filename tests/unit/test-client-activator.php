<?php

class TestClientActivator extends \WP_Mock\Tools\TestCase {


    public function setUp() {
        \WP_Mock::setUp();
    }


    public function tearDown() {
        \WP_Mock::tearDown();
    }


    public function test_activate() {
        $this->assertTrue( Clue\Core\Activator::activate(), 'should return true' );
    }
}
