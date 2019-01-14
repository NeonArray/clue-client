<?php

class TestTriggerExport extends \WP_Mock\Tools\TestCase {

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
        $core = new Clue\Core\Triggers\Export( $this->logStub, $this->loaderStub );

        $this->assertTrue( is_array( $core->get_lexicon() ), 'should return an array' );
        $this->assertArrayHasKey( 'description', $core->get_lexicon(), 'should contain a description key' );
        $this->assertArrayHasKey( 'capability', $core->get_lexicon(), 'should contain a capability key' );
        $this->assertArrayHasKey( 'actions', $core->get_lexicon(), 'should contain a actions key' );
    }


    public function test_attach_hooks() {
        $export = new Clue\Core\Triggers\Export( $this->logStub, new Clue\Core\Loader() );

        \WP_Mock::expectActionAdded( 'export_wp', array( $export, 'on_export' ) );

        $export->attach_hooks();
        $loader = $export->get_loader();
        $loader->run();
        $this->assertHooksAdded();
    }


    public function test_on_export() {
        $export = new Clue\Core\Triggers\Export( $this->logStub, $this->loaderStub );
        $this->assertTrue( $export->on_export( array() ) );
    }
}
