<?php
/*
Plugin Name: CF Gatekeeper
Description: Redirect to login page if the user is not logged in.
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
Version: 1.8.3-dev
*/

define( 'CF_GATEKEEPER', true );
define( 'CFGK_VER', '1.8.3' );

/* Load localization library */
load_plugin_textdomain( 'cf_gatekeeper' );

function cf_gatekeeper() {
	global $current_user;
	if ( ! isset( $current_user ) || empty( $current_user->ID ) ) {
		global $cf_user_api;
		$cf_user_api->key_login();
	}
	$user_capability = apply_filters( 'cf_gatekeeper_capability', 'read' );
	$gatekeeper_enabled = apply_filters( 'cf_gatekeeper_enabled', true );
	if ( ! current_user_can( $user_capability ) && $gatekeeper_enabled ) {
		$login_page = site_url( 'wp-login.php' );
		$network_login_page = network_site_url( 'wp-login.php' );
		is_ssl() ? $proto = 'https://' : $proto = 'http://';
		$requested = $proto.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		if ( substr( $requested, 0, strlen( $login_page ) ) != $login_page ) {
			if ( $login_page == $network_login_page || substr( $requested, 0, strlen( $network_login_page ) ) != $network_login_page ) {
				auth_redirect();
			}
		}
	}
}
if ( ! defined( 'XMLRPC_REQUEST' ) ) {
	// This needs to run at 11+ as to run after cfgk_process_users
	// And to catch any filters running at default priority 10
	add_action( 'init', 'cf_gatekeeper', 12 );
}

class cf_user_api {
	function cf_user_api() {
	}

	function generate_key( $user_id ) {
		return md5( $user_id.AUTH_KEY );
	}

	function add_key_to_user( $user_id, $key = null ) {
		if ( is_null( $key ) ) {
			$key = $this->generate_key( $user_id );
		}
		update_user_meta( $user_id, 'cf_user_key', $key );
	}

