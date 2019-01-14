<?php

class TestTriggerMenu extends \WP_Mock\Tools\TestCase {

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

        $this->loaderStub = $this->getMockBuilder( Clue\Core\Loader::class )->getMock();
    }


    public function tearDown() {
        \WP_Mock::tearDown();
    }


    public function test_lexicon() {
        $core = new Clue\Core\Triggers\Import( $this->logStub, new Clue\Core\Loader() );

        $this->assertTrue( is_array( $core->get_lexicon() ), 'should return an array' );
        $this->assertArrayHasKey( 'description', $core->get_lexicon(), 'should contain a description key' );
        $this->assertArrayHasKey( 'capability', $core->get_lexicon(), 'should contain a capability key' );
        $this->assertArrayHasKey( 'actions', $core->get_lexicon(), 'should contain a actions key' );
    }


    public function test_attach_hooks() {
        $export = new Clue\Core\Triggers\Menu( $this->logStub, new Clue\Core\Loader() );

        \WP_Mock::expectActionAdded( 'wp_create_nav_menu', array( $export, 'on_create_menu' ), 10, 2 );
        \WP_Mock::expectActionAdded( 'load-nav-menus.php', array( $export, 'on_menu_action' ) );

        $export->attach_hooks();
        $loader = $export->get_loader();
        $loader->run();
        $this->assertHooksAdded();
    }


    public function test_on_create_menu() {
        $this->logStub->method( 'dispatch' )
                      ->with( array(
                          'trigger'  => 'Clue\Core\Triggers\Menu',
                          'action'   => 'created_menu',
                          'severity' => 'info',
                          'details'  => array(
                              'id'   => 1,
                              'name' => 'test_menu',
                          ),
                      ) )
                      ->willReturn( true );
        $menu = new Clue\Core\Triggers\Menu( $this->logStub, $this->loaderStub );

        $this->assertTrue( $menu->on_create_menu( 1, array( 'menu-name' => 'test_menu' ) ), 'should call dispatch and return true' );
    }


    public function test_on_menu_action() {
        $menu = $this->getMockBuilder( Clue\Core\Triggers\Menu::class )
                     ->setConstructorArgs( array(
                         $this->logStub,
                         new Clue\Core\Loader()
                     ) )
                     ->setMethods( array( 'on_menu_update' ) )
                     ->getMock();
        $menu->method( 'on_menu_update' )->willReturn( true );

        \WP_Mock::userFunction( 'is_nav_menu', array(
            'return_in_order' => array(
                false, // [2]
                true,  // [3]
            ),
        ) );

        $this->assertFalse( $menu->on_menu_action( 0 ), 'should return false if $_REQUEST does not contain `menu` or `action`' );

        $_REQUEST = array(
            'action' => 'update',
            'menu'   => 1,
        );

        $this->assertFalse( $menu->on_menu_action( 0 ), 'should return false if is_nav_menu is false' );
        $this->assertTrue( $menu->on_menu_action( 0 ), 'should return true if on_menu_udpate is called' );
    }


    public function test_on_menu_update() {
        $this->logStub->method( 'dispatch' )
                  ->with( array(
                      'trigger'  => 'Clue\Core\Triggers\Menu',
                      'action'   => 'edited_menu',
                      'severity' => 'info',
                      'details'  => array(
                          'menu_id'            => 1,
                          'menu_items_added'   => array(),
                          'menu_items_removed' => array(),
                      ),
                  ) )
                  ->willReturn( true );

        $menu = $this->getMockBuilder( Clue\Core\Triggers\Menu::class )
                     ->setConstructorArgs( array( $this->logStub, $this->loaderStub ) )
                     ->setMethods( array( 'get_menu_diff' ) )
                     ->getMock();
        $menu->method( 'get_menu_diff' )->willReturn( array(
            'incoming_menu_items' => array(),
            'previous_menu_items' => array(),
        ) );

        \WP_Mock::userFunction( 'is_nav_menu', array(
            'return_in_order' => array(
                true,  // [1]
            ),
        ) );

        $this->assertTrue( $menu->on_menu_update( array(
            'menu' => 1,
        ) ), 'should call dispatch and return true' );
    }


    public function test_on_menu_delete() {
        $menu = new Clue\Core\Triggers\Menu( $this->logStub, $this->loaderStub );
        $this->logStub->method( 'dispatch' )
                      ->with( array(
                          'trigger'  => 'Clue\Core\Triggers\Menu',
                          'action'   => 'deleted_menu',
                          'severity' => 'info',
                          'details'  => array(
                              'menu_id' => 1,
                          ),
                      ) )
                      ->willReturn( true );

        $this->assertTrue( $menu->on_menu_delete( array(
            'menu' => 1,
        ) ), 'should call dispatch and return true' );
    }


    public function test_get_menu_diff() {
        $factory = new WP_Factory\Factory();

        \WP_Mock::userFunction( 'wp_get_nav_menu_items', array(
            'return_in_order' => array(
               false,   // [1]
               array(   // [2]
                    $factory->post->create( array( '_menu_item' => true ) ),
               ),
            )
        ) );
        \WP_Mock::userFunction( 'wp_list_pluck', array(
            'return_in_order' => array(
                array( 1, 2, 3, 4, 5, 6, ), // [2]
            )
        ) );

        $menu = new Clue\Core\Triggers\Menu( $this->logStub, $this->loaderStub );

        $this->assertEquals( array(),  $menu->get_menu_diff( 0 ) );
        $this->assertEquals( array(
            'previous_menu_items' => array( 1, 2, 3, 4, 5, 6, ),
            'incoming_menu_items' => array(),
        ),  $menu->get_menu_diff( 0 ) );
    }
}
