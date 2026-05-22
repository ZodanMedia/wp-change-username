<?php
/**
 * Audit Log
 *
 * @package     ZodanChangeUsername\Admin\AuditLog
 * @since       4.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Zodan_Change_Username_Audit_Log' ) ) {

	class Zodan_Change_Username_Audit_Log {

		private static $instance;

		public static function instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
				self::$instance->hooks();
				self::$instance->create_table();
			}
			return self::$instance;
		}

		private function hooks() {
			add_action( 'zodan_change_username_after_process', array( $this, 'log_change' ), 10, 2 );
			add_action( 'wp_ajax_uc_export_audit_log',         array( $this, 'export_csv' ) );
		}

		public static function get_table_name() {
			global $wpdb;
			return $wpdb->prefix . 'uc_audit_log';
		}

		public static function create_table() {
			global $wpdb;

			$table   = self::get_table_name();
			$charset = $wpdb->get_charset_collate();

			$sql = $wpdb->prepare( "CREATE TABLE {%s} (
				id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				changed_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				changed_by_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
				changed_by_login varchar(60) NOT NULL DEFAULT '',
				old_username varchar(60) NOT NULL DEFAULT '',
				new_username varchar(60) NOT NULL DEFAULT '',
				ip_address varchar(45) NOT NULL DEFAULT '',
				status varchar(20) NOT NULL DEFAULT 'success',
				PRIMARY KEY (id),
				KEY changed_at (changed_at),
				KEY status (status)
			) {%s};", [$table, $charset]);

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}


		/**
		 * Write a single entry to the audit log table.
		 *
		 * Hooked to `zodan_change_username_after_process` with priority 10.
		 *
		 * @since  4.0.0
		 * @param  string $old_username
		 * @param  string $new_username
		 * @param  string $log_status   'success' (default) or any other status string.
		 * @return void
		 */
		public function log_change( $old_username, $new_username, $log_status = 'success' ) {
			global $wpdb;

			$current_user = wp_get_current_user();
			$ip           = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				self::get_table_name(),
				array(
					'changed_at'       => current_time( 'mysql' ),
					'changed_by_id'    => (int) $current_user->ID,
					'changed_by_login' => $current_user->user_login,
					'old_username'     => $old_username,
					'new_username'     => $new_username,
					'ip_address'       => $ip,
					'status'           => $log_status,
				),
				array( '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
			);
		}


		/**
		 * Retrieve log rows, with optional search and ordering.
		 *
		 * @since  4.0.0
		 * @param  array $args {
		 *     Optional. Query arguments.
		 *     @type int    $per_page  Rows per page.   Default 20.
		 *     @type int    $page      Current page.    Default 1.
		 *     @type string $search    Search string.   Default ''.
		 *     @type string $orderby   Column to sort.  Default 'changed_at'.
		 *     @type string $order     'ASC' or 'DESC'. Default 'DESC'.
		 * }
		 * @return array  Array of row objects.
		 */
		public function get_logs( $args = array() ) {
			global $wpdb;

			$defaults = array(
				'per_page' => 20,
				'page'     => 1,
				'search'   => '',
				'orderby'  => 'changed_at',
				'order'    => 'DESC',
			);
			$args = wp_parse_args( $args, $defaults );

			$table  = self::get_table_name();
			$offset = ( absint( $args['page'] ) - 1 ) * absint( $args['per_page'] );

			$allowed_orderby = array( 'changed_at', 'old_username', 'new_username', 'changed_by_login', 'status' );
			$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'changed_at';
			$order           = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

			if ( ! empty( $args['search'] ) ) {
				$s = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
				return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$wpdb->prepare(
						"SELECT * FROM {%s} WHERE old_username LIKE %s OR new_username LIKE %s OR changed_by_login LIKE %s ORDER BY {%s} {%s} LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						$table, $s, $s, $s,
						$orderby,
						$order,
						absint( $args['per_page'] ),
						$offset
					)
				);
			}

			return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare(
					"SELECT * FROM {%s} ORDER BY {%s} {%s} LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$table,
					$orderby,
					$order,
					absint( $args['per_page'] ),
					$offset
				)
			);
		}


		/**
		 * Return the total number of matching log rows.
		 *
		 * @since  4.0.0
		 * @param  string $search  Optional search string.
		 * @return int
		 */
		public function get_total( $search = '' ) {
			global $wpdb;

			$table = self::get_table_name();

			if ( ! empty( $search ) ) {
				$s = '%' . $wpdb->esc_like( sanitize_text_field( $search ) ) . '%';
				return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {%s} WHERE old_username LIKE %s OR new_username LIKE %s OR changed_by_login LIKE %s",
						$table, $s, $s, $s
					)
				);
			}

			return (int) $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {%s}", $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		}


		/**
		 * Return the human-readable labels for each clean-interval option.
		 *
		 * Centralised here so render_page() and clean_log() always agree on
		 * the valid set of keys.
		 *
		 * @since  4.1.0
		 * @return array  [ interval_key => label ]
		 */
		public static function get_clean_intervals() {
			return array(
				'all'     => __( 'Everything (full clear)', 'zodan-change-username' ),
				'7days'   => __( 'Older than 7 days',      'zodan-change-username' ),
				'3days'   => __( 'Older than 3 days',      'zodan-change-username' ),
				'1day'    => __( 'Older than 1 day',       'zodan-change-username' ),
				'8hours'  => __( 'Older than 8 hours',     'zodan-change-username' ),
			);
		}


		/**
		 * Delete log entries according to the requested interval.
		 *
		 * Called by the main plugin class inside its `admin_init` (priority 9)
		 * handler, after nonce and capability checks have already passed.
		 *
		 * @since  4.1.0
		 * @param  string $interval  One of the keys returned by get_clean_intervals().
		 * @return int|false  Number of rows deleted, or false on DB error.
		 */
		public function clean_log( $interval ) {
			global $wpdb;

			$table = self::get_table_name();

			if ( 'all' === $interval ) {
				// TRUNCATE is faster and resets AUTO_INCREMENT; fine here because
				// we are intentionally wiping the whole table.
				$result = $wpdb->query( $wpdb->prepare("TRUNCATE TABLE {%s}", $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared

				// TRUNCATE returns 0 on success (not the row count), so report
				// a meaningful value by returning 0 explicitly on success.
				return ( false === $result ) ? false : 0;
			}

			// Map interval keys to MySQL interval expressions.
			$interval_map = array(
				'7days'  => 'INTERVAL 7 DAY',
				'3days'  => 'INTERVAL 3 DAY',
				'1day'   => 'INTERVAL 1 DAY',
				'8hours' => 'INTERVAL 8 HOUR',
			);

			if ( ! isset( $interval_map[ $interval ] ) ) {
				return false;
			}

			$mysql_interval = $interval_map[ $interval ];

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return $wpdb->query(
				$wpdb->prepare( "DELETE FROM {%s} WHERE changed_at < ( NOW() - {%d} )", $table, $mysql_interval )
			);
		}


		/**
		 * Stream the full audit log as a downloadable CSV.
		 *
		 * Triggered via wp_ajax_uc_export_audit_log.
		 *
		 * @since  4.0.0
		 * @return void  Exits after streaming.
		 */
		public function export_csv() {
			check_ajax_referer( 'uc_export_audit_log', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Insufficient permissions.', 'zodan-change-username' ) );
			}

			$logs = $this->get_logs( array( 'per_page' => 9999, 'page' => 1 ) );

			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=zcu-audit-log-' . gmdate( 'Y-m-d' ) . '.csv' );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );

			$output = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			fputcsv( $output, array( 'ID', 'Date', 'Changed By', 'Old Username', 'New Username', 'IP Address', 'Status' ) );

			foreach ( $logs as $log ) {
				fputcsv( $output, array(
					$log->id,
					$log->changed_at,
					$log->changed_by_login,
					$log->old_username,
					$log->new_username,
					$log->ip_address,
					$log->status,
				) );
			}

			fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			exit;
		}


		/**
		 * Render the Log/history tab contents.
		 *
		 * This method never processes POST data — that is handled by the main
		 * plugin class at admin_init priority 9.  Here we only read transients
		 * left behind by those handlers and display the page.
		 *
		 * @since  4.0.0
		 * @return void
		 */
		public function render_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to access this page.', 'zodan-change-username' ) );
			}

			add_filter('admin_footer_text', 'zodan_change_username_footer_print_thankyou', 900);

			// Retrieve and immediately delete the one-time clean result.
			$transient_key = 'uc_clean_log_result_' . get_current_user_id();
			$clean_result  = get_transient( $transient_key );
			delete_transient( $transient_key );

			$search   = isset( $_GET['uc_search'] ) ? sanitize_text_field( wp_unslash( $_GET['uc_search'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			$paged    = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
			$per_page = 20;

			$logs  = $this->get_logs( array(
				'per_page' => $per_page,
				'page'     => $paged,
				'search'   => $search,
			) );
			$total = $this->get_total( $search );
			$pages = ceil( $total / $per_page );

			$export_nonce   = wp_create_nonce( 'uc_export_audit_log' );
			$clean_intervals = self::get_clean_intervals();
			?>
			<div class="wrap zcu-audit-log-wrap">
				<h1 class="wp-heading-inline"><?php esc_html_e( 'Change Username Activity Log', 'zodan-change-username' ); ?></h1>
				<hr class="wp-header-end">

				<?php if ( false !== $clean_result ) : ?>
					<?php if ( false === $clean_result['deleted'] ) : ?>
						<div class="notice notice-error is-dismissible">
							<p><?php esc_html_e( 'An error occurred while cleaning the log. Please try again.', 'zodan-change-username' ); ?></p>
						</div>
					<?php elseif ( 'all' === $clean_result['interval'] ) : ?>
						<div class="notice notice-success is-dismissible">
							<p><?php esc_html_e( 'The log has been completely cleared.', 'zodan-change-username' ); ?></p>
						</div>
					<?php else : ?>
						<div class="notice notice-success is-dismissible">
							<p><?php
								printf(
									/* translators: 1: number of deleted rows, 2: human-readable interval label */
									esc_html__( '%1$d log %2$s deleted (%3$s).', 'zodan-change-username' ),
									(int) $clean_result['deleted'],
									(int) $clean_result['deleted'] === 1
										? esc_html__( 'entry', 'zodan-change-username' )
										: esc_html__( 'entries', 'zodan-change-username' ),
									esc_html( $clean_intervals[ $clean_result['interval'] ] ?? $clean_result['interval'] )
								);
							?></p>
						</div>
					<?php endif; ?>
				<?php endif; ?>

				<!-- Clean Log -->
				<div class="zcu-card">
					<div class="zcu-log-button-bar">
						<div class="zcu-clean-log-box">
							<h2><?php esc_html_e( 'Clean Log', 'zodan-change-username' ); ?></h2>
							<p class="description"><?php esc_html_e( 'Permanently delete log entries. This action cannot be undone.', 'zodan-change-username' ); ?></p>
							<form method="post" action="" id="zcu-clean-log-form">
								<?php wp_nonce_field( 'uc_clean_log', 'uc_clean_log_nonce' ); ?>
								<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
									<label for="uc_clean_interval" class="screen-reader-text">
										<?php esc_html_e( 'Delete interval', 'zodan-change-username' ); ?>
									</label>
									<select name="uc_clean_interval" id="uc_clean_interval">
										<?php foreach ( $clean_intervals as $key => $label ) : ?>
											<option value="<?php echo esc_attr( $key ); ?>">
												<?php echo esc_html( $label ); ?>
											</option>
										<?php endforeach; ?>
									</select>
									<button
										type="submit"
										class="button button-secondary"
										id="zcu-clean-log-btn"
										data-confirm-all="<?php esc_attr_e( 'This will permanently delete ALL log entries. Are you sure?', 'zodan-change-username' ); ?>"
										data-confirm-partial="<?php esc_attr_e( 'This will permanently delete the selected log entries. Are you sure?', 'zodan-change-username' ); ?>"
									>
										<?php esc_html_e( 'Clean Log', 'zodan-change-username' ); ?>
									</button>
								</div>
							</form>
							<script>
							( function () {
								var form = document.getElementById( 'zcu-clean-log-form' );
								if ( ! form ) return;
								form.addEventListener( 'submit', function ( e ) {
									var select  = document.getElementById( 'uc_clean_interval' );
									var btn     = document.getElementById( 'zcu-clean-log-btn' );
									var msg     = select && select.value === 'all'
										? btn.dataset.confirmAll
										: btn.dataset.confirmPartial;
									if ( ! window.confirm( msg ) ) {
										e.preventDefault();
									}
								} );
							}() );
							</script>
						</div>
						
						<!-- export -->
						<a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=uc_export_audit_log&security=' . $export_nonce ) ); ?>" class="page-title-action">
							<?php esc_html_e( 'Export CSV', 'zodan-change-username' ); ?>
						</a>


						<!-- Search -->
						<form class="zcu-log-search" method="get" action="">
							<input type="hidden" name="page" value="zodan-change-username-settings">
							<input type="hidden" name="tab"  value="log">
							<p class="search-box">
								<input
									type="search"
									name="uc_search"
									value="<?php echo esc_attr( $search ); ?>"
									placeholder="<?php esc_attr_e( 'Search logs...', 'zodan-change-username' ); ?>"
								>
								<button type="submit" class="button"><?php esc_html_e( 'Search', 'zodan-change-username' ); ?></button>
							</p>
						</form>
					</div>
				</div>



				<!-- Log table -->
				<table class="wp-list-table widefat fixed striped zcu-audit-log-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Date',         'zodan-change-username' ); ?></th>
							<th><?php esc_html_e( 'Changed By',   'zodan-change-username' ); ?></th>
							<th><?php esc_html_e( 'Old Username', 'zodan-change-username' ); ?></th>
							<th><?php esc_html_e( 'New Username', 'zodan-change-username' ); ?></th>
							<th><?php esc_html_e( 'IP Address',   'zodan-change-username' ); ?></th>
							<th><?php esc_html_e( 'Status',       'zodan-change-username' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $logs ) ) : ?>
							<?php foreach ( $logs as $log ) : ?>
								<tr>
									<td><?php echo esc_html( $log->changed_at ); ?></td>
									<td><?php echo esc_html( $log->changed_by_login ); ?></td>
									<td><?php echo esc_html( $log->old_username ); ?></td>
									<td><?php echo esc_html( $log->new_username ); ?></td>
									<td><?php echo esc_html( $log->ip_address ); ?></td>
									<td>
										<span class="zcu-log-status zcu-log-status--<?php echo esc_attr( $log->status ); ?>">
											<?php echo esc_html( ucfirst( $log->status ) ); ?>
										</span>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="6"><?php esc_html_e( 'No log entries found.', 'zodan-change-username' ); ?></td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>

				<?php if ( $pages > 1 ) : ?>
					<div class="tablenav bottom">
						<div class="tablenav-pages">
							<?php
							echo wp_kses_post( paginate_links( array(
								'base'      => add_query_arg( 'paged', '%#%' ),
								'format'    => '',
								'prev_text' => '&laquo;',
								'next_text' => '&raquo;',
								'total'     => $pages,
								'current'   => $paged,
							) ) );
							?>
						</div>
					</div>
				<?php endif; ?>
			</div>
			<?php
		}
	}
}
