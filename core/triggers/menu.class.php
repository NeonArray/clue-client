<?php
/**
 * The file that defines the Menu trigger class
 *
 * A class definition that includes attributes and functions used
 * to detect changes in menu related properties and actions.
 *
 * @link       https://leapsparkagency.com
 * @since      1.0.0
 *
 * @package    Client
 * @subpackage Client/Core/Triggers
 */

namespace Clue\Core\Triggers;

use Clue\Core\Loader;
use Clue\Core\Utils\Papertrail;
use Clue\Core\Utils\Severity;

/**
 * Class Menu
 *
 * @package Clue\Core\Triggers
 */
class Menu extends Trigger {

    protected $lexicon = array(
        'description' => 'Triggers events pertaining to menus',
        'capability'  => 'export',
        'actions'    => array(
            'created_menu'          => Severity::INFO,
            'edited_menu'           => Severity::INFO,
            'deleted_menu'          => Severity::INFO,
            'edited_menu_locations' => Severity::INFO,
        ),
    );


    public function __construct( Papertrail $papertrail_instance, Loader $loader, string $slug = __CLASS__ ) {
        parent::__construct( $papertrail_instance, $loader, $slug );
    }


    public function attach_hooks() {
        $this->loader->add_action( 'load-nav-menus.php', $this, 'on_menu_action' );
        $this->loader->add_action( 'wp_create_nav_menu', $this, 'on_create_menu', 10, 2 );
    }


    /**
     * Fires after a navigation menu is successfully created.
     *
     * @link https://developer.wordpress.org/reference/hooks/wp_create_nav_menu/
     *
     * @since 1.0.0
     *
     * @param int   $term_id
     * @param array $menu_data
     *
     * @return bool
     */
    public function on_create_menu( int $term_id, array $menu_data ) : bool {
        return $this->log->dispatch( array(
            'trigger'  => $this->slug,
            'action'   => 'created_menu',
            'severity' => $this->lexicon['actions']['created_menu'],
            'details'  => array(
                'id'   => $term_id,
                'name' => $menu_data['menu-name'],
            ),
        ) );
    }


    /**
     * Fires when a menu is deleted.
     *
     * @since 1.0.0
     *
     * @param array $request Request object, cleaned.
     *
     * @return bool
     */
    public function on_menu_delete( array $request ) : bool {
        return $this->log->dispatch( array(
            'trigger' => $this->slug,
            'action'  => 'deleted_menu',
            'severity' => $this->lexicon['actions']['deleted_menu'],
            'details'  => array(
                'menu_id' => $request['menu'],
            ),
        ) );
    }


    /**
     * Fired when a menu is edited.
     *
     * @since 1.0.0
     *
     * @param array  $request Request object, cleaned.
     *
     * @return bool
     */
    public function on_menu_update( array $request ) : bool {
        $menu_diffs = $this->get_menu_diff( $request['menu'] );

        return $this->log->dispatch( array(
            'trigger'  => $this->slug,
            'action'   => 'edited_menu',
            'severity' => $this->lexicon['actions']['edited_menu'],
            'details'  => array(
                'menu_id'            => $request['menu'],
                'menu_items_added'   => $menu_diffs['incoming_menu_items'] ?? 0,
                'menu_items_removed' => $menu_diffs['previous_menu_items'] ?? 0,
            ),
        ) );
    }


    /**
     * Returns a diff of a menu. Compares the previous items to the new items.
     *
     * @since 1.0.0
     *
     * @param int $menu_id ID of a menu.
     *
     * @return array
     */
    public function get_menu_diff( int $menu_id ) : array {
        $post = $this->get_post();
        $previous_items = wp_get_nav_menu_items( $menu_id );

        if ( ! $previous_items ) {
            return array();
        }

        $previous_id_set = wp_list_pluck( $previous_items, 'db_id' );
        $incoming_id_set = $post['menu-item-db-id'] ?? array();

        $previous_menu = array_diff( $previous_id_set, $incoming_id_set );
        $incoming_menu = array_diff( $incoming_id_set, $previous_id_set );

        return array(
            'previous_menu_items' => $previous_menu,
            'incoming_menu_items' => $incoming_menu,
        );
    }


    /**
     * Invokes the appropriate method based on the action.
     *
     * @since 1.0.0
     *
     * @param string $term_id ID of the resource being modified.
     *
     * @return bool
     */
    public function on_menu_action( string $term_id ) {
        $request = filter_var_array( $_REQUEST, FILTER_SANITIZE_STRING );

        if ( ! isset( $request['menu'], $request['action'] ) ) {
            return false;
        }

        $action = "on_menu_{$request['action']}";

        if ( ! is_nav_menu( $request['menu'] ) ) {
            return false;
        }

        // Dynamically call the correct menu action
        return $this->$action( $request );
    }
}
