<?php

/**
 * Theme updater admin page and functions.
 *
 * @package capsule
 */

namespace CrowdFavorite\Capsule\Updater;

use CrowdFavorite\Capsule\Updater\Options as Options;

/**
 * Class to implement updater admin page.
 */
class Admin
{
	/**
	 * License page hook.
	 *
	 * @access protected
	 *
	 * @var string
	 *
	 * @see Admin::registerLicensePage()
	 */
	protected $licensePageHook;

	/**
	 * Updater Admin instance.
	 *
	 * @access protected
	 *
	 * @var Admin
	 */
	protected static $instance;

	/**
	 * Admin constructor.
	 *
	 * @access protected
	 */
	protected function __construct()
	{
		add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
		add_action('admin_menu', [$this, 'registerLicensePage']);
		add_filter('http_request_args', [$this, 'disableWPorgRequest'], 5, 2);
		add_action('load-themes.php', [$this, 'loadThemesScreen']);
	}

	/**
	 * Disable requests to wp.org repository for this theme.
	 *
	 * @access public
	 *
	 * @param array $r An array of HTTP request arguments.
	 * @param string $url The request URL.
	 *
	 * @return array
	 */
	public function disableWPorgRequest($r, $url)
	{
		// If it's not a theme update request, bail.
		if (0 !== strpos($url, 'https://api.wordpress.org/themes/update-check/1.1/')) {
			return $r;
		}

		// Decode the JSON response.
		$themes = json_decode($r['body']['themes']);

		// Remove the active parent and child themes from the check.
		$parent = get_option('template');
		$child = get_option('stylesheet');
		unset($themes->themes->$parent);
		unset($themes->themes->$child);

		// Encode the updated JSON response
		$r['body']['themes'] = wp_json_encode($themes);

		return $r;
	}

