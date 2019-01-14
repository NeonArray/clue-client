<?php

class TestClientDeactivator extends \WP_Mock\Tools\TestCase {


    public function setUp() {
        \WP_Mock::setUp();
    }


    public function tearDown() {
        \WP_Mock::tearDown();
    }


    public function test_deactivate() {
        $this->assertTrue( Clue\Core\Deactivator::deactivate(), 'should return true' );
    }
}
