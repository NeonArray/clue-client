<?php
/**
 * The file that defines the User trigger class
 *
 * A class definition that includes attributes and functions used
 * to detect changes in user related properties and actions.
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
use Clue\Core\Utils\Redact;

/**
 * Class User
 *
 * @package Clue\Core\Triggers
 */
class User extends Trigger {


    protected $lexicon= array(
        'description' => 'Triggers when changes are made to users such as logins, logouts, password changes, etc.',
        'capability'  => 'edit_users',
        'actions'     => array(
            'user_login_failed'                  => Severity::WARNING,
            'user_unknown_login_failed'          => Severity::WARNING,
            'user_logged_in'                     => Severity::INFO,
            'user_unknown_logged_in'             => Severity::INFO,
            'user_logged_out'                    => Severity::INFO,
            'user_updated_profile'               => Severity::INFO,
            'user_created'                       => Severity::WARNING,
            'user_deleted'                       => Severity::WARNING,
            'user_password_reset'                => Severity::WARNING,
            'user_requested_password_reset_link' => Severity::INFO,
            'user_session_destroy_others'        => Severity::WARNING,
            'user_session_destroy_everywhere'    => Severity::WARNING,
        ),
    );


    public function __construct( Papertrail $papertrail_instance, Loader $loader, string $slug = __CLASS__ ) {
        parent::__construct( $papertrail_instance, $loader, $slug );
    }


    public function get_lexicon() : array {
        return $this->lexicon;
    }


    public function attach_hooks() {
        $this->loader->add_action( 'wp_login', $this, 'on_login', 10, 2 );
        $this->loader->add_action( 'wp_logout', $this, 'on_logout' );
        $this->loader->add_action( 'wp_authenticate_user', $this, 'on_authenticated_user', 10, 2 );
        $this->loader->add_action( 'authenticate', $this, 'on_authenticate', 30, 3 );
        $this->loader->add_action( 'user_register', $this, 'on_user_register' );
        $this->loader->add_action( 'delete_user', $this, 'on_delete_user', 10, 2 );
        $this->loader->add_action( 'wp_ajax_destroy_sessions', $this, 'on_destroy_user_session', 0 );
        $this->loader->add_action( 'validate_password_reset', $this, 'on_validate_password_reset', 10, 2 );
        $this->loader->add_action( 'retrieve_password_message', $this, 'on_retrieve_password_manage', 10, 4 );
        $this->loader->add_action( 'insert_user_meta', $this, 'on_insert_user_meta', 10, 3 );
    }


    /**
     * Triggers when a user logs in.
     *
     * @link https://developer.wordpress.org/reference/hooks/wp_login/
     *
     * @param string   $username    The plain text username.
     * @param \WP_User $user_object The WordPress user object.
     *
     * @return bool
     */
    public function on_login( string $username, \WP_User $user_object ) : bool {
        return $this->log->dispatch( array(
            'trigger' => $this->slug,
            'action' => 'user_logged_in',
            'severity' =>  $this->lexicon['actions']['user_logged_in'],
            'details' => array(
                'id' => $user_object->data->ID,
                'email' => Redact::email( $user_object->data->user_email ),
            ),
        ) );
    }


    /**
     * Triggers when a user logs out. There is no context attached because the user data will alraedy
     * be appended by the Papertrail->dispatch method when it executes the `get_user_details` function.
     *
     * @link http://codex.wordpress.org/Plugin_API/Action_Reference/wp_logout
     *
     * @return bool
     */
    public function on_logout() : bool {
        return $this->log->dispatch( array(
            'trigger' => $this->slug,
            'action' => 'user_logged_out',
            'severity' =>  $this->lexicon['actions']['user_logged_out'],
            'details' => array(),
        ) );
    }


