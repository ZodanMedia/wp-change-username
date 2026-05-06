<?php
/**
 * Template tags
 *
 * @package     ZodanChangeUsername\TemplateTags
 * @since       3.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Template tag class
 *
 * @since       3.0.0
 */
class Zodan_Change_Username_Template_Tags {


	/**
	 * Container for storing all tags
	 *
	 * @access      private
	 * @since       3.0.0
	 * @var         array $tags Container for storing all tags
	 */
	private $tags;


	/**
	 * The old username of the user
	 *
	 * @access      private
	 * @since       3.0.0
	 * @var         string $old_username The old username of the user
	 */
	private $old_username;


	/**
	 * The new username of the user
	 *
	 * @access      private
	 * @since       3.0.0
	 * @var         string $new_username The new username of the user
	 */
	private $new_username;


	/**
	 * Add a template tag
	 *
	 * @access      public
	 * @since       3.0.0
	 * @param       string $tag Tag to be replaced.
	 * @param       string $desc Description of the tag.
	 * @param       array  $context Context for the tag.
	 * @param       string $func The hook to run when tag is found.
	 * @return      void
	 */
	public function add( $tag, $desc, $context, $func ) {
		if ( is_callable( $func ) ) {
			$this->tags[ $tag ] = array(
				'tag'     => $tag,
				'desc'    => $desc,
				'context' => $context,
				'func'    => $func,
			);
		}
	}


	/**
	 * Remove a template tag
	 *
	 * @access      public
	 * @since       3.0.0
	 * @param       string $tag Tag to be removed.
	 * @return      void
	 */
	public function remove( $tag ) {
		unset( $this->tags[ $tag ] );
	}


	/**
	 * Check if $tag is a registered template tag
	 *
	 * @access      public
	 * @since       3.0.0
	 * @param       string $tag Tag to search for.
	 * @return      bool True if found, false otherwise
	 */
	public function template_tag_exists( $tag ) {
		return array_key_exists( $tag, $this->tags );
	}


	/**
	 * Returns a list of all tags
	 *
	 * @access      public
	 * @since       3.0.0
	 * @param       string $context The context to return tags for.
	 * @return      array $tags The available tags
	 */
	public function get_tags( $context ) {
		$tags = $this->tags;

		foreach ( $tags as $tag_id => $tag_data ) {
			if ( ! in_array( $context, $tag_data['context'], true ) ) {
				unset( $tags[ $tag_id ] );
			}
		}

		return $tags;
	}


	/**
	 * Search content for tags and filter through their hooks
	 *
	 * @access      public
	 * @since       3.0.0
	 * @param       string $content Content to search.
	 * @param       string $old_username The old username of the user.
	 * @param       string $new_username The new username of the user.
	 * @return      string $new_content Filtered content
	 */
	public function do_tags( $content, $old_username = '', $new_username = '' ) {
		// Ensure there is at least one tag.
		if ( empty( $this->tags ) || ! is_array( $this->tags ) ) {
			return $content;
		}

		$this->old_username = $old_username;
		$this->new_username = $new_username;

		$new_content = preg_replace_callback( '/{([A-z0-9\-\_]+)}/s', array( $this, 'do_tag' ), $content );

		$this->old_username = '';
		$this->new_username = '';

		return $new_content;
	}


	/**
	 * Do a specific tag
	 *
	 * @access      public
	 * @since       3.0.0
	 * @param       array $m Message.
	 * @return      mixed
	 */
	public function do_tag( $m ) {
		// Get tag.
		$tag = $m[1];

		// Return tag if tag not set.
		if ( ! $this->template_tag_exists( $tag ) ) {
			return $m[0];
		}

		return call_user_func( $this->tags[ $tag ]['func'], $this->old_username, $this->new_username, $tag );
	}
}


/**
 * Add a template tag
 *
 * @since       3.0.0
 * @param       string $tag Tag to be replaced.
 * @param       string $desc Description of the tag.
 * @param       array  $context Context for the tag.
 * @param       string $func The hook to run when tag is found.
 * @return      void
 */
function zodan_change_username_add_template_tag( $tag, $desc, $context, $func ) {
	Zodan_Change_Username()->template_tags->add( $tag, $desc, $context, $func );
}


/**
 * Remove a template tag
 *
 * @since       3.0.0
 * @param       string $tag Template tag to remove.
 * @return      void
 */
function zodan_change_username_remove_template_tag( $tag ) {
	Zodan_Change_Username()->template_tags->remove( $tag );
}


