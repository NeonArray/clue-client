<?php
/**
 * The file that defines the Core trigger class
 *
 * A class definition that includes attributes and functions used
 * to detect changes in core related properties and actions.
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
 * Class Core
 *
 * @package Clue\Core\Triggers
 */
class Core extends Trigger {


    protected $log;

    protected $lexicon = array(
        'description' => 'Triggers on updates of WordPress (manual and automatic updates)',
        'capability'  => 'update_core',
        'actions'    => array(
            'core_updated' => Severity::NOTICE,
            'core_auto_updated' => Severity::INFO,
            'core_db_version_updated' => Severity::INFO,
        ),
    );


    public function __construct( Papertrail $papertrail_instance, Loader $loader, string $slug = __CLASS__ ) {
        parent::__construct( $papertrail_instance, $loader, $slug );
    }


    public function attach_hooks() {
        $this->loader->add_action( '_core_updated_successfully', $this, 'on_core_updated' );
        $this->loader->add_action( 'update_feedback', $this, 'on_update_feedback' );
    }


    public function on_core_updated( string $wp_upgraded_to ) : bool {
        $auto_updated = $GLOBALS['pagenow'] === 'update-core.php';
        $current_wp_version = $GLOBALS['wp_version'] ?? '0.0.0';
        $action = 'core_auto_updated';

        if ( $auto_updated ) {
            $action = 'core_updated';
        }

        return $this->log->dispatch( array(
            'trigger'  => $this->slug,
            'action'   => $action,
            'severity' => $this->lexicon['actions'][ $action ],
            'details'  => array(
                'current_version' => $current_wp_version,
                'new_version'     => $wp_upgraded_to,
            ),
        ) );
    }
}