	function process_users() {
		global $wpdb;
		$keyed_users = $wpdb->get_results( "
			SELECT user_id
			FROM $wpdb->usermeta
			WHERE meta_key = 'cf_user_key'
		" );

		$user_ids = array();
		foreach ( $keyed_users as $user_id ) {
			if ( is_object( $user_id ) ) {
				$user_ids[] = $user_id->user_id;
			}
			else if ( is_int( $user_id ) ) {
					$user_ids[] = $user_id;
				}
			else {
				return;
			}
		}
		if ( is_array( $user_ids ) && count( $user_ids ) > 0 ) {
			$where = ' WHERE ID NOT IN ('.implode( ', ', $user_ids ).') ';
		}
		else {
			$where = ' ';
		}
		$users = $wpdb->get_results( "
			SELECT *
			FROM $wpdb->users
			$where
		" );

		if ( count( $users ) ) {
			foreach ( $users as $user ) {
				$this->add_key_to_user( $user->ID );
			}
		}
	}

	function key_login() {
		if ( ! empty( $_GET['cf_user_key'] ) ) {
			global $wpdb;
			$user_id = $wpdb->get_var( "
				SELECT user_id
				FROM $wpdb->usermeta
				WHERE meta_key = 'cf_user_key'
				AND meta_value = '".$wpdb->escape( stripslashes( $_GET['cf_user_key'] ) )."'
			" );
			$user_id = intval( $user_id );
			if ( $user_id > 0 ) {
				wp_set_current_user( $user_id );
				return true;
			}
		}
		return false;
	}
}

function cfgk_process_users() {
	global $cf_user_api;

	/* Make sure we have an object to deal with.  This was throwing
	Fatal Errors on plugin activation without the check. */
	if ( ! is_object( $cf_user_api ) ) {
		$cf_user_api = new cf_user_api();
	}
	$cf_user_api->process_users();

	/* Don't turn on by default */
	update_option( 'cfgk_enabled', '0' );
}
/* Do inital assignment of cf_user_key's */
add_action( 'init', 'cfgk_process_users' );


function cfgk_add_key_to_user( $user_id, $unused = null ) {
	global $cf_user_api;
	$cf_user_api->add_key_to_user( $user_id );
}

$cf_user_api = new cf_user_api();

add_action( 'user_register', 'cfgk_add_key_to_user' );
add_action( 'profile_update', 'cfgk_add_key_to_user' );

function cfgk_user_api_feeds( $url ) {
	global $userdata;
	if ( ! empty( $userdata->ID ) ) {
		$key = get_user_meta( $userdata->ID, 'cf_user_key', true );
		if ( ! empty( $key ) ) {
			if ( strpos( $url, '?' ) !== false ) {
				$url .= '&amp;cf_user_key='.urlencode( $key );
			}
			else {
				$url .= '?cf_user_key='.urlencode( $key );
			}
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

function cfgk_show_api_key() {
	global $profileuser;
	$key = get_user_meta( $profileuser->ID, 'cf_user_key', true );
?>
<table class="form-table">
<tr>
	<th><label for="description"><?php echo sprintf( __( '%s API Key', 'cf_gatekeeper' ), 'Gatekeeper' ); ?></label></th>
	<td><span><?php echo esc_html( $key ); ?></span></td>
</tr>
</table>
<?php
}
add_action( 'show_user_profile', 'cfgk_show_api_key' );
add_action( 'edit_user_profile', 'cfgk_show_api_key' );

function cfgk_request_handler() {
	if ( isset( $_POST['cf_action'] ) ) {
		switch ( $_POST['cf_action'] ) {
		case 'save_gatekeeper_options':
			if ( update_option( 'cfgk_enabled', $_POST['cfgk_enable_gatekeeper'] ) ) {
				if ( get_option( 'cfgk_enabled' ) ) {
					do_action( 'cfgk_enabled' );
					$message_id = 1;
				}
				else {
					do_action( 'cfgk_disabled' );
					$message_id = 2;
				}

				$query_args = array(
					'page' => $_GET['page'],
					'updated' => true,
					'message' => $message_id
				);
				/* Redirect properly, with a message id */
				wp_redirect( basename( $_SERVER['SCRIPT_NAME'] ).'?page='.$_GET['page'].'&updated=true&message='.$message_id );
				exit;
			}
			/* Nothing updated */
			wp_redirect( basename( $_SERVER['SCRIPT_NAME'] ).'?page='.$_GET['page'] );
			exit;
			break;
		default:
			break;
		}
	}
}
add_action( 'init', 'cfgk_request_handler' );

function cfgk_settings_form() {
	$option_value = get_option( 'cfgk_enabled' );

	$enabled_options = array(
		__( 'Yes', 'cf_gatekeeper' ) => '1',
		__( 'No', 'cf_gatekeeper' ) => '0'
	);

	$radio_inputs_html = '';
	foreach ( $enabled_options as $label => $value ) {
		if ( $option_value == $value ) {
			$selected = ' checked="checked"';
		}
		else {
			$selected = '';
		}
		$radio_inputs_html .= '
			<input type="radio" name="cfgk_enable_gatekeeper" id="cfgk_enable_gatekeeper_'.attribute_escape( strtolower( $label ) ).'" value="'.attribute_escape( $value ).'"'.$selected.' /> <label for="cfgk_enable_gatekeeper_'.attribute_escape( strtolower( $label ) ).'">'.attribute_escape( $label ).'</label>
		';
	}
?>
	<div class="wrap">
		<h2>CF Gatekeeper</h2>
		<?php do_action( 'cfgk_settings_form_notices', $_GET['message'] ); ?>
		<form method="post">
			<div>
			<label for="cfgk_enable_gatekeeper"><?php _e( 'Enable Gatekeeper?', 'cf_gatekeeper' ); ?></label>
			<?php echo $radio_inputs_html; ?>
			</div>
			<button type="submit" class="button-primary"><?php _e( 'Save Option', 'cf_gatekeeper' ); ?></button>
			<input type="hidden" name="cf_action" value="save_gatekeeper_options" />
		</form>
	</div>
<?php
}

/**
 * Removing for this version
 *
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
/* add_action('admin_menu', 'cfgk_admin_menu'); */
