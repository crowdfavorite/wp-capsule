<?php

/**
 * Options functionality for the plugin.
 *
 * @package capsule
 */

namespace CrowdFavorite\Capsule\Updater;

/**
 * Options class.
 */
class Options
{
	/**
	 * Store the options.
	 *
	 * @access private
	 * @static
	 *
	 * @var array $options Options array.
	 */
	private static $options;

	/**
	 * Get license options
	 *
	 * @access public
	 * @static
	 *
	 * @param bool $force_reload Whether to skip caching and get options from the database.
	 *
	 * @return array Options.
	 */
	public static function get($force_reload = false)
	{
		// Try to get cached options.
		$options = self::$options;

		if (!$options || true === $force_reload) {
			$options = get_site_option('capsule_license_options', []);
		}

		// Store options.
		if (!is_array($options)) {
			$options = [];
		}

		$defaults = [
			'license' => false,
			'license_status' => '',
		];

		/**
		 * Filter for option defaults.
		 *
		 * @param array $defaults Option defaults.
		 */
		$defaults = apply_filters('capsule_license_options_defaults', $defaults);

		if (empty($options) || count($options) < count($defaults)) {
			$options = wp_parse_args(
				$options,
				$defaults
			);
		}

		self::$options = $options;

		/**
		 * Filter for overall options.
		 *
		 * @param array $options Options array.
		 */
		$options = apply_filters('capsule_license_options', $options);

		return $options;
	}

	/**
	 * Save license options.
	 *
	 * @param array $options Options array.
	 *
	 * @return array Options.
	 */
	public static function update($options = [])
	{
		$saved_options = self::get(true);
		$options = array_merge($saved_options, $options);

		/**
		 * Filter for saving options.
		 *
		 * @param array $options Options array.
		 */
		$options = apply_filters('capsule_license_options_save_pre', $options);

		update_site_option('capsule_license_options', $options);

		self::$options = $options;

		return $options;
	}
}