/**
 * Check if a text exists
 *
 * @since       3.0.0
 * @param       string $tag The string to check.
 * @return      bool True if exists, false otherwise
 */
function zodan_change_username_tag_exists( $tag ) {
	return Zodan_Change_Username()->template_tags->template_tag_exists( $tag );
}


/**
 * Get all tags
 *
 * @since       3.0.0
 * @param       string $context The context to return tags for.
 * @return      array The existing tags
 */
function zodan_change_username_get_template_tags( $context = 'message' ) {
	return Zodan_Change_Username()->template_tags->get_tags( $context );
}


/**
 * Get a formatted list of all available tags
 *
 * @since       3.0.0
 * @param       string $context The context to display.
 * @return      void
 */
function zodan_change_username_tags_list( $context = 'message' ) {
	// Get all tags.
	$tags = zodan_change_username_get_template_tags( $context );

	// Check.
	if ( count( $tags ) > 0 ) {
		?>
		<ul class="zodan-change-username-template-tag-list">
		<?php
		foreach ( $tags as $tag ) {
			// Add tag to list.
			?>
			<li class="zodan-change-username-template-tag"><span>{<?php echo esc_attr( $tag['tag'] ); ?>}</span><?php echo esc_attr( $tag['desc'] ); ?></li>
			<?php
		}
		?>
		</ul>
		<?php
	}
}


/**
 * Search content for tags and filter
 *
 * @since       3.0.0
 * @param       string $content Content to search.
 * @param       string $old_username The old username of the user.
 * @param       string $new_username The new username of the user.
 * @return      string $content Filtered content
 */
function zodan_change_username_do_tags( $content, $old_username = '', $new_username = '' ) {
	// Replace all tags.
	$content = apply_filters( 'zodan_change_username_do_tags', Zodan_Change_Username()->template_tags->do_tags( $content, $old_username, $new_username ) );

	return $content;
}


/**
 * Load tags
 *
 * @since       3.0.0
 * @return      void
 */
function zodan_change_username_load_template_tags() {
	do_action( 'zodan_change_username_add_template_tags' );
}
add_action( 'init', 'zodan_change_username_load_template_tags', -999 );


/**
 * Add default tags
 *
 * @since       3.0.0
 * @return      void
 */
function zodan_change_username_setup_template_tags() {
	// Setup default tags array.
	$tags = array(
		array(
			'tag'     => 'old_username',
			'desc'    => __( 'The original username of the user', 'zodan-change-username' ),
			'context' => array( 'email', 'message' ),
			'func'    => 'zodan_change_username_template_tag_old_username',
		),
		array(
			'tag'     => 'new_username',
			'desc'    => __( 'The new username of the user', 'zodan-change-username' ),
			'context' => array( 'email', 'message' ),
			'func'    => 'zodan_change_username_template_tag_new_username',
		),
		array(
			'tag'     => 'email',
			'desc'    => __( 'The email address of the user', 'zodan-change-username' ),
			'context' => array( 'email' ),
			'func'    => 'zodan_change_username_template_tag_email',
		),
		array(
			'tag'     => 'sitename',
			'desc'    => __( 'Your site name', 'zodan-change-username' ),
			'context' => array( 'email' ),
			'func'    => 'zodan_change_username_template_tag_sitename',
		),
		array(
			'tag'     => 'siteurl',
			'desc'    => __( 'Your site URL', 'zodan-change-username' ),
			'context' => array( 'email' ),
			'func'    => 'zodan_change_username_template_tag_siteurl',
		),
		array(
			'tag'     => 'loginurl',
			'desc'    => __( 'The login URL for your site', 'zodan-change-username' ),
			'context' => array( 'email' ),
			'func'    => 'zodan_change_username_template_tag_loginurl',
		),
		array(
			'tag'     => 'date',
			'desc'    => __( 'Today\'s date', 'zodan-change-username' ),
			'context' => array( 'email' ),
			'func'    => 'zodan_change_username_template_tag_date',
		),
		array(
			'tag'     => 'name',
			'desc'    => __( 'The first name of the user', 'zodan-change-username' ),
			'context' => array( 'email' ),
			'func'    => 'zodan_change_username_template_tag_name',
		),
		array(
			'tag'     => 'fullname',
			'desc'    => __( 'The full name of the user, first and last', 'zodan-change-username' ),
			'context' => array( 'email' ),
			'func'    => 'zodan_change_username_template_tag_fullname',
		),
		array(
			'tag'     => 'minlength',
			'desc'    => __( 'The minimum allowed username length', 'zodan-change-username' ),
			'context' => array( 'message' ),
			'func'    => 'zodan_change_username_template_tag_minlength',
		),
	);

	$tags = apply_filters( 'zodan_change_username_template_tags', $tags );

	foreach ( $tags as $tag ) {
		zodan_change_username_add_template_tag( $tag['tag'], $tag['desc'], $tag['context'], $tag['func'] );
	}
}
add_action( 'zodan_change_username_add_template_tags', 'zodan_change_username_setup_template_tags' );


