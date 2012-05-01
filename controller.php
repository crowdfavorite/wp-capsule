<?php

function cfrutter_controller() {
	if (!empty($_GET['rutter_action'])) {
		switch ($_GET['rutter_action']) {
			case 'post_content':
				if (isset($_GET['post_id'])) {
					$post_id = intval($_GET['post_id']);
					if (!empty($post_id)) {
						global $post;
						$post = get_post($post_id);
						setup_postdata($post);
						ob_start();
						include(STYLESHEETPATH.'/views/content.php');
						$html = ob_get_clean();
						$response = compact('html');
						header('Content-type: application/json');
						echo json_encode($response);
						die();
					}
				}
			break;
		}
	}
}
add_action('init', 'cfrutter_controller', 11);
