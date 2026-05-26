<?php
/**
 * Register settings
 *
 * @package     ZodanChangeUsername\Admin\Settings\Register
 * @since       0.0.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Setup the settings menu
 *
 * @since       0.0.3
 * @param       array $menu The default menu settings.
 * @return      array $menu Our defined settings
 */
function zodan_change_usernames_add_menu( $menu ) {
	$menu['type']       = 'submenu';
	$menu['parent']     = 'users.php';
	$menu['page_title'] = __( 'Zodan Change Usernames Settings', 'zodan-change-usernames' );
	$menu['menu_title'] = __( 'Change Usernames', 'zodan-change-usernames' );

	return $menu;
}
add_filter( 'zodan_change_usernames_menu', 'zodan_change_usernames_add_menu' );


/**
 * Define our settings tabs
 *
 * @since       0.0.3
 * @param       array $tabs The default tabs.
 * @return      array $tabs Our defined tabs
 */
function zodan_change_usernames_settings_tabs( $tabs ) {
	$tabs['settings'] = __( 'Settings', 'zodan-change-usernames' );
	$tabs['bulk']     = __( 'Bulk change', 'zodan-change-usernames' );
	$tabs['log']      = __( 'Log/history', 'zodan-change-usernames' );
	// $tabs['help']     = __( 'Help', 'zodan-change-usernames' );

	return $tabs;
}
add_filter( 'zodan_change_usernames_settings_tabs', 'zodan_change_usernames_settings_tabs' );


/**
 * Define settings sections
 *
 * @since       0.0.3
 * @param       array $sections The default sections.
 * @return      array $sections Our defined sections
 */
function zodan_change_usernames_registered_settings_sections( $sections ) {
	$sections = array(
		'settings' => apply_filters(
			'zodan_change_usernames_settings_sections_settings',
			array(
				'main'    => __( 'General Settings', 'zodan-change-usernames' ),
				'email'   => __( 'Email Settings', 'zodan-change-usernames' ),
				'strings' => __( 'String Settings', 'zodan-change-usernames' ),
			)
		),
		'bulk'     => apply_filters( 'zodan_change_usernames_settings_sections_bulk', array( 'main' => '' ) ),
		'log'     => apply_filters( 'zodan_change_usernames_settings_sections_log', array( 'main' => '' ) ),
		// 'help'     => apply_filters( 'zodan_change_usernames_settings_sections_help', array( 'main' => '' ) ),
	);

	return $sections;
}
add_filter( 'zodan_change_usernames_registered_settings_sections', 'zodan_change_usernames_registered_settings_sections' );


/**
 * Disable save button on unsavable tabs
 *
 * @since       0.0.3
 * @return      array $tabs The updated tabs
 */
function zodan_change_usernames_define_unsavable_tabs() {
	// $tabs = array( 'help', 'support' );
	$tabs = array( 'bulk', 'log', 'help' );

	return $tabs;
}
add_filter( 'zodan_change_usernames_unsavable_tabs', 'zodan_change_usernames_define_unsavable_tabs' );


/**
 * Define our settings
 *
 * @since       0.0.3
 * @param       array $settings The default settings.
 * @return      array $settings Our defined settings
 */