/**
 * Template tag: old_username
 *
 * @since       3.0.0
 * @param       string $old_username The old username of the user.
 * @param       string $new_username The new username of the user.
 * @return      string $username The original username of the user
 */
function zodan_change_username_template_tag_old_username( $old_username, $new_username ) {
	$current_user = wp_get_current_user();
	$username     = $current_user->user_login;

	return '<strong>' . $username . '</strong>';
}


/**
 * Template tag: new_username
 *
 * @since       3.0.0
 * @param       string $old_username The old username of the user.
 * @param       string $new_username The new username of the user.
 * @return      string $username The new username of the user
 */
function zodan_change_username_template_tag_new_username( $old_username, $new_username ) {
	return '<strong>' . $new_username . '</strong>';
}


/**
 * Template tag: email
 *
 * @since       3.0.0
 * @param       string $old_username The old username of the user.
 * @param       string $new_username The new username of the user.
 * @return      string $email The email address of the user
 */
function zodan_change_username_template_tag_email( $old_username, $new_username ) {
	$current_user = wp_get_current_user();
	$email        = $current_user->user_email;

	return $email;
}


/**
 * Template tag: sitename
 *
 * @since       3.0.0
 * @param       string $old_username The old username of the user.
 * @param       string $new_username The new username of the user.
 * @return      string Site name
 */
function zodan_change_username_template_tag_sitename( $old_username, $new_username ) {
	return get_bloginfo( 'name' );
}


/**
 * Template tag: siteurl
 *
 * @since       3.0.0
 * @param       string $old_username The old username of the user.
 * @param       string $new_username The new username of the user.
 * @return      string Site URL
 */
function zodan_change_username_template_tag_siteurl( $old_username, $new_username ) {
	return get_site_url();
}


/**
 * Template tag: loginurl
 *
 * @since       3.0.0
 * @param       string $old_username The old username of the user.
 * @param       string $new_username The new username of the user.
 * @return      string Site URL
 */
function zodan_change_username_template_tag_loginurl( $old_username, $new_username ) {
	return wp_login_url();
}


/**
 * Template tag: date
 *
 * @since       3.0.0
 * @param       string $old_username The old username of the user.
 * @param       string $new_username The new username of the user.
 * @return      string The purchase date
 */
function zodan_change_username_template_tag_date( $old_username, $new_username ) {
	return date_i18n( get_option( 'date_format' ), strtotime( gmdate( 'U' ) ) );
}


/**
 * Template tag: name
 *
 * @since       3.0.0
 * @param       string $old_username The old username of the user.
 * @param       string $new_username The new username of the user.
 * @return      string $name The first name of the user
 */
function zodan_change_username_template_tag_name( $old_username, $new_username ) {
	$current_user = get_user_by( 'login', $new_username );

	if ( isset( $current_user->user_firstname ) ) {
		$name = $current_user->user_firstname;
	} else {
		$name = $current_user->user_email;
	}

	return $name;
}


/**
 * Template tag: fullname
 *
 * @since       3.0.0
 * @param       string $old_username The old username of the user.
 * @param       string $new_username The new username of the user.
 * @return      string $name The full name of the user
 */
function zodan_change_username_template_tag_fullname( $old_username, $new_username ) {
	$current_user = get_user_by( 'login', $new_username );

	if ( isset( $current_user->user_firstname ) && isset( $current_user->user_lastname ) ) {
		$name = $current_user->user_firstname . ' ' . $current_user->user_lastname;
	} elseif ( isset( $current_user->user_firstname ) ) {
		$name = $current_user->user_firstname;
	} else {
		$name = $current_user->user_email;
	}

	return $name;
}


/**
 * Template tag: minlength
 *
 * @since       3.0.0
 * @param       string $old_username The old username of the user.
 * @param       string $new_username The new username of the user.
 * @return      string $minlength The minimum username length
 */
function zodan_change_username_template_tag_minlength( $old_username, $new_username ) {
	return zodan_change_username()->settings->get_option( 'minimum_length', 3 );
}
