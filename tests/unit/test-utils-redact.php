<?php

use Clue\Core\Utils\Redact;

class TestUtilRedact extends \WP_Mock\Tools\TestCase {


    public function setUp() {
        \WP_Mock::setUp();
    }


    public function tearDown() {
        \WP_Mock::tearDown();
    }


    public function test_email() {
        $expected = 'aar****@th***.com';
        $actual = Redact::email( 'aarney@thejumpagency.com' );
        $this->assertEquals( $expected, $actual, 'should redact email address' );

        $expected = 'aar****@ho***.net';
        $actual = Redact::email( 'aaron_12@hosting123.net' );
        $this->assertEquals( $expected, $actual, 'should redact email address with underscores and numbers' );

        $expected = 'bo****@ju***.org';
        $actual = Redact::email( 'bob@jump.org' );
        $this->assertEquals( $expected, $actual, 'should redact email address with three characters or less' );

        $expected = '';
        $actual = Redact::email( 'notanemail' );
        $this->assertEquals( $expected, $actual, 'should return empty string if passed a non-email string' );
    }
}
