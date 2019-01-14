<?php
/**
 * The file that defines the Import trigger class
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
 * Class Import
 *
 * @package Clue\Core\Triggers
 */
class Import extends Trigger {

    protected $lexicon = array(
        'description' => 'Logs events pertaining to importing',
        'capability'  => 'export',
        'actions'    => array(
            'created_import' => Severity::INFO,
        ),
    );


    public function __construct( Papertrail $papertrail_instance, Loader $loader, string $slug = __CLASS__ ) {
        parent::__construct( $papertrail_instance, $loader, $slug );
    }


    public function attach_hooks() {
        $this->loader->add_action( 'import_start', $this, 'on_import_start' );
    }


    /**
     * Just taking the whole $_POST object and sending it since there is
     * variable information that comes with importing.
     *
     * @param string $args Doesn't seem to really ever supply anything.
     *
     * @return bool
     */
    public function on_import_start( $args ) : bool {
        $post = $this->get_post();

        return $this->log->dispatch( array(
            'trigger'  => $this->slug,
            'action'   => 'created_import',
            'severity' => $this->lexicon['actions']['created_import'],
            'details'  => array(
                'import' => json_encode( $post ),
            ),
        ) );
    }
}
