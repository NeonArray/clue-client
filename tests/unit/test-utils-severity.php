<?php

use Clue\Core\Utils\Severity;

class TestUtiSeverity extends \WP_Mock\Tools\TestCase {


    public function setUp() {
        \WP_Mock::setUp();
    }


    public function tearDown() {
        \WP_Mock::tearDown();
    }


    public function test_genetics() {
        $this->assertTrue(  'emergency' === Severity::EMERGENCY, 'severity levels should exist' );
    }
}
