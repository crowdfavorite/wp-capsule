<?php

/**
 * Updater ajax functionality.
 *
 * @package capsule
 */

namespace CrowdFavorite\Capsule\Updater;

use CrowdFavorite\Capsule\Updater\Options as Options;

/**
 * Updater ajax class.
 */
class Ajax
{
	/**
	 * Updater Ajax instance.
	 *
	 * @access protected
	 *
	 * @var Ajax
	 */
	protected static $instance;

	/**
	 * Ajax constructor.
	 *
	 * @access public
	 */
	protected function __construct()
	{
		// License Saving/Checking.
		add_action('wp_ajax_capsule_sl_license_check', [$this, 'ajaxCheckLicense']);
		add_action('wp_ajax_capsule_sl_license_deactivate', [$this, 'ajaxDeactivateLicense']);
		add_action('wp_ajax_capsule_sl_license_save', [$this, 'ajaxSaveLicense']);
	}

	/**
	 * Method to check license.
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function ajaxCheckLicense()
	{
		$this->checkAccess();
		$this->checkLicense($license);

		// Try to check the license.
		$response = Client::getInstance()->checkLicense($license);

		$this->checkResponse($response);

		$license_data = json_decode(wp_remote_retrieve_body($response));

		// Check license data for any "errors".
		$license_error_message = Admin::getLicenseErrorMessageFromLicenseData($license_data);

		$message = '<ul>';

		if ($license_error_message) {
			$message .= sprintf(
				'<li>%s</li>',
				wp_kses_post($license_error_message)
			);
		}

		$message .= sprintf(
			'<li><strong>%s:</strong> %s</li>',
			esc_html__('License Status', 'capsule'),
			'valid' === $license_data->license
				? esc_html_x('Valid', 'Valid license', 'capsule')
				: esc_html_x('Invalid', 'Invalid license', 'capsule')
		);

		$expiration_message = Admin::getLicenseExpirationMessageFromLicenseData($license_data);

		if ($expiration_message) {
			$message .= sprintf(
				'<li>%s</li>',
				$expiration_message
			);
		}

		$message .= sprintf(
			'<li><strong>%s:</strong> %s</li>',
			esc_html__('Activations Left', 'capsule'),
			esc_html(strtoupper($license_data->activations_left))
		);

		$message .= sprintf(
			'<li><strong>%s:</strong> %s %s</li>',
			esc_html__('Active on', 'capsule'),
			number_format(absint($license_data->site_count)),
			_n('site', 'sites', absint($license_data->site_count), 'capsule')
		);

		$message .= '</ul>';

		$options = Options::get(true);

		ob_start();
		include WP_CAPSULE_UPDATER_DIR . '/views/license-buttons.php';
		wp_send_json_success(
			[
				'html' => ob_get_clean(),
				'message' => $message,
			]
		);
	}

	/**
	 * Method to deactivate a license.
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function ajaxDeactivateLicense()
	{
		$this->checkAccess();
		$this->checkLicense($license);

		// Try to check the license.
		$response = Client::getInstance()->deactivateLicense($license);

		$this->checkResponse($response);

		$options['license_status'] = '';
		$options['license'] = '';

		Options::update($options);

		ob_start();
		include WP_CAPSULE_UPDATER_DIR . '/views/license-buttons.php';
		wp_send_json_success(
			[
				'html' => ob_get_clean(),
				'message' => esc_html__('Your license is now deactivated.', 'capsule'),
			]
		);
	}

	/**
	 * Method to save license.
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function ajaxSaveLicense()
	{
		$this->checkAccess();
		$this->checkLicense($license);

		// Try to activate the license.
		$response = Client::getInstance()->activateLicense($license);

		$this->checkResponse($response);

		$license_data = json_decode(wp_remote_retrieve_body($response));

		// Check license data for any "errors".
		$license_error_message = Admin::getLicenseErrorMessageFromLicenseData($license_data);

		if ($license_error_message) {
			wp_send_json_error(new \WP_Error('capsule_sl_license_fail', $license_error_message));
		}

		$options['license_status'] = $license_data->license;
		$options['license'] = sanitize_text_field($license);

		Options::update($options);

		ob_start();
		include WP_CAPSULE_UPDATER_DIR . '/views/license-buttons.php';
		wp_send_json_success(
			[
				'html' => ob_get_clean(),
				'message' => esc_html__('Your license is now active.', 'capsule'),
			]
		);
	}

	/**
	 * Method to check access for running ajax command.
	 *
	 * @access protected
	 *
	 * @return void
	 */
	protected function checkAccess()
	{
		$nonce = filter_input(INPUT_POST, 'nonce', FILTER_DEFAULT);

		if (!wp_verify_nonce($nonce, 'save_capsule_license_options') || !current_user_can('manage_options')) {
			wp_send_json_error(
				new \WP_Error(
					'capsule_sl_license_save',
					esc_html__('Security check failed.', 'capsule')
				)
			);
		}
	}

	/**
	 * Method to check license and pass it by reference.
	 *
	 * @access protected
	 *
	 * @param mixed $license License key; passed by reference.
	 *
	 * @return void
	 */
	protected function checkLicense(&$license)
	{
		$license = trim(filter_input(INPUT_POST, 'license', FILTER_DEFAULT));

		if (!$license) {
			wp_send_json_error(
				new \WP_Error(
					'capsule_sl_invalid_license',
					esc_html__('The license field cannot be empty.', 'capsule')
				)
			);
		}
	}

	/**
	 * Method to check response.
	 *
	 * @access protected
	 *
	 * @param array|\WP_Error $response Response.
	 *
	 * @return void
	 */
	protected function checkResponse($response)
	{
		if (is_wp_error($response)) {
			wp_send_json_error($response);
		}

		if (200 !== wp_remote_retrieve_response_code($response)) {
			wp_send_json_error(
				new \WP_Error(
					'capsule_sl_invalid_code',
					esc_html__('We could not communicate with the update server. Please try again later.', 'capsule')
				)
			);
		}
	}

	/**
	 * Method to get instance of Ajax.
	 *
	 * @access public
	 * @static
	 *
	 * @return Ajax
	 */
	public static function getInstance()
	{
		if (!self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
