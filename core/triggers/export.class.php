<?php
/**
 * The file that defines the Export trigger class
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
 * Class Export
 *
 * @package Clue\Core\Triggers
 */
class Export extends Trigger {

    protected $lexicon = array(
        'description' => 'Logs events pertaining to exporting',
        'capability'  => 'export',
        'actions'    => array(
            'created_export' => Severity::NOTICE,
        ),
    );


    public function __construct( Papertrail $papertrail_instance, Loader $loader, string $slug = __CLASS__ ) {
        parent::__construct( $papertrail_instance, $loader, $slug );
    }


    public function attach_hooks() {
        $this->loader->add_action( 'export_wp', $this, 'on_export' );
    }


    public function on_export( $args ) : bool {
        return $this->log->dispatch( array(
            'trigger' => $this->slug,
            'action' => 'created_export',
            'severity' =>  $this->lexicon['actions']['created_export'],
            'details' => array(
                'args' => json_encode( $args ),
            ),
        ) );
    }
}
