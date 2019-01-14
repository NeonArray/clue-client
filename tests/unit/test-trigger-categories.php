<?php

class TestTriggerCategories extends \WP_Mock\Tools\TestCase {

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
        $core = new Clue\Core\Triggers\Import( $this->logStub, $this->loaderStub );

        $this->assertTrue( is_array( $core->get_lexicon() ), 'should return an array' );
        $this->assertArrayHasKey( 'description', $core->get_lexicon(), 'should contain a description key' );
        $this->assertArrayHasKey( 'capability', $core->get_lexicon(), 'should contain a capability key' );
        $this->assertArrayHasKey( 'actions', $core->get_lexicon(), 'should contain a actions key' );
    }


    public function test_attach_hooks() {
        $category = new Clue\Core\Triggers\Category( $this->logStub, new Clue\Core\Loader() );

        \WP_Mock::expectActionAdded( 'created_term', array( $category, 'on_create_term' ), 10, 3 );
        \WP_Mock::expectActionAdded( 'delete_term', array( $category, 'on_delete_term' ), 10, 5 );
        \WP_Mock::expectActionAdded( 'wp_update_term_parent', array( $category, 'on_wp_update_term_parent' ), 10, 5 );

        $category->attach_hooks();
        $loader = $category->get_loader();
        $loader->run();
        $this->assertHooksAdded();
    }


    public function test_on_create_term() {
        $category = new Clue\Core\Triggers\Category( $this->logStub, $this->loaderStub );

        $this->logStub->method( 'dispatch' )
                      ->with( array(
                          'trigger'  => 'Clue\Core\Triggers\Category',
                          'action'   => 'created_term',
                          'severity' =>  'info',
                          'details'  => array(
                              'term_id'       => 1,
                              'term_name'     => 'blogs',
                              'term_taxonomy' => 'something'
                          ),
                      ) )
                      ->willReturn( true );

        $wp_term = new stdClass();
        $wp_term->term_id = 1;
        $wp_term->name = 'blogs';
        $wp_term->taxonomy = 'something';

        \WP_Mock::userFunction( 'get_term_by', array(
            'return_in_order' => array(
                false, // [1]
                $wp_term, // [2]
            )
        ) );

        // [1]
        $this->assertFalse( $category->on_create_term( 0, 0, '' ), 'should return false if no term is found by given ID' );

        // [2]
        $this->assertTrue( $category->on_create_term( 0, 0, '' ), 'should call dispatch and return true' );
    }


    public function test_on_delete_term() {
        $category = new Clue\Core\Triggers\Category( $this->logStub, $this->loaderStub );

        $this->logStub->method( 'dispatch' )
                      ->with( array(
                          'trigger'  => 'Clue\Core\Triggers\Category',
                          'action'   => 'deleted_term',
                          'severity' =>  'info',
                          'details'  => array(
                              'term_id'       => 1,
                              'term_name'     => 'blogs',
                              'term_taxonomy' => 'something'
                          ),
                      ) )
                      ->willReturn( true );

        $wp_term = new stdClass();
        $wp_term->term_id = 1;
        $wp_term->name = 'blogs';
        $wp_term->taxonomy = 'something';

        \WP_Mock::userFunction( 'is_wp_error', array(
            'return_in_order' => array(
                true, // [1]
                false, // [2]
            )
        ) );

        // [1]
        $this->assertFalse( $category->on_delete_term( 0, 0, '', '', array() ), 'should return false if deleted_term is a WP error object' );

        // [2]
        $this->assertTrue( $category->on_delete_term( 0, 0, '', $wp_term, array() ), 'should call dispatch and return true' );
    }


    public function test_on_wp_update_term_parent() {
        $category = new Clue\Core\Triggers\Category( $this->logStub, $this->loaderStub );

        $this->logStub->method( 'dispatch' )
                      ->with( array(
                          'trigger'  => 'Clue\Core\Triggers\Category',
                          'action'   => 'edited_term',
                          'severity' =>  'info',
                          'details'  => array(
                              'term_id'            => 1,
                              'from_term_name'     => 'blogs',
                              'from_term_taxonomy' => 'something',
                              'to_term_name'       => 'test',
                              'to_term_taxonomy'   => 'test',
                          ),
                      ) )
                      ->willReturn( true );

        $wp_term = new stdClass();
        $wp_term->term_id = 1;
        $wp_term->name = 'blogs';
        $wp_term->taxonomy = 'something';

        \WP_Mock::userFunction( 'get_term_by', array(
            'return_in_order' => array(
                false,    // [1]
                true,     // [2]
                $wp_term, // [3]
            )
        ) );

        // [1]
        $this->assertEquals(
            0,
            $category->on_wp_update_term_parent(
                0,
                0,
                '',
                array(),
                array()
            ), 'should return $parent if $wp_term is false'
        );

        // [2]
        $this->assertEquals(
            0,
            $category->on_wp_update_term_parent(
                0,
                0,
                '',
                array(),
                array()
            ), 'should return $parent if $term_update_args is empty'
        );

        // [3]
        $this->assertEquals(
            0,
            $category->on_wp_update_term_parent(
                0,
                0,
                '',
                array(),
                array(
                    'name'     => 'test',
                    'taxonomy' => 'test',
                )
            ), 'should call dispatch and return $parent'
        );
    }
}
