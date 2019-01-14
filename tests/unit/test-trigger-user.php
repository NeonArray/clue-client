<?php

class TestTriggerUser extends \WP_Mock\Tools\TestCase {


    protected $logStub;

    protected $loaderStub;

    protected $userStub;


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

        $this->userStub = $this->getMockBuilder( WP_User::class )->getMock();
        $userInfo                      = new stdClass();
        $userInfo->ID                  = 1;
        $userInfo->user_login          = 'aaron';
        $userInfo->user_pass           = '$P$BeKT9xgze0mX9zw6GwgTBiQPLlo0mp';
        $userInfo->user_nicename       = 'aaron';
        $userInfo->user_email          = 'aarney@thejumpagency.com';
        $userInfo->user_url            = '';
        $userInfo->user_registered     = '';
        $userInfo->user_activation_key = '';
        $userInfo->user_status         = 0;
        $userInfo->display_name        = 'aaron';
        $this->userStub->data = $userInfo;
    }


    public function tearDown() {
        \WP_Mock::tearDown();
    }


    public function test_lexicon() {
        $this->logStub->method( 'dispatch' )
                      ->willReturn( true );

        $themes = new Clue\Core\Triggers\User( $this->logStub, $this->loaderStub );

        $this->assertTrue( is_array( $themes->get_lexicon() ), 'should return an array' );
        $this->assertArrayHasKey( 'description', $themes->get_lexicon(), 'should contain a description key' );
        $this->assertArrayHasKey( 'capability', $themes->get_lexicon(), 'should contain a capability key' );
        $this->assertArrayHasKey( 'actions', $themes->get_lexicon(), 'should contain a actions key' );
    }


    public function test_attach_hooks() {
        $this->logStub->method( 'dispatch' )
                      ->willReturn( true );

        $user = new Clue\Core\Triggers\User( $this->logStub, $this->loaderStub );

        \WP_Mock::expectActionAdded( 'wp_login', array( $user, 'on_login' ), 10, 2 );
        \WP_Mock::expectActionAdded( 'wp_logout', array( $user, 'on_logout' ) );
        \WP_Mock::expectActionAdded( 'wp_authenticate_user', array( $user, 'on_authenticated_user' ), 10, 2 );
        \WP_Mock::expectActionAdded( 'authenticate', array( $user, 'on_authenticate' ), 30, 3 );
        \WP_Mock::expectActionAdded( 'user_register', array( $user, 'on_user_register' ) );
        \WP_Mock::expectActionAdded( 'delete_user', array( $user, 'on_delete_user' ), 10, 2 );
        \WP_Mock::expectActionAdded( 'wp_ajax_destroy_sessions', array( $user, 'on_destroy_user_session' ), 0 );
        \WP_Mock::expectActionAdded( 'validate_password_reset', array( $user, 'on_validate_password_reset' ), 10, 2 );
        \WP_Mock::expectActionAdded( 'retrieve_password_message', array( $user, 'on_retrieve_password_manage' ),
            10, 4 );
        \WP_Mock::expectActionAdded( 'insert_user_meta', array( $user, 'on_insert_user_meta' ), 10, 3 );

        $user->attach_hooks();
        $loader = $user->get_loader();
        $loader->run();
        $this->assertHooksAdded();
    }


    public function test_on_login() {
        $this->logStub->method( 'dispatch' )
                      ->with( array(
                          'trigger' => 'Clue\Core\Triggers\User',
                          'action' => 'user_logged_in',
                          'severity' =>  'info',
                          'details' => array(
                              'id' => 1,
                              'email' => 'aar****@th***.com',
                          ),
                      ) )
                      ->willReturn( true );

        $wp_login = $this->userStub;
        $user = new Clue\Core\Triggers\User( $this->logStub, $this->loaderStub );

        $this->assertTrue( $user->on_login( 'admin',$wp_login ), 'should return true' );
    }


    public function test_on_logout() {
        $this->logStub->method( 'dispatch' )
                      ->with( array(
                          'trigger' => 'Clue\Core\Triggers\User',
                          'action' => 'user_logged_out',
                          'severity' =>  'info',
                          'details' => array(),
                      ) )
                      ->willReturn( true );

        $user = new Clue\Core\Triggers\User( $this->logStub, $this->loaderStub );

        $this->assertTrue( $user->on_logout(), 'should return true' );
    }


    public function test_on_authenticated_user() {
        $this->logStub->method( 'dispatch' )
                      ->with( array(
                          'trigger' => 'Clue\Core\Triggers\User',
                          'action' => 'user_login_failed',
                          'severity' =>  'warning',
                          'details' => array(
                              'id' => 1,
                              'user_email' => 'aar****@th***.com',
                          ),
                      ) )
                      ->willReturn( true );

        $wp_user = $this->userStub;
        $user = new Clue\Core\Triggers\User( $this->logStub, $this->loaderStub );

        \WP_Mock::userFunction( 'wp_check_password', array(
            'return_in_order' => array( true, false ),
        ) );

        $this->assertEquals( array(), $user->on_authenticated_user( array(), '' ), 'should return first arg if not a WP_User object' );

        $this->assertEquals( $wp_user, $user->on_authenticated_user( $wp_user, '' ), 'should return user stub if wp_check_password evaluates true' );

        $this->assertEquals( $wp_user, $user->on_authenticated_user( $wp_user, '' ), 'should call dispatch if wp_check_password evaluates false and return user stub' );
    }


    public function test_on_authenticate() {
        $this->logStub->method( 'dispatch' )
                      ->with( array(
                          'trigger'  => 'Clue\Core\Triggers\User',
                          'action'   => 'user_unknown_login_failed',
                          'severity' =>  'warning',
                          'details'  => array(
                              'username' => 'admin',
                          ),
                      ) )
                      ->willReturn( true );
        $user = new Clue\Core\Triggers\User( $this->logStub, $this->loaderStub );
        $wp_error = $this->getMockBuilder( WP_Error::class )
                        ->setMethods( array( 'get_error_code' ) )
                        ->getMock();
        $wp_error->method( 'get_error_code' )->will(
            $this->onConsecutiveCalls( 'invalid_poptarts', 'invalid_username' )
        );


        $this->assertEquals( null,$user->on_authenticate( null, '', '' ), 'should return null if wp_object is null' );

        $this->assertEquals( $this->userStub,$user->on_authenticate( $this->userStub, '', '' ), 'should return the $wp_user if exists' );

        $this->assertEquals( $wp_error,$user->on_authenticate( $wp_error, '', '' ), 'should return the wp_object if the error code isnt username or email related' );

        $this->assertEquals( $wp_error,$user->on_authenticate( $wp_error, 'admin', '' ), 'should return the wp_object and dispatch the message' );
    }


    public function test_on_user_register() {
        $this->logStub->method( 'dispatch' )
                      ->with( array(
                          'trigger'  => 'Clue\Core\Triggers\User',
                          'action'   => 'user_created',
                          'severity' => 'warning',
                          'details'  => array(
                              'id' => 1,
                              'username' => 'aaron',
                              'user_email' => 'aar****@th***.com',
                          ),
                      ) )
                      ->willReturn( true );
        $user = new Clue\Core\Triggers\User( $this->logStub, $this->loaderStub );
        $wp_user = $this->userStub;
        \WP_Mock::userFunction( 'get_userdata', array(
            'return_in_order' => array( $wp_user, false ),
        ) );

        $this->assertTrue( $user->on_user_register( 1 ), 'should call dispatch and return true' );
        $this->assertFalse( $user->on_user_register( 1 ), 'should return false if no user is found' );
    }


    public function test_on_delete_user() {
        $this->logStub->method( 'dispatch' )
                      ->with( array(
                          'trigger'  => 'Clue\Core\Triggers\User',
                          'action'   => 'user_deleted',
                          'severity' => 'warning',
                          'details'  => array(
                              'id' => 1,
                              'username' => 'aaron',
                              'email' => 'aar****@th***.com',
                              'reassign_content_to' => 2,
                          ),
                      ) )
                      ->willReturn( true );
        $user = new Clue\Core\Triggers\User( $this->logStub, $this->loaderStub );
        $wp_user = $this->userStub;
        \WP_Mock::userFunction( 'get_userdata', array(
            'return_in_order' => array( $wp_user, false ),
        ) );

        $this->assertTrue( $user->on_delete_user( 1, 2 ), 'should call dispatch and return true' );

        $this->assertFalse( $user->on_delete_user( 1, 2 ), 'should return false if no user is found' );
    }


    public function test_on_destroy_user_session() {
        $this->logStub->method( 'dispatch' )
                      ->willReturn( true );
        $user = new Clue\Core\Triggers\User( $this->logStub, $this->loaderStub );
        $_POST = array(
            'nonce' => '',
            'user_id' => 1,
        );
        \WP_Mock::userFunction( 'get_userdata', array(
            'return_in_order' => array(
                false, // [1]
                $this->userStub,  // [2]
                $this->userStub,  // [3]
                $this->userStub,  // [4]
            ),
        ) );
        \WP_Mock::userFunction( 'current_user_can', array(
            'return_in_order' => array(
                false, // [2]
                true,  // [3]
                true,  // [4]
            ),
        ) );
        \WP_Mock::userFunction( 'wp_verify_nonce', array(
            'return_in_order' => array(
                false, // [3]
                true,  // [4]
            ),
        ) );
        \WP_Mock::userFunction( 'get_current_user_id', array(
            'return_in_order' => array(
                0, // [4]
                1, // [5]
            ),
        ) );

        // [1]
        $this->assertFalse( $user->on_destroy_user_session(), 'should return false if no user data is returned' );

        // [2]
        $this->assertFalse( $user->on_destroy_user_session(), 'should return false if current user cant edit_user' );

        // [3]
        $this->assertFalse( $user->on_destroy_user_session(), 'should return false if nonce is false' );

        // [4]
        $this->assertTrue( $user->on_destroy_user_session(), 'should return true with user_session_destroy_everywhere' );

        // [5]
        $this->assertTrue( $user->on_destroy_user_session(), 'should return true with user_session_destroy_others' );
    }


    public function test_on_validate_password_reset() {
        $this->logStub->method( 'dispatch' )
                      ->with( array(
                          'trigger'  => 'Clue\Core\Triggers\User',
                          'action'   => 'user_password_reset',
                          'severity' => 'warning',
                          'details'  => array(
                              'id' => 1,
                              'username' => 'aaron',
                              'email' => 'aar****@th***.com',
                          ),
                      ) )
                      ->willReturn( true );
        $user = new Clue\Core\Triggers\User( $this->logStub, $this->loaderStub );
        $wp_error = $this->getMockBuilder( \WP_Error::class )
                         ->setMethods( array( 'get_error_code' ) )
                         ->getMock();
        $wp_error->method( 'get_error_code')->will(
            $this->onConsecutiveCalls(
                1,      // [1]
                false,  // [2]
                false   // [3]
            )
        );

        $this->assertFalse( $user->on_validate_password_reset( $wp_error, $wp_error ), 'should return false if $wp_error is error object' );

        // [1]
        $this->assertFalse( $user->on_validate_password_reset( $wp_error, $this->userStub ), 'should return false if $errors->get_error_code is true' );

        // [2]
        $this->assertFalse( $user->on_validate_password_reset( $wp_error, $this->userStub ), 'should return false if $_POST["pass1"] is not set' );

        // [3]
        $_POST = array(
            'pass1' => 'abc123',
        );
        $this->assertTrue( $user->on_validate_password_reset( $wp_error, $this->userStub ), 'should return true if no errors are triggered and pass1 is set' );
    }


    public function test_on_retrieve_password_manage() {
        $this->logStub->method( 'dispatch' )
                      ->with( array(
                          'trigger'  => 'Clue\Core\Triggers\User',
                          'action'   => 'user_requested_password_reset_link',
                          'severity' => 'info',
                          'details'  => array(
                              'message' => 'message',
                              'key'     => 'a',
                              'user'    => 'aaron',
                          ),
                      ) )
                      ->willReturn( true );
        $user = new Clue\Core\Triggers\User( $this->logStub, $this->loaderStub );

        $this->assertEquals( 'message',$user->on_retrieve_password_manage( 'message', 'a', 'aaron', $this->userStub ), 'should return message if $_GET["action"] isnt set' );

        $_GET = array(
            'action' => 'lostpassword',
        );
        $this->assertEquals( 'message',$user->on_retrieve_password_manage( 'message', 'a', 'aaron', $this->userStub ), 'should return message and call dispatch if $_GET["action"] is set' );
    }


    public function test_on_insert_user_meta() {
        $meta = array(
            'nickname' => 'admin',
            'first_name' => 'Aaron',
            'last_name' => 'Arney',
            'description' => '',
            'rich_editing' => 'true',
            'syntax_highlighting' => 'true',
            'comment_shortcuts' => 'false',
            'admin_color' => 'fresh',
            'use_ssl' => 0,
            'show_admin_bar_front' => 'true',
            'locale' => '',
            'email' => 'aar****@th***.com',
            'username' => 'aaron',
        );

        $this->logStub->method( 'dispatch' )
                      ->with( array(
                          'trigger'  => 'Clue\Core\Triggers\User',
                          'action'   => 'user_updated_profile',
                          'severity' => 'info',
                          'details'  => $meta,
                      ) )
                      ->willReturn( true );
        $user = new Clue\Core\Triggers\User( $this->logStub, $this->loaderStub );

        $this->assertEquals( array(), $user->on_insert_user_meta( array(), $this->userStub, false ), 'should return meta if update is false' );

        $this->assertEquals( $meta, $user->on_insert_user_meta( $meta, $this->userStub, true ), 'should call dispatch and return meta array if update is true' );
    }
}
