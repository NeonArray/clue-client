<?php
/**
 * The file that defines the Helper utility class
 *
 * A class definition that includes attributes and functions used for the
 * helper utility class.
 *
 * @link       https://leapsparkagency.com
 * @since      1.0.0
 *
 * @package    Clue
 * @subpackage Clue/Core/Utils
 */

namespace Clue\Core\Utils;

/**
 * Class Helpers
 *
 * @package Clue\Core\Utils
 */
final class Helpers {


    public static function format_date( string $date ) : string {
        $new_date = new \DateTime( $date );
        return $new_date->format('Y-m-d h:m:s');
    }


    public static function get_localtime() : string {
        return gmdate( 'Y-m-d H:i:s' );
    }


    public static function validate_ip( $ip ) : bool {
        return (bool) filter_var( $ip, FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
    }


    public static function get_wp_version() : string {
        global $wp_version;

        try {
            include ABSPATH . WPINC . '/version.php';
        } catch ( \Exception $e ) {
            $wp_version = '0.0.0';
        }

        return esc_html( $wp_version );
    }
}
