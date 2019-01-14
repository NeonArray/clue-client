<?php
/**
 * Provide a public-facing view for the plugin
 *
 * @since      1.2.1
 *
 * @package    Clue
 * @subpackage Clue\Core\public/partials
 */
?>

<div class="wrap">
    <h1>Clue Client Settings</h1>

    <form method="post" action="options.php">
        <?php settings_fields( 'clue_options_group' ); ?>
        <?php do_settings_sections( 'clue-options-admin' ); ?>

        <?php submit_button(); ?>
    </form>
</div>