function zodan_change_usernames_registered_settings( $settings ) {
	$new_settings = array(
		// General Settings.
		'settings' => apply_filters(
			'zodan_change_usernames_settings_settings',
			array(
				'main'    => array(
					array(
						'id'            => 'allowed_roles',
						'name'          => __( 'Allowed Roles', 'zodan-change-usernames' ),
						'desc'          => __( 'Select the user roles which are permitted to change their own username.', 'zodan-change-usernames' ),
						'type'          => 'multicheck',
						'options'       => zodan_change_usernames_get_user_roles(),
						'tooltip_title' => __( 'Allowed Roles', 'zodan-change-usernames' ),
						'tooltip_desc'  => __( 'Administrators can always change usernames, and are the only role capable of changing other users username.', 'zodan-change-usernames' ),
					),
					array(
						'id'            => 'minimum_length',
						'name'          => __( 'Minimum Length', 'zodan-change-usernames' ),
						'desc'          => __( 'Specify the minimum allowed username length.', 'zodan-change-usernames' ),
						'type'          => 'number',
						'size'          => 'small-text',
						'min'           => 3,
						'step'          => 1,
						'std'           => 3,
						'tooltip_title' => __( 'Minimum Length', 'zodan-change-usernames' ),
						'tooltip_desc'  => __( 'The minimum allowed length for usernames is {minlength} characters.', 'zodan-change-usernames' ),
					),
				),
				'email'    => array(
					array(
						'id'            => 'enable_notifications',
						'name'          => __( 'Enable Email Notifications', 'zodan-change-usernames' ),
						'desc'          => __( 'Enable to send notification emails when usernames are changed.', 'zodan-change-usernames' ),
						'type'          => 'checkbox',
						'tooltip_title' => __( 'Enable Email Notifications', 'zodan-change-usernames' ),
						'tooltip_desc'  => __( 'Notifications are not sent when a user changes their own username.', 'zodan-change-usernames' ),
					),
					array(
						'id'   => 'email_subject',
						'name' => __( 'Email Subject', 'zodan-change-usernames' ),
						'desc' => __( 'Specify the subject for username change notifications.', 'zodan-change-usernames' ),
						'type' => 'text',
						'std'  => __( 'Username change notification - {sitename}', 'zodan-change-usernames' ),
					),
					array(
						'id'   => 'email_message',
						'name' => __( 'Email Message', 'zodan-change-usernames' ),
						'desc' => __( 'Specify the message to send for username change notifications.', 'zodan-change-usernames' ),
						'type' => 'editor',
						'std'  => __( 'Howdy! We\'re just writing to let you know that your username for {siteurl} has been changed to {new_username}.', 'zodan-change-usernames' ) . "\n\n" . __( 'Login now at {loginurl}', 'zodan-change-usernames' ),
					),
					array(
						'id'   => 'footer_thankyou',
						'name' => '',
						'desc' => '',
						'type' => 'hook',
					),
					array(
						'id'   => 'email_subheader',
						'name' => '',
						'desc' => '',
						'type' => 'hook',
					),
				),
				'strings' => array(
					array(
						'id'   => 'button_labels_header',
						'name' => '<h3>'.__( 'Button Labels', 'zodan-change-usernames' ).'</h3>',
						'desc' => '',
						'type' => 'header',
					),
					array(
						'id'   => 'change_button_label',
						'name' => __( 'Change Button Label', 'zodan-change-usernames' ),
						'desc' => __( 'Customize the text for the \'change username\' button.', 'zodan-change-usernames' ),
						'type' => 'text',
						'std'  => __( 'Change Username', 'zodan-change-usernames' ),
					),
					array(
						'id'   => 'save_button_label',
						'name' => __( 'Save Button Label', 'zodan-change-usernames' ),
						'desc' => __( 'Customize the text for the save button.', 'zodan-change-usernames' ),
						'type' => 'text',
						'std'  => __( 'Save Username', 'zodan-change-usernames' ),
					),
					array(
						'id'   => 'cancel_button_label',
						'name' => __( 'Cancel Button Label', 'zodan-change-usernames' ),
						'desc' => __( 'Customize the text for the cancel button.', 'zodan-change-usernames' ),
						'type' => 'text',
						'std'  => __( 'Cancel', 'zodan-change-usernames' ),
					),
					array(
						'id'   => 'messages_header',
						'name' => '<h3>'.__( 'Messages', 'zodan-change-usernames' ) . '</h3>',
						'desc' => 'test',
						'type' => 'header',
					),
					array(
						'id'   => 'please_wait_message',
						'name' => __( 'Please Wait Message', 'zodan-change-usernames' ),
						'desc' => __( 'Customize the text displayed while usernames are being checked.', 'zodan-change-usernames' ),
						'type' => 'text',
						'std'  => __( 'Please wait...', 'zodan-change-usernames' ),
					),
					array(
						'id'   => 'success_message',
						'name' => __( 'Username Changed Message', 'zodan-change-usernames' ),
						'desc' => __( 'Customize the message displayed when a username is changed successfully.', 'zodan-change-usernames' ),
						'type' => 'text',
						'std'  => __( 'Username successfully changed to {new_username}.', 'zodan-change-usernames' ),
					),
					array(
						'id'   => 'relogin_message',
						'name' => __( 'Relogin Message', 'zodan-change-usernames' ),
						'desc' => __( 'Customize the text for the relogin link shown if a user changes their own username.', 'zodan-change-usernames' ),
						'type' => 'text',
						'std'  => __( 'Click here to log back in.', 'zodan-change-usernames' ),
					),
					array(
						'id'   => 'error_short_username',
						'name' => __( 'Short Username Error', 'zodan-change-usernames' ),
						'desc' => __( 'Customize the error displayed when a username is too short.', 'zodan-change-usernames' ),
						'type' => 'text',
						'std'  => __( 'Username is too short, the minimum length is {minlength} characters.', 'zodan-change-usernames' ),
					),
					array(
						'id'            => 'error_wrong_permissions',
						'name'          => __( 'Wrong Permissions Error', 'zodan-change-usernames' ),
						'desc'          => __( 'Customize the error displayed when a user attempts to change a username they do not have permission to change.', 'zodan-change-usernames' ),
						'type'          => 'text',
						'std'           => __( 'You do not have the correct permissions to change this username.', 'zodan-change-usernames' ),
						'tooltip_title' => __( 'Wrong Permissions Error', 'zodan-change-usernames' ),
						'tooltip_desc'  => __( 'In normal circumstances, this message should never be triggered. It exists only to provide an extra layer of security against unauthorized use.', 'zodan-change-usernames' ),
					),
					array(
						'id'   => 'error_duplicate_username',
						'name' => __( 'Duplicate Username Error', 'zodan-change-usernames' ),
						'desc' => __( 'Customize the error displayed when a user attempts to change a username to something that is already in use.', 'zodan-change-usernames' ),
						'type' => 'text',
						'std'  => __( 'The username {new_username} is already in use. Please try again.', 'zodan-change-usernames' ),
					),
					array(
						'id'   => 'messages_subheader',
						'name' => __( 'Duplicate Username Error', 'zodan-change-usernames' ),
						'desc' => '',
						'type' => 'hook',
					),
					array(
						'id'   => 'footer_thankyou',
						'name' => '',
						'desc' => '',
						'type' => 'hook',
					),
				),
			)
		),
		'bulk'     => apply_filters(
			'zodan_change_usernames_settings_bulk',
			array(
				'main' => array(
					array(
						'id'   => 'bulk_updater',
						'name' => '',
						'desc' => '',
						'type' => 'hook',
					),
				),
			)
		),
		'log'     => apply_filters(
			'zodan_change_usernames_settings_log',
			array(
				'main' => array(
					array(
						'id'   => 'audit_log',
						'name' => '',
						'desc' => '',
						'type' => 'hook',
					),
				),
			)
		),
		// 'help'     => apply_filters(
		// 	'zodan_change_usernames_settings_help',
		// 	array(
		// 		'main' => array(
		// 			array(
		// 				'id'   => 'tutorial_resources',
		// 				'name' => '',
		// 				'desc' => '',
		// 				'type' => 'hook',
		// 			),
		// 		),
		// 	)
		// ),
	);

	return array_merge( $settings, $new_settings );
}
add_filter( 'zodan_change_usernames_registered_settings', 'zodan_change_usernames_registered_settings' );


