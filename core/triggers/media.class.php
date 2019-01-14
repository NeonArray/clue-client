<?php
/**
 * The file that defines the Media trigger class
 *
 * A class definition that includes attributes and functions used
 * to detect changes in Media related properties and actions.
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
 * Class Media
 *
 * @package Clue\Core\Triggers
 */
class Media extends Trigger {

    protected $lexicon = array(
        'description' => 'Triggers events for media upload and editing',
        'capability'  => 'edit_pages',
        'actions'    => array(
            'attachment_created' => Severity::INFO,
            'attachment_updated' => Severity::INFO,
            'attachment_deleted' => Severity::INFO,
        ),
    );


    public function __construct( Papertrail $papertrail_instance, Loader $loader, string $slug = __CLASS__ ) {
        parent::__construct( $papertrail_instance, $loader, $slug );
    }


    public function attach_hooks() {
        $this->loader->add_action( 'admin_init', $this, 'on_admin_init' );
        $this->loader->add_action( 'xmlrpc_call_success_mw_newMediaObject', $this, 'on_new_media_object' );
    }


    public function on_admin_init() {
        $this->loader->add_action( 'add_attachment', $this, 'on_add_attachment' );
        $this->loader->add_action( 'edit_attachment', $this, 'on_edit_attachment' );
        $this->loader->add_action( 'delete_attachment', $this, 'on_delete_attachment' );
        $this->loader->run();
    }


    /**
     * Fires after a new attachment has been added via the XML-RPC MovableType API.
     *
     * @link https://developer.wordpress.org/reference/hooks/xmlrpc_call_success_mw_newmediaobject/
     *
     * @since 1.0.0
     *
     * @param int   $attachment_id 	ID of the new attachment.
     *
     * @return bool
     */
    public function on_new_media_object( int $attachment_id ) : bool {
        $meta = $this->get_file_meta( $attachment_id );

        if ( empty( $meta ) ) {
            return false;
        }

        return $this->log->dispatch( array(
            'trigger'  => $this->slug,
            'action'   => 'attachment_created',
            'severity' =>  $this->lexicon['actions']['attachment_created'],
            'details'  => array(
                'post_type'           => $meta['post_type'],
                'attachment_id'       => $meta['attachment_id'],
                'attachment_title'    => $meta['attachment_title'],
                'attachment_filename' => $meta['attachment_filename'],
                'attachment_mime'     => $meta['attachment_mime'],
                'attachment_filesize' => $meta['attachment_filesize'],
            ),
        ) );
    }


    /**
     * Fires once an attachment has been added.
     *
     * @link https://developer.wordpress.org/reference/hooks/add_attachment/
     *
     * @since 1.0.0
     *
     * @param int $attachment_id ID of the new attachment.
     *
     * @return bool
     */
    public function on_add_attachment( int $attachment_id ) : bool {
        $meta = $this->get_file_meta( $attachment_id );

        if ( empty( $meta ) ) {
            return false;
        }

        return $this->log->dispatch( array(
            'trigger'  => $this->slug,
            'action'   => 'attachment_created',
            'severity' =>  $this->lexicon['actions']['attachment_created'],
            'details'  => array(
                'post_type'           => $meta['post_type'],
                'attachment_id'       => $meta['attachment_id'],
                'attachment_title'    => $meta['attachment_title'],
                'attachment_filename' => $meta['attachment_filename'],
                'attachment_mime'     => $meta['attachment_mime'],
                'attachment_filesize' => $meta['attachment_filesize'],
            ),
        ) );
    }


    /**
     * Fires once an existing attachment has been updated.
     *
     * @link https://developer.wordpress.org/reference/hooks/edit_attachment-5/
     *
     * @since 1.0.0
     *
     * @param int $attachment_id ID of the new attachment.
     *
     * @return bool
     */
    public function on_edit_attachment( int $attachment_id ) : bool {
        $meta = $this->get_file_meta( $attachment_id );

        if ( empty( $meta ) ) {
            return false;
        }

        return $this->log->dispatch( array(
            'trigger'  => $this->slug,
            'action'   => 'attachment_updated',
            'severity' =>  $this->lexicon['actions']['attachment_updated'],
            'details'  => array(
                'post_type'           => $meta['post_type'],
                'attachment_id'       => $meta['attachment_id'],
                'attachment_title'    => $meta['attachment_title'],
                'attachment_filename' => $meta['attachment_filename'],
                'attachment_mime'     => $meta['attachment_mime'],
                'attachment_filesize' => $meta['attachment_filesize'],
            ),
        ) );
    }


    /**
     * Fires before an attachment is deleted, at the start of wp_delete_attachment().
     *
     * @link https://developer.wordpress.org/reference/hooks/delete_attachment/
     *
     * @since 1.0.0
     *
     * @param int $attachment_id ID of the new attachment.
     *
     * @return bool
     */
    public function on_delete_attachment( int $attachment_id ) : bool {
        $meta = $this->get_file_meta( $attachment_id );

        if ( empty( $meta ) ) {
            return false;
        }

        return $this->log->dispatch( array(
            'trigger'  => $this->slug,
            'action'   => 'attachment_deleted',
            'severity' =>  $this->lexicon['actions']['attachment_deleted'],
            'details'  => array(
                'post_type'           => $meta['post_type'],
                'attachment_id'       => $meta['attachment_id'],
                'attachment_title'    => $meta['attachment_title'],
                'attachment_filename' => $meta['attachment_filename'],
                'attachment_mime'     => $meta['attachment_mime'],
                'attachment_filesize' => $meta['attachment_filesize'],
            ),
        ) );
    }


    /**
     * Returns a file size.
     *
     * @since 1.0.0
     *
     * @param string $file The file.
     *
     * @return int
     */
    protected function get_file_size( string $file ) : int {
        return file_exists( $file ) ? filesize( $file ) : 0;
    }


    /**
     * Gets file data for a given attachment.
     *
     * @since 1.0.0
     *
     * @param int $id The attachment ID.
     *
     * @return array
     */
    protected function get_file_meta( int $id ) : array {
        $wp_post = get_post( $id );

        if ( ! $wp_post ) {
            return array();
        }

        $filename = wp_basename( $wp_post->guid );
        $mime = get_post_mime_type( $wp_post );
        $file = get_attached_file( $id );
        $file_size = $this->get_file_size( $file );

        return array(
            'post_type'           => get_post_type( $wp_post ),
            'attachment_id'       => $id,
            'attachment_title'    => get_the_title( $wp_post ),
            'attachment_filename' => $filename,
            'attachment_mime'     => $mime,
            'attachment_filesize' => $file_size,
        );
    }
}
