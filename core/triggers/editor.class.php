<?php
/**
 * The file that defines the Editor trigger class
 *
 * A class definition that includes attributes and functions used
 * to detect changes in file editor related properties and actions.
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
 * Class Editor
 *
 * @package Clue\Core\Triggers
 */
class Editor extends Trigger {

    protected $lexicon = array(
        'description' => 'Triggers events pertaining to the file editor',
        'capability'  => 'manage_options',
        'actions'    => array(
            'editor_loaded' => Severity::INFO,
            'editor_saved'  => Severity::ALERT,
        ),
    );


    public function __construct( Papertrail $papertrail_instance, Loader $loader, string $slug = __CLASS__ ) {
        parent::__construct( $papertrail_instance, $loader, $slug );
    }


    public function attach_hooks() {
        $this->loader->add_filter( 'wp_code_editor_settings', $this, 'on_editor_load' );
        $this->loader->add_filter( 'wp_doing_ajax', $this, 'on_editor_save' );
    }


    /**
     * Filters settings that are passed into the code editor.
     *
     * @link https://developer.wordpress.org/reference/hooks/wp_code_editor_settings/
     *
     * @since 1.0.0
     *
     * @param array $settings The array of settings passed to the code editor. A falsey value disables the editor.
     *
     * @return array
     */
    public function on_editor_load( array $settings ) : array {
        $get = $this->get_get();

        if ( ! isset( $get['file'] ) ) {
            return $settings;
        }

        $this->log->dispatch( array(
            'trigger'  => $this->slug,
            'action'   => 'editor_loaded',
            'severity' =>  $this->lexicon['actions']['editor_loaded'],
            'details'  => array(
                'file'  => $get['file'],
                'theme' => $get['theme'],
            ),
        ) );

        return $settings;
    }


    /**
     * There is no way to hook into editor saves, so what we are doing instead
     * is detecting AJAX requests since the editor saves via AJAX. Then we just
     * have to discern if its the type of request we are looking for.
     *
     * @since 1.0.0
     *
     * @param bool $is_ajax Is the current request AJAX
     *
     * @return bool
     */
    public function on_editor_save( bool $is_ajax ) : bool {

        if ( ! $is_ajax ) {
            return $is_ajax;
        }

        $post = $this->get_post();

        if ( ! isset( $post['action'] )
             || ( isset( $post['action'] )
                  && 'edit-theme-plugin-file' !== $post['action']
             )
        ) {
            return $is_ajax;
        }

        $file        = $post['file'] ?? 'undefined';
        $theme       = $post['theme'] ?? 'undefined';
        $new_content = $post['newcontent'] ?? 'undefined';

        $this->log->dispatch( array(
            'trigger'  => $this->slug,
            'action'   => 'editor_saved',
            'severity' =>  $this->lexicon['actions']['editor_saved'],
            'details'  => array(
                'file'    => $file,
                'theme'   => $theme,
                'content' => $new_content,
            ),
        ) );

        return $is_ajax;
    }
}
