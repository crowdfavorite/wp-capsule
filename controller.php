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
// - post_id (if prefixed with "new-", this is for a new, unsaved post
				if (isset($_GET['post_id'])) {
					$post_id = stripslashes($_GET['post_id']);
					if (substr($post_id, 0, 4) == 'new-') {
						ob_start();
						include(STYLESHEETPATH.'/views/new.php');
						$html = ob_get_clean();
						$response = array(
							'html' => $html,
							'content' => ''
						);
					}
					else {
						$post_id = intval($post_id);
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
						}
					}
					if (isset($response)) {
						header('Content-type: application/json');
						echo json_encode($response);
					}
					die();
				}
			break;
		}
	}
	if (!empty($_POST['rutter_action'])) {
		switch ($_POST['rutter_action']) {
			case 'save_post':
// required params:
// - content
// optional params:
// - post_id

// TODO - check post editing permissions

// TODO - parse content for taxonomies

				$post_id = (!empty($_POST['post_id']) ? stripslashes($_POST['post_id']) : 'new-');
				if (substr($post_id, 0, 4) == 'new-') {
					// create a new post

// TODO - set title as @Project Name + #tags

					$post_id = wp_insert_post(array(
						'post_title' => time(),
						'post_status' => 'publish',
						'post_content' => $_POST['content']
					), true);
					if (is_wp_error($post_id)) {
						$result = 'error';
						$msg = $post_id->get_error_message();
					}
					else {
						$result = 'success';
						$msg = __('Post created.', 'rutter');
					}
				}
				else {
					// update an existing post

// TODO

				}
				if ($result == 'success') {

// TODO - update taxonomies

				}
				$response = compact('post_id', 'result', 'msg');
				header('Content-type: application/json');
				echo json_encode($response);
				die();

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
