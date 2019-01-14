<?php

class TestTriggerEditor extends \WP_Mock\Tools\TestCase {

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
        $core = new Clue\Core\Triggers\Editor( $this->logStub, $this->loaderStub );

        $this->assertTrue( is_array( $core->get_lexicon() ), 'should return an array' );
        $this->assertArrayHasKey( 'description', $core->get_lexicon(), 'should contain a description key' );
        $this->assertArrayHasKey( 'capability', $core->get_lexicon(), 'should contain a capability key' );
        $this->assertArrayHasKey( 'actions', $core->get_lexicon(), 'should contain a actions key' );
    }


    public function test_attach_hooks() {
        $export = new Clue\Core\Triggers\Editor( $this->logStub, new Clue\Core\Loader() );

        \WP_Mock::expectFilterAdded( 'wp_code_editor_settings', array( $export, 'on_editor_load' ) );

        $export->attach_hooks();
        $loader = $export->get_loader();
        $loader->run();
        $this->assertHooksAdded();
    }


    public function test_on_editor_load() {
        $this->logStub->method( 'dispatch' )
                      ->with( array(
                          'trigger'  => 'Clue\Core\Triggers\Editor',
                          'action'   => 'editor_loaded',
                          'severity' => 'info',
                          'details'  => array(
                              'file'  => 'functions.php',
                              'theme' => 'twentyseventeen',
                          ),
                      ) )
                      ->willReturn( true );

        $export = new Clue\Core\Triggers\Editor( $this->logStub, $this->loaderStub );
        $this->assertEquals( array(), $export->on_editor_load( array() ), '' );

        $_GET = array( 'file' => 'functions.php', 'theme' => 'twentyseventeen', );
        $export = new Clue\Core\Triggers\Editor( $this->logStub, $this->loaderStub );
        $this->assertEquals( array(), $export->on_editor_load( array() ), '' );
    }


    public function test_on_editor_save() {
        $this->logStub->method( 'dispatch' )
                      ->with( array(
                          'trigger'  => 'Clue\Core\Triggers\Editor',
                          'action'   => 'editor_saved',
                          'severity' => 'alert',
                          'details'  => array(
                              'file'    => 'functions.php',
                              'theme'   => 'twentyseventeen',
                              'content' => '.tons{font-family:&#34;stuff&#34;;}',
                          ),
                      ) )
                      ->willReturn( true );
        $_POST = array(
            'action' => 'some-action',
        );
        $editor = new Clue\Core\Triggers\Editor( $this->logStub, $this->loaderStub );

        $this->assertFalse( $editor->on_editor_save( false ), 'should return false if wp_ajax is false' );
        $this->assertTrue( $editor->on_editor_save( true ), 'should return true if the action being performed is not the file editor' );

        $_POST = array(
            'action'     => 'edit-theme-plugin-file',
            'file'       => 'functions.php',
            'theme'      => 'twentyseventeen',
            'newcontent' => '.tons{font-family:"stuff";}',
        );
        $editor_two = new Clue\Core\Triggers\Editor( $this->logStub, $this->loaderStub );

        $this->assertTrue( $editor_two->on_editor_save( true ), 'should call dispatch and then return true' );
    }
}
