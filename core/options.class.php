<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.2.1
 *
 * @package    Clue
 * @subpackage Clue\Core
 */

namespace Clue\Core;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Clue
 * @subpackage Clue\Core
 */
class Options {

    private $plugin_name;

    private $options;

    private $version;


    public function __construct( string $plugin_name, string $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->options = $this->get_options();
    }


    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, plugin_dir_path( __FILE__ ) . 'public/css/clue-client.css', array(),
            $this->version, 'all' );
    }


    public static function get_options() {
        $options = get_option( 'clue_options' );

        return array_map( function ( $option ) {
            return esc_html( $option );
        }, $options );
    }


    public function add_options_page() {
        add_submenu_page(
            'options-general.php',
            'Clue Client',
            'Clue Client',
            'manage_options',
            'clue-client',
            array( $this, 'display_options_page' )
        );
    }


    /**
     * Gets executed by the function callback param in $this->add_options_page
     */
    public function display_options_page() {
        require_once plugin_dir_path( __FILE__ ) . 'public/partials/client-public-display.php';
    }


    public function page_init() {
        register_setting(
            'clue_options_group',
            'clue_options',
            array( $this, 'sanitize' )
        );

        add_settings_section(
            'clue_options_section',
            'General Settings',
            '',
            'clue-options-admin'
        );

        add_settings_field(
            'api_key',
            'API Key',
            array( $this, 'api_key_callback' ),
            'clue-options-admin',
            'clue_options_section'
        );

        add_settings_field(
            'client_id',
            'Client ID',
            array( $this, 'client_id_callback' ),
            'clue-options-admin',
            'clue_options_section'
        );
    }


    public function sanitize( array $input ) : array {
        $new_input = array();

        if ( isset( $input['api_key'] ) ) {
            $new_input['api_key'] = esc_html( $input['api_key'] );
        }

        if ( isset( $input['client_id'] ) ) {
            $new_input['client_id'] = esc_html( $input['client_id'] );
        }

        return $new_input;
    }


    public function client_id_callback() {
        $client_id = $this->options['client_id'] ?? '';

        printf(
            '<input type="text" id="client_id" name="clue_options[client_id]" value="%s" />',
            esc_attr( $client_id )
        );
    }


    public function api_key_callback() {
        $api_key = $this->options['api_key'] ?? '';

        printf(
            '<input type="text" id="api_key" name="clue_options[api_key]" value="%s" />',
            esc_attr( $api_key )
        );
    }
}
