<?php

function cfrutter_controller() {
	if (!empty($_GET['rutter_action'])) {
		switch ($_GET['rutter_action']) {
			case 'post_excerpt':
			case 'post_content':
// required params:
// - post_id
				if (isset($_GET['post_id'])) {
					$post_id = intval($_GET['post_id']);
					if (!empty($post_id)) {
						global $post;
						$post = get_post($post_id);
						setup_postdata($post);
						$view = str_replace('post_', '', $_GET['rutter_action']);
						ob_start();
						include(STYLESHEETPATH.'/views/'.$view.'.php');
						$html = ob_get_clean();
						$response = compact('html');
						header('Content-type: application/json');
						echo json_encode($response);
						die();
					}
				}
			break;
			case 'post_editor':
// required params:
// - post_id
				if (isset($_GET['post_id'])) {
					$post_id = intval($_GET['post_id']);
					if (!empty($post_id)) {
						global $post;
						$post = get_post($post_id);
						setup_postdata($post);
						ob_start();
						include(STYLESHEETPATH.'/views/edit.php');
						$html = ob_get_clean();
						$response = array(
							'html' => $html,
							'content' => $post->post_content
						);
						header('Content-type: application/json');
						echo json_encode($response);
						die();
					}
				}
			break;
			case 'save_post':
// required params:
// - content
// optional params:
// - post_id

// TODO

			break;
			case 'split_post':
// required params:
// - post_id
// - content
// - new_post_content

// TODO

			break;
			case 'merge_posts':
// required params:
// - post_ids (array)

// TODO

			break;
		}
	}
}
add_action('init', 'cfrutter_controller', 11);
