<?php
/**
 * The file that defines the Post trigger class
 *
 * A class definition that includes attributes and functions used
 * to detect changes in post related properties and actions.
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
 * Class Post
 *
 * @package Clue\Core\Triggers
 */
class Post extends Trigger {


    protected $lexicon = array(
        'description' => 'Triggers events pertaining to posts',
        'capability'  => 'edit_pages',
        'actions'    => array(
            'post_created'  => Severity::INFO,
            'post_updated'  => Severity::INFO,
            'post_restored' => Severity::INFO,
            'post_deleted'  => Severity::INFO,
            'post_trashed'  => Severity::INFO,
        ),
    );


    public function __construct( Papertrail $papertrail_instance, Loader $loader, string $slug = __CLASS__ ) {
        parent::__construct( $papertrail_instance, $loader, $slug );
    }


    public function attach_hooks() {
        $this->loader->add_action( 'transition_post_status', $this, 'on_edit_post', 10, 3 );
        $this->loader->add_action( 'delete_post', $this, 'on_delete_post' );
        $this->loader->add_action( 'untrash_post', $this, 'on_untrash_post' );
    }


    /**
     * Fires when a post is transitioned from one status to another.
     *
     * @link https://developer.wordpress.org/reference/hooks/transition_post_status/
     *
     * @since 1.0.0
     *
     * @param string   $new_status New post status.
     * @param string   $old_status Old post status.
     * @param \WP_Post $post       Post object.
     *
     * @return bool
     */
    public function on_edit_post( string $new_status, string $old_status, \WP_Post $post ) : bool {

        if ( ! is_admin() ) {
            return false;
        }

        if ( wp_is_post_revision( $post ) ) {
            return false;
        }

        $skip_posttypes = array(
            'nav_menu_item',
            'jetpack_migration',
        );

        if ( in_array( $post->post_type, $skip_posttypes, true ) ) {
            return false;
        }

        return $this->log->dispatch( array(
            'trigger'  => $this->slug,
            'action'   => 'post_updated',
            'severity' =>  $this->lexicon['actions']['post_updated'],
            'details'  => array(
                'post_id'    => $post->ID,
                'post_type'  => $post->post_type,
                'post_title' => $post->post_title,
            ),
        ) );
    }


    /**
     * Fires immediately before a post is deleted from the database.
     *
     * @link https://developer.wordpress.org/reference/hooks/delete_post/
     *
     * @since 1.0.0
     *
     * @param int $post_id Post ID.
     *
     * @return bool
     */
    public function on_delete_post( int $post_id ) : bool {
        $wp_post = get_post( $post_id );

        if ( wp_is_post_revision( $post_id ) ) {
            return false;
        }

        if ( 'auto-draft' === $wp_post->post_status || 'inherit' === $wp_post->post_status ) {
            return false;
        }

        if ( 'nav_menu_item' === $wp_post->post_type ) {
            return false;
        }

        /**
         * Posts that have been in the trash for 30 days (default) are deleted by a WordPress cron job
         * using action hook `wp_scheduled_delete`. Add context for this event.
         */
        global $wp_current_filter;
        $wp_scheduled_delete = false;

        if ( isset( $wp_current_filter ) && is_array( $wp_current_filter ) ) {
            if ( in_array( 'wp_scheduled_delete', $wp_current_filter, true ) ) {
               $wp_scheduled_delete = true;
            }
        }

        return $this->log->dispatch( array(
            'trigger'  => $this->slug,
            'action'   => 'post_deleted',
            'severity' => $this->lexicon['actions']['post_deleted'],
            'details'  => array(
                'post_id'     => $post_id,
                'post_type'   => $wp_post->post_type,
                'post_title'  => $wp_post->post_title,
                'cron_delete' => $wp_scheduled_delete,
            ),
        ) );
    }


    /**
     * Fires before a post is restored from the trash.
     *
     * @link https://developer.wordpress.org/reference/hooks/untrash_post/
     *
     * @since 1.0.0
     *
     * @param int $post_id Post ID.
     *
     * @return bool
     */
    public function on_untrash_post( int $post_id ) : bool {
        $wp_post = get_post( $post_id );

        return $this->log->dispatch( array(
            'trigger'  => $this->slug,
            'action'   => 'post_restored',
            'severity' => $this->lexicon['actions']['post_restored'],
            'details'  => array(
                'post_id'    => $post_id,
                'post_type'  => $wp_post->post_type,
                'post_title' => $wp_post->post_title,
            ),
        ) );
    }
}