/**
 * Display the subheader for the emails section
 *
 * @since       3.1.0
 * @return      void
 */
function zodan_change_usernames_display_email_subheader() {
	?>
	<details class="zodan-change-usernames-settings-note">
		<summary><span class="note-title"><?php esc_attr_e( 'Available Template Tags', 'zodan-change-usernames' ); ?></span></summary>
		<p><?php esc_attr_e( 'Emails allow the use of the following template tags:', 'zodan-change-usernames' ); ?></p>
		<?php zodan_change_usernames_tags_list( 'email' ); ?>
	</details>
	<?php
}
add_action( 'zodan_change_usernames_email_subheader', 'zodan_change_usernames_display_email_subheader' );


/**
 * Display the subheader for the messages section
 *
 * @since       0.0.3
 * @return      void
 */
function zodan_change_usernames_display_messages_subheader() {
	?>
	<details class="zodan-change-usernames-settings-note">
		<summary><span class="note-title"><?php esc_attr_e( 'Available Template Tags', 'zodan-change-usernames' ); ?></span></summary>
		<p><?php esc_attr_e( 'The message settings fields allow the use of the following template tags:', 'zodan-change-usernames' ); ?></p>
		<?php zodan_change_usernames_tags_list( 'message' ); ?>
	</details>
	<?php
}
add_action( 'zodan_change_usernames_messages_subheader', 'zodan_change_usernames_display_messages_subheader' );



