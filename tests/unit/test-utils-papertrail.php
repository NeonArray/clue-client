<?php

use Clue\Core\Utils\Severity;
use Clue\Core\Utils\Papertrail;
use Clue\Core\Utils\Http;

class TestPapertrail extends \WP_Mock\Tools\TestCase {


    protected $httpMock;


    public function setUp() {
        \WP_Mock::setUp();

        $this->httpMock = $this->getMockBuilder( Clue\Core\Utils\Http::class )
                               ->setMethods( array( 'post' ) )
                               ->getMock();
        $this->httpMock->method( 'post' )->willReturn( true );
    }


    public function tearDown() {
        \WP_Mock::tearDown();
    }


    public function test_dispatch() {
        $papertrail = $this->getMockBuilder( Papertrail::class )
                           ->setConstructorArgs( array( $this->httpMock ) )
                           ->setMethods( array( 'add_meta_context', 'send_post' ) )
                           ->getMock();
        $papertrail->method( 'add_meta_context' )->willReturn( array() );
        $papertrail->method( 'send_post' )->willReturn( true );

        $this->assertTrue(
            $papertrail->dispatch( array(
                'trigger'  => 'Logger',
                'severity' => 'info',
                'action'   => 'post_created',
                'context'  => array(),
                'details'  => array(),
            ) ),
            'should return true' );

        \WP_Mock::userFunction( 'get_user_context' )->andReturn( array(
            'user' => array(
                'id'    => 1,
                'login' => 'Aaron Arney',
                'email' => 'aarney@test.local',
            ),
        ) );

        $this->assertTrue(
            $papertrail->dispatch( array(
                'trigger'  => 'Logger',
                'severity' => 'info',
                'action'   => 'post_created',
                'context'  => array(),
                'details'  => array(),
            )  ),
            'should return true' );
    }


    public function test_add_meta_context() {
        $context = array();

        $papertrail = $this->getMockBuilder( Papertrail::class )
                           ->setConstructorArgs( array( $this->httpMock ) )
                           ->setMethods( array( 'get_perpetrator_context', 'get_server_context' ) )
                           ->getMock();
        $papertrail->method( 'get_perpetrator_context' )->willReturn( array(
            'perpetrator' => 'other',
        ) );
        $papertrail->method( 'get_server_context' )->willReturn( array() );

        /**
         * If $context is an empty array, it should just pass through the method and return an array with
         * perpetrator set to other
         */
        $actual = $papertrail->add_meta_context( $context );
        $this->assertEquals(array( 'user' => array(
            'perpetrator' => 'other',
        ) ), $actual, 'should return empty array' );


        /**
         * Testing the `if ( ! empty( $user ) )` condition
         */
        $papertrail2 = $this->getMockBuilder( Papertrail::class )
                           ->setConstructorArgs( array( $this->httpMock ) )
                           ->setMethods( array( 'get_perpetrator_context', 'get_server_context' ) )
                           ->getMock();
        $papertrail2->method( 'get_perpetrator_context' )->willReturn( array(
            'email' => 'aarney@test.local',
            'id' => 1,
            'login' => 'aaron',
        ) );
        $papertrail2->method( 'get_server_context' )->willReturn( array() );
        $this->assertEquals(array(
            'user' => array(
                'email' => 'aarney@test.local',
                'id' => 1,
                'login' => 'aaron',
            ),
        ), $papertrail2->add_meta_context( array() ), 'should return array with just user meta' );


        /**
         * Testing the `if ( ! empty( $server ) )` condition
         */
        $papertrail3 = $this->getMockBuilder( Papertrail::class )
                            ->setConstructorArgs( array( $this->httpMock ) )
                            ->setMethods( array( 'get_perpetrator_context', 'get_server_context' ) )
                            ->getMock();
        $papertrail3->method( 'get_perpetrator_context' )->willReturn( array() );
        $papertrail3->method( 'get_server_context' )->willReturn( array( '1' ) );
        $this->assertSame(array(
            'server' => array( '1' )
        ), $papertrail3->add_meta_context( array() ), 'should return empty array if no http data' );
    }


    public function test_get_user_context() {
        $papertrail = new Papertrail( $this->httpMock );
        $this->assertEquals( array(), invokeMethod( $papertrail, 'get_user_context' ), 'should return empty array if wp_get_current_user function doesn\'t exist' );

        $user = new stdClass();
        $user->ID = 1;
        $user->user_login = 'Aaron Arney';
        $user->user_email = 'aarney@test.com';

        \WP_Mock::userFunction( 'wp_get_current_user' )->andReturn( $user );

        $actual = invokeMethod( $papertrail, 'get_user_context' );
        $expected = array(
            'id' => 1,
            'login' => 'Aaron Arney',
            'email' => 'aar****@te***.com',
        );

        $this->assertEquals( $expected, $actual, 'should return user array' );
    }


