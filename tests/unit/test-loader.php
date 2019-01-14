<?php

class TestLoader extends \WP_Mock\Tools\TestCase {


    protected $component;


    public function setUp() {
        \WP_Mock::setUp();

        $httpStub = $this->getMockBuilder( Clue\Core\utils\Http::class )
                         ->setMethods( array( 'post' ) )
                         ->getMock();
        $httpStub->method( 'post' )->willReturn( true );

        $logStub = $this->getMockBuilder( Clue\Core\Utils\Papertrail::class )
                              ->setConstructorArgs( array( $httpStub ) )
                              ->setMethods( array( 'dispatch' ) )
                              ->getMock();

        $logStub->method( 'dispatch' )
                      ->willReturn( true );

        $loaderStub = $this->getMockBuilder( Clue\Core\Loader::class )->getMock();

        $this->component = $this->getMockBuilder( Clue\Core\Triggers\Import::class )->setConstructorArgs(
            array( $logStub, $loaderStub )
        )->getMock();
    }


    public function tearDown() {
        \WP_Mock::tearDown();
    }


    public function test_add_action() {
        $loader = new Clue\Core\Loader();

        $actions = array(
            array(
              'hook'          => 'test_hook',
              'component'     => $this->component,
              'callback'      => 'test_callback',
              'priority'      => 10,
              'accepted_args' => 1,
          ), );

        $loader->add_action( 'test_hook', $this->component, 'test_callback', 10, 1 );

        $this->assertEquals( $actions, $loader->get_registered_actions(), 'action should be placed into actions array' );
    }


    public function test_add_filter() {
        $loader = new Clue\Core\Loader();

        $filters = array(
            array(
                'hook'          => 'test_hook',
                'component'     => $this->component,
                'callback'      => 'test_callback',
                'priority'      => 10,
                'accepted_args' => 1,
        ), );

        $loader->add_filter( 'test_hook', $this->component, 'test_callback', 10, 1 );

        $this->assertEquals( $filters, $loader->get_registered_filters(), 'filter should be placed into filters array' );
    }


    public function test_run() {
        $loader = new Clue\Core\Loader();

        $loader->add_action( 'test_hook', $this->component, 'test_callback', 10, 1 );
        $loader->add_filter( 'test_hook', $this->component, 'test_callback', 10, 1 );

        \WP_Mock::expectActionAdded( 'test_hook', array( $this->component, 'test_callback' ), 10, 1 );
        \WP_Mock::expectFilterAdded( 'test_hook', array( $this->component, 'test_callback' ), 10, 1 );

        $loader->run();

        $this->assertHooksAdded();
    }
}