/**
 * Render tutorial resources on the help tab.
 *
 * @since 4.0.0
 * @return void
 */
function zodan_change_usernames_display_tutorial_resources() {
	?>
	<tr valign="top">
		<td colspan="2" style="padding-top:0;">
			<div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:20px 22px;max-width:740px;margin-bottom:20px;">
				<h2 style="margin:0 0 10px;font-size:20px;"><?php esc_html_e( 'Documentation & Tutorial', 'zodan-change-usernames' ); ?></h2>
				<p style="margin:0 0 14px;color:#50575e;"><?php esc_html_e( 'Use the live demo to preview the admin flow, share a product walkthrough, and guide customers through the plugin UI.', 'zodan-change-usernames' ); ?></p>
				<p style="margin:0;">
					<a href="https://plugins.zodan.nl" target="_blank" rel="noopener noreferrer" class="button button-primary">
						<?php esc_html_e( 'Open Demo / Tutorial', 'zodan-change-usernames' ); ?>
					</a>
				</p>
			</div>
		</td>
	</tr>
	<?php
}
add_action( 'zodan_change_usernames_tutorial_resources', 'zodan_change_usernames_display_tutorial_resources' );



/**
 * Render bulk updater tab.
 *
 * @since 4.0.0
 * @return void
 */
function zodan_change_usernames_display_bulk_updater() {
	/*
	 * First, create an instance of the zodan_change_usernames_Bulk_Updater::instance();
	 * 
	 */
	$bulk_updater = zodan_change_usernames_Bulk_Updater::instance(); ?>

	<tr valign="top">
		<td colspan="2" style="padding-top:0;">
			<?php $bulk_updater->render_page(); ?>
		</td>
	</tr>
	<?php
}
add_action( 'zodan_change_usernames_bulk_updater', 'zodan_change_usernames_display_bulk_updater' );






function zodan_change_usernames_display_audit_log() {
	/*
	 * First, create an instance of the zodan_change_usernames_Audit_Log::instance();
	 * 
	 */
	$audit_log = zodan_change_usernames_Audit_Log::instance(); ?>

	<tr valign="top">
		<td colspan="2" style="padding-top:0;">
			<?php $audit_log->render_page(); ?>
		</td>
	</tr>
	<?php
}
add_action( 'zodan_change_usernames_audit_log', 'zodan_change_usernames_display_audit_log' );



add_action( 'zodan_change_usernames_footer_thankyou', 'zodan_change_usernames_display_footer_thankyou' );
function zodan_change_usernames_display_footer_thankyou() {
	add_filter('admin_footer_text', 'zodan_change_usernames_footer_print_thankyou', 900);
}
