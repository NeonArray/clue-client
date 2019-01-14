<?php
/**
 * The file that defines the Theme trigger class
 *
 * A class definition that includes attributes and functions used
 * to detect changes in theme related properties and actions.
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
 * Class Theme
 *
 * @package Clue\Core\Triggers
 */
class Theme extends Trigger {


    protected $lexicon = array(
        'description' => 'Logs theme edits',
        'capability'  => 'edit_theme_options',
        'actions'     => array(
            'theme_switched'            => Severity::INFO,
            'theme_install'             => Severity::INFO,
            'theme_deleted'             => Severity::WARNING,
            'theme_upgrade'             => Severity::INFO,
            'appearance_customized'     => Severity::INFO,
            'widget_removed'            => Severity::INFO,
            'widget_added'              => Severity::INFO,
            'widget_order_changed'      => Severity::INFO,
            'widget_edited'             => Severity::INFO,
        ),
    );


    public function __construct( Papertrail $papertrail_instance, Loader $loader, string $slug = __CLASS__ ) {
        parent::__construct( $papertrail_instance,$loader, $slug );
    }


    public function get_lexicon() : array {
        return $this->lexicon;
    }


    public function attach_hooks() {
        $this->loader->add_action( 'switch_theme', $this, 'on_switch_theme', 10, 3 );
        $this->loader->add_action( 'customize_save_after',$this, 'on_action_customize_save' );
        $this->loader->add_action( 'sidebar_admin_setup', $this, 'on_sidebar_setup' );
        $this->loader->add_filter( 'widget_update_callback', $this, 'on_widget_update', 10, 4 );
        $this->loader->add_action( 'upgrader_process_complete', $this, 'on_upgrade',
        10, 2 );
        $this->loader-> add_action( 'deleted_site_transient', $this, 'on_delete_transient' );
    }


    /**
     * When the theme is switched.
     *
     * @param string    $new_name   The new theme name.
     * @param \WP_Theme $new_theme  The new theme WordPress object.
     * @param \WP_Theme $old_theme  The old theme WordPress object.
     *
     * @return bool
     */
    public function on_switch_theme( string $new_name, \WP_Theme $new_theme, \WP_Theme $old_theme ) : bool {
        return $this->log->dispatch( array(
            'trigger'  => $this->slug,
            'action'   => 'theme_switched',
            'severity' => $this->lexicon['actions']['theme_switched'],
            'details'  => array(
                'new_theme' => array(
                    'name' => $new_theme->name,
                    'version' => $new_theme->version,
                ),
                'old_theme' => array(
                    'name' => $old_theme->name,
                    'version' => $old_theme->version,
                ),
            ),
        ) );
    }


    /**
     * When the customizer is used.
     *
     * @param \WP_Customize_Manager $customize_manager  The WordPress customizer object.
     *
     * @return bool
     */
    public function on_action_customize_save( \WP_Customize_Manager $customize_manager ) : bool {
        $modified_fields = filter_var_array($customize_manager->unsanitized_post_values(), FILTER_SANITIZE_STRING );

        return $this->log->dispatch( array(
            'trigger'  => $this->slug,
            'action'   => 'appearance_customized',
            'severity' =>  $this->lexicon['actions']['appearance_customized'],
            'details'  => array(
                'modified_fields' => $modified_fields,
            ),
        ) );
    }


    /**
     * When a sidebar is modified.
     *
     * @return bool
     */
    public function on_sidebar_setup() : bool {

        if ( empty( $_POST ) ) {
            return false;
        }

        $post = filter_var_array( $_POST, FILTER_SANITIZE_STRING );
        $action = 'widget_' . $this->is_sidebar_setup_operation( $post );

        return $this->log->dispatch( array(
            'trigger'  => $this->slug,
            'action'   => $action,
            'severity' => $this->lexicon['actions'][ $action ],
            'details'  => array(
                'widget_id' => $post['widget-id'],
                'sidebar' => $post['sidebar']
            ),
        ) );
    }


    /**
     * Helper function to discern the sidebar operation in use.
     *
     * @param array $post   The post array.
     *
     * @return string
     */
    private function is_sidebar_setup_operation( array $post ) : string {

         if ( isset( $post['delete_widget'] )  && ! empty( $post['delete_widget'] ) ) {
             return 'removed';
         }

         if ( isset( $post['add_new'] ) && ! empty( $post['add_new'] ) ) {
             return 'added';
         }

         return 'edited';
    }


    /**
     * When a widget is added, edited, or deleted.
     *
     * @param array      $instance      Instance of the Widget.
     * @param array      $new_instance  The new Widget instance state.
     * @param array      $old_instance  The original Widget instance state.
     * @param \WP_Widget $widget        The WordPress widget object.
     *
     * @return bool
     */
    public function on_widget_update( array $instance, array $new_instance, array $old_instance, \WP_Widget $widget )
    : bool {

        if ( empty( $old_instance ) ) {
            return false;
        }

        $post = filter_var_array( $_POST, FILTER_SANITIZE_STRING );

        if ( empty( $post ) ) {
            return false;
        }

        return $this->log->dispatch( array(
            'trigger' => $this->slug,
            'action'  => 'widget_edited',
            'severity' => $this->lexicon['actions']['widget_edited'],
            'details' => array(
                'widget_id' => $widget->id_base,
                'sidebar' => $post['sidebar'],
                'old_instance' => json_encode( $old_instance ),
                'new_instance' => json_encode( $new_instance ),
            ),
        ) );
    }


    /**
     * Fires when the upgrader process is complete.
     *
     * @link https://developer.wordpress.org/reference/hooks/upgrader_process_complete-3/
     *
     * @param \WP_Upgrader $wp_upgrader     The WP_Upgrader class object.
     * @param array        $hook_extra      Contains data about the upgrade.
     *
     * @return bool
     */
    public function on_upgrade( \WP_Upgrader $wp_upgrader, array $hook_extra ) : bool {

        if ( empty( $hook_extra ) || empty( $wp_upgrader ) ) {
            return false;
        }

        if ( ! isset( $hook_extra['type'] ) || $hook_extra['type'] !== 'theme' ) {
            return false;
        }

        $theme_info = $wp_upgrader->theme_info();
        $action = 'theme_' . $hook_extra['action'];

        return $this->log->dispatch( array(
            'trigger'  => $this->slug,
            'action'   => $action,
            'severity' => $this->lexicon['actions'][ $action ],
            'details'  => array(
                'theme_name'    => $theme_info->Name,
                'theme_version' => $theme_info->Version,
            ),
        ) );
    }


    /**
     * WordPress does not offer a convenient way to hook into theme deletion. To get around this
     * we simply listen for the `delete_transient` hook which is what occurs after the theme
     * has been deleted. It will at least give us the name of the theme which is better than nothing.
     *
     * @link https://developer.wordpress.org/reference/hooks/delete_transient_transient/
     *
     * @param $transient
     *
     * @return bool
     */
    public function on_delete_transient( string $transient ) : bool {
        $post = filter_var_array( $_POST, FILTER_SANITIZE_STRING );

        if ( 'update_themes' !== $transient ) {
            return false;
        }

        if ( isset( $post['action'] ) && 'delete-theme' !== $post['action'] ) {
            return false;
        }

        return $this->log->dispatch( array(
            'trigger'  => $this->slug,
            'action'   => 'theme_deleted',
            'severity' => $this->lexicon['actions']['theme_deleted'],
            'details'  => array(
                'theme_name' => $post['slug'],
            ),
        ) );
    }
}
