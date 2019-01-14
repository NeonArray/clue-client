<?php

class TestTriggerPost extends \WP_Mock\Tools\TestCase {

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
        $core = new Clue\Core\Triggers\Post( $this->logStub, $this->loaderStub );

        $this->assertTrue( is_array( $core->get_lexicon() ), 'should return an array' );
        $this->assertArrayHasKey( 'description', $core->get_lexicon(), 'should contain a description key' );
        $this->assertArrayHasKey( 'capability', $core->get_lexicon(), 'should contain a capability key' );
        $this->assertArrayHasKey( 'actions', $core->get_lexicon(), 'should contain a actions key' );
    }


    public function test_attach_hooks() {
        $export = new Clue\Core\Triggers\Post( $this->logStub, new Clue\Core\Loader() );

        \WP_Mock::expectActionAdded( 'transition_post_status', array( $export, 'on_edit_post' ), 10, 3 );
        \WP_Mock::expectActionAdded( 'delete_post', array( $export, 'on_delete_post' ) );
        \WP_Mock::expectActionAdded( 'untrash_post', array( $export, 'on_untrash_post' ) );

        $export->attach_hooks();
        $loader = $export->get_loader();
        $loader->run();
        $this->assertHooksAdded();
    }


    public function test_on_edit_post() {
        $this->logStub->method( 'dispatch' )
                      ->with( array(
                          'trigger' => 'Clue\Core\Triggers\Post',
                          'action'  => 'post_updated',
                          'severity' => 'info',
                          'details' => array(
                              'post_id' => 1,
                              'post_type' => 'post',
                              'post_title' => 'test',
                          ),
                      ) )
                      ->willReturn( true );
        $post = new Clue\Core\Triggers\Post( $this->logStub, $this->loaderStub );

        $wp_post = $this->getMockBuilder( \WP_Post::class )->getMock();
        $wp_post->ID = 1;
        $wp_post->post_type = 'nav_menu_item';
        $wp_post->post_title = 'test';

        \WP_Mock::userFunction( 'is_admin', array(
            'return_in_order' => array(
                false, // [1]
                true,  // [2]
                true,  // [3]
                true,  // [4]
            ),
        ) );
        \WP_Mock::userFunction( 'wp_is_post_revision', array(
            'return_in_order' => array(
                true,  // [2]
                false, // [3]
                false, // [4]
            )
        ) );

        // [1]
        $this->assertFalse( $post->on_edit_post( '', '', $wp_post ), 'should return false if is_admin is false' );

        // [2]
        $this->assertFalse( $post->on_edit_post( '', '', $wp_post ), 'should return false if wp_is_post_revision is true' );

        // [3]
        $this->assertFalse( $post->on_edit_post( '', '', $wp_post ), 'should return false if get_post_type is in $skip_posttypes array' );

        // [4]
        $wp_post->post_type = 'post';
        $this->assertTrue( $post->on_edit_post( '', '', $wp_post ), 'should call dispatch and return true' );
    }


    public function test_on_delete_post() {
        $this->logStub->method( 'dispatch' )
                      ->with( array(
                          'trigger'  => 'Clue\Core\Triggers\Post',
                          'action'   => 'post_deleted',
                          'severity' => 'info',
                          'details'  => array(
                              'post_id'     => 1,
                              'post_type'   => 'post',
                              'post_title'  => 'test',
                              'cron_delete' => true,
                          ),
                      ) )
                      ->willReturn( true );
        $post = new Clue\Core\Triggers\Post( $this->logStub, $this->loaderStub );

        $wp_post = $this->getMockBuilder( \WP_Post::class )->getMock();
        $wp_post->ID = 1;
        $wp_post->post_type = 'nav_menu_item';
        $wp_post->post_title = 'test';

        \WP_Mock::userFunction( 'wp_is_post_revision', array(
            'return_in_order' => array(
                true,  // [2]
                false, // [3]
                false, // [4]
                false, // [5]
                false, // [6]
            )
        ) );
        \WP_Mock::userFunction( 'get_post' )->andReturn( $wp_post );


        // [1]
        $this->assertFalse( $post->on_delete_post( 1 ), '' );

        // [2]
        $wp_post->post_status = 'auto-draft';
        $this->assertFalse( $post->on_delete_post( 1 ), '' );

        // [3]
        $wp_post->post_status = 'inherit';
        $this->assertFalse( $post->on_delete_post( 1 ), '' );

        // [4]
        $wp_post->post_status = 'publish';
        $this->assertFalse( $post->on_delete_post( 1 ), '' );

        // [5]
        $wp_post->post_type = 'nav_menu_item';
        $this->assertFalse( $post->on_delete_post( 1 ), '' );

        $wp_post->post_type = 'post';
        global $wp_current_filter;
        $wp_current_filter = array(
            'wp_scheduled_delete'
        );
        $this->assertTrue( $post->on_delete_post( 1 ), '' );
//
//        $wp_current_filter = false;
//        $this->assertTrue( $post->on_delete_post( 1 ), '' );
    }


    public function test_on_untrash_post() {
        $this->logStub->method( 'dispatch' )
                      ->with( array(
                          'trigger'  => 'Clue\Core\Triggers\Post',
                          'action'   => 'post_restored',
                          'severity' => 'info',
                          'details'  => array(
                              'post_id'     => 1,
                              'post_type'   => 'post',
                              'post_title'  => 'test',
                          ),
                      ) )
                      ->willReturn( true );
        $post = new Clue\Core\Triggers\Post( $this->logStub, $this->loaderStub );

        $wp_post = $this->getMockBuilder( \WP_Post::class )->getMock();
        $wp_post->ID = 1;
        $wp_post->post_type = 'post';
        $wp_post->post_title = 'test';

        \WP_Mock::userFunction( 'get_post' )->andReturn( $wp_post );

        $this->assertTrue( $post->on_untrash_post( 1 ), 'should call dispatch and return true' );
    }
}
