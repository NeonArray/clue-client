<?php

class TestTriggerThemes extends \WP_Mock\Tools\TestCase {


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

        $this->loaderStub = $this->getMockBuilder( Clue\Core\Loader::class )
                                 ->setMethods( null )
                                 ->getMock();
    }


    public function tearDown() {
        \WP_Mock::tearDown();
    }


    public function test_lexicon() {
        $this->logStub->method( 'dispatch' )
                ->willReturn( true );

        $themes = new Clue\Core\Triggers\Theme( $this->logStub, $this->loaderStub );

        $this->assertTrue( is_array( $themes->get_lexicon() ), 'should return an array' );
        $this->assertArrayHasKey( 'description', $themes->get_lexicon(), 'should contain a description key' );
        $this->assertArrayHasKey( 'capability', $themes->get_lexicon(), 'should contain a capability key' );
        $this->assertArrayHasKey( 'actions', $themes->get_lexicon(), 'should contain a actions key' );
    }


    public function test_attach_hooks() {
        $this->logStub->method( 'dispatch' )
                ->willReturn( true );

        $themes = new Clue\Core\Triggers\Theme( $this->logStub, $this->loaderStub );

        \WP_Mock::expectActionAdded( 'switch_theme', array( $themes, 'on_switch_theme' ), 10, 3 );
        \WP_Mock::expectActionAdded( 'customize_save_after', array( $themes, 'on_action_customize_save' ) );
        \WP_Mock::expectActionAdded( 'sidebar_admin_setup', array( $themes, 'on_sidebar_setup' ) );
        // TODO: Discern why this doesn't pass
        // \WP_Mock::expectActionAdded( 'widget_update_callback', array( $themes, 'on_widget_update' ), 10, 4 );
        \WP_Mock::expectActionAdded( 'upgrader_process_complete', array( $themes, 'on_upgrade' ), 10, 2 );
        \WP_Mock::expectActionAdded( 'deleted_site_transient', array( $themes, 'on_delete_transient' ) );

        $themes->attach_hooks();
        $loader = $themes->get_loader();
        $loader->run();
        $this->assertHooksAdded();
    }


    public function test_on_switch_theme() {
        $this->logStub->method( 'dispatch' )
                ->with( array(
                    'trigger'  => 'Clue\Core\Triggers\Theme',
                    'action'   => 'theme_switched',
                    'severity' =>  'info',
                    'details'  => array(
                        'new_theme' => array(
                            'name' => 'themename',
                            'version' => '1.0.0',
                        ),
                        'old_theme' => array(
                            'name' => 'themename',
                            'version' => '1.0.0',
                        ),
                    ),
                ) )
                ->willReturn( true );

        $themes = new Clue\Core\Triggers\Theme( $this->logStub, $this->loaderStub );

        $theme_name = '';

        $wp_theme = $this->getMockBuilder( \WP_Theme::class )
                         ->getMock();

        $wp_theme->name = 'themename';
        $wp_theme->version = '1.0.0';

        $this->assertTrue( $themes->on_switch_theme( $theme_name, $wp_theme, $wp_theme ) );

    }


    public function test_on_action_customize_save() {
        $customize_manager = $this->getMockBuilder( 'WP_Customize_Manager' )
                                  ->setMethods( array( 'unsanitized_post_values' ) )
                                  ->getMock();

        $customize_manager->method( 'unsanitized_post_values' )->willReturn( array(
            'title' => 'Website Title',
            'description' => 'Some descriptive text',
            'logo' => 15,
        ) );

        $this->logStub->method( 'dispatch' )
                ->with( array(
                    'trigger'  => 'Clue\Core\Triggers\Theme',
                    'action'   => 'appearance_customized',
                    'severity' =>  'info',
                    'details'  => array(
                        'modified_fields' => array(
                            'title' => 'Website Title',
                            'description' => 'Some descriptive text',
                            'logo' => 15,
                        ),
                    ),
                ) )
                ->willReturn( true );

        $theme = new Clue\Core\Triggers\Theme( $this->logStub, $this->loaderStub );

        $this->assertTrue( $theme->on_action_customize_save( $customize_manager ) );
    }


    public function test_on_sidebar_setup_removed() {
        $_POST = array(
            'widget-id' => 'categories-2',
            'id_base' => 'categories',
            'add_new' => '',
            'sidebar' => 'sidebar-1',
            'delete_widget' => 1,
        );

        $this->logStub->method( 'dispatch' )
                      ->with( array(
                          'trigger'  => 'Clue\Core\Triggers\Theme',
                          'action'   => 'widget_removed',
                          'severity' =>  'info',
                          'details'  => array(
                              'widget_id' => 'categories-2',
                              'sidebar' => 'sidebar-1',
                          ),
                      ) )
                      ->willReturn( true );

        $theme = $this->getMockBuilder( Clue\Core\Triggers\Theme::class )
                      ->setConstructorArgs( array( $this->logStub, $this->loaderStub ) )
                      ->setMethods( array( 'is_sidebar_setup_operation' ) )
                      ->getMock();
        $theme->method( 'is_sidebar_setup_operation' )->willReturn( 'removed' );

        $this->assertTrue( $theme->on_sidebar_setup(), 'should return true for removed condition' );
    }


    public function test_on_sidebar_setup_added() {
        $_POST = array(
            'widget-id' => 'categories11',
            'id_base' => 'categories',
            'add_new' => 1,
            'sidebar' => 'sidebar-1',
        );

        $this->logStub->method( 'dispatch' )
                      ->with( array(
                          'trigger'  => 'Clue\Core\Triggers\Theme',
                          'action'   => 'widget_added',
                          'severity' =>  'info',
                          'details'  => array(
                              'widget_id' => 'categories11',
                              'sidebar' => 'sidebar-1',
                          ),
                      ) )
                      ->willReturn( true );

        $theme = $this->getMockBuilder( Clue\Core\Triggers\Theme::class )
                      ->setConstructorArgs( array( $this->logStub, $this->loaderStub ) )
                      ->setMethods( array( 'is_sidebar_setup_operation' ) )
                      ->getMock();
        $theme->method( 'is_sidebar_setup_operation' )->willReturn( 'added' );

        $this->assertTrue( $theme->on_sidebar_setup(), 'should return true for added condition' );
    }


    public function test_on_sidebar_setup_edited() {
        $_POST = array(
            'widget-id' => 'categories11',
            'id_base' => 'categories',
            'sidebar' => 'sidebar-1',
        );

        $this->logStub->method( 'dispatch' )
                      ->with( array(
                          'trigger'  => 'Clue\Core\Triggers\Theme',
                          'action'   => 'widget_edited',
                          'severity' =>  'info',
                          'details'  => array(
                              'widget_id' => 'categories11',
                              'sidebar' => 'sidebar-1',
                          ),
                      ) )
                      ->willReturn( true );

        $theme = $this->getMockBuilder( Clue\Core\Triggers\Theme::class )
                      ->setConstructorArgs( array( $this->logStub, $this->loaderStub ) )
                      ->setMethods( array( 'is_sidebar_setup_operation' ) )
                      ->getMock();
        $theme->method( 'is_sidebar_setup_operation' )->willReturn( 'edited' );

        $this->assertTrue( $theme->on_sidebar_setup(), 'should return true for edited condition' );
    }


    public function test_on_sidebar_setup_false() {
        $_POST = null;

        $theme = $this->getMockBuilder( Clue\Core\Triggers\Theme::class )
                      ->setConstructorArgs( array( $this->logStub, $this->loaderStub ) )
                      ->setMethods( array( 'is_sidebar_setup_operation' ) )
                      ->getMock();

        $this->assertFalse( $theme->on_sidebar_setup(), 'should return false if post is null' );
    }


    public function test_is_sidebar_setup_operation() {
        $themes = new Clue\Core\Triggers\Theme( $this->logStub, $this->loaderStub );

        $actual = invokeMethod( $themes, 'is_sidebar_setup_operation', array( array( 'delete_widget' => 1 ) ) );
        $expected = 'removed';
        $this->assertEquals( $expected, $actual, 'should return `removed`' );


        $actual = invokeMethod( $themes, 'is_sidebar_setup_operation', array( array( 'add_new' => 1 ) ) );
        $expected = 'added';
        $this->assertEquals( $expected, $actual, 'should return `added`' );


        $actual = invokeMethod( $themes, 'is_sidebar_setup_operation', array( array() ) );
        $expected = 'edited';
        $this->assertEquals( $expected, $actual, 'should default return `edited`' );
    }


    public function test_on_widget_update() {
        $wp_widget = $this->getMockBuilder( WP_Widget::class )->getMock();
        $new_instance = array (
            'title' => '',
            'ids' => '',
            'columns' => '3',
            'size' => 'thumbnail',
            'link_type' => 'post',
            'orderby_random' => '',
        );
        $themes = new Clue\Core\Triggers\Theme( $this->logStub, $this->loaderStub );

        $actual = $themes->on_widget_update( array(), array(), array(), $wp_widget );
        $this->assertFalse( $actual, 'should return false if old_instance is empty' );


        $_POST = array();
        $actual = $themes->on_widget_update( array(), array(), $new_instance, $wp_widget );
        $this->assertFalse( $actual, 'should return false if post is empty' );


        $this->logStub->method( 'dispatch' )
                      ->with( array(
                          'trigger' => 'Clue\Core\Triggers\Theme',
                          'action'  => 'widget_edited',
                          'severity' => 'info',
                          'details' => array(
                              'widget_id' => '',
                              'sidebar' => 'sidebar-2',
                              'old_instance' => '{"title":"","ids":"","columns":"3","size":"thumbnail","link_type":"post","orderby_random":""}',
                              'new_instance' => '{"title":"","ids":"","columns":"3","size":"thumbnail","link_type":"post","orderby_random":""}',
                          ),
                      ) )
                      ->willReturn( true );

        $wp_widget->id_base = '';
        $_POST = array (
            'widget-pages' => array (
               array (
                   'title' => '',
                   'sortby' => 'post_title',
                   'exclude' => '',
               ),
            ),
            'widget-id' => '',
            'id_base' => 'pages',
            'widget-width' => '250',
            'widget-height' => '200',
            'widget_number' => '-1',
            'multi_number' => '2',
            'add_new' => 'multi',
            'sidebar' => 'sidebar-2',
        );
        $actual = $themes->on_widget_update( $new_instance, $new_instance, $new_instance, $wp_widget );
        $this->assertTrue( $actual, 'should return true' );
    }


    public function test_on_upgrade() {
        $themes = new Clue\Core\Triggers\Theme( $this->logStub, $this->loaderStub );
        $wp_upgrader = $this->getMockBuilder( WP_Upgrader::class )
                            ->setMethods( array( 'theme_info' ) )
                            ->getMock();
        $this->assertFalse( $themes->on_upgrade( $wp_upgrader, array() ), 'should return false if either argument is empty' );


        $this->assertFalse( $themes->on_upgrade( $wp_upgrader, array(
            'type' => 'plugin',
            'action' => 'upgrade',
        ) ), 'should return false if upgrade type isnt theme' );


        $theme_info = new stdClass();
        $theme_info->Name = 'twentyfifteen';
        $theme_info->Version = '1.0.0';
        $wp_upgrader->method( 'theme_info' )->willReturn( $theme_info );

        $this->logStub->method( 'dispatch' )
            ->with( array(
                'trigger'  => 'Clue\Core\Triggers\Theme',
                'action'   => 'theme_upgrade',
                'severity' => 'info',
                'details'  => array(
                    'theme_name'    => 'twentyfifteen',
                    'theme_version' => '1.0.0',
                ),
            ) )
            ->willReturn( true );

        $this->assertTrue( $themes->on_upgrade( $wp_upgrader, array(
            'type' => 'theme',
            'action' => 'upgrade',
        ) ), 'should return true' );
    }


    public function test_on_delete_transient() {
        $themes = new Clue\Core\Triggers\Theme( $this->logStub, $this->loaderStub );


        $this->assertFalse( $themes->on_delete_transient( 'update_plugin' ), 'should return false if argument != `update_themes`' );


        $_POST = array(
            'slug' => 'twentyfifteen',
        );

        $this->logStub->method( 'dispatch' )
                      ->with( array(
                          'trigger'  => 'Clue\Core\Triggers\Theme',
                          'action'   => 'theme_deleted',
                          'severity' => 'warning',
                          'details'  => array(
                              'theme_name' => 'twentyfifteen',
                          ),
                      ) )
                      ->willReturn( true );

        $this->assertTrue( $themes->on_delete_transient( 'update_themes' ), 'should return true' );
    }
}
