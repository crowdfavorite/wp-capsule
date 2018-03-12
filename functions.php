<?php
/**
 * Theme functions file.
 *
 * @package capsule
 */

require_once 'ui/functions.php';
require_once 'inc/class-capsule-client.php';

$cap_client = new Capsule_Client();
$cap_client->add_actions();
