<?php
/**
 * Settings handler — replaces Simple_Settings with native WordPress Settings API.
 *
 * Responsibilities:
 *  - Register the admin submenu page.
 *  - Register settings / sections / fields via the WP Settings API.
 *  - Render the settings page (tabs + fields).
 *  - Expose get_option() so the rest of the plugin can read values.
 *
 * @package     ZodanChangeUsername\Admin\Settings
 * @since       1.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class zodan_change_usernames_Settings
 *
 * Drop-in replacement for Simple_Settings. The public API used by the rest
 * of the plugin is intentionally the same:
 *
 *   $value = zodan_change_usernames()->settings->get_option( 'key', $default );
 *
 * Settings are stored in a single WordPress option named
 * `zodan_change_usernames_settings`.
 *
 * @since 1.1.0
 */
class zodan_change_usernames_Settings {

	/**
	 * Option key used to store all plugin settings.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'zodan_change_usernames_settings';

	/**
	 * Cached copy of all saved settings.
	 *
	 * @var array
	 */
	private $options = array();

	/**
	 * Registered settings definitions (from register-settings.php).
	 *
	 * @var array
	 */
	private $registered_settings = array();

	/**
	 * Tabs definition.
	 *
	 * @var array
	 */
	private $tabs = array();

	/**
	 * Sections definition.
	 *
	 * @var array
	 */
	private $sections = array();

	/**
	 * Tabs that have no save button.
	 *
	 * @var array
	 */
	private $unsavable_tabs = array();

	/**
	 * Constructor — hooks up everything.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		// Load saved options once.
		$this->options = (array) get_option( self::OPTION_KEY, array() );

		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ), 10 );
	}


	// -------------------------------------------------------------------------
	// Public API
	// -------------------------------------------------------------------------

	/**
	 * Get a single option value.
	 *
	 * @param  string $key     Option key.
	 * @param  mixed  $default Default value when key is not set.
	 * @return mixed
	 */
	public function get_option( $key, $default = false ) {
		if ( array_key_exists( $key, $this->options ) ) {
			return $this->options[ $key ];
		}
		return $default;
	}


	// -------------------------------------------------------------------------
	// Menu
	// -------------------------------------------------------------------------

