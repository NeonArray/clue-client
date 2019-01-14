<?php

class TestTriggerCore extends \WP_Mock\Tools\TestCase {

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
        $core = new Clue\Core\Triggers\Core( $this->logStub, $this->loaderStub );

        $this->assertTrue( is_array( $core->get_lexicon() ), 'should return an array' );
        $this->assertArrayHasKey( 'description', $core->get_lexicon(), 'should contain a description key' );
        $this->assertArrayHasKey( 'capability', $core->get_lexicon(), 'should contain a capability key' );
        $this->assertArrayHasKey( 'actions', $core->get_lexicon(), 'should contain a actions key' );
    }


    public function test_attach_hooks() {
        $core = new Clue\Core\Triggers\Core( $this->logStub, new Clue\Core\Loader() );

        \WP_Mock::expectActionAdded( '_core_updated_successfully', array( $core, 'on_core_updated' ) );
        \WP_Mock::expectActionAdded( 'update_feedback', array( $core, 'on_update_feedback' ) );

        $core->attach_hooks();

        $loader = $core->get_loader();
        $loader->run();
        $this->assertHooksAdded();
    }


    public function test_on_core_updated() {
        $GLOBALS['pagenow'] = 'update-core.php';

        $core = new Clue\Core\Triggers\Core( $this->logStub, $this->loaderStub );

        $this->assertTrue( $core->on_core_updated( '5.0.0' ), 'should return true' );
    }


    public function test_get_superglobal() {
        $core = new Clue\Core\Triggers\Core( $this->logStub, $this->loaderStub );

        $this->assertTrue( is_array($core->get_post( $_POST ) ) );
        $this->assertTrue( is_array($core->get_get( $_GET ) ) );
        $this->assertTrue( is_array($core->get_server( $_SERVER ) ) );
        $this->assertTrue( is_array($core->get_request( $_REQUEST ) ) );
    }
}
