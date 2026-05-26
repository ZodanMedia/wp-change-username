<?php
/**
 * Scripts
 *
 * @package     ZodanChangeUsername\Scripts
 * @since       0.0.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



/**
 * Load admin scripts
 *
 * @since       0.0.1
 * @return      void
 */
function zodan_change_usernames_admin_scripts() {
	$js_dir  = zodan_change_usernames_URL . 'assets/js/';
	$css_dir = zodan_change_usernames_URL . 'assets/css/';

	// Use minified libraries if SCRIPT_DEBUG is turned off.
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	$minimum_length = zodan_change_usernames()->settings->get_option( 'minimum_length', 3 );
	$screen         = get_current_screen();

	wp_enqueue_script( 'zodan-change-usernames', $js_dir . 'admin.js', array( 'jquery' ), zodan_change_usernames_VER, true );
	wp_localize_script(
		'zodan-change-usernames',
		'zodan_change_usernames_vars',
		array(
			'nonce'                 => wp_create_nonce( 'change_username' ),
			'ajaxurl'               => admin_url( 'admin-ajax.php' ),
			'change_button_label'   => zodan_change_usernames()->settings->get_option( 'change_button_label', __( 'Change Username', 'zodan-change-usernames' ) ),
			'save_button_label'     => zodan_change_usernames()->settings->get_option( 'save_button_label', __( 'Save Username', 'zodan-change-usernames' ) ),
			'cancel_button_label'   => zodan_change_usernames()->settings->get_option( 'cancel_button_label', __( 'Cancel', 'zodan-change-usernames' ) ),
			'please_wait_message'   => zodan_change_usernames()->settings->get_option( 'please_wait_message', __( 'Please wait...', 'zodan-change-usernames' ) ),
			'error_short_username'  => zodan_change_usernames_do_tags( zodan_change_usernames()->settings->get_option( 'error_short_username', __( 'Username is too short, the minimum length is {minlength} characters.', 'zodan-change-usernames' ) ) ),
			'warning_same_username' => zodan_change_usernames_do_tags( zodan_change_usernames()->settings->get_option( 'warning_same_username', __( 'The new username is the same as the current one.', 'zodan-change-usernames' ) ) ),
			'current_screen'        => $screen->id,
			'can_change_username'   => zodan_change_usernames_can_change_own_username(),
			'minimum_length'        => $minimum_length,
		)
	);

	wp_register_style( 'zodan-change-usernames', $css_dir . 'admin.css', array(), zodan_change_usernames_VER );
	wp_enqueue_style( 'zodan-change-usernames' );
}
add_action( 'admin_enqueue_scripts', 'zodan_change_usernames_admin_scripts', 100 );
