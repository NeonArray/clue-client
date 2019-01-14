<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://leapsparkagency.com
 * @since      1.0.0
 *
 * @package    Client
 * @subpackage Client/includes
 */

namespace Clue\Core;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Client
 * @subpackage Client/includes
 * @author     Aaron Arney <aarney@leapsparkagency.com>
 */
class Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() : bool {
        return true;
	}

}
