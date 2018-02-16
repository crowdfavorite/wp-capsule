<?php
/**
 * Implementation of custom site settings.
 *
 * @package cf-gatekeeper
 */

/**
 * CF User API class.
 */
class CF_User_API {

	/**
	 * Generate key.
	 *
	 * @access public
	 * @static
	 *
	 * @param int $user_id User id.
	 *
	 * @return string
	 */
	public static function generate_key( $user_id ) {
		$data = $user_id . AUTH_KEY;

		if ( function_exists( 'hash' ) ) {
			return hash( 'md5', $data );
		}

		return md5( $data );
	}

	/**
	 * Add key to user.
	 *
	 * @access public
	 * @static
	 *
	 * @param int         $user_id User ID.
	 * @param null|string $key     Null to automatically generate a key or a key string.
	 *
	 * @return void
	 */
	public static function add_key_to_user( $user_id, $key = null ) {
		if ( is_null( $key ) ) {
			$key = self::generate_key( $user_id );
		}

		update_user_meta( $user_id, 'cf_user_key', $key );
	}

	/**
	 * Add keys to users with no keys.
	 *
	 * @access public
	 * @static
	 *
	 * @return void
	 */
	public static function process_users() {
		// Create the query for retrieving the IDs of the users which have no keys.
		$query = new WP_User_Query( array(
			'fields'     => 'ID',
			'meta_query' => array(
				/*
				Set relation to 'AND' as a workaround for https://core.trac.wordpress.org/ticket/23849,
				which would cause the WP_User_Query to retrieve the wrong results.
				*/
				'relation' => 'AND',
				array(
					'compare' => 'NOT EXISTS',
					'key'     => 'cf_user_key',
				),
			),
		) );

		$users_ids = $query->get_results();

		if ( ! empty( $users_ids ) && is_array( $users_ids ) ) {
			foreach ( $users_ids as $user_id ) {
				self::add_key_to_user( $user_id );
			}
		}
	}

	/**
	 * Handle key login.
	 *
	 * @access public
	 * @static
	 *
	 * @return bool
	 */
	public static function key_login() {
		$cf_user_key = filter_input( INPUT_GET, 'cf_user_key' );

		if ( ! empty( $cf_user_key ) ) {
			global $wpdb;

			$user_id = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT user_id
				FROM $wpdb->usermeta
				WHERE meta_key = 'cf_user_key' AND meta_value = %s",
				$cf_user_key
			) );

			if ( $user_id > 0 ) {
				// @TODO: this will not automatically log in the user. Fix this to log in the user if that is intended functionality.
				wp_set_current_user( $user_id );
				return true;
			}
		}

		return false;
	}
}
