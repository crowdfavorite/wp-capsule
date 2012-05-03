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
					}
					die();
				}
			break;
		}
	}
	if (!empty($_POST['rutter_action'])) {
		if (!current_user_can('edit_posts')) {
			die();
		}
		switch ($_POST['rutter_action']) {
			case 'create_post':
				$post_id = wp_insert_post(array(
					'post_title' => time(),
					'post_status' => 'draft',
					'post_content' => ''
				), true);
				if (is_wp_error($post_id)) {
					$result = 'error';
					$msg = $post_id->get_error_message();
					$response = compact('result', 'msg');
				}
				else {
					$result = 'success';
					$msg = __('Post created.', 'rutter');
					$post = get_post($post_id);
					setup_postdata($post);
					ob_start();
					include(STYLESHEETPATH.'/views/edit.php');
					$html = ob_get_clean();
					$ymd = get_the_time('Ymd', $post);
					$response = array(
						'post_id' => $post_id,
						'result' => $result,
						'msg' => $msg,
						'html' => $html,
						'content' => $post->post_content,
					);
				}
				header('Content-type: application/json');
				echo json_encode($response);
				die();
			break;
			case 'update_post':
// required params:
// - content
// - post_id

// TODO - parse content for taxonomies
// TODO - set title as @Project Name + #tags

				$post_id = intval($_POST['post_id']);
				$update = wp_update_post(array(
					'ID' => $post_id,
					'post_content' => stripslashes($_POST['content']),
					'post_status' => 'publish',
				));
				if ($update) {

// TODO - update taxonomies

					$result = 'success';
					$msg = 'Post saved.';
				}
				else {
					$result = 'error';
					$msg = 'Saving post #'.$post_id.' failed.';
				}
				$response = compact('post_id', 'result', 'msg');
				header('Content-type: application/json');
				echo json_encode($response);
				die();
			break;
			case 'delete_post':

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
