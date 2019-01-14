<?php
/**
 * The file that defines the Papertrail utility class
 *
 * A class definition that includes attributes and functions used
 * to generate an event.
 *
 * @link       https://leapsparkagency.com
 * @since      1.0.0
 *
 * @package    Client
 * @subpackage Client/Core/Utils
 */

namespace Clue\Core\Utils;

use Clue\Core\Options;

/**
 * Class Papertrail
 *
 * @package Clue\Core\Utils
 */
class Papertrail {


    protected $http;


    public function __construct( Http $http_service ) {
        $this->http = $http_service;
    }


    /**
     * Dispatch an event by gathering the required fields and posting the data to the remote server.
     *
     * @since 1.0.0
     *
     * @param array $event An array of data.
     *
     * @return bool
     */
    public function dispatch( array $event ) : bool {
        $options = Options::get_options();

        $data = array(
            'client'   => $options['client_id'] ?? 'Undefined',
            'date'     => Helpers::get_localtime(),
            'trigger'  => $event['trigger'],
            'action'   => trim( $event['action'] ),
            'severity' => $event['severity'] ?? 'info',
            'details'  => $event['details'] ?? array( 'empty' => true ),
            'meta' => array(
                $this->add_meta_context( $event['details'] )
            ),
        );

        return $this->send_post( $data );
    }


    public function send_post( array $data ) : bool {
        return $this->http::post( $data );
    }


    public function add_meta_context( $context ) : array {
        $meta = array();
        $user = $this->get_perpetrator_context( $context );
        $server = $this->get_server_context( $context );

        if ( ! empty( $user ) ) {
            $meta['user'] = $user;
        }

        if ( ! empty( $server ) ) {
            $meta['server'] = $server;
        }

        return $meta;
    }


    /**
     * Get the "perpetrator" context of this event. The perpetrator is the actor responsible for triggering the
     * event that is being created. This actor can be a cron, wp_cli, or xmlrpc_request.
     *
     * @since 1.0.0
     *
     * @param array $context The context array.
     *
     * @return array|mixed
     */
    public function get_perpetrator_context( array $context ) {

        if ( isset( $context['perpetrator'] ) ) {
            return $context['perpetrator'];
        }

        $data = $this->get_user_context();

        if ( empty( $data ) ) {
            $data['perpetrator'] = Suspects::OTHER;
        }

        if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
            $data['perpetrator'] = Suspects::WORDPRESS;
            $data['wp_cron_running'] = true;
        }

        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            $data['perpetrator'] = Suspects::WP_CLI;
        }

        // Detect XML-RPC calls and append to context, if not already there
        if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST && ! isset( $context['xmlrpc_request'] ) ) {
            $data['perpetrator'] = Suspects::XMLRPC;
            $data['xmlrpc_request'] = true;
        }

        return $data;
    }


    /**
     * The user context provides helpful information about the given user such as the email (redacted), the id, and
     * the users login name.
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function get_user_context() : array {
        $user = array();

        if ( function_exists( 'wp_get_current_user' ) ) {
            $current_user = wp_get_current_user();

            if ( isset( $current_user->ID ) ) {

                $user = array(
                    'email' => Redact::email( $current_user->user_email ),
                    'id'    => $current_user->ID,
                    'login' => $current_user->user_login,
                );
            }
        }

        return $user;
    }


    /**
     * Returns the server context information in an array. This info provides data on the http_referrer, http_origin,
     * and the http_address.
     *
     * @since 1.0.0
     *
     * @param array $context The context of the event.
     *
     * @return array
     */
    public function get_server_context( array $context ) : array {
        $server = array();

        if ( $http_referrer = $this->get_http_referrer( $context ) ) {
            $server['http_referrer'] = $http_referrer;
        }

        if ( $http_origin = $this->get_http_origin( $context ) ) {
            $server['http_origin'] = $http_origin;
        }

        if ( $http_address = $this->get_http_address( $context ) ) {
            $server['http_address'] = $http_address;
        }

        return $server;
    }


    public function get_http_referrer( array $context ) : string {
        $server_referrer = $_SERVER['HTTP_REFERER'] ?? '';
        return esc_html( $context['http_referrer'] ?? $server_referrer );
    }


    public function get_http_origin( array $context ) : string {
        if ( ! isset( $context['http_referer'] ) ) {
            return '';
        }

        $url = parse_url( $context['http_referer'] );

        return esc_html( $url['host'] ?? '' );
    }


    public function get_http_address( array $context ) : string {
        $addresses = '';

        if ( ! isset( $context['http_address'] ) && ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $addresses = esc_html( $_SERVER['REMOTE_ADDR'] );
        }

        return $addresses;
    }
}
