<?php

/**
 * Updater Client class.
 *
 * @package capsule
 */

namespace CrowdFavorite\Capsule\Updater;

use CrowdFavorite\Capsule\Updater\Options as Options;

/**
 * Updater Client class.
 */
class Client
{
	/**
	 * Response key.
	 */
	const RESPONSE_KEY = WP_CAPSULE_THEME_SLUG . '-' . WP_CAPSULE_UPDATER_USE_BETA . '-update-response'; // phpcs:ignore

	/**
	 * Updater Client instance.
	 *
	 * @access protected
	 *
	 * @var Client
	 */
	protected static $instance;

	/**
	 * Updater Client constructor.
	 *
	 * @access protected
	 */
	protected function __construct()
	{
	}

	/**
	 * Method to activate license.
	 *
	 * @access public
	 *
	 * @param string $license License key.
	 *
	 * @return array|\WP_Error Returns response array or WP_Error.
	 */
	public function activateLicense($license)
	{
		// Data to send in our API request.
		$api_params = [
			'edd_action' => 'activate_license',
			'item_id' => WP_CAPSULE_UPDATER_ITEM_ID,
			'item_name' => rawurlencode(WP_CAPSULE_THEME_NAME),
			'license' => $license,
			'url' => home_url(),
		];

		return $this->getApiResponse($api_params);
	}

	/**
	 * Call the EDD SL API to get the latest version information.
	 *
	 * @access public
	 *
	 * @return array|bool If an update is available, returns the update parameters,
	 *                    if no update is needed returns false, if the request fails returns false.
	 */
	public function checkForUpdate()
	{
		$update_data = get_transient(self::RESPONSE_KEY);

		if (false === $update_data) {
			$failed = false;

			$options = Options::get();

			$api_params = [
				'author' => WP_CAPSULE_AUTHOR,
				'beta' => WP_CAPSULE_UPDATER_USE_BETA,
				'edd_action' => 'get_version',
				'item_id' => WP_CAPSULE_UPDATER_ITEM_ID,
				'license' => $options['license'],
				'name' => WP_CAPSULE_THEME_NAME,
				'slug' => WP_CAPSULE_THEME_SLUG,
				'version' => WP_CAPSULE_THEME_VERSION,
			];

			$response = $this->getApiResponse($api_params);

			// Make sure the response was successful
			if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
				$failed = true;
			}

			$update_data = json_decode(wp_remote_retrieve_body($response));

			if (!is_object($update_data)) {
				$failed = true;
			}

			// If the response failed, try again in 30 minutes.
			if ($failed) {
				$data = new \stdClass();
				$data->new_version = WP_CAPSULE_THEME_VERSION;
				set_transient(self::RESPONSE_KEY, $data, strtotime('+30 minutes', time()));
				return false;
			}

			// If the status is 'ok', return the update arguments.
			if (!$failed) {
				$sections = maybe_unserialize($update_data->sections);

				if ($sections && is_array($sections)) {
					foreach ($sections as $i => $section) {
						$section_aux = wp_strip_all_tags($section);

						// Check if section is actually a URL.
						if (wp_http_validate_url($section_aux)) {
							$section_response = wp_safe_remote_get($section_aux);

							if (
								is_wp_error($section_response)
								|| 200 !== wp_remote_retrieve_response_code($section_response)
							) {
								// Remove section if an error has occurred.
								unset($sections[$i]);
							} else {
								// Get response body and sanitize it.
								$sections[$i] = wp_kses_post(wp_remote_retrieve_body($section_response));
							}
						} else {
							// Section is regular text.
							$sections[$i] = wp_kses_post($section);
						}
					}
				}

				$update_data->sections = $sections;
				set_transient(self::RESPONSE_KEY, $update_data, strtotime('+12 hours', time()));
			}
		}

		if (version_compare(WP_CAPSULE_THEME_VERSION, $update_data->new_version, '>=')) {
			return false;
		}

		return (array)$update_data;
	}

	/**
	 * Checks if license is valid and gets expire date.
	 *
	 * @access public
	 *
	 * @param string $license License key.
	 *
	 * @return array|\WP_Error Returns response array or WP_Error.
	 */
	public function checkLicense($license)
	{
		$api_params = [
			'edd_action' => 'check_license',
			'item_id' => WP_CAPSULE_UPDATER_ITEM_ID,
			'item_name' => WP_CAPSULE_THEME_NAME,
			'license' => $license,
			'url' => home_url(),
		];

		return $this->getApiResponse($api_params);
	}

	/**
	 * Method to deactivate license.
	 *
	 * @access public
	 *
	 * @param string $license License key.
	 *
	 * @return array|\WP_Error Returns response array or WP_Error.
	 */
	public function deactivateLicense($license)
	{
		// Data to send in our API request.
		$api_params = [
			'edd_action' => 'deactivate_license',
			'item_id' => WP_CAPSULE_UPDATER_ITEM_ID,
			'item_name' => rawurlencode(WP_CAPSULE_THEME_NAME),
			'license' => $license,
			'url' => home_url(),
		];

		return $this->getApiResponse($api_params);
	}

	/**
	 * Method to remove the update data for the theme.
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function deleteThemeUpdateTransient()
	{
		delete_transient(self::RESPONSE_KEY);
	}

	/**
	 * Makes a call to the API.
	 *
	 * @access public
	 *
	 * @param array $api_params to be used for wp_remote_get.
	 *
	 * @return array|\WP_Error $response Response array or WP_Error.
	 */
	public function getApiResponse($api_params)
	{
		// Call the custom API.
		$verify_ssl = (bool)apply_filters('capsule_sl_api_request_verify_ssl', true);

		$response = wp_safe_remote_post(
			WP_CAPSULE_UPDATER_REMOTE_API_URL,
			[
				'body' => $api_params,
				'sslverify' => $verify_ssl,
				'timeout' => 15,
			]
		);

		return $response;
	}

	/**
	 * Method to get instance of Client.
	 *
	 * @access public
	 * @static
	 *
	 * @return Client
	 */
	public static function getInstance()
	{
		if (!self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Method to get the update data for the theme.
	 *
	 * @access public
	 *
	 * @return mixed
	 */
	public function getThemeUpdateTransient()
	{
		return get_transient(self::RESPONSE_KEY);
	}

	/**
	 * Method to register update checks.
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function registerUpdateChecks()
	{
		add_action('load-themes.php', [$this, 'deleteThemeUpdateTransient']);
		add_action('load-update-core.php', [$this, 'deleteThemeUpdateTransient']);

		add_filter('delete_site_transient_update_themes', [$this, 'deleteThemeUpdateTransient']);
		add_filter('site_transient_update_themes', [$this, 'setThemeUpdateTransient']);
	}

	/**
	 * Update the theme update transient with the response from the version check.
	 *
	 * @access public
	 *
	 * @param array $value The default update values.
	 *
	 * @return array|bool If an update is available, returns the update parameters,
	 * 					  if no update is needed returns false, if the request fails returns false.
	 */
	public function setThemeUpdateTransient($value)
	{
		$update_data = $this->checkForUpdate();

		if ($update_data) {
			// Make sure the theme property is set. See issue 1463 on Github in the Software Licensing Repo.
			$update_data['theme'] = WP_CAPSULE_THEME_SLUG;

			$value->response[WP_CAPSULE_THEME_SLUG] = $update_data;
		}

		return $value;
	}
}
