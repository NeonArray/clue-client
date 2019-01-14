<?php

class TestClient extends \WP_Mock\Tools\TestCase {


    public $logger;
    protected $logStub;
    protected $loaderStub;

    public function setUp() {
        \WP_Mock::setUp();

        $http = $this->getMockBuilder( Clue\Core\Utils\Http::class )->getMock();
        $this->logStub = $this->getMockBuilder( Clue\Core\Utils\Papertrail::class )->setConstructorArgs( array( $http ) )->getMock();
        $this->loaderStub = $this->getMockBuilder( Clue\Core\Loader::class )
                                 ->setMethods( array( 'run' ) )
                                 ->getMock();
        $this->loaderStub->method( 'run' )->willReturn( true );
    }


    public function tearDown() {
        \WP_Mock::tearDown();
    }


    public function test_get_version() {
        \WP_Mock::userFunction( 'is_admin' )->andReturn( false );
        $logger = new Clue\Core\Client( $this->loaderStub, $this->logStub );

        $this->assertEquals( '1.0.0', $logger->get_version(), 'should default to 1.0.0 if CLUE_CLIENT is not defined' );

        define( 'CLUE_CLIENT', '2.0.0' );
        $logger_two = new Clue\Core\Client( $this->loaderStub, $this->logStub );

        $this->assertEquals( '2.0.0', $logger_two->get_version(), 'should inherit CLUE_CLIENT if defined' );
    }


    public function test_get_loader() {
        \WP_Mock::userFunction( 'is_admin' )->andReturn( false );
        $client = new Clue\Core\Client( $this->loaderStub, $this->logStub );
        $this->assertInstanceOf( Clue\Core\Loader::class, $client->get_loader(), 'should return an instance of Loader class' );
    }


    public function test_get_plugin_name() {
        \WP_Mock::userFunction( 'is_admin' )->andReturn( false );
        $logger = new Clue\Core\Client( $this->loaderStub, $this->logStub );

        $this->assertEquals( 'clue-client', $logger->get_plugin_name(), 'should return plugin name' );
    }


    public function test_run() {
        \WP_Mock::userFunction( 'is_admin' )->andReturn( false );
        $logger = new Clue\Core\Client( $this->loaderStub, $this->logStub );

        $this->assertTrue( $logger->run(), 'should return true' );
    }
}
