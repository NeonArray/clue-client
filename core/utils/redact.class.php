<?php
/**
 * The file that defines the Redact utility class
 *
 * A class definition that includes attributes and functions used
 * to redact strings.
 *
 * @link       https://leapsparkagency.com
 * @since      1.0.0
 *
 * @package    Client
 * @subpackage Client/Core/Utils
 */

namespace Clue\Core\Utils;

/**
 * Class Redact
 *
 * @package Clue\Core\Utils
 */
class Redact {


    /**
     * Redacts an email address by replacing most tokens with asterisks.
     *
     * Examples:
     *      input:  "aaron@test.com"
     *      output: "aar****@te***.com"
     *
     * @param string $email An email address string.
     *
     * @return string
     */
    public static function email( string $email ) : string {
        $valid_email = filter_var( $email, FILTER_VALIDATE_EMAIL );

        if ( ! $valid_email ) {
            return '';
        }

        return preg_replace("/([a-zA-Z]{2,3}).+@([a-zA-Z]{2}).+\.([a-zA-Z]{3})/", "$1****@$2***.$3", $valid_email );
    }
}
