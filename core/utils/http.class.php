<?php
/**
 * The file that defines the Http utility class
 *
 * A class definition that includes attributes and functions used
 * for the http service.
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
 * Class Http
 *
 * @package Clue\Core\Utils
 */
class Http {


    /**
     * Post data to the remote server.
     *
     * @param array $data The array of data to send
     *
     * @return bool If the request was successful
     */
    public static function post( array $data ) : bool {
        $endpoint = self::get_endpoint();

        if ( self::get_api_key() === '' ) {
            return false;
        }

        $result = wp_remote_post( $endpoint, array(
            'method'  => 'POST',
            'headers' => array(
                'x-auth'       => self::get_api_key(),
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode( $data ),
        ) );

        if ( is_wp_error( $result ) || $result['response']['code'] !== 200 ) {
            return false;
        }

        return true;
    }


    public static function get_endpoint() : string {
        return defined( 'CLUE_API_ENDPOINT' ) ? CLUE_API_ENDPOINT : 'localhost';
    }


    public static function get_api_key() : string {
        $option = Options::get_options();

        return $option['api_key'] ?? '';
    }
}
