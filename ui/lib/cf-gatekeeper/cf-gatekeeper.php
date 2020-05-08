<?php
/**
 * Plugin Name: CF Gatekeeper
 * Description: Redirect to login page if the user is not logged in.
 * Author: CrowdFavorite
 * Author URI: https://crowdfavorite.com
 * Version: 1.8.3-dev
 *
 * @package cf-gatekeeper
 */

define( 'CF_GATEKEEPER', true );
define( 'CFGK_VER', '1.8.3' );

// Load localization library.
load_plugin_textdomain( 'cf_gatekeeper' );

require_once dirname( __FILE__ ) . '/class-cf-user-api.php';

/**
 * Initialize gate keeper.
 *
 * @return void
 */
function cf_gatekeeper() {
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return;
	}

	if ( ! is_user_logged_in() ) {
		CF_User_API::key_login();
	}

	$user_capability    = apply_filters( 'cf_gatekeeper_capability', 'read' );
	$gatekeeper_enabled = apply_filters( 'cf_gatekeeper_enabled', true );

	if ( $gatekeeper_enabled && ! current_user_can( $user_capability ) ) {
		$login_page = site_url( 'wp-login.php' );
		$protocol   = is_ssl() ? 'https://' : 'http://';
		$requested  = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		if ( substr( $requested, 0, strlen( $login_page ) ) !== $login_page ) {
			$network_login_page = network_site_url( 'wp-login.php' );
			if ( $login_page === $network_login_page || substr( $requested, 0, strlen( $network_login_page ) ) !== $network_login_page ) {
				auth_redirect();
			}
		}
	}
}

if ( ! defined( 'XMLRPC_REQUEST' ) ) {
	/*
	This needs to run at 11+ as to run after cfgk_process_users
	and to catch any filters running at default priority 10.
	*/
	add_action( 'init', 'cf_gatekeeper', 12 );
}

/**
 * Init callback function for processing users.
 *
 * @return void
 */
function cfgk_process_users() {
	CF_User_API::process_users();

	// Don't turn on by default.
	update_option( 'cfgk_enabled', '0' );
}

// Do initial assignment of cf_user_key's.
add_action( 'init', 'cfgk_process_users' );

/**
 * Callback function for adding a key to an user on user_register and profile_update.
 *
 * @param int $user_id User ID.
 *
 * @return void
 */
function cfgk_add_key_to_user( $user_id ) {
	CF_User_API::add_key_to_user( $user_id );
}

add_action( 'user_register', 'cfgk_add_key_to_user' );
add_action( 'profile_update', 'cfgk_add_key_to_user' );

/**
 * Callback function for adding the user key to the feeds links.
 *
 * @param string $url Feed URL.
 *
 * @return string
 */
function cfgk_user_api_feeds( $url ) {
	$current_user_id = get_current_user_id();

	if ( ! empty( $current_user_id ) ) {
		$key = get_user_meta( $current_user_id, 'cf_user_key', true );
		if ( ! empty( $key ) ) {
			$url = add_query_arg( 'cf_user_key', $key, $url );
		}
	}

	return $url;
}

add_filter( 'feed_link', 'cfgk_user_api_feeds' );
add_filter( 'category_feed_link', 'cfgk_user_api_feeds' );
add_filter( 'tag_feed_link', 'cfgk_user_api_feeds' );
add_filter( 'search_feed_link', 'cfgk_user_api_feeds' );
add_filter( 'author_feed_link', 'cfgk_user_api_feeds' );
add_filter( 'post_comments_feed_link', 'cfgk_user_api_feeds' );

/**
 * Callback function to show the user key on the profile show/edit pages.
 *
 * @return void
 */
function cfgk_show_api_key() {
	global $profileuser;
	$key = get_user_meta( $profileuser->ID, 'cf_user_key', true );

	?>
	<table class="form-table">
		<tr>
			<th><label for="description"><?php esc_html_e( 'Gatekeeper API Key', 'cf_gatekeeper' ); ?></label></th>
			<td><span><?php echo esc_html( $key ); ?></span></td>
		</tr>
	</table>
	<?php
}