	/**
	 * Add the admin submenu page.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function add_menu() {
		$menu = apply_filters(
			'zodan_change_usernames_menu',
			array(
				'type'       => 'submenu',
				'parent'     => 'users.php',
				'page_title' => __( 'Zodan Change Usernames Settings', 'zodan-change-usernames' ),
				'menu_title' => __( 'Change Usernames', 'zodan-change-usernames' ),
				'capability' => 'manage_options',
				'slug'       => 'zodan-change-usernames-settings',
			)
		);

		add_submenu_page(
			$menu['parent'],
			$menu['page_title'],
			$menu['menu_title'],
			isset( $menu['capability'] ) ? $menu['capability'] : 'manage_options',
			'zodan-change-usernames-settings',
			array( $this, 'render_page' )
		);
	}


	// -------------------------------------------------------------------------
	// Registration
	// -------------------------------------------------------------------------

	/**
	 * Register all settings, sections, and fields with the WP Settings API.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function register_settings() {
		// Collect definitions from register-settings.php (which uses filters).
		$this->tabs             = apply_filters( 'zodan_change_usernames_settings_tabs', array() );
		$this->sections         = apply_filters( 'zodan_change_usernames_registered_settings_sections', array() );
		$this->unsavable_tabs   = apply_filters( 'zodan_change_usernames_unsavable_tabs', array() );
		$this->registered_settings = apply_filters( 'zodan_change_usernames_registered_settings', array() );

		// Single option group.
		register_setting(
			'zodan_change_usernames_settings_group',
			self::OPTION_KEY,
			array( $this, 'sanitize_settings' )
		);

		// Register a WP section + field for every defined field.
		foreach ( $this->registered_settings as $tab => $sections ) {
			if ( ! is_array( $sections ) ) {
				continue;
			}
			foreach ( $sections as $section => $fields ) {
				if ( ! is_array( $fields ) ) {
					continue;
				}

				$section_id = "zodan_change_usernames_{$tab}_{$section}";

				add_settings_section(
					$section_id,
					isset( $this->sections[ $tab ][ $section ] ) ? $this->sections[ $tab ][ $section ] : '',
					'__return_empty_string',
					"zodan_change_usernames_{$tab}"
				);

				foreach ( $fields as $field ) {
					if ( empty( $field['id'] ) || in_array( $field['type'], array( 'header', 'hook' ), true ) ) {
						continue;
					}

					add_settings_field(
						$field['id'],
						isset( $field['name'] ) ? $field['name'] : '',
						array( $this, 'render_field' ),
						"zodan_change_usernames_{$tab}",
						$section_id,
						array(
							'field'   => $field,
							'tab'     => $tab,
							'section' => $section,
						)
					);
				}
			}
		}
	}

	/**
	 * Sanitize and merge submitted settings.
	 *
	 * @param  array $input Raw POST input.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$output = (array) get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $input ) ) {
			return $output;
		}

		foreach ( $input as $key => $value ) {
			$key    = sanitize_key( $key );
			$output[ $key ] = $this->sanitize_field( $key, $value );
		}

		// Update the in-memory cache.
		$this->options = $output;

		return $output;
	}

	/**
	 * Sanitize a single field value according to its registered type.
	 *
	 * @param  string $key   Field ID.
	 * @param  mixed  $value Raw value.
	 * @return mixed
	 */
	private function sanitize_field( $key, $value ) {
		$type = $this->get_field_type( $key );

		switch ( $type ) {
			case 'checkbox':
				return (bool) $value ? 1 : 0;

			case 'multicheck':
				if ( ! is_array( $value ) ) {
					return array();
				}
				return array_map( 'sanitize_key', $value );

			case 'number':
				return absint( $value );

			case 'editor':
				return wp_kses_post( $value );

			case 'text':
			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Look up the registered type for a field ID.
	 *
	 * @param  string $key Field ID.
	 * @return string
	 */
	private function get_field_type( $key ) {
		foreach ( $this->registered_settings as $tab => $sections ) {
			if ( ! is_array( $sections ) ) {
				continue;
			}
			foreach ( $sections as $section => $fields ) {
				if ( ! is_array( $fields ) ) {
					continue;
				}
				foreach ( $fields as $field ) {
					if ( isset( $field['id'] ) && $field['id'] === $key ) {
						return isset( $field['type'] ) ? $field['type'] : 'text';
					}
				}
			}
		}
		return 'text';
	}


	// -------------------------------------------------------------------------
	// Page rendering
	// -------------------------------------------------------------------------

	/**
	 * Render the full settings page.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : array_key_first( $this->tabs ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! array_key_exists( $active_tab, $this->tabs ) ) {
			$active_tab = array_key_first( $this->tabs );
		}

		$is_unsavable = in_array( $active_tab, $this->unsavable_tabs, true );
		?>
		<div class="wrap zodan-change-usernames-settings-wrap">
			<h1><?php esc_html_e( 'Zodan Change Usernames Settings', 'zodan-change-usernames' ); ?></h1>

			<?php settings_errors( 'zodan_change_usernames_settings_group' ); ?>

			<!-- Tabs -->
			<nav class="nav-tab-wrapper" style="margin-bottom:0;">
				<?php foreach ( $this->tabs as $tab_slug => $tab_label ) : ?>
					<a href="<?php echo esc_url( admin_url( 'users.php?page=zodan-change-usernames-settings&tab=' . $tab_slug ) ); ?>"
					   class="nav-tab <?php echo $active_tab === $tab_slug ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $tab_label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<?php if ( ! $is_unsavable ) : ?>
			<form method="post" action="options.php">
				<?php settings_fields( 'zodan_change_usernames_settings_group' ); ?>
				<input type="hidden" name="zodan_change_usernames_active_tab" value="<?php echo esc_attr( $active_tab ); ?>">

				<?php $this->render_tab_fields( $active_tab ); ?>

				<?php submit_button( __( 'Save Settings', 'zodan-change-usernames' ) ); ?>
			</form>
			<?php else : ?>
				<?php $this->render_tab_hooks( $active_tab ); ?>
			<?php endif; ?>

		</div>
		<?php
	}

	/**
	 * Render fields for a savable tab (uses WP Settings API + manual hook fields).
	 *
	 * @param string $tab Tab slug.
	 * @return void
	 */
	private function render_tab_fields( $tab ) {
		if ( empty( $this->registered_settings[ $tab ] ) ) {
			return;
		}

		foreach ( $this->registered_settings[ $tab ] as $section => $fields ) {
			$section_label = isset( $this->sections[ $tab ][ $section ] ) ? $this->sections[ $tab ][ $section ] : '';

			echo '<div class="zodan-settings-section">';

			if ( $section_label ) {
				echo '<h2>' . esc_html( $section_label ) . '</h2>';
			}

			echo '<table class="form-table" role="presentation"><tbody>';

			foreach ( $fields as $field ) {
				$this->render_field_row( $field );
			}

			echo '</tbody></table>';
			echo '</div>';
		}
	}

	/**
	 * Render all hook-type fields for an unsavable tab (bulk, log, …).
	 *
	 * @param string $tab Tab slug.
	 * @return void
	 */
	private function render_tab_hooks( $tab ) {
		if ( empty( $this->registered_settings[ $tab ] ) ) {
			return;
		}
		echo '<table class="form-table" role="presentation"><tbody>';
		foreach ( $this->registered_settings[ $tab ] as $section => $fields ) {
			foreach ( $fields as $field ) {
				if ( isset( $field['type'] ) && $field['type'] === 'hook' && ! empty( $field['id'] ) ) {
					do_action( "zodan_change_usernames_{$field['id']}" );
				}
			}
		}
		echo '</tbody></table>';
	}

	/**
	 * Render a single field row (called both by WP Settings API and directly).
	 *
	 * @param array $field Field definition array.
	 * @return void
	 */
	public function render_field( $args ) {
		$this->render_field_row( $args['field'] );
	}

	/**
	 * Output a <tr> for a field.
	 *
	 * @param array $field Field definition.
	 * @return void
	 */
	private function render_field_row( $field ) {
		$type = isset( $field['type'] ) ? $field['type'] : 'text';
		$id   = isset( $field['id'] ) ? $field['id'] : '';
		$name = isset( $field['name'] ) ? $field['name'] : '';
		$desc = isset( $field['desc'] ) ? $field['desc'] : '';

		// Headers.
		if ( $type === 'header' ) {
			echo '<tr><td colspan="2" style="padding-bottom:0;">' . wp_kses_post( $name ) . '</td></tr>';
			return;
		}

		// Hooks — fire action and bail.
		if ( $type === 'hook' ) {
			if ( $id ) {
				do_action( "zodan_change_usernames_{$id}" );
			}
			return;
		}

		echo '<tr>';
		echo '<th scope="row"><label for="' . esc_attr( $id ) . '">';
		echo wp_kses_post( $name );

		// Tooltip.
		if ( ! empty( $field['tooltip_title'] ) ) {
			echo ' <span class="zodan-tooltip" title="' . esc_attr( $field['tooltip_desc'] ?? '' ) . '">?</span>';
		}
		echo '</label></th>';
		echo '<td>';

		$this->render_field_input( $field );

		if ( $desc ) {
			echo '<p class="description">' . wp_kses_post( $desc ) . '</p>';
		}

		echo '</td>';
		echo '</tr>';
	}

	/**
	 * Output the actual input element for a field.
	 *
	 * @param array $field Field definition.
	 * @return void
	 */
	private function render_field_input( $field ) {
		$id      = $field['id'];
		$type    = $field['type'];
		$std     = isset( $field['std'] ) ? $field['std'] : '';
		$value   = $this->get_option( $id, $std );
		$name    = 'zodan_change_usernames_settings[' . esc_attr( $id ) . ']';

		switch ( $type ) {
			case 'text':
				printf(
					'<input type="text" id="%s" name="%s" value="%s" class="regular-text">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value )
				);
				break;

			case 'number':
				$min  = isset( $field['min'] ) ? (int) $field['min'] : 0;
				$step = isset( $field['step'] ) ? (int) $field['step'] : 1;
				$size = isset( $field['size'] ) ? $field['size'] : 'small-text';
				printf(
					'<input type="number" id="%s" name="%s" value="%s" min="%d" step="%d" class="%s">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					intval($min),
					intval($step),
					esc_attr( $size )
				);
				break;

			case 'checkbox':
				printf(
					'<input type="checkbox" id="%s" name="%s" value="1" %s>',
					esc_attr( $id ),
					esc_attr( $name ),
					checked( 1, $value, false )
				);
				break;

			case 'multicheck':
				$options  = isset( $field['options'] ) ? (array) $field['options'] : array();
				$selected = is_array( $value ) ? $value : array();
				foreach ( $options as $key => $label ) {
					$checked = in_array( $key, $selected, true );
					printf(
						'<label style="display:block;margin-bottom:4px;"><input type="checkbox" name="%s[]" value="%s" %s> %s</label>',
						esc_attr( $name ),
						esc_attr( $key ),
						checked( true, $checked, false ),
						esc_html( $label )
					);
				}
				break;

			case 'editor':
				wp_editor(
					wpautop( $value ),
					$id,
					array(
						'textarea_name' => $name,
						'textarea_rows' => 8,
						'teeny'         => true,
						'media_buttons' => false,
					)
				);
				break;

			default:
				printf(
					'<input type="text" id="%s" name="%s" value="%s" class="regular-text">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value )
				);
				break;
		}
	}
}
