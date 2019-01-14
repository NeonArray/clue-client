<?php
/**
 * The file that defines the Trigger abstract class
 *
 * A class definition that includes attributes and functions used
 * across all triggers.
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
use const Patchwork\Config\FILE_NAME;

/**
 * Class Trigger
 *
 * @package Clue\Core\Triggers
 */
abstract class Trigger {


    protected $loader;

    protected $log;

    protected $slug;

    protected $lexicon;

    protected $_server;

    protected $_request;

    protected $_post;

    protected $_get;


    public function __construct( Papertrail $papertrail_instance, Loader $loader_instance, string $slug = __CLASS__ ) {
        $this->loader = $loader_instance;
        $this->log = $papertrail_instance;
        $this->slug = $slug;
        $this->_post = $this->clean_array( $_POST );
        $this->_get = $this->clean_array( $_GET );
        $this->_server = $this->clean_array( $_SERVER );
        $this->_request = $this->clean_array( $_REQUEST );

        $this->attach_hooks();
    }


    public function attach_hooks() {}


    public function lexicon() : array {
        return array();
    }


    public function get_post() : array {
        return $this->_post;
    }


    public function get_get() : array {
        return $this->_get;
    }


    public function get_server() : array {
        return $this->_server;
    }


    public function get_request() : array {
        return $this->_request;
    }


    private function clean_array( $accessor ) : array {
        $response = array();

        if ( is_null( $accessor ) ) {
            return $response;
        }

        $data = filter_var_array( $accessor, FILTER_SANITIZE_STRING );

        return $data ?? $response;
    }


    public function get_loader() : \Clue\Core\Loader {
        return $this->loader;
    }


    public function get_slug() : string {
        return $this->slug;
    }


    public function get_log() : Papertrail {
        return $this->log;
    }


    public function get_lexicon() : array {
        return $this->lexicon;
    }
}
