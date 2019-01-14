<?php

class TestTriggerMedia extends \WP_Mock\Tools\TestCase {

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
        $core = new Clue\Core\Triggers\Media( $this->logStub, $this->loaderStub );

        $this->assertTrue( is_array( $core->get_lexicon() ), 'should return an array' );
        $this->assertArrayHasKey( 'description', $core->get_lexicon(), 'should contain a description key' );
        $this->assertArrayHasKey( 'capability', $core->get_lexicon(), 'should contain a capability key' );
        $this->assertArrayHasKey( 'actions', $core->get_lexicon(), 'should contain a actions key' );
    }


    public function test_attach_hooks() {
        $media = new Clue\Core\Triggers\Media( $this->logStub, new Clue\Core\Loader() );

        \WP_Mock::expectActionAdded( 'admin_init', array( $media, 'on_admin_init' ) );
        \WP_Mock::expectActionAdded( 'xmlrpc_call_success_mw_newMediaObject', array( $media, 'on_new_media_object' ) );
        \WP_Mock::expectActionAdded( 'add_attachment', array( $media, 'on_add_attachment' ) );
        \WP_Mock::expectActionAdded( 'edit_attachment', array( $media, 'on_edit_attachment' ) );
        \WP_Mock::expectActionAdded( 'delete_attachment', array( $media, 'on_delete_attachment' ) );

        $media->attach_hooks();
        $media->on_admin_init();
        $loader = $media->get_loader();
        $loader->run();
        $this->assertHooksAdded();
    }


    public function test_on_new_media_object() {
        $media = $this->getMockBuilder( Clue\Core\Triggers\Media::class )
                      ->setConstructorArgs( array( $this->logStub, $this->loaderStub ) )
                      ->setMethods( array( 'get_file_meta' ) )
                      ->getMock();
        $media->method( 'get_file_meta' )->willReturnOnConsecutiveCalls(
            array(),
                array(
                    'post_type'           => 'attachment',
                    'attachment_id'       => 1,
                    'attachment_title'    => 'web-of-murder-color',
                    'attachment_filename' => 'web-of-murder-color.jpg',
                    'attachment_mime'     => 'image/jpeg',
                    'attachment_filesize' => 0,
                )
            );

        $this->logStub->method( 'dispatch' )
                      ->with( array(
                            'trigger'  => 'Clue\Core\Triggers\Media',
                            'action'   => 'attachment_created',
                            'severity' => 'info',
                            'details'  => array(
                                'post_type'           => 'attachment',
                                'attachment_id'       => 1,
                                'attachment_title'    => 'web-of-murder-color',
                                'attachment_filename' => 'web-of-murder-color.jpg',
                                'attachment_mime'     => 'image/jpeg',
                                'attachment_filesize' => 0,
                            ),
                          )
                      )
                      ->willReturn( true );

        $this->assertFalse( $media->on_new_media_object( 1 ), 'should return false if no post is found' );
        $this->assertTrue( $media->on_new_media_object( 1 ), 'should call dispatch and return true' );
    }


    public function test_on_add_attachment() {
        $media = $this->getMockBuilder( Clue\Core\Triggers\Media::class )
                      ->setConstructorArgs( array( $this->logStub, $this->loaderStub ) )
                      ->setMethods( array( 'get_file_meta' ) )
                      ->getMock();
        $media->method( 'get_file_meta' )->willReturnOnConsecutiveCalls(
            array(),
            array(
                'post_type'           => 'attachment',
                'attachment_id'       => 1,
                'attachment_title'    => 'web-of-murder-color',
                'attachment_filename' => 'web-of-murder-color.jpg',
                'attachment_mime'     => 'image/jpeg',
                'attachment_filesize' => 0,
            )
        );

        $this->logStub->method( 'dispatch' )
                      ->with( array(
                              'trigger'  => 'Clue\Core\Triggers\Media',
                              'action'   => 'attachment_created',
                              'severity' => 'info',
                              'details'  => array(
                                  'post_type'           => 'attachment',
                                  'attachment_id'       => 1,
                                  'attachment_title'    => 'web-of-murder-color',
                                  'attachment_filename' => 'web-of-murder-color.jpg',
                                  'attachment_mime'     => 'image/jpeg',
                                  'attachment_filesize' => 0,
                              ),
                          )
                      )
                      ->willReturn( true );

        $this->assertFalse( $media->on_add_attachment( 1 ), 'should return false if no post is found' );
        $this->assertTrue( $media->on_add_attachment( 1 ), 'should call dispatch and return true' );
    }


    public function test_on_edit_attachment() {
        $media = $this->getMockBuilder( Clue\Core\Triggers\Media::class )
                      ->setConstructorArgs( array( $this->logStub, $this->loaderStub ) )
                      ->setMethods( array( 'get_file_meta' ) )
                      ->getMock();
        $media->method( 'get_file_meta' )->willReturnOnConsecutiveCalls(
            array(),
            array(
                'post_type'           => 'attachment',
                'attachment_id'       => 1,
                'attachment_title'    => 'web-of-murder-color',
                'attachment_filename' => 'web-of-murder-color.jpg',
                'attachment_mime'     => 'image/jpeg',
                'attachment_filesize' => 0,
            )
        );

        $this->logStub->method( 'dispatch' )
                      ->with( array(
                              'trigger'  => 'Clue\Core\Triggers\Media',
                              'action'   => 'attachment_updated',
                              'severity' => 'info',
                              'details'  => array(
                                  'post_type'           => 'attachment',
                                  'attachment_id'       => 1,
                                  'attachment_title'    => 'web-of-murder-color',
                                  'attachment_filename' => 'web-of-murder-color.jpg',
                                  'attachment_mime'     => 'image/jpeg',
                                  'attachment_filesize' => 0,
                              ),
                          )
                      )
                      ->willReturn( true );

        $this->assertFalse( $media->on_edit_attachment( 1 ), 'should return false if no post is found' );
        $this->assertTrue( $media->on_edit_attachment( 1 ), 'should call dispatch and return true' );
    }


    public function test_on_delete_attachment() {
        $media = $this->getMockBuilder( Clue\Core\Triggers\Media::class )
                      ->setConstructorArgs( array( $this->logStub, $this->loaderStub ) )
                      ->setMethods( array( 'get_file_meta' ) )
                      ->getMock();
        $media->method( 'get_file_meta' )->willReturnOnConsecutiveCalls(
            array(),
            array(
                'post_type'           => 'attachment',
                'attachment_id'       => 1,
                'attachment_title'    => 'web-of-murder-color',
                'attachment_filename' => 'web-of-murder-color.jpg',
                'attachment_mime'     => 'image/jpeg',
                'attachment_filesize' => 0,
            )
        );

        $this->logStub->method( 'dispatch' )
                      ->with( array(
                              'trigger'  => 'Clue\Core\Triggers\Media',
                              'action'   => 'attachment_deleted',
                              'severity' => 'info',
                              'details'  => array(
                                  'post_type'           => 'attachment',
                                  'attachment_id'       => 1,
                                  'attachment_title'    => 'web-of-murder-color',
                                  'attachment_filename' => 'web-of-murder-color.jpg',
                                  'attachment_mime'     => 'image/jpeg',
                                  'attachment_filesize' => 0,
                              ),
                          )
                      )
                      ->willReturn( true );

        $this->assertFalse( $media->on_delete_attachment( 1 ), 'should return false if no post is found' );
        $this->assertTrue( $media->on_delete_attachment( 1 ), 'should call dispatch and return true' );
    }


    public function test_get_file_meta() {
        $factory = new WP_Factory\Factory();
        $media = $this->getMockBuilder( Clue\Core\Triggers\Media::class )
                      ->setConstructorArgs( array( $this->logStub, $this->loaderStub ) )
                      ->setMethods( array( 'get_file_size' ) )
                      ->getMock();
        $media->method( 'get_file_size' )->willReturnOnConsecutiveCalls( 0, 1000 );


        // Create a new WP_Post object
        $wp_post = $factory->post->create( array(
            'ID' => 1,
            'attachment_title' => 'Web of Murder Color',
            'attachment_filename' => 'web-of-murder-color',
        ) );



        // Mock out all the WordPress functions this method invokes
        \WP_Mock::userFunction( 'get_post', array(
            'return_in_order' => array(
                false,    // [1]
                $wp_post, // [2]
                $wp_post  // [3]
            ),
        ) );
        \WP_Mock::userFunction( 'wp_basename' )
                ->andReturn( 'web-of-murder-color.jpg' );
        \WP_Mock::userFunction( 'get_post_mime_type' )
                ->andReturn( 'image/jpeg' );
        \WP_Mock::userFunction( 'get_attached_file', array(
            'return_in_order' => array(
                false, // [2]
                '/srv/www/clue/public_html/wp-content/uploads/2018/05/web-of-murder-color.jpg', // [3]
            ),
        ) );
        \WP_Mock::userFunction( 'get_post_type' )
                ->andReturn( 'attachment' );
        \WP_Mock::userFunction( 'get_the_title' )
                ->andReturn( 'web-of-murder-color' );


        // [1]
        $this->assertEquals(
            array(),
            invokeMethod( $media, 'get_file_meta', array( 0, ), 'should return false if $wp_post is false'
        ) );

        // [2]
        $this->assertEquals(
            array(
                'post_type'           => 'attachment',
                'attachment_id'       => 1,
                'attachment_title'    => 'web-of-murder-color',
                'attachment_filename' => 'web-of-murder-color.jpg',
                'attachment_mime'     => 'image/jpeg',
                'attachment_filesize' => 0,
            ),
            invokeMethod( $media, 'get_file_meta', array( 1, ), 'should return array with file meta and 0 file size'
        ) );

        // [3]
        $this->assertEquals(
            array(
                'post_type'           => 'attachment',
                'attachment_id'       => 1,
                'attachment_title'    => 'web-of-murder-color',
                'attachment_filename' => 'web-of-murder-color.jpg',
                'attachment_mime'     => 'image/jpeg',
                'attachment_filesize' => 1000,
            ),
            invokeMethod( $media, 'get_file_meta', array( 1, ), 'should return array with file meta and 1000 file size'
        ) );
    }
}
