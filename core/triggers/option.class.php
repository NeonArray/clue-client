<?php
/**
 * The file that defines the Option trigger class
 *
 * A class definition that includes attributes and functions used
 * to detect changes in option related properties and actions.
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
 * Class Option
 *
 * @package Clue\Core\Triggers
 */
class Option extends Trigger {

    protected $lexicon = array(
        'description' => 'Triggers events pertaining to options API',
        'capability'  => 'manage_options',
        'actions'    => array(
            'updated_option' => Severity::INFO,
        ),
    );


    public function __construct( Papertrail $papertrail_instance, Loader $loader, string $slug = __CLASS__ ) {
        parent::__construct( $papertrail_instance, $loader, $slug );
    }


    public function attach_hooks() {
        $this->loader->add_action( 'updated_option', $this, 'on_update_option', 10, 3 );
    }


    /**
     * Fires after the value of an option has been successfully updated.
     *
     * @link https://developer.wordpress.org/reference/hooks/updated_option/
     *
     * @since 1.0.0
     *
     * @param string $option     Name of the updated option.
     * @param mixed  $old_value  The old option value.
     * @param mixed  $new_value  The new option value.
     *
     * @return bool
     */
    public function on_update_option( string $option, $old_value, $new_value ) : bool {
        $request = $this->get_request();
        $server  = $this->get_server();

        if ( empty( $server['REQUEST_URI'] ) || 'update' !== $request['action']) {
            return false;
        }

        // Don't track changes on plugin option pages
        if ( ! $this->is_native_option_page( $option ) ) {
            return false;
        }

        return $this->log->dispatch( array(
            'trigger'  => $this->slug,
            'action'   => 'updated_option',
            'severity' => $this->lexicon['actions']['updated_option'],
            'details'  => array(
                'option'    => $option,
                'old_value' => $old_value,
                'new_value' => $new_value,
            ),
        ) );
    }


    protected function is_native_option_page( string $option ) : bool {
        $request = $this->get_request();
        $server  = $this->get_server();
        $option_page = $request['option_page'] ?? '';
        $accepted_pages = array(
            'general',
            'discussion',
            'media',
            'reading',
            'writing',
        );
        $is_valid_page = $option_page && in_array( $option_page, $accepted_pages );

        // Permalink settings have to be detected through http referrer
        if ( false !== strpos( $server['REQUEST_URI'], 'options-permalink.php' ) ) {
            $is_valid_page = true;
        }

        // Exclude rewrite rules
        if ( in_array( $option, array( 'rewrite_rules' ) ) ) {
            $is_valid_page = false;
        }

        return $is_valid_page;
    }
}
