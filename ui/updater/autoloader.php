<?php

/**
 * Updater autoloader.
 *
 * @package capsule
 */

spl_autoload_register(
	function ($class) {
		// Assume we're using namespaces (because that's how the plugin is structured).
		$namespace = explode('\\', $class);

		// If a class ends with "Trait" then prefix the filename with 'trait-', else use 'class-'.
		$class_trait = preg_match('/Trait$/', $class) ? 'trait-' : 'class-';

		// If we're not in the plugin's namespace then just return.
		if (strpos($class, 'CrowdFavorite\Capsule') === false) {
			return;
		}

		// Class name is the last part of the FQN.
		$class_name = array_pop($namespace);

		// Remove "Trait" from the class name.
		if ('trait-' === $class_trait) {
			$class_name = str_replace('Trait', '', $class_name);
		}

		$filename = $class_trait . $class_name . '.php';

		// For file naming, the namespace is everything but the class name and the root namespace.
		$namespace = trim(implode(DIRECTORY_SEPARATOR, $namespace));

		// Because WordPress file naming conventions are odd.
		$filename = strtolower(str_replace('_', '-', $filename));
		$namespace = strtolower(str_replace('_', '-', $namespace));

		// Get the path to our files.
		$directory = dirname(__FILE__);

		$directory .= DIRECTORY_SEPARATOR . 'includes';

		$file = $directory . DIRECTORY_SEPARATOR . $filename;

		if (file_exists($file)) {
			require_once $file;
		}
	},
	false
);
