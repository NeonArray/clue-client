<?php

class TestTriggerOption extends \WP_Mock\Tools\TestCase {

    protected $logStub;

    protected $loaderStub;


    public function setUp() {
        \WP_Mock::setUp();

        $httpStub = $this->getMockBuilder( Clue\Core\utils\Http::class )
                         ->setMethods( array( 'post' ) )
                         ->getMock();
        $httpStub->method( 'post' )->willReturn( true );

        $this->logStub = $this->getMockBuilder( Clue\Core\Utils\Papertrail::class )
                              ->setConstructorArgs( array( $httpStub ) )
                              ->setMethods( array( 'dispatch' ) )
                              ->getMock();

        $this->logStub->method( 'dispatch' )
                      ->willReturn( true );

        $this->loaderStub = $this->getMockBuilder( Clue\Core\Loader::class )->getMock();
    }


    public function tearDown() {
        \WP_Mock::tearDown();
    }


    public function test_lexicon() {
        $core = new Clue\Core\Triggers\Option( $this->logStub, $this->loaderStub );

        $this->assertTrue( is_array( $core->get_lexicon() ), 'should return an array' );
        $this->assertArrayHasKey( 'description', $core->get_lexicon(), 'should contain a description key' );
        $this->assertArrayHasKey( 'capability', $core->get_lexicon(), 'should contain a capability key' );
        $this->assertArrayHasKey( 'actions', $core->get_lexicon(), 'should contain a actions key' );
    }


    public function test_attach_hooks() {
        $export = new Clue\Core\Triggers\Option( $this->logStub, new Clue\Core\Loader() );

        \WP_Mock::expectActionAdded( 'updated_option', array( $export, 'on_update_option' ), 10, 3 );

        $export->attach_hooks();
        $loader = $export->get_loader();
        $loader->run();
        $this->assertHooksAdded();
    }


    public function test_on_update_option() {
        $this->logStub->method( 'dispatch' )
                      ->with( array(
                          'trigger'  => 'Clue\Core\Triggers\Option',
                          'action'   => 'updated_option',
                          'severity' => 'info',
                          'details'  => array(
                              'option'    => 'a_setting',
                              'old_value' => 'oldval',
                              'new_value' => 'newval',
                          ),
                      ) )
                      ->willReturn( true );

        $option = $this->getMockBuilder( Clue\Core\Triggers\Option::class )
                       ->setConstructorArgs( array( $this->logStub, $this->loaderStub ) )
                       ->setMethods( array( 'get_request', 'get_server', 'is_native_option_page' ) )
                       ->getMock();

        $option->method( 'is_native_option_page' )->willReturnOnConsecutiveCalls(
            false, // [2]
            true // [3]
        );
        $option->method( 'get_server' )->willReturnOnConsecutiveCalls(
                array(), // [1]
                array( 'REQUEST_URI' => 'somestring' ), // [2]
                array( 'REQUEST_URI' => 'somestring' ) // [3]
        );
        $option->method( 'get_request' )->willReturnOnConsecutiveCalls(
                array(), // [1]
                array( 'action' => 'update' ), // [2]
                array( 'action' => 'update' )  // [3]
        );


        // [1]
        $this->assertFalse( $option->on_update_option( '', '', '' ), 'should return false if request uri is empty or action !== update' );

        // [2]
        $this->assertFalse( $option->on_update_option( '', '', '' ), 'should return false if is_native_option_page returns false' );

        // [3]
        $this->assertTrue( $option->on_update_option( 'a_setting', 'oldval', 'newval' ), 'should call dispatch and return true' );
    }
}