add_action( 'show_user_profile', 'cfgk_show_api_key' );
add_action( 'edit_user_profile', 'cfgk_show_api_key' );

/**
 * Gate keeper request handler.
 *
 * @return void
 */
function cfgk_request_handler() {
	$action = filter_input( INPUT_POST, 'cf_action' );

	if ( ! $action || ! check_admin_referer( 'update_gatekeeper_options' ) ) {
		return;
	}

	$page = filter_input( INPUT_GET, 'page' );

	switch ( $action ) {
		case 'save_gatekeeper_options':
			$enable_gatekeeper = filter_input( INPUT_POST, 'cfgk_enable_gatekeeper' );

			if ( update_option( 'cfgk_enabled', $enable_gatekeeper ) ) {
				if ( $enable_gatekeeper ) {
					do_action( 'cfgk_enabled' );
					$message_id = 1;
				} else {
					do_action( 'cfgk_disabled' );
					$message_id = 2;
				}

				// Redirect properly, with a message id.
				wp_safe_redirect(
					basename( $_SERVER['SCRIPT_NAME'] ) .
					sprintf( '?page=%1$s&updated=true&message=%2%s', $page, $message_id )
				);
				exit;
			}

			// Nothing updated.
			wp_safe_redirect( basename( $_SERVER['SCRIPT_NAME'] ) . '?page=' . $page );
			exit;
		default:
			break;
	}
}

add_action( 'init', 'cfgk_request_handler' );

/**
 * Gate keeper settings form.
 *
 * @return void
 */
function cfgk_settings_form() {
	$option_value = get_option( 'cfgk_enabled' );

	$enabled_options = array(
		__( 'Yes', 'cf_gatekeeper' ) => '1',
		__( 'No', 'cf_gatekeeper' )  => '0',
	);

	?>
	<div class="wrap">
		<h2><?php esc_html_e( 'CF Gatekeeper', 'cf_gatekeeper' ); ?></h2>
		<?php do_action( 'cfgk_settings_form_notices', filter_input( INPUT_GET, 'message' ) ); ?>
		<form method="post">
			<?php wp_nonce_field( 'update_gatekeeper_options' ); ?>
			<div>
				<label for="cfgk_enable_gatekeeper"><?php esc_html_e( 'Enable Gatekeeper?', 'cf_gatekeeper' ); ?></label>
				<?php foreach ( $enabled_options as $label => $value ) : ?>
					<input type="radio" name="cfgk_enable_gatekeeper"
						id="cfgk_enable_gatekeeper<?php echo esc_attr( strtolower( $label ) ); ?>"
						value="<?php echo esc_attr( $value ); ?>"
						<?php echo checked( $value, $option_value ); /* WPCS: XSS OK. */ ?>  />

					<label for="cfgk_enable_gatekeeper_<?php echo esc_attr( strtolower( $label ) ); ?>">
						<?php echo esc_html( $label ); ?>
					</label>
				<?php endforeach; ?>
			</div>
			<button type="submit" class="button-primary"><?php esc_html_e( 'Save Option', 'cf_gatekeeper' ); ?></button>
			<input type="hidden" name="cf_action" value="save_gatekeeper_options" />
		</form>
	</div>
<?php
}

/**
 * Add gatekeeper settings page.
 *
 * @return void
 */
function cfgk_admin_menu() {
	if ( current_user_can( 'manage_options' ) ) {
		add_options_page(
			'CF Gatekeeper',
			'CF Gatekeeper',
			'activate_plugins',
			'cf-gatekeeper',
			'cfgk_settings_form'
		);
	}
}

// @TODO: decide what to do with this.
// add_action( 'admin_menu', 'cfgk_admin_menu' );