    public function test_get_server_context() {
        $papertrail = new Papertrail( $this->httpMock );
        $actual = $papertrail->get_server_context( array() );
        $this->assertEquals( array(), $actual, 'should return empty array' );

        $stub = $this->getMockBuilder( Papertrail::class )
             ->setConstructorArgs( array(  $this->httpMock ) )
             ->setMethods( array( 'get_http_referrer', 'get_http_origin', 'get_http_address' ) )
             ->getMock();

        $stub->expects( $this->once() )
             ->method( 'get_http_referrer' )
             ->willReturn( 'localhost' );

        $stub->expects( $this->once() )
             ->method( 'get_http_origin' )
             ->willReturn( 'origin' );

        $stub->expects( $this->once() )
             ->method( 'get_http_address' )
             ->willReturn( 'address' );

        $expected = array(
            'http_referrer' => 'localhost',
            'http_origin' => 'origin',
            'http_address' => 'address',
        );

        $this->assertEquals( $expected, $stub->get_server_context( array() ) );
    }


    public function test_get_http_origin() {
        $papertrail = new Papertrail( $this->httpMock );

        $this->assertEquals( '', invokeMethod( $papertrail, 'get_http_origin', array( array() ) ), 'should return empty string since HTTP_HOST is not present' );

        $this->assertEquals( 'aaronarney.com', invokeMethod( $papertrail, 'get_http_origin', array( array( 'http_referer' => 'http://aaronarney.com' ) ) ), 'should return host' );

        $this->assertEquals( 'subdomain.aaronarney.com', invokeMethod( $papertrail, 'get_http_origin', array(
            array( 'http_referer' => 'http://subdomain.aaronarney.com' )
        ) ), 'should return host with subdomain' );

        $this->assertEquals( 'subdomain.aaronarney.com',invokeMethod( $papertrail, 'get_http_origin', array(
            array( 'http_referer' => 'http://subdomain.aaronarney.com/route/v2/' )
        ) ) , 'should return host without trailing routes' );
    }


    public function test_get_http_address() {
        $papertrail = new Papertrail( $this->httpMock );
        $context = array( 'http_address' => '' );
        $this->assertEquals( '', invokeMethod( $papertrail, 'get_http_address', array( $context ) ), 'should return empty string' );

        $context                = array();
        $ip                     = '64.192.9.10';
        $_SERVER['REMOTE_ADDR'] = $ip;
        $this->assertEquals( '64.192.9.10', invokeMethod( $papertrail, 'get_http_address', array( $context ) ), 'should return array with http_remote_address value' );

        // TODO: Finish the assertion once I figure out how to mock SERVER
//        $this->assertEquals( array(
//            'http_remote_address' => '64.192.9.10',
//            'http_server' => array(
//                'test' => 'value',
//            ),
//        ), Papertrail::get_http_address( $context ) );
    }


    public function test_get_perpetrator_context() {
        $papertrail = new Papertrail( $this->httpMock );
        $context = array(
            'perpetrator' => 'your sister',
        );

        $actual = invokeMethod( $papertrail, 'get_perpetrator_context', array( $context ) );
        $this->assertEquals( 'your sister', $actual, 'should return perpetrator if exists in context' );

        $actual = invokeMethod( $papertrail, 'get_perpetrator_context', array( array() ) );

        $this->assertEquals( array(
            'perpetrator' => 'other',
        ), $actual, 'should return perpetrator other' );
    }


    public function test_get_perpetrator_context_cron() {
        $papertrail = new Papertrail( $this->httpMock );
        define( 'DOING_CRON', true );
        $actual = invokeMethod( $papertrail, 'get_perpetrator_context', array( array() ) );
        $this->assertEquals( array(
            'perpetrator' => 'wp',
            'wp_cron_running' => true,
        ), $actual,'should return array if cron is running' );
    }


    public function test_get_perpetrator_context_cli() {
        $papertrail = new Papertrail( $this->httpMock );
        define( 'WP_CLI', true );
        $actual = invokeMethod( $papertrail, 'get_perpetrator_context', array( array() ) );
        $this->assertEquals( array(
            'perpetrator' => 'wp_cli',
            'wp_cron_running' => true,
        ), $actual,'should return array if wp cli is running' );
    }


    public function test_get_perpetrator_context_xmlrpc_request() {
        $papertrail = new Papertrail( $this->httpMock );
        define( 'XMLRPC_REQUEST', true );
        $actual = invokeMethod( $papertrail, 'get_perpetrator_context', array( array() ) );
        $this->assertEquals( array(
            'perpetrator' => 'xmlrpc',
            'xmlrpc_request' => true,
            'wp_cron_running' => true,
        ), $actual,'should return array if xmlrpc is executing commands' );
    }
}
