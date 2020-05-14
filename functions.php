<?php

/**
 * Theme functions file.
 *
 * @package capsule
 */

require_once 'config.php';
require_once 'ui/functions.php';
require_once 'inc/class-capsule-client.php';

$cap_client = new CrowdFavorite\Capsule\CapsuleClient();
$cap_client->add_actions();
