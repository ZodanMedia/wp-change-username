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
function zodan_change_username_add_menu( $menu ) {
	$menu['type']       = 'submenu';
	$menu['parent']     = 'users.php';
	$menu['page_title'] = __( 'Zodan Change Username Settings', 'zodan-change-username' );
	$menu['menu_title'] = __( 'Change Usernames', 'zodan-change-username' );

	return $menu;
}
add_filter( 'zodan_change_username_menu', 'zodan_change_username_add_menu' );


/**
 * Define our settings tabs
 *
 * @since       0.0.3
 * @param       array $tabs The default tabs.
 * @return      array $tabs Our defined tabs
 */
function zodan_change_username_settings_tabs( $tabs ) {
	$tabs['settings'] = __( 'Settings', 'zodan-change-username' );
	$tabs['bulk']     = __( 'Bulk change', 'zodan-change-username' );
	$tabs['log']      = __( 'Log/history', 'zodan-change-username' );
	// $tabs['help']     = __( 'Help', 'zodan-change-username' );

	return $tabs;
}
add_filter( 'zodan_change_username_settings_tabs', 'zodan_change_username_settings_tabs' );


/**
 * Define settings sections
 *
 * @since       0.0.3
 * @param       array $sections The default sections.
 * @return      array $sections Our defined sections
 */
function zodan_change_username_registered_settings_sections( $sections ) {
	$sections = array(
		'settings' => apply_filters(
			'zodan_change_username_settings_sections_settings',
			array(
				'main'    => __( 'General Settings', 'zodan-change-username' ),
				'strings' => __( 'String Settings', 'zodan-change-username' ),
			)
		),
		'bulk'     => apply_filters( 'zodan_change_username_settings_sections_bulk', array( 'main' => '' ) ),
		'log'     => apply_filters( 'zodan_change_username_settings_sections_log', array( 'main' => '' ) ),
		// 'help'     => apply_filters( 'zodan_change_username_settings_sections_help', array( 'main' => '' ) ),
	);

	return $sections;
}
add_filter( 'zodan_change_username_registered_settings_sections', 'zodan_change_username_registered_settings_sections' );


/**
 * Disable save button on unsavable tabs
 *
 * @since       0.0.3
 * @return      array $tabs The updated tabs
 */
function zodan_change_username_define_unsavable_tabs() {
	// $tabs = array( 'help', 'support' );
	$tabs = array( 'bulk', 'log', 'help' );

	return $tabs;
}
add_filter( 'zodan_change_username_unsavable_tabs', 'zodan_change_username_define_unsavable_tabs' );


/**
 * Define our settings
 *
 * @since       0.0.3
 * @param       array $settings The default settings.
 * @return      array $settings Our defined settings
 */
