<?php

class TestTriggerPlugin extends \WP_Mock\Tools\TestCase {

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
        $core = new Clue\Core\Triggers\Plugin( $this->logStub, $this->loaderStub );

        $this->assertTrue( is_array( $core->get_lexicon() ), 'should return an array' );
        $this->assertArrayHasKey( 'description', $core->get_lexicon(), 'should contain a description key' );
        $this->assertArrayHasKey( 'capability', $core->get_lexicon(), 'should contain a capability key' );
        $this->assertArrayHasKey( 'actions', $core->get_lexicon(), 'should contain a actions key' );
    }


    public function test_on_activate_plugin() {
        $this->logStub->method( 'dispatch' )
                      ->with( array(
                           'trigger'  => 'Clue\Core\Triggers\Plugin',
                           'action'   => 'plugin_activated',
                           'severity' => 'warning',
                           'details'  => array(
                               'plugin_name'         => 'Clue Client',
                               'plugin_uri'          => 'https://leapsparkagency.com',
                               'plugin_version'      => '1.1.0',
                               'plugin_author'       => '<a href="https://leapsparkagency.com">Leap Spark Agency</a>',
                               'plugin_author_uri'   => 'https://leapsparkagency.com',
                               'plugin_network_wide' => false,
                           ),
                       ) )
                      ->willReturn( true );
        $plugin = new Clue\Core\Triggers\Plugin( $this->logStub, $this->loaderStub );

        $wp_plugin = array (
            'Name' => 'Clue Client',
            'PluginURI' => 'https://leapsparkagency.com',
            'Version' => '1.1.0',
            'Description' => 'The client companion for the Clue event log application. <cite>By <a href="https://leapsparkagency.com">Leap Spark Agency</a>.</cite>',
            'Author' => '<a href="https://leapsparkagency.com">Leap Spark Agency</a>',
            'AuthorURI' => 'https://leapsparkagency.com',
            'TextDomain' => 'clue-client',
            'DomainPath' => '/languages',
            'Network' => false,
            'Title' => '<a href="https://leapsparkagency.com">Clue Client</a>',
            'AuthorName' => 'Leap Spark Agency',
        );

        define( 'WP_PLUGIN_DIR', '.' );

        \WP_Mock::userFunction( 'get_plugin_data', array(
            'return_in_order' => array(
                array(
                    'Name' => '',
                ), // [1]
                $wp_plugin, // [2]
            ),
        ) );

        $this->assertFalse( $plugin->on_activate_plugin( '', false ), 'should return false if get_plugin_data returns false' );

        $this->assertTrue( $plugin->on_activate_plugin( '', false ), 'should call dispatch and return true' );
    }


    public function test_on_deactivate_plugin() {
        $this->logStub->method( 'dispatch' )
                      ->with( array(
                          'trigger'  => 'Clue\Core\Triggers\Plugin',
                          'action'   => 'plugin_deactivated',
                          'severity' => 'warning',
                          'details'  => array(
                              'plugin_name'         => 'Clue Client',
                              'plugin_uri'          => 'https://leapsparkagency.com',
                              'plugin_version'      => '1.1.0',
                              'plugin_author'       => '<a href="https://leapsparkagency.com">Leap Spark Agency</a>',
                              'plugin_author_uri'   => 'https://leapsparkagency.com',
                              'plugin_network_wide' => false,
                          ),
                      ) )
                      ->willReturn( true );
        $plugin = new Clue\Core\Triggers\Plugin( $this->logStub, $this->loaderStub );

        $wp_plugin = array (
            'Name' => 'Clue Client',
            'PluginURI' => 'https://leapsparkagency.com',
            'Version' => '1.1.0',
            'Description' => 'The client companion for the Clue event log application. <cite>By <a href="https://leapsparkagency.com">Leap Spark Agency</a>.</cite>',
            'Author' => '<a href="https://leapsparkagency.com">Leap Spark Agency</a>',
            'AuthorURI' => 'https://leapsparkagency.com',
            'TextDomain' => 'clue-client',
            'DomainPath' => '/languages',
            'Network' => false,
            'Title' => '<a href="https://leapsparkagency.com">Clue Client</a>',
            'AuthorName' => 'Leap Spark Agency',
        );

        \WP_Mock::userFunction( 'get_plugin_data', array(
            'return_in_order' => array(
                array(
                    'Name' => '',
                ), // [1]
                $wp_plugin, // [2]
            ),
        ) );

        // [1]
        $this->assertFalse( $plugin->on_deactivate_plugin( '', false ), 'should return false if get_plugin_data returns false' );

        // [2]
        $this->assertTrue( $plugin->on_deactivate_plugin( '', false ), 'should call dispatch and return true' );
    }


    public function test_on_delete_plugin() {
        $this->logStub->method( 'dispatch' )
                      ->with( array(
                          'trigger'  => 'Clue\Core\Triggers\Plugin',
                          'action'   => 'plugin_deleted',
                          'severity' => 'warning',
                          'details'  => array(
                              'plugin_name'         => 'Clue Client',
                              'plugin_uri'          => 'https://leapsparkagency.com',
                              'plugin_version'      => '1.1.0',
                              'plugin_author'       => '<a href="https://leapsparkagency.com">Leap Spark Agency</a>',
                              'plugin_author_uri'   => 'https://leapsparkagency.com',
                              'plugin_network_wide' => false,
                          ),
                      ) )
                      ->willReturn( true );
        $plugin = new Clue\Core\Triggers\Plugin( $this->logStub, $this->loaderStub );

        $wp_plugin = array (
            'Name' => 'Clue Client',
            'PluginURI' => 'https://leapsparkagency.com',
            'Version' => '1.1.0',
            'Description' => 'The client companion for the Clue event log application. <cite>By <a href="https://leapsparkagency.com">Leap Spark Agency</a>.</cite>',
            'Author' => '<a href="https://leapsparkagency.com">Leap Spark Agency</a>',
            'AuthorURI' => 'https://leapsparkagency.com',
            'TextDomain' => 'clue-client',
            'DomainPath' => '/languages',
            'Network' => false,
            'Title' => '<a href="https://leapsparkagency.com">Clue Client</a>',
            'AuthorName' => 'Leap Spark Agency',
        );

        \WP_Mock::userFunction( 'get_plugin_data', array(
            'return_in_order' => array(
                array(
                    'Name' => '',
                ), // [1]
                $wp_plugin, // [2]
            ),
        ) );

        // [1]
        $this->assertFalse( $plugin->on_delete_plugin( '' ), 'should return false if get_plugin_data returns false' );

        // [2]
        $this->assertTrue( $plugin->on_delete_plugin( '' ), 'should call dispatch and return true' );
    }


    public function test_on_upgrade_plugin() {
        $this->logStub->method( 'dispatch' )
                      ->with( array(
                          'trigger'  => 'Clue\Core\Triggers\Plugin',
                          'action'   => 'plugin_updated',
                          'severity' => 'warning',
                          'details'  => array(
                              'plugin_name'         => 'Clue Client',
                              'plugin_uri'          => 'https://leapsparkagency.com',
                              'plugin_version'      => '1.1.0',
                              'plugin_author'       => '<a href="https://leapsparkagency.com">Leap Spark Agency</a>',
                              'plugin_author_uri'   => 'https://leapsparkagency.com',
                              'plugin_network_wide' => false,
                          ),
                      ) )
                      ->willReturn( true );
        $plugin = new Clue\Core\Triggers\Plugin( $this->logStub, $this->loaderStub );

        $wp_plugin = array (
            'Name' => 'Clue Client',
            'PluginURI' => 'https://leapsparkagency.com',
            'Version' => '1.1.0',
            'Description' => 'The client companion for the Clue event log application. <cite>By <a href="https://leapsparkagency.com">Leap Spark Agency</a>.</cite>',
            'Author' => '<a href="https://leapsparkagency.com">Leap Spark Agency</a>',
            'AuthorURI' => 'https://leapsparkagency.com',
            'TextDomain' => 'clue-client',
            'DomainPath' => '/languages',
            'Network' => false,
            'Title' => '<a href="https://leapsparkagency.com">Clue Client</a>',
            'AuthorName' => 'Leap Spark Agency',
        );

        $wp_upgrader = $this->getMockBuilder( \Plugin_Upgrader::class )->getMock();
        $wp_upgrader->skin = new stdClass();
        $wp_upgrader->skin->plugin_info = $wp_plugin;

        // [1]
        $this->assertFalse( $plugin->on_upgrade_plugin( new stdClass(),  array() ), 'should return false $wp_upgrader is not an instance of Plugin_Upgrader' );

        $this->assertFalse( $plugin->on_upgrade_plugin( $wp_upgrader, array(
            'action' => 'install',
        ) ), 'should return false if the action is not update' );

        $this->assertTrue( $plugin->on_upgrade_plugin( $wp_upgrader, array(
            'action' => 'update',
        ) ), 'should call dispatch and return true' );
    }
}
