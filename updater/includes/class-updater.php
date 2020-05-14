<?php

/**
 * Capsule licensing implementation.
 *
 * @package capsule
 */

namespace CrowdFavorite\Capsule\Updater;

use CrowdFavorite\Capsule\Updater\Ajax as Ajax;
use CrowdFavorite\Capsule\Updater\Client as Client;
use CrowdFavorite\Capsule\Updater\Options as Options;

/**
 * Capsule licensing class.
 */
class Updater
{
	/**
	 * Licensing instance.
	 *
	 * @access protected
	 * @static
	 *
	 * @var Updater
	 */
	protected static $instance;

	/**
	 * Updater constructor.
	 *
	 * @access protected
	 */
	protected function __construct()
	{
		add_action('after_setup_theme', __NAMESPACE__ . '\\Admin::getInstance');
		add_action('init', [$this, 'registerUpdateChecks']);

		Ajax::getInstance();
	}

	/**
	 * Method to get instance of Updater.
	 *
	 * @access public
	 * @static
	 *
	 * @return Updater
	 */
	public static function getInstance()
	{
		if (!self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
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
		if (!current_user_can('manage_options')) {
			return;
		}

		$options = Options::get();

		// If there is no valid license key status, don't allow updates.
		if ('valid' === $options['license_status']) {
			Client::getInstance()->registerUpdateChecks();
		}
	}
}