function zodan_change_username_registered_settings( $settings ) {
	$new_settings = array(
		// General Settings.
		'settings' => apply_filters(
			'zodan_change_username_settings_settings',
			array(
				'main'    => array(
					array(
						'id'   => 'settings_header_one',
						'name' => '<h1>'.__( 'Zodan Change Username settings', 'zodan-change-username' ).'</h1>',
						'desc' => '',
						'type' => 'header',
					),
					array(
						'id'   => 'settings_header',
						'name' => '<h2>'.__( 'General Settings', 'zodan-change-username' ).'</h2>',
						'desc' => '',
						'type' => 'header',
					),
					array(
						'id'            => 'allowed_roles',
						'name'          => __( 'Allowed Roles', 'zodan-change-username' ),
						'desc'          => __( 'Select the user roles which are permitted to change their own username.', 'zodan-change-username' ),
						'type'          => 'multicheck',
						'options'       => zodan_change_username_get_user_roles(),
						'tooltip_title' => __( 'Allowed Roles', 'zodan-change-username' ),
						'tooltip_desc'  => __( 'Administrators can always change usernames, and are the only role capable of changing other users username.', 'zodan-change-username' ),
					),
					array(
						'id'            => 'minimum_length',
						'name'          => __( 'Minimum Length', 'zodan-change-username' ),
						'desc'          => __( 'Specify the minimum allowed username length.', 'zodan-change-username' ),
						'type'          => 'number',
						'size'          => 'small-text',
						'min'           => 3,
						'step'          => 1,
						'std'           => 3,
						'tooltip_title' => __( 'Minimum Length', 'zodan-change-username' ),
						'tooltip_desc'  => __( 'The minimum allowed length for usernames is {minlength} characters.', 'zodan-change-username' ),
					),
					array(
						'id'   => 'email_header',
						'name' => '<h3>'.__( 'Email Settings', 'zodan-change-username' ).'</h3>',
						'desc' => '',
						'type' => 'header',
					),
					array(
						'id'            => 'enable_notifications',
						'name'          => __( 'Enable Email Notifications', 'zodan-change-username' ),
						'desc'          => __( 'Enable to send notification emails when usernames are changed.', 'zodan-change-username' ),
						'type'          => 'checkbox',
						'tooltip_title' => __( 'Enable Email Notifications', 'zodan-change-username' ),
						'tooltip_desc'  => __( 'Notifications are not sent when a user changes their own username.', 'zodan-change-username' ),
					),
					array(
						'id'   => 'email_subheader',
						'name' => '',
						'desc' => '',
						'type' => 'hook',
					),
					array(
						'id'   => 'email_subject',
						'name' => __( 'Email Subject', 'zodan-change-username' ),
						'desc' => __( 'Specify the subject for username change notifications.', 'zodan-change-username' ),
						'type' => 'text',
						'std'  => __( 'Username change notification - {sitename}', 'zodan-change-username' ),
					),
					array(
						'id'   => 'email_message',
						'name' => __( 'Email Message', 'zodan-change-username' ),
						'desc' => __( 'Specify the message to send for username change notifications.', 'zodan-change-username' ),
						'type' => 'editor',
						'std'  => __( 'Howdy! We\'re just writing to let you know that your username for {siteurl} has been changed to {new_username}.', 'zodan-change-username' ) . "\n\n" . __( 'Login now at {loginurl}', 'zodan-change-username' ),
					),
					array(
						'id'   => 'footer_thankyou',
						'name' => '',
						'desc' => '',
						'type' => 'hook',
					),
				),
				'strings' => array(
					array(
						'id'   => 'button_labels_header',
						'name' => __( 'Button Labels', 'zodan-change-username' ),
						'desc' => '',
						'type' => 'header',
					),
					array(
						'id'   => 'change_button_label',
						'name' => __( 'Change Button Label', 'zodan-change-username' ),
						'desc' => __( 'Customize the text for the \'change username\' button.', 'zodan-change-username' ),
						'type' => 'text',
						'std'  => __( 'Change Username', 'zodan-change-username' ),
					),
					array(
						'id'   => 'save_button_label',
						'name' => __( 'Save Button Label', 'zodan-change-username' ),
						'desc' => __( 'Customize the text for the save button.', 'zodan-change-username' ),
						'type' => 'text',
						'std'  => __( 'Save Username', 'zodan-change-username' ),
					),
					array(
						'id'   => 'cancel_button_label',
						'name' => __( 'Cancel Button Label', 'zodan-change-username' ),
						'desc' => __( 'Customize the text for the cancel button.', 'zodan-change-username' ),
						'type' => 'text',
						'std'  => __( 'Cancel', 'zodan-change-username' ),
					),
					array(
						'id'   => 'messages_header',
						'name' => __( 'Messages', 'zodan-change-username' ),
						'desc' => 'test',
						'type' => 'header',
					),
					array(
						'id'   => 'messages_subheader',
						'name' => '',
						'desc' => '',
						'type' => 'hook',
					),
					array(
						'id'   => 'please_wait_message',
						'name' => __( 'Please Wait Message', 'zodan-change-username' ),
						'desc' => __( 'Customize the text displayed while usernames are being checked.', 'zodan-change-username' ),
						'type' => 'text',
						'std'  => __( 'Please wait...', 'zodan-change-username' ),
					),
					array(
						'id'   => 'success_message',
						'name' => __( 'Username Changed Message', 'zodan-change-username' ),
						'desc' => __( 'Customize the message displayed when a username is changed successfully.', 'zodan-change-username' ),
						'type' => 'text',
						'std'  => __( 'Username successfully changed to {new_username}.', 'zodan-change-username' ),
					),
					array(
						'id'   => 'relogin_message',
						'name' => __( 'Relogin Message', 'zodan-change-username' ),
						'desc' => __( 'Customize the text for the relogin link shown if a user changes their own username.', 'zodan-change-username' ),
						'type' => 'text',
						'std'  => __( 'Click here to log back in.', 'zodan-change-username' ),
					),
					array(
						'id'   => 'error_short_username',
						'name' => __( 'Short Username Error', 'zodan-change-username' ),
						'desc' => __( 'Customize the error displayed when a username is too short.', 'zodan-change-username' ),
						'type' => 'text',
						'std'  => __( 'Username is too short, the minimum length is {minlength} characters.', 'zodan-change-username' ),
					),
					array(
						'id'            => 'error_wrong_permissions',
						'name'          => __( 'Wrong Permissions Error', 'zodan-change-username' ),
						'desc'          => __( 'Customize the error displayed when a user attempts to change a username they do not have permission to change.', 'zodan-change-username' ),
						'type'          => 'text',
						'std'           => __( 'You do not have the correct permissions to change this username.', 'zodan-change-username' ),
						'tooltip_title' => __( 'Wrong Permissions Error', 'zodan-change-username' ),
						'tooltip_desc'  => __( 'In normal circumstances, this message should never be triggered. It exists only to provide an extra layer of security against unauthorized use.', 'zodan-change-username' ),
					),
					array(
						'id'   => 'error_duplicate_username',
						'name' => __( 'Duplicate Username Error', 'zodan-change-username' ),
						'desc' => __( 'Customize the error displayed when a user attempts to change a username to something that is already in use.', 'zodan-change-username' ),
						'type' => 'text',
						'std'  => __( 'The username {new_username} is already in use. Please try again.', 'zodan-change-username' ),
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
			'zodan_change_username_settings_bulk',
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
			'zodan_change_username_settings_log',
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
		// 	'zodan_change_username_settings_help',
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
add_filter( 'zodan_change_username_registered_settings', 'zodan_change_username_registered_settings' );


/**
 * Display the subheader for the emails section
 *
 * @since       3.1.0
 * @return      void
 */
function zodan_change_username_display_email_subheader() {
	?>
	<details class="zodan-change-username-settings-note">
		<summary><span class="note-title"><?php esc_attr_e( 'Available Template Tags', 'zodan-change-username' ); ?></span></summary>
		<p><?php esc_attr_e( 'Emails allow the use of the following template tags:', 'zodan-change-username' ); ?></p>
		<?php zodan_change_username_tags_list( 'email' ); ?>
	</details>
	<?php
}
add_action( 'zodan_change_username_email_subheader', 'zodan_change_username_display_email_subheader' );


/**
 * Display the subheader for the messages section
 *
 * @since       0.0.3
 * @return      void
 */
function zodan_change_username_display_messages_subheader() {
	?>
	<details class="zodan-change-username-settings-note">
		<summary><span class="note-title"><?php esc_attr_e( 'Available Template Tags', 'zodan-change-username' ); ?></span></summary>
		<p><?php esc_attr_e( 'The message settings fields allow the use of the following template tags:', 'zodan-change-username' ); ?></p>
		<?php zodan_change_username_tags_list( 'message' ); ?>
	</details>
	<?php
}
add_action( 'zodan_change_username_messages_subheader', 'zodan_change_username_display_messages_subheader' );



/**
 * Render tutorial resources on the help tab.
 *
 * @since 4.0.0
 * @return void
 */
function zodan_change_username_display_tutorial_resources() {
	?>
	<tr valign="top">
		<td colspan="2" style="padding-top:0;">
			<div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:20px 22px;max-width:740px;margin-bottom:20px;">
				<h2 style="margin:0 0 10px;font-size:20px;"><?php esc_html_e( 'Documentation & Tutorial', 'zodan-change-username' ); ?></h2>
				<p style="margin:0 0 14px;color:#50575e;"><?php esc_html_e( 'Use the live demo to preview the admin flow, share a product walkthrough, and guide customers through the plugin UI.', 'zodan-change-username' ); ?></p>
				<p style="margin:0;">
					<a href="https://plugins.zodan.nl" target="_blank" rel="noopener noreferrer" class="button button-primary">
						<?php esc_html_e( 'Open Demo / Tutorial', 'zodan-change-username' ); ?>
					</a>
				</p>
			</div>
		</td>
	</tr>
	<?php
}
add_action( 'zodan_change_username_tutorial_resources', 'zodan_change_username_display_tutorial_resources' );



/**
 * Render bulk updater tab.
 *
 * @since 4.0.0
 * @return void
 */
function zodan_change_username_display_bulk_updater() {
	/*
	 * First, create an instance of the Zodan_Change_Username_Bulk_Updater::instance();
	 * 
	 */
	$bulk_updater = Zodan_Change_Username_Bulk_Updater::instance(); ?>

	<tr valign="top">
		<td colspan="2" style="padding-top:0;">
			<?php $bulk_updater->render_page(); ?>
		</td>
	</tr>
	<?php
}
add_action( 'zodan_change_username_bulk_updater', 'zodan_change_username_display_bulk_updater' );






function zodan_change_username_display_audit_log() {
	/*
	 * First, create an instance of the Zodan_Change_Username_Audit_Log::instance();
	 * 
	 */
	$audit_log = Zodan_Change_Username_Audit_Log::instance(); ?>

	<tr valign="top">
		<td colspan="2" style="padding-top:0;">
			<?php $audit_log->render_page(); ?>
		</td>
	</tr>
	<?php
}
add_action( 'zodan_change_username_audit_log', 'zodan_change_username_display_audit_log' );



add_action( 'zodan_change_username_footer_thankyou', 'zodan_change_username_display_footer_thankyou' );
function zodan_change_username_display_footer_thankyou() {
	add_filter('admin_footer_text', 'zodan_change_username_footer_print_thankyou', 900);
}