    /**
     * When a user can be authenticated with the given password. This is used to track invalid login attempts
     * against a known username. Note that $wp_object have a hint since it can be either WP_User or WP_Error.
     *
     * @link https://developer.wordpress.org/reference/hooks/wp_authenticate_user/
     *
     * @param \WP_User|\WP_Error $wp_object Will either be the user or an error.
     * @param string $password              The hashed password.
     * @return \WP_User|\WP_Error $wp_object
     */
    public function on_authenticated_user( $wp_object, $password ) {

        if ( ! is_a( $wp_object, 'WP_User' ) ) {
            return $wp_object;
        }

        if ( wp_check_password( $password, $wp_object->data->user_pass, $wp_object->data->ID ) ) {
            return $wp_object;
        }

        $this->log->dispatch( array(
            'trigger' => $this->slug,
            'action' => 'user_login_failed',
            'severity' =>  $this->lexicon['actions']['user_login_failed'],
            'details' => array(
                'id' => $wp_object->data->ID,
                'user_email' => Redact::email( $wp_object->data->user_email ),
            ),
        ) );

        return $wp_object;
    }


    /**
     * Filters whether a set of user login credentials are valid. We use this trigger to track when login attempts
     * are made on username that don't exist.
     *
     * @link https://developer.wordpress.org/reference/hooks/authenticate/
     *
     * @param \WP_User|\WP_Error $wp_object Will either be the user or an error.
     * @param string             $username  The username.
     * @param string             $password  The users password.
     *
     * @return
     */
    public function on_authenticate( $wp_object, string $username, string $password ) {

        // Sometimes WordPress sends a null object.
        if ( is_null( $wp_object ) ) {
            return $wp_object;
        }

        // We don't care about this attempt if is an existing user.
        if ( is_a( $wp_object, 'WP_User' ) ) {
            return $wp_object;
        }

        $code = $wp_object->get_error_code();

        // Only log an attempt if the error is an invalid email or username.
        if ( 'invalid_username' === $code || 'invalid_email' === $code ) {
            $this->log->dispatch( array(
                'trigger'  => $this->slug,
                'action'   => 'user_unknown_login_failed',
                'severity' => $this->lexicon['actions']['user_unknown_login_failed'],
                'details'  => array(
                    'username' => trim( $username ),
                ),
            ) );
        }

        return $wp_object;
    }


    /**
     * Fires immediately after a new user is registered.
     *
     * @link https://developer.wordpress.org/reference/hooks/user_register/
     *
     * @param int $user_id The users id.
     *
     * @return bool
     */
    public function on_user_register( int $user_id ) : bool {
        $wp_user = get_userdata( $user_id );

        if ( ! $wp_user ) {
            return false;
        }

        return $this->log->dispatch( array(
            'trigger'  => $this->slug,
            'action'   => 'user_created',
            'severity' => $this->lexicon['actions']['user_created'],
            'details'  => array(
                'id'         => $wp_user->data->ID,
                'username'   => $wp_user->data->user_login,
                'user_email' => Redact::email( $wp_user->data->user_email ),
            ),
        ) );
    }


    /**
     * Fires immediately before a user is deleted from the database.
     *
     * @link https://developer.wordpress.org/reference/hooks/delete_user/
     *
     * @param int      $user_id      The user ID being deleted.
     * @param int|null $reassign_to  Which user the deleted ID's content will be assigned to. Defaults to null.
     *
     * @return bool
     */
    public function on_delete_user( int $user_id, $reassign_to ) : bool {
        $wp_user = get_userdata( $user_id );

        if ( ! $wp_user ) {
            return false;
        }

        return $this->log->dispatch( array(
            'trigger'  => $this->slug,
            'action'   => 'user_deleted',
            'severity' => $this->lexicon['actions']['user_deleted'],
            'details'  => array(
                'id'                  => $wp_user->data->ID,
                'username'            => $wp_user->data->user_login,
                'email'               => Redact::email( $wp_user->data->user_email ),
                'reassign_content_to' => $reassign_to,
            ),
        ) );
    }