	/**
	 * Method to enqueue assets.
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function enqueueAssets()
	{
		$screen = get_current_screen();
		// @TODO note this has not been tested in Multisite.
		if (empty($screen->base) || $this->licensePageHook !== $screen->base) {
			return;
		}

		wp_enqueue_style(
			'capsule-sl-admin',
			WP_CAPSULE_UPDATER_URI . '/dist/admin.css',
			[],
			WP_CAPSULE_THEME_VERSION,
			'all'
		);

		wp_register_script(
			'jquery.block.ui',
			WP_CAPSULE_UPDATER_URI . '/assets/js/block.ui.js',
			['jquery'],
			'2.70.0-2014.11.23',
			true
		);

		wp_enqueue_script(
			'capsule-sl-admin',
			WP_CAPSULE_UPDATER_URI . '/dist/admin.js',
			['jquery', 'jquery.block.ui', 'wp-i18n'],
			WP_CAPSULE_THEME_VERSION,
			true
		);

		wp_localize_script(
			'capsule-sl-admin',
			'capsule_sl_admin',
			array_merge(
				Options::get(),
				[
					'admin_url' => admin_url('admin.php'),
					'loading'   => WP_CAPSULE_UPDATER_URI . '/assets/img/loading.svg',
				]
			)
		);
	}

	/**
	 * Method to get instance of Admin.
	 *
	 * @access public
	 * @static
	 *
	 * @return Admin
	 */
	public static function getInstance()
	{
		if (!self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Method to get license info from license data object.
	 *
	 * @access public
	 * @static
	 *
	 * @param object $license_data License data object.
	 *
	 * @return string
	 */
	public static function getLicenseErrorMessageFromLicenseData($license_data)
	{
		if (!empty($license_data->success)) {
			return '';
		}

		$options['license_status'] = '';
		$error = empty($license_data->error) ? null : $license_data->error;

		switch ($error) {
			case 'expired':
				$license_message = sprintf(
					/* Translators: %s is a date format placeholder */
					__('Your license key expired on %s.', 'capsule'),
					date_i18n(
						get_option('date_format'),
						strtotime($license_data->expires, current_time('timestamp'))
					)
				);
				break;

			case 'disabled':
			case 'revoked':
				$license_message = __('Your license key has been disabled.', 'capsule');
				break;

			case 'missing':
				$license_message = __('Invalid license.', 'capsule');
				break;

			case 'invalid':
			case 'site_inactive':
				$license_message = __('Your license is not active for this URL.', 'capsule');
				break;

			case 'item_name_mismatch':
				$license_message = sprintf(
					/* Translators: %s is the plugin name */
					__('This appears to be an invalid license key for %s.', 'capsule'),
					WP_CAPSULE_THEME_NAME
				);
				break;

			case 'no_activations_left':
				$license_message = __('Your license key has reached its activation limit.', 'capsule');
				break;
			default:
				$license_message = __('An error occurred, please try again.', 'capsule');
				break;
		}

		return $license_message;
	}

	/**
	 * Method to get license expiration message from license data object.
	 *
	 * @access public
	 * @static
	 *
	 * @param object $license_data License data object.
	 *
	 * @return string
	 */
	public static function getLicenseExpirationMessageFromLicenseData($license_data)
	{
		$expires = false;

		// Get expiry date.
		if (isset($license_data->expires) && 'lifetime' !== $license_data->expires) {
			$expires = date_i18n(
				get_option('date_format'),
				strtotime($license_data->expires, current_time('timestamp'))
			);
		} elseif (isset($license_data->expires) && 'lifetime' === $license_data->expires) {
			$expires = 'lifetime';
		}

		if ('expired' === $license_data->license) {
			if ($expires) {
				$message = sprintf(
					esc_html__('License key expired %s.', 'capsule'),
					$expires
				);
			} else {
				$message = esc_html__('License key has expired.', 'capsule');
			}

			$renew_link = sprintf(
				'<a href="%1$s" target="_blank">%2$s</a>',
				esc_url(self::getRenewalLink()),
				esc_html__('Renew?', 'capsule')
			);

			$message .= ' ' . $renew_link;
		} elseif ($expires && 'lifetime' !== $expires) {
			$message = sprintf(
				'<strong>%1$s:</strong> %2$s',
				esc_html__('Expires', 'capsule'),
				$expires
			);
		} elseif ($expires && 'lifetime' === $expires) {
			$message = esc_html__('Lifetime License.', 'capsule');
		} else {
			$message = '';
		}

		return $message;
	}

	/**
	 * Method to get renewal link.
	 *
	 * @access public
	 * @static
	 *
	 * @return string
	 */
	public static function getRenewalLink()
	{
		// If a renewal link was passed in the config, use that.
		if (WP_CAPSULE_UPDATER_RENEWAL_URL !== '') {
			return WP_CAPSULE_UPDATER_RENEWAL_URL;
		}

		$options = Options::get();

		// If download_id was passed in the config, a renewal link can be constructed.
		if (WP_CAPSULE_UPDATER_DOWNLOAD_ID !== '' && $options['license']) {
			return sprintf(
				'%1$s/checkout/?edd_license_key=%2$s&download_id=%3$s',
				WP_CAPSULE_UPDATER_REMOTE_API_URL,
				$options['license'],
				WP_CAPSULE_UPDATER_DOWNLOAD_ID
			);
		}

		return WP_CAPSULE_UPDATER_REMOTE_API_URL;
	}

	/**
	 * Method to load ThickBox and register the update notification hook.
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function loadThemesScreen()
	{
		add_thickbox();
		add_action('admin_notices', [$this, 'renderUpdateNag']);
	}

	/**
	 * Method to register license page under the appearance menu.
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function registerLicensePage()
	{
		$this->licensePageHook = add_submenu_page(
			'capsule',
			__('License', 'capsule'),
			__('License', 'capsule'),
			'manage_options',
			'capsule-license',
			[$this, 'renderLicensePage']
		);
	}

	/**
	 * Method to render license page.
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function renderLicensePage()
	{
		$options = Options::get();
		?>
		<div class="wrap">
			<h2><?php esc_html_e('Capsule License', 'capsule'); ?></h2>
			<form action="" method="POST">
				<?php wp_nonce_field('save_capsule_license_options', '_capsule_sl'); ?>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="edd-license">
									<?php esc_html_e('Enter Your License', 'capsule'); ?>
								</label>
							</th>
							<td>
								<input
									id="edd-license"
									class="regular-text"
									type="password"
									value="<?php echo esc_attr($options['license']); ?>" name="options[license]"
								>
								<br />
								<div class="capsule-sl-field capsule-sl-field--checkbox">
									<label for="capsule-sl-field-license-reveal">
										<input type="checkbox" id="capsule-sl-field-license-reveal" value="0" />
										<?php esc_html_e('Reveal license', 'capsule'); ?>
									</label>
								</div>
								<?php include WP_CAPSULE_UPDATER_DIR . '/views/license-buttons.php'; ?>
								<div class="capsule-sl-field capsule-sl-status capsule-sl-success license-status"
									style="display: none;"></div>
							</td>
						</tr>
					</tbody>
				</table>
			</form>
		<?php
	}

	/**
	 * Method to render the update notifications.
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function renderUpdateNag()
	{
		$api_response = Client::getInstance()->getThemeUpdateTransient();

		if (false === $api_response || version_compare(WP_CAPSULE_THEME_VERSION, $api_response->new_version, '>=')) {
			return;
		}

		$theme = wp_get_theme(WP_CAPSULE_THEME_SLUG);

		$update_url = wp_nonce_url(
			'update.php?action=upgrade-theme&amp;theme=' . urlencode(WP_CAPSULE_THEME_SLUG),
			'upgrade-theme_' . WP_CAPSULE_THEME_SLUG
		);

		$update_notice = __(
			"Updating this theme will lose any customizations you have made. 'Cancel' to stop, 'OK' to update.",
			'capsule'
		);

		$update_onclick = ' onclick="if(confirm(\'' . esc_js($update_notice) . '\')){return true;}return false;"';

		// phpcs:disable Generic.Files.LineLength.TooLong
		$update_available = __(
			// Translators: %1$s - theme name, %2$s - theme version, %3$s - changelog location, %4s - theme name, %5$s - update url, %6$s - update confirmation JS.
			'<strong>%1$s %2$s</strong> is available. <a href="%3$s" class="thickbox" title="%4s">Check out what\'s new</a> or <a href="%5$s"%6$s>update now</a>.',
			'capsule'
		);
		// phpcs:enable Generic.Files.LineLength.TooLong

		echo '<div id="update-nag">';
		printf(
			$update_available, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html($theme->get('Name')),
			esc_html($api_response->new_version),
			esc_url_raw('#TB_inline?width=640&amp;inlineId=' . WP_CAPSULE_THEME_SLUG . '_changelog'),
			esc_attr($theme->get('Name')),
			esc_url_raw($update_url),
			$update_onclick // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
		echo '</div>';

		echo '<div id="' . esc_attr(WP_CAPSULE_THEME_SLUG) . '_' . 'changelog" style="display:none;">';
		echo wp_kses_post(
			wpautop($api_response->sections['changelog'])
		);
		echo '</div>';
	}
}
