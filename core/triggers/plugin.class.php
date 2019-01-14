<?php
/**
 * The file that defines the Plugin trigger class
 *
 * A class definition that includes attributes and functions used
 * to detect changes in export related properties and actions.
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
 * Class Plugin
 *
 * @package Clue\Core\Triggers
 */
class Plugin extends Trigger {

    protected $lexicon = array(
        'description' => 'Triggers events pertaining to plugin',
        'capability'  => 'export',
        'actions'    => array(
            'plugin_activated'              => Severity::WARNING,
            'plugin_deactivated'            => Severity::WARNING,
            'plugin_installed'              => Severity::ALERT,
            'plugin_install_failed'         => Severity::INFO,
            'plugin_updated'                => Severity::WARNING,
            'plugin_update_failed'          => Severity::WARNING,
            'plugin_deleted'                => Severity::WARNING,
            'plugin_bulk_updated'           => Severity::WARNING,
            'plugin_disabled_because_error' => Severity::INFO,
        ),
    );


    public function __construct( Papertrail $papertrail_instance, Loader $loader, string $slug = __CLASS__ ) {
        parent::__construct( $papertrail_instance, $loader, $slug );
    }


    public function attach_hooks() {
        $this->loader->add_action( 'activated_plugin', $this, 'on_activate_plugin', 10, 2 );
        $this->loader->add_action( 'deactivated_plugin', $this, 'on_deactivate_plugin', 10, 2 );
        $this->loader->add_action( 'delete_plugin', $this, 'on_delete_plugin' );
        $this->loader->add_action( 'upgrader_process_complete', $this, 'on_upgrade_plugin', 10, 2 );
    }


    /**
     * Fires immediately before a plugin deletion attempt.
     *
     * @link https://developer.wordpress.org/reference/hooks/delete_plugin/
     *
     * @since 1.0.0
     *
     * @param string $plugin_file Plugin file name.
     *
     * @return bool
     */
    public function on_delete_plugin( string $plugin_file ) : bool {
        $wp_plugin = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file, true, false );

        if ( empty( $wp_plugin['Name'] ) ) {
            return false;
        }

        return $this->log->dispatch( array(
            'trigger'  => $this->slug,
            'action'   => 'plugin_deleted',
            'severity' => $this->lexicon['actions']['plugin_deleted'],
            'details'  => array(
                'plugin_name'         => $wp_plugin['Name'],
                'plugin_uri'          => $wp_plugin['PluginURI'],
                'plugin_version'      => $wp_plugin['Version'],
                'plugin_author'       => $wp_plugin['Author'],
                'plugin_author_uri'   => $wp_plugin['AuthorURI'],
                'plugin_network_wide' => $wp_plugin['Network'],
            ),
        ) );
    }


    /**
     * Fires after a plugin has been activated.
     *
     * @link https://developer.wordpress.org/reference/hooks/activated_plugin/
     *
     * @since 1.0.0
     *
     * @param string $plugin        Path to the main plugin file from plugins directory.
     * @param bool   $network_wide  Whether to enable the plugin for all sites in the network or just the current site. Multisite only. Default is false.
     *
     * @return bool
     */
    public function on_activate_plugin( string $plugin, bool $network_wide ) : bool {
        $wp_plugin = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin, true, false );

        // The get_plugin_data function inexplicably returns an array with the same keys whether or not a plugin
        // is found. It just returns the array with empty values.
        if ( empty( $wp_plugin['Name'] ) ) {
            return false;
        }

        return $this->log->dispatch( array(
            'trigger'  => $this->slug,
            'action'   => 'plugin_activated',
            'severity' => $this->lexicon['actions']['plugin_activated'],
            'details'  => array(
                'plugin_name'         => $wp_plugin['Name'],
                'plugin_uri'          => $wp_plugin['PluginURI'],
                'plugin_version'      => $wp_plugin['Version'],
                'plugin_author'       => $wp_plugin['Author'],
                'plugin_author_uri'   => $wp_plugin['AuthorURI'],
                'plugin_network_wide' => $wp_plugin['Network'],
            ),
        ) );
    }


    /**
     * Fires after a plugin is deactivated.
     *
     * @link https://developer.wordpress.org/reference/hooks/deactivated_plugin/
     *
     * @since 1.0.0
     *
     * @param string $plugin        Path to the main plugin file from plugins directory.
     * @param bool   $network_wide  Whether the plugin is deactivated for all sites in the network. or just the current site. Multisite only. Default false.
     * @return bool
     */
    public function on_deactivate_plugin( string $plugin, bool $network_wide ) : bool {
        $wp_plugin = get_plugin_data( WP_PLUGIN_DIR  . '/' . $plugin, true, false );

        if ( empty( $wp_plugin['Name'] ) ) {
            return false;
        }

        return $this->log->dispatch( array(
            'trigger'  => $this->slug,
            'action'   => 'plugin_deactivated',
            'severity' => $this->lexicon['actions']['plugin_deactivated'],
            'details'  => array(
                'plugin_name'         => $wp_plugin['Name'],
                'plugin_uri'          => $wp_plugin['PluginURI'],
                'plugin_version'      => $wp_plugin['Version'],
                'plugin_author'       => $wp_plugin['Author'],
                'plugin_author_uri'   => $wp_plugin['AuthorURI'],
                'plugin_network_wide' => $wp_plugin['Network'],
            ),
        ) );
    }


    /**
     * Fires when the upgrader process is complete.
     *
     * @link https://developer.wordpress.org/reference/hooks/upgrader_process_complete-3/
     *
     * @since 1.0.0
     *
     * @param \WP_Upgrader $wp_upgrader WP_Upgrader instance. In other contexts, $this, might be a Theme_Upgrader, Plugin_Upgrader, Core_Upgrade, or Language_Pack_Upgrader instance.
     * @param array        $hook_extra  Array of bulk item update data.
     *
     * @return bool
     */
    public function on_upgrade_plugin( $wp_upgrader, array $hook_extra ) : bool {

        // Only continue if a plugin is being upgraded
        if ( ! $wp_upgrader instanceof \Plugin_Upgrader ) {
            return false;
        }

        // Plugin is being installed not upgraded
        if ( isset( $hook_extra['action'] ) && 'update' !== $hook_extra['action'] ) {
            return false;
        }

        $wp_plugin = $wp_upgrader->skin->plugin_info;

        return $this->log->dispatch( array(
            'trigger'  => $this->slug,
            'action'   => 'plugin_updated',
            'severity' => $this->lexicon['actions']['plugin_updated'],
            'details'  => array(
                'plugin_name'         => $wp_plugin['Name'],
                'plugin_uri'          => $wp_plugin['PluginURI'],
                'plugin_version'      => $wp_plugin['Version'],
                'plugin_author'       => $wp_plugin['Author'],
                'plugin_author_uri'   => $wp_plugin['AuthorURI'],
                'plugin_network_wide' => $wp_plugin['Network'],
            ),
        ) );
    }
}