    /**
     * Ajax handler for destroying multiple open sessions for a user.
     *
     * @link https://developer.wordpress.org/reference/functions/wp_ajax_destroy_sessions/
     *
     * @return bool
     */
    public function on_destroy_user_session() : bool {
        $post = filter_var_array( $_POST,FILTER_SANITIZE_STRING );
        $wp_user = get_userdata( $post['user_id'] ?? 99999 );

        if ( ! $wp_user ) {
            return false;
        }

        if ( ! current_user_can( 'edit_user', $wp_user->data->ID ) ) {
            return false;
        }

        if ( ! wp_verify_nonce( $post['nonce'], 'update-user_' . $wp_user->data->ID ) ) {
            return false;
        }

        $action = 'user_session_destroy_everywhere';

        if ( $wp_user->data->ID === get_current_user_id() ) {
            $action = 'user_session_destroy_others';
        }

        return $this->log->dispatch( array(
            'trigger'  => $this->slug,
            'action'   => $action,
            'severity' => $this->lexicon['actions'][ $action ],
            'details'  => array(
                'id'       => $wp_user->data->ID,
                'username' => $wp_user->data->user_login,
                'email'    => Redact::email( $wp_user->data->user_email ),
            ),
        ) );
    }


    /**
     * Fires before the password reset procedure is validated.
     *
     * @link https://developer.wordpress.org/reference/hooks/validate_password_reset/
     *
     * @param \WP_Error          $errors    An error object.
     * @param \WP_User|\WP_Error $wp_object Either error or user object.
     *
     * @return bool
     */
    public function on_validate_password_reset( $errors, $wp_object ) {
        $post = filter_var_array( $_POST,FILTER_SANITIZE_STRING );

        if ( ! is_a( $wp_object, 'WP_User' ) ) {
            return false;
        }

        if ( $errors->get_error_code() ) {
            return false;
        }

        if ( ! isset( $post['pass1'] ) ) {
            return false;
        }

        return $this->log->dispatch( array (
            'trigger'  => $this->slug,
            'action'   => 'user_password_reset',
            'severity' => $this->lexicon['actions']['user_password_reset'],
            'details'  => array(
                'id'       => $wp_object->data->ID,
                'username' => $wp_object->data->user_login,
                'email'    => Redact::email( $wp_object->data->user_email ),
            ),
        ) );
    }


    /**
     * Filters the message body of the password reset mail.
     *
     * @link https://developer.wordpress.org/reference/hooks/retrieve_password_message/
     *
     * @param string   $message   Default mail message.
     * @param string   $key       The activation key.
     * @param string   $username  The username for the user.
     * @param \WP_User $wp_object WP_User object.
     *
     * @return string
     */
    public function on_retrieve_password_manage( string $message, string $key, string $username, \WP_User $wp_user
    ) :
    string {

        if ( isset( $_GET['action'] ) && 'lostpassword' === $_GET['action'] ) {

            $this->log->dispatch( array (
                'trigger'  => $this->slug,
                'action'   => 'user_requested_password_reset_link',
                'severity' => $this->lexicon['actions']['user_requested_password_reset_link'],
                'details'  => array(
                    'message' => $message,
                    'key'     => $key,
                    'user'    => $username,
                ),
            ) );

        }

        return $message;
    }


    public function on_insert_user_meta( array $meta, \WP_User $wp_user, bool $update ) : array {

        if ( ! $update ) {
            return $meta;
        }

        $details = array_merge( $meta, array(
            'email'    => Redact::email( $wp_user->data->user_email ),
            'username' => $wp_user->data->user_login,
        ) );

        $this->log->dispatch( array (
            'trigger'  => $this->slug,
            'action'   => 'user_updated_profile',
            'severity' => $this->lexicon['actions']['user_updated_profile'],
            'details'  => $details,
        ) );

        return $meta;
    }
}
