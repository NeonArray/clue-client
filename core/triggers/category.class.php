<?php
/**
 * The file that defines the Category trigger class
 *
 * A class definition that includes attributes and functions used
 * to detect changes in category related properties and actions.
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
 * Class Category
 *
 * @package Clue\Core\Triggers
 */
class Category extends Trigger {

    protected $lexicon = array(
        'description' => 'Triggers events related to categories, tags, and taxonomies',
        'capability'  => 'edit_categories',
        'actions'    => array(
            'created_term' => Severity::INFO,
            'deleted_term' => Severity::INFO,
            'edited_term'  => Severity::INFO,
        ),
    );


    public function __construct( Papertrail $papertrail_instance, Loader $loader, string $slug = __CLASS__ ) {
        parent::__construct( $papertrail_instance, $loader, $slug );
    }


    public function attach_hooks() {
        $this->loader->add_action( 'created_term', $this, 'on_create_term', 10, 3 );
        $this->loader->add_action( 'delete_term', $this, 'on_delete_term', 10, 5 );
        $this->loader->add_action( 'wp_update_term_parent', $this, 'on_wp_update_term_parent', 10, 5 );
    }


    /**
     * Fires after a new term is created, and after the term cache has been cleaned.
     *
     * @link https://developer.wordpress.org/reference/hooks/created_term/
     *
     * @since 1.0.0
     *
     * @param int    $term_id   Term ID.
     * @param int    $tt_id     Term taxonomy ID.
     * @param string $taxonomy  Taxonomy slug.
     *
     * @return bool
     */
    public function on_create_term( int $term_id, int $tt_id, string $taxonomy ) : bool {
        $wp_term = get_term_by( 'id', $term_id, $taxonomy );

        if ( ! $wp_term ) {
            return false;
        }

        return $this->log->dispatch( array(
            'trigger'  => $this->slug,
            'action'   => 'created_term',
            'severity' =>  $this->lexicon['actions']['created_term'],
            'details'  => array(
                'term_id'       => $wp_term->term_id,
                'term_name'     => $wp_term->name,
                'term_taxonomy' => $wp_term->taxonomy,
            ),
        ) );
    }


    /**
     * Fires after a term is deleted from the database and the cache is cleaned.
     *
     * @link https://developer.wordpress.org/reference/hooks/delete_term/
     *
     * @since 1.0.0
     *
     * @param int    $term_id       Term ID.
     * @param int    $tt_id         Term taxonomy ID.
     * @param string $taxonomy      Taxonomy slug.
     * @param mixed  $deleted_term  Copy of the already-deleted term, in the form specified by the parent function.
     * @param array  $object_ids    List of term object IDs.
     *s
     * @return bool
     */
    public function on_delete_term( int $term_id , int $tt_id, string $taxonomy, $deleted_term, array $object_ids ) :
    bool {

        if ( is_wp_error( $deleted_term ) ) {
            return false;
        }

        return $this->log->dispatch( array(
            'trigger'  => $this->slug,
            'action'   => 'deleted_term',
            'severity' =>  $this->lexicon['actions']['deleted_term'],
            'details'  => array(
                'term_id'       => $deleted_term->term_id,
                'term_name'     => $deleted_term->name,
                'term_taxonomy' => $deleted_term->taxonomy,
            ),
        ) );
    }


    /**
     * Filters the term parent.
     *
     * @link https://developer.wordpress.org/reference/hooks/wp_update_term_parent/
     *
     * @since 1.0.0
     *
     * @param int    $parent            ID of the parent term.
     * @param int    $term_id           Term ID.
     * @param string $taxonomy          Taxonomy slug.
     * @param array  $parsed_args       An array of potentially altered update arguments for the given term.
     * @param array  $term_update_args  An array of potentially altered update arguments for the given term.
     *
     * @return bool
     */
    public function on_wp_update_term_parent( int $parent, int $term_id, string $taxonomy, array $parsed_args, array $term_update_args ) : int {
        $wp_term = get_term_by( 'id', $term_id, $taxonomy );

        if ( ! $wp_term || empty( $term_update_args ) ) {
            return $parent;
        }

        $this->log->dispatch( array(
            'trigger'  => $this->slug,
            'action'   => 'edited_term',
            'severity' =>  $this->lexicon['actions']['edited_term'],
            'details'  => array(
                'term_id'            => $wp_term->term_id,
                'from_term_name'     => $wp_term->name,
                'from_term_taxonomy' => $wp_term->taxonomy,
                'to_term_name'       => $term_update_args['name'],
                'to_term_taxonomy'   => $term_update_args['taxonomy'],
            ),
        ) );

        return $parent;
    }
}
