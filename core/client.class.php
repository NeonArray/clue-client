<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://leapsparkagency.com
 * @since      1.0.0
 *
 * @package    Client
 * @subpackage Client/includes
 */

namespace Clue\Core;

use Clue\Core\Utils;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Client
 * @subpackage Client/includes
 * @author     Aaron Arney <aarney@leapsparkagency.com>
 */
class Client {


	protected $loader;

	protected $log;

	protected $plugin_name;

	protected $version;

	protected $instantiated_triggers;


    /**
     * Client constructor.
     *
     * @since 1.0.0
     *
     * @param Loader           $loader      Instance of a Loader class.
     * @param Utils\Papertrail $papertrail  Instance of a papertrail class.
     * @param array            $triggers    Array of triggers to instantiate.
     */
	public function __construct( Loader $loader, Utils\Papertrail $papertrail, array $triggers = array() ) {

		if ( defined( 'CLUE_CLIENT' ) ) {
			$this->version = CLUE_CLIENT;
		} else {
			$this->version = '1.0.0';
		}

        $this->loader = $loader;
        $this->plugin_name = 'clue-client';
        $this->log = $papertrail;

        $this->instantiate_triggers( $triggers );

        $this->run();

        $this->set_instantiated_triggers();

        $this->initialize_options_page();
	}


	public function initialize_options_page() {
        if ( is_admin() ) {
            $options = new Options( $this->plugin_name, $this->version );

            $this->loader->add_action( 'admin_menu', $options, 'add_options_page' );
            $this->loader->add_action( 'admin_init', $options, 'page_init' );
        }
    }


	protected function instantiate_triggers( array $triggers ) {
        foreach ( $triggers as $trigger ) {
            $class = "Clue\Core\Triggers\\" . $trigger;

            try {
                new $class( $this->log, $this->loader );
            } catch ( \Exception $e ) {
                error_log( $trigger . ' class could not be instantiated', 1, 'aarney@leapsparkagency.com' );
            }
        }
    }


    public function set_instantiated_triggers() {
        $this->instantiated_triggers = array_filter( get_declared_classes(), function ( $class ) {
            return strpos( $class, 'Triggers' ) > 0;
        } );
    }


    public function get_instantiated_triggers() : array {
	    return $this->instantiated_triggers;
    }


	public function run() : bool {
		return $this->loader->run();
	}


	public function get_plugin_name() : string {
		return $this->plugin_name;
	}


	public function get_loader() : Loader {
		return $this->loader;
	}


	public function get_version() : string {
		return $this->version;
	}
}
