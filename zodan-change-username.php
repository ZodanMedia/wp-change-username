<?php
/**
 * Plugin Name: Zodan Change Username
 * Contributors: zodannl, martenmoolenaar
 * Plugin URI: https://plugins.zodan.nl/wordpress-change-username
 * Description: Change usernames without any hassle
 * Author: Zodan
 * Author URI: https://zodan.nl
 * Version: 1.0.0
 * Tested up to: 7.0
 * Stable Tag: 1.0.0
 * Text Domain: zodan-change-username
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */


// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'Zodan_Change_Username' ) ) {


	/**
	 * Main Zodan_Change_Username class
	 *
	 * @access      public
	 * @since       0.0.2
	 */
	final class Zodan_Change_Username {

		/**
		 * The one true Zodan_Change_Username
		 *
		 * @access      private
		 * @since       0.0.2
		 * @var         Zodan_Change_Username $instance The one true Zodan_Change_Username
		 */
		private static $instance;


		/**
		 * The settings object
		 *
		 * @access      public
		 * @since       0.0.3
		 * @var         object $settings The settings object
		 */
		public $settings;


		/**
		 * The template tags object
		 *
		 * @access      public
		 * @since       0.0.3
		 * @var         object $template_tags The template tags object
		 */
		public $template_tags;


		/**
		 * Get active instance
		 *
		 * @access      public
		 * @since       0.0.2
		 * @static
		 * @return      object self::$instance The one true Zodan_Change_Username
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Zodan_Change_Username ) ) {
				self::$instance = new Zodan_Change_Username();
				self::$instance->setup_constants();
				self::$instance->hooks();
				self::$instance->includes();
				self::$instance->template_tags = new Zodan_Change_Username_Template_Tags();
			}

			return self::$instance;
		}


		/**
		 * Throw error on object clone
		 *
		 * The whole idea of the singleton design pattern is that there is
		 * a single object. Therefore, we don't want the object to be cloned.
		 *
		 * @access      protected
		 * @since       0.0.1
		 * @return      void
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, esc_attr__( 'Nah. You cannot do that. Sorry.', 'zodan-change-username' ), '1.0.0' );
		}


		/**
		 * Disable unserializing of the class
		 *
		 * @access      protected
		 * @since       0.0.1
		 * @return      void
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, esc_attr__( 'Nah. You cannot do that. Sorry.', 'zodan-change-username' ), '1.0.0' );
		}


		/**
		 * Setup plugin constants
		 *
		 * @access      private
		 * @since       0.0.2
		 * @return      void
		 */
		private function setup_constants() {
			// Plugin version.
			if ( ! defined( 'ZODAN_CHANGE_USERNAME_VER' ) ) {
				define( 'ZODAN_CHANGE_USERNAME_VER', '1.0.0' );
			}

			// Plugin path.
			if ( ! defined( 'ZODAN_CHANGE_USERNAME_DIR' ) ) {
				define( 'ZODAN_CHANGE_USERNAME_DIR', plugin_dir_path( __FILE__ ) );
			}

			// Plugin URL.
			if ( ! defined( 'ZODAN_CHANGE_USERNAME_URL' ) ) {
				define( 'ZODAN_CHANGE_USERNAME_URL', plugin_dir_url( __FILE__ ) );
			}

			// Plugin file.
			if ( ! defined( 'ZODAN_CHANGE_USERNAME_FILE' ) ) {
				define( 'ZODAN_CHANGE_USERNAME_FILE', __FILE__ );
			}
		}


		/**
		 * Run plugin base hooks
		 *
		 * @access      private
		 * @since       3.2.0
		 * @return      void
		 */
		private function hooks() {
			// AJAX handlers — these run via admin-ajax.php, not admin_init, so no conflict.
			add_action( 'wp_ajax_zodan_user_names_bulk_update', array( self::$instance, 'prepare_ajax_bulk_update' ) );
			add_action( 'wp_ajax_zcu_export_users_csv',          array( self::$instance, 'prepare_export_users_csv' ) );

			/*
			 * CSV import is a regular POST to the admin page, not an AJAX request.
			 * We intercept it at priority 9, do all the work here, then redirect —
			 * so render_page() just reads the transient for results.
			 */
			add_action( 'admin_init', array( self::$instance, 'handle_csv_import_request' ), 9 );

			/*
			 * Log-clean form POST — same pattern: intercept at priority 9,
			 * do the work, redirect.
			 */
			add_action( 'admin_init', array( self::$instance, 'handle_clean_log_request' ), 9 );
		}


		/**
		 * Include necessary files
		 *
		 * @access      private
		 * @since       0.0.1
		 * @return      void
		 */
		private function includes() {
			global $zodan_change_username_options;

			// Load native settings handler.
			require_once ZODAN_CHANGE_USERNAME_DIR . 'includes/admin/settings/class-settings.php';
			require_once ZODAN_CHANGE_USERNAME_DIR . 'includes/admin/settings/register-settings.php';

			self::$instance->settings     = new Zodan_Change_Username_Settings();
			$zodan_change_username_options = get_option( Zodan_Change_Username_Settings::OPTION_KEY, array() );

			require_once ZODAN_CHANGE_USERNAME_DIR . 'includes/misc-functions.php';
			require_once ZODAN_CHANGE_USERNAME_DIR . 'includes/scripts.php';
			require_once ZODAN_CHANGE_USERNAME_DIR . 'includes/class-zodan-change-username-template-tags.php';

			// Include bulk updater.
			require_once ZODAN_CHANGE_USERNAME_DIR . 'includes/admin/class-bulk-updater.php';

			// Include audit log and initialise it inside the main singleton lifecycle.
			require_once ZODAN_CHANGE_USERNAME_DIR . 'includes/admin/class-audit-log.php';
			Zodan_Change_Username_Audit_Log::instance();

			// Add a link to the plugin settings page
			$plugin_basename = plugin_basename( __FILE__ );
			// add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_plugin_settings_link' ) );
			add_filter( 'plugin_action_links_' . $plugin_basename, array( self::$instance, 'add_plugin_settings_link' ) );

			if ( is_admin() ) {
				require_once ZODAN_CHANGE_USERNAME_DIR . 'includes/admin/actions.php';
			}
		}


		/**
		 * Delegate AJAX bulk-update to the Bulk_Updater class.
		 *
		 * @since  4.0.0
		 * @return void
		 */
		public function prepare_ajax_bulk_update() {
			$bulk_updater = Zodan_Change_Username_Bulk_Updater::instance();
			$bulk_updater->ajax_bulk_update();
		}


		/**
		 * Delegate CSV export to the Bulk_Updater class.
		 *
		 * @since  4.0.0
		 * @return void
		 */
		public function prepare_export_users_csv() {
			$bulk_updater = Zodan_Change_Username_Bulk_Updater::instance();
			$bulk_updater->export_users_csv();
		}


		/**
		 * Handle the CSV import form POST early (priority 9)
		 * hooks in at priority 10.
		 *
		 * After processing we store the results in a short-lived transient and
		 * redirect back to the Bulk tab so that:
		 *  - the browser URL is clean (no re-submit on refresh), and
		 *  - render_page() simply reads the transient to display results.
		 *
		 * @since  4.0.0
		 * @return void
		 */
		public function handle_csv_import_request() {
			// Only act when our nonce field is present.
			if ( ! isset( $_POST['zcu_import_csv_nonce'] ) ) {
				return;
			}

			// Verify nonce and capability.
			check_admin_referer( 'zcu_import_csv', 'zcu_import_csv_nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Insufficient permissions.', 'zodan-change-username' ) );
			}

			$import_results = array();

			if ( ! empty( $_FILES['zcu_csv_file']['name'] ) ) {
				$bulk_updater   = Zodan_Change_Username_Bulk_Updater::instance();
				$import_results = $bulk_updater->process_csv_import( $_FILES['zcu_csv_file'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			}

			// Store results for render_page() to pick up after the redirect.
			set_transient(
				'zcu_import_results_' . get_current_user_id(),
				$import_results,
				60
			);

			// Redirect back to the Bulk tab — prevents form re-submission on refresh.
			wp_safe_redirect(
				admin_url( 'users.php?page=zodan-change-username-settings&tab=bulk' )
			);
			exit;
		}


		/**
		 * Handle the clean-log form POST early (priority 9)
		 * hooks in at priority 10.
		 *
		 * Delegates the actual deletion to Zodan_Change_Username_Audit_Log, stores
		 * a one-time result notice in a transient, then redirects back to the Log tab.
		 *
		 * @since  4.1.0
		 * @return void
		 */
		public function handle_clean_log_request() {
			if ( ! isset( $_POST['zcu_clean_log_nonce'] ) ) {
				return;
			}

			check_admin_referer( 'zcu_clean_log', 'zcu_clean_log_nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Insufficient permissions.', 'zodan-change-username' ) );
			}

			$interval = isset( $_POST['zcu_clean_interval'] ) ? sanitize_key( $_POST['zcu_clean_interval'] ) : 'all';
			$audit_log = Zodan_Change_Username_Audit_Log::instance();
			$deleted   = $audit_log->clean_log( $interval );

			set_transient(
				'zcu_clean_log_result_' . get_current_user_id(),
				array(
					'deleted'  => $deleted,
					'interval' => $interval,
				),
				60
			);

			wp_safe_redirect(
				admin_url( 'users.php?page=zodan-change-username-settings&tab=log' )
			);
			exit;
		}


		/**
		 * Add link to settings page on plugin overview
		 *
		 * @since v1.0
		 */
		public static function add_plugin_settings_link( $links ) {
			$settings_link = '<a href="users.php?page=zodan-change-username-settings">' . __( 'Settings', 'zodan-change-username' ) . '</a>';
			array_unshift( $links, $settings_link );
			return $links;
		}


	}
}






/**
 * The main function responsible for returning the one true Zodan_Change_Username
 * instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without
 * needing to declare the global.
 *
 * Example: <?php $zodan_change_username = zodan_change_username(); ?>
 *
 * @since       0.0.2
 * @return      Zodan_Change_Username The one true Zodan_Change_Username
 */
function zodan_change_username() {
	return Zodan_Change_Username::instance();
}


// Get things started.
zodan_change_username();
