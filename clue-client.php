<?php
/**
 * Plugin entry point.
 *
 * @package Clue_Client
 * @author  Aaron Arney <aarney@leapsparkagency.com>
 * @link    https://leapsparkagency.com
 * @since   1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       Clue Client
 * Plugin URI:        https://leapsparkagency.com
 * Description:       The client companion for the Clue event log application.
 * Version:           1.3.0
 * Author:            Leap Spark Agency
 * Author URI:        https://leapsparkagency.com
 * License:           MS Reference
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       clue-client
 * Domain Path:       /languages
 */

namespace Clue;

if ( ! defined( 'WPINC' ) ) {
    die();
}

if ( version_compare( phpversion(), '7.0.22', '<' ) ) {
    echo 'Clue Client requires PHP version 7.0.22 or greater.';
    die();
}

define( 'CLUE_CLIENT', '1.3.0' );

// TODO: Edit this with your endpoint
define( 'CLUE_API_ENDPOINT', 'https://clue.localtunel.me/api/v1/event' );

register_activation_hook( __FILE__, function () {
    require_once plugin_dir_path( __FILE__ ) . 'core/activator.class.php';
    Core\Activator::activate();
} );


register_deactivation_hook( __FILE__, function () {
    require_once plugin_dir_path( __FILE__ ) . 'core/deactivator.class.php';
    Core\Deactivator::deactivate();
} );


/**
 * Autoloading strategy for plugin classes.
 *
 * The classes will be called in the format of a fully qualified namespace, such as:
 *
 *     Clue\Core\Triggers\User
 *
 * In order to fetch the class a few things have to be in place:
 * [1] First of all we need to make sure we are only trying to load classes pertaining to this namespace
 *     or else this loader will try and load WordPress classes as well.
 * [2] The folder structure needs to match the namespace structure. We have to strip `Clue` to match ours.
 * [3] Next, to appease Linux the path has to be lowercase.
 * [4] Lastly, we need to append `class` to the file name.
 *
 * @since 1.1.0
 */
spl_autoload_register( function ( $class ) {

    // [1]
    if ( strpos( $class, 'Clue' ) !== false ) {
        $remove_prepend = str_replace( 'Clue\\', '', $class ); // [1]
        $path = str_replace('\\', '/', strtolower( $remove_prepend ) ); // [2]
        include_once plugin_dir_path( __FILE__ ) . $path . '.class.php'; // [3]
    }

} );


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since 1.0.0
 */
function run_clue_client() {
    $triggers = array(
        'Category',
        'Core',
        'Editor',
        'Export',
        'Import',
        'Media',
        'Menu',
        'Option',
        'Plugin',
        'Post',
        'Theme',
        'User',
    );
    $Http = new Core\Utils\Http();
    $Papertrail = new Core\Utils\Papertrail( $Http );

    $plugin = new Core\Client( new Core\Loader(), $Papertrail, $triggers );
    $plugin->run();
}

run_clue_client();
