<?php
/**
 * Helper functions
 *
 * @package     ZodanChangeUsername\Functions
 * @since       0.0.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Get an array of user roles
 *
 * @since       0.0.3
 * @return      array $roles The available user roles
 */
function zodan_change_usernames_get_user_roles() {
	global $wp_roles;

	$roles = $wp_roles->get_names();

	// Administrator can always edit.
	unset( $roles['administrator'] );

	return apply_filters( 'zodan_change_usernames_user_roles', $roles );
}


/**
 * Check if a user can change a given username
 *
 * @since       0.0.3
 * @return      bool $allowed Whether or not this user can change their username
 */
function zodan_change_usernames_can_change_own_username() {
	$allowed = false;

	if ( is_user_logged_in() ) {
		$allowed_roles = Zodan_Change_Usernames()->settings->get_option( 'allowed_roles', array() );
		$user_data     = wp_get_current_user();
		$user_roles    = $user_data->roles;

		if ( in_array( 'administrator', $user_roles, true ) ) {
			$allowed = true;
		} elseif ( is_array( $user_roles ) ) {
			foreach ( $user_roles as $user_role => $role_name ) {
				if ( in_array( $user_role, $allowed_roles, true ) ) {
					$allowed = true;
				}
			}
		}
	}

	return apply_filters( 'zodan_change_usernames_can_change_own_username', $allowed );
}


/**
 * Process a username change
 *
 * @since       0.0.3
 * @param       string $old_username The old (current) username.
 * @param       string $new_username The new username.
 * @return      bool $return Whether or not we completed successfully
 * @return      int $user_id | bool false
 */
function zodan_change_usernames_process( $old_username, $new_username ) {
	global $wpdb;

	$return = false;

	// One last sanity check to ensure the user exists.
	$user_id = username_exists( $old_username );
	if ( $user_id ) {
		// Let devs hook into the process.
		do_action( 'zodan_change_usernames_before_process', $old_username, $new_username );

		// Update username!
		$q = $wpdb->prepare( "UPDATE $wpdb->users SET user_login = %s WHERE user_login = %s", $new_username, $old_username ); // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables

		if ( false !== $wpdb->query( $q ) ) { // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
			// Update user_nicename.
			$qnn = $wpdb->prepare( "UPDATE $wpdb->users SET user_nicename = %s WHERE user_login = %s AND user_nicename = %s", $new_username, $new_username, $old_username ); // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables
			$wpdb->query( $qnn ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery

			// Update display_name.
			$qdn = $wpdb->prepare( "UPDATE $wpdb->users SET display_name = %s WHERE user_login = %s AND display_name = %s", $new_username, $new_username, $old_username ); // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables
			$wpdb->query( $qdn ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery

			// Update nickname.
			$nickname = get_user_meta( $user_id, 'nickname', true );
			if ( $nickname === $old_username ) {
				update_user_meta( $user_id, 'nickname', $new_username );
			}

			// If the user is a Super Admin, update their permissions.
			if ( is_multisite() && is_super_admin( $user_id ) ) {
				grant_super_admin( $user_id );
			}

			// Reassign Coauthor Attribution.
			if ( zodan_change_usernames_plugin_installed( 'co-authors-plus/co-authors-plus.php' ) ) {
				global $coauthors_plus;

				$coauthor_posts = get_posts(
					array(
						'post_type'      => get_post_types(),
						'posts_per_page' => -1,
						'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery
							array(
								'taxonomy' => $coauthors_plus->coauthor_taxonomy,
								'field'    => 'name',
								'terms'    => $old_username,
							),
						),
					)
				);

				$current_term = get_term_by( 'name', $old_username, $coauthors_plus->coauthor_taxonomy );
				wp_delete_term( $current_term->term_id, $coauthors_plus->coauthor_taxonomy );

				if ( ! empty( $coauthor_posts ) ) {
					foreach ( $coauthor_posts as $coauthor_post ) {
						$coauthors_plus->add_coauthors( $coauthor_post->ID, array( $new_username ), true );
					}
				}
			}

			$return = $user_id;
		}

		// Let devs hook into the process.
		do_action( 'zodan_change_usernames_after_process', $old_username, $new_username );

		return $return;
	}
}


/**
 * Check if a plugin is installed
 *
 * @since       0.0.1
 * @param       string $plugin The path to the plugin to check.
 * @return      boolean true if installed and active, false otherwise
 */
function zodan_change_usernames_plugin_installed( $plugin = false ) {
	$ret = false;

	if ( $plugin ) {
		// $active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
		$active_plugins = wp_get_active_and_valid_plugins();

		if ( in_array( $plugin, $active_plugins, true ) ) {
			$ret = true;
		}
	}

	return $ret;
}



/**
 * Print nice Zodan footer text
 * 
 */
function zodan_change_usernames_footer_print_thankyou( $data ) {
    $data = '<p class="zThanks"><a href="https://zodan.nl" target="_blank" rel="noreferrer">' .
                esc_html__('Made with', 'zodan-change-usernames') . 
                '<svg id="heart" data-name="heart" xmlns="http://www.w3.org/2000/svg" width="745.2" height="657.6" version="1.1" viewBox="0 0 745.2 657.6"><path class="heart" d="M372,655.6c-2.8,0-5.5-1.3-7.2-3.6-.7-.9-71.9-95.4-159.9-157.6-11.7-8.3-23.8-16.3-36.5-24.8-60.7-40.5-123.6-82.3-152-151.2C0,278.9-1.4,217.6,12.6,158.6,28,93.5,59,44.6,97.8,24.5,125.3,10.2,158.1,2.4,190.2,2.4s.3,0,.4,0c34.7,0,66.5,9,92.2,25.8,22.4,14.6,70.3,78,89.2,103.7,18.9-25.7,66.8-89,89.2-103.7,25.7-16.8,57.6-25.7,92.2-25.8,32.3-.1,65.2,7.8,92.8,22.1h0c38.7,20.1,69.8,69,85.2,134.1,14,59.1,12.5,120.3-3.8,159.8-28.5,69-91.3,110.8-152,151.2-12.8,8.5-24.8,16.5-36.5,24.8-88.1,62.1-159.2,156.6-159.9,157.6-1.7,2.3-4.4,3.6-7.2,3.6Z"></path></svg>' .
                esc_html__('by Zodan', 'zodan-change-usernames') .
            '</a></p>';

    return $data;
}