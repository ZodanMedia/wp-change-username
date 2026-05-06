<?php
/**
 * Bulk Username Updater
 *
 * @package     ZodanChangeUsername\Admin\BulkUpdater
 * @since       4.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Zodan_Change_Username_Bulk_Updater' ) ) {

	class Zodan_Change_Username_Bulk_Updater {

		private static $instance;

		public static function instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
				self::$instance->hooks();
			}
			return self::$instance;
		}

		private function hooks() {
			// AJAX handlers are registered in the main plugin class to avoid
			// duplicate registration if instance() is called more than once.
		}


		/**
		 * Handle AJAX bulk-update requests (inline table editing).
		 *
		 * @since  4.0.0
		 * @return void  Exits via wp_send_json_*.
		 */
		public function ajax_bulk_update() {
			check_ajax_referer( 'zodan_user_names_bulk_update', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Insufficient permissions.', 'zodan-change-username' ) );
			}

			$raw_updates = isset( $_POST['updates'] ) ? wp_unslash( $_POST['updates'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

			if ( ! is_array( $raw_updates ) || empty( $raw_updates ) ) {
				wp_send_json_error( __( 'No updates provided.', 'zodan-change-username' ) );
			}

			// Enforce max 100 per request.
			$raw_updates = array_slice( $raw_updates, 0, 100, true );

			$results = array();

			foreach ( $raw_updates as $update ) {
				$old = sanitize_user( (string) $update['currentUsername'] );
				$new = sanitize_user( (string) $update['newUsername'] );

				if ( empty( $new ) || $old === $new ) {
					continue;
				}

				if ( ! validate_username( $new ) ) {
					$results[] = array(
						'old'     => $old,
						'new'     => $new,
						'success' => false,
						'message' => __( 'Invalid characters in username.', 'zodan-change-username' ),
					);
					continue;
				}

				$illegal = array_map( 'strtolower', (array) apply_filters( 'illegal_user_logins', array() ) );
				if ( in_array( strtolower( $new ), $illegal, true ) ) {
					$results[] = array(
						'old'     => $old,
						'new'     => $new,
						'success' => false,
						'message' => __( 'That username is not allowed.', 'zodan-change-username' ),
					);
					continue;
				}

				if ( username_exists( $new ) ) {
					$results[] = array(
						'old'     => $old,
						'new'     => $new,
						'success' => false,
						'message' => __( 'Username already exists.', 'zodan-change-username' ),
					);
					continue;
				}

				$display_name = false;
				$result       = zodan_change_username_process( $old, $new );

				if ( false !== $result ) {
					$user         = get_userdata( (int) $result );
					$display_name = $user ? $user->display_name : false;
					$success      = true;
				} else {
					$success = false;
				}

				$results[] = array(
					'old'          => $old,
					'new'          => $new,
					'success'      => $success,
					'display_name' => $display_name,
					'message'      => $success
						? __( 'Changed', 'zodan-change-username' )
						: __( 'Failed to update username.', 'zodan-change-username' ),
				);
			}

			wp_send_json_success( array(
				'new_nonce' => wp_create_nonce( 'zodan_user_names_bulk_update' ),
				'results'   => $results,
			) );
		}


		/**
		 * Stream the full user list as a downloadable CSV.
		 *
		 * @since  4.0.0
		 * @return void  Exits after streaming.
		 */
		public function export_users_csv() {
			check_ajax_referer( 'uc_export_users', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Insufficient permissions.', 'zodan-change-username' ) );
			}

			$users = get_users( array( 'number' => 9999 ) );

			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=users-' . gmdate( 'Y-m-d' ) . '.csv' );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );

			$output = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			fputcsv( $output, array( 'old_username', 'new_username' ) );

			foreach ( $users as $user ) {
				fputcsv( $output, array( $user->user_login, '' ) );
			}

			fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			exit;
		}


		/**
		 * Parse a CSV upload and return an array of [ old => new ] username pairs.
		 *
		 * This method only reads and validates the file structure; it does not
		 * perform any database writes.
		 *
		 * @since  4.0.0
		 * @param  array $file  An element from $_FILES.
		 * @return array|WP_Error  Associative array on success, WP_Error on failure.
		 */
		public function handle_csv_import( $file ) {
			if ( ! isset( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
				return new WP_Error( 'invalid_file', __( 'Invalid file upload.', 'zodan-change-username' ) );
			}

			$handle = fopen( $file['tmp_name'], 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			if ( ! $handle ) {
				return new WP_Error( 'read_error', __( 'Could not read uploaded file.', 'zodan-change-username' ) );
			}

			$updates = array();
			$row_num = 0;

			while ( ( $row = fgetcsv( $handle ) ) !== false ) {
				$row_num++;

				// Skip header row.
				if ( 1 === $row_num && isset( $row[0] ) && strtolower( trim( $row[0] ) ) === 'old_username' ) {
					continue;
				}

				if ( empty( $row[0] ) || empty( $row[1] ) ) {
					continue;
				}

				$old = sanitize_user( trim( $row[0] ) );
				$new = sanitize_user( trim( $row[1] ) );

				if ( ! empty( $old ) && ! empty( $new ) ) {
					$updates[ $old ] = $new;
				}
			}

			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

			return $updates;
		}


		/**
		 * Parse the CSV file and apply all username changes.
		 *
		 * Called by the main plugin class during admin_init (priority 9), before
		 * Simple_Settings can intercept the POST.  Returns a results array that
		 * the main class stores in a transient for render_page() to display.
		 *
		 * @since  4.0.0
		 * @param  array $file  An element from $_FILES.
		 * @return array  Each element: [ 'old', 'new', 'success' (bool), 'message' ].
		 *                On file-level error, returns [ 'error' => message ].
		 */
		public function process_csv_import( $file ) {
			$updates = $this->handle_csv_import( $file );

			if ( is_wp_error( $updates ) ) {
				return array( 'error' => $updates->get_error_message() );
			}

			$results = array();

			foreach ( $updates as $old => $new ) {
				$old = sanitize_user( $old );
				$new = sanitize_user( $new );

				if ( ! validate_username( $new ) ) {
					$results[] = array(
						'old'     => $old,
						'new'     => $new,
						'success' => false,
						'message' => __( 'Invalid characters.', 'zodan-change-username' ),
					);
					continue;
				}

				$illegal = array_map( 'strtolower', (array) apply_filters( 'illegal_user_logins', array() ) );
				if ( in_array( strtolower( $new ), $illegal, true ) ) {
					$results[] = array(
						'old'     => $old,
						'new'     => $new,
						'success' => false,
						'message' => __( 'That username is not allowed.', 'zodan-change-username' ),
					);
					continue;
				}

				if ( username_exists( $new ) ) {
					$results[] = array(
						'old'     => $old,
						'new'     => $new,
						'success' => false,
						'message' => __( 'Already exists.', 'zodan-change-username' ),
					);
					continue;
				}

				$success   = zodan_change_username_process( $old, $new );
				$results[] = array(
					'old'     => $old,
					'new'     => $new,
					'success' => (bool) $success,
					'message' => $success
						? __( 'Updated.', 'zodan-change-username' )
						: __( 'Failed.', 'zodan-change-username' ),
				);
			}

			return $results;
		}


		/**
		 * Render the Bulk tab contents.
		 *
		 * Import results are read from a transient (written by the main plugin
		 * class after it processes the POST and redirects here), so this method
		 * only ever displays the form and any previous-request feedback — it
		 * never processes POST data itself.
		 *
		 * @since  4.0.0
		 * @return void
		 */
		public function render_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to access this page.', 'zodan-change-username' ) );
			}

			// Retrieve and immediately delete the one-time import results.
			$transient_key  = 'uc_import_results_' . get_current_user_id();
			$import_results = get_transient( $transient_key );
			delete_transient( $transient_key );

			// Normalise: false (transient missing) → empty array.
			if ( false === $import_results ) {
				$import_results = array();
			}

			$paged    = isset( $_GET['ucbu_page'] ) ? absint( $_GET['ucbu_page'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
			$per_page = 50;
			$users    = get_users( array(
				'number' => $per_page,
				'offset' => ( $paged - 1 ) * $per_page,
			) );
			$total    = (int) count_users()['total_users'];
			$pages    = ceil( $total / $per_page );

			$export_nonce = wp_create_nonce( 'uc_export_users' );
			$bulk_nonce   = wp_create_nonce( 'zodan_user_names_bulk_update' );
			?>
			<div class="wrap uc-bulk-updater-wrap">
				<h1 class="wp-heading-inline"><?php esc_html_e( 'Change Username Bulk Updater', 'zodan-change-username' ); ?></h1>
				<hr class="wp-header-end">

				<?php if ( ! empty( $import_results ) && ! isset( $import_results['error'] ) ) : ?>
					<div class="notice notice-success is-dismissible">
						<p><?php
							$success_count = count( array_filter( $import_results, function( $r ) { return $r['success']; } ) );
							$fail_count    = count( $import_results ) - $success_count;
							printf(
								/* translators: 1: number of successes, 2: number of failures */
								esc_html__( 'CSV import complete: %1$d updated, %2$d failed.', 'zodan-change-username' ),
								(int) $success_count,
								(int) $fail_count
							);
						?></p>
					</div>
				<?php elseif ( isset( $import_results['error'] ) ) : ?>
					<div class="notice notice-error is-dismissible">
						<p><?php echo esc_html( $import_results['error'] ); ?></p>
					</div>
				<?php endif; ?>

				<!-- CSV Import -->
				<div class="uc-card">
					<h2><?php esc_html_e( 'Import from CSV', 'zodan-change-username' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Upload a CSV file with columns: old_username, new_username', 'zodan-change-username' ); ?></p>
					<form method="post" enctype="multipart/form-data">
						<?php wp_nonce_field( 'uc_import_csv', 'uc_import_csv_nonce' ); ?>
						<input type="file" name="uc_csv_file" accept=".csv" required>
						<button type="submit" class="button button-secondary"><?php esc_html_e( 'Import CSV', 'zodan-change-username' ); ?></button>
						<a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=uc_export_users_csv&security=' . $export_nonce ) ); ?>" class="button">
							<?php esc_html_e( 'Download User List (CSV)', 'zodan-change-username' ); ?>
						</a>
					</form>
				</div>

				<!-- CSV Import Results -->
				<?php if ( ! empty( $import_results ) && ! isset( $import_results['error'] ) ) : ?>
					<div class="uc-card">
						<h3><?php esc_html_e( 'Import Results', 'zodan-change-username' ); ?></h3>
						<table class="wp-list-table widefat fixed striped" style="max-width:600px">
							<thead><tr><th><?php esc_html_e( 'Old', 'zodan-change-username' ); ?></th><th><?php esc_html_e( 'New', 'zodan-change-username' ); ?></th><th><?php esc_html_e( 'Result', 'zodan-change-username' ); ?></th></tr></thead>
							<tbody>
								<?php foreach ( $import_results as $r ) : ?>
									<tr>
										<td><?php echo esc_html( $r['old'] ); ?></td>
										<td><?php echo esc_html( $r['new'] ); ?></td>
										<td class="<?php echo $r['success'] ? 'uc-result-success' : 'uc-result-fail'; ?>">
											<?php echo $r['success'] ? '&#10003; ' : '&#10007; '; echo esc_html( $r['message'] ); ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>

				<!-- Inline Bulk Edit -->
				<div class="uc-card">
					<h2><?php esc_html_e( 'Inline Bulk Edit', 'zodan-change-username' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Enter new usernames for selected users and click "Update Selected". Leave blank to skip.', 'zodan-change-username' ); ?></p>

					<div id="uc-bulk-results"></div>

					<form id="uc-bulk-update-form" method="post" enctype="multipart/form-data" action="">
						<input type="hidden" id="zodan-change-username-bulk-nonce" name="security" value="<?php echo esc_attr( $bulk_nonce ); ?>">

						<div style="margin-bottom:10px;">
							<label>
								<input type="checkbox" id="uc-select-all">
								<?php esc_html_e( 'Select All', 'zodan-change-username' ); ?>
							</label>
						</div>

						<table class="wp-list-table widefat fixed striped uc-bulk-table">
							<thead>
								<tr>
									<th></th>
									<th><?php esc_html_e( 'Current Username', 'zodan-change-username' ); ?></th>
									<th><?php esc_html_e( 'Display Name', 'zodan-change-username' ); ?></th>
									<th><?php esc_html_e( 'Role', 'zodan-change-username' ); ?></th>
									<th><?php esc_html_e( 'New Username', 'zodan-change-username' ); ?></th>
									<th><?php esc_html_e( 'Status', 'zodan-change-username' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $users as $user ) : ?>
									<tr data-user-login="<?php echo esc_attr( $user->user_login ); ?>">
										<td><input type="checkbox" class="uc-user-check" value="<?php echo esc_attr( $user->user_login ); ?>"></td>
										<td class="uc-current-username"><?php echo esc_html( $user->user_login ); ?></td>
										<td class="uc-current-display-name"><?php echo esc_html( $user->display_name ); ?></td>
										<td><?php echo esc_html( implode( ', ', $user->roles ) ); ?></td>
										<td>
											<input
												type="text"
												class="uc-new-username regular-text"
												data-old="<?php echo esc_attr( $user->user_login ); ?>"
												placeholder="<?php echo esc_attr( $user->user_login ); ?>"
												autocomplete="off"
											>
										</td>
										<td class="uc-row-status"></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>

						<?php if ( $pages > 1 ) : ?>
							<div style="margin-top:10px;">
								<?php
								echo wp_kses_post( paginate_links( array(
									'base'      => add_query_arg( 'ucbu_page', '%#%' ),
									'format'    => '',
									'prev_text' => '&laquo;',
									'next_text' => '&raquo;',
									'total'     => $pages,
									'current'   => $paged,
								) ) );
								?>
							</div>
						<?php endif; ?>

						<p style="margin-top:15px;">
							<button type="submit" id="uc-bulk-update-btn" class="button button-primary" data-text-default="<?php
									esc_html_e( 'Update Selected Usernames', 'zodan-change-username' );
								?>"><?php
									esc_html_e( 'Update Selected Usernames', 'zodan-change-username' );
							?></button>
						</p>
					</form>
				</div>
			</div>
			<?php
		}
	}
}
