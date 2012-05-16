<?php

function capsule_controller() {
	if (!empty($_GET['capsule_action'])) {
		if (!current_user_can('edit_posts')) {
			capsule_unauthorized_json();
		}
		switch ($_GET['capsule_action']) {
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
						$view = str_replace('post_', '', $_GET['capsule_action']);
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
	if (!empty($_POST['capsule_action'])) {
		if (!current_user_can('edit_posts')) {
			capsule_unauthorized_json();
		}
		switch ($_POST['capsule_action']) {
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
					$msg = __('Post created.', 'capsule');
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
				$post_id = intval($_POST['post_id']);
				if (!$post_id) {
					die();
				}
				$post_title = '';
				$taxonomies = array(
					'projects' => array(),
					'post_tag' => array(),
					'code' => array(),
				);
				foreach ($taxonomies as $tax => $terms) {
					$terms = json_decode(stripslashes($_POST[$tax]));
					$taxonomies[$tax] = $terms;
					$post_title .= ' '.implode(' ', $terms);
				}
				$update = wp_update_post(array(
					'ID' => $post_id,
					'post_title' => trim($post_title),
					'post_content' => stripslashes($_POST['content']),
					'post_status' => 'publish',
				));
				if ($update) {
					foreach ($taxonomies as $tax => $terms) {
						wp_set_post_terms($post_id, $terms, $tax);
					}
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
// required params:
// - post_id
				$post_id = intval($_POST['post_id']);
				$delete = wp_delete_post($post_id);
				if ($delete != false) {
					$post = get_post($post_id);
					setup_postdata($post);
					$result = 'success';
					$msg = __('Post deleted', 'capsule');
					ob_start();
					include(STYLESHEETPATH.'/views/deleted.php');
					$html = ob_get_clean();
				}
				else {
					$result = 'error';
					$msg = __('Post not deleted, please try again.', 'capsule');
					$html = '';
				}
				$response = compact('post_id', 'result', 'msg', 'html');
				header('Content-type: application/json');
				echo json_encode($response);
				die();
			break;
			case 'undelete_post':
// required params:
// - post_id
				$post_id = intval($_POST['post_id']);
				$post = wp_untrash_post($post_id);
				if ($post != false) {
					$post = get_post($post_id);
					setup_postdata($post);
					$result = 'success';
					$msg = __('Post recovered from trash.', 'capsule');
					ob_start();
					include(STYLESHEETPATH.'/views/excerpt.php');
					$html = ob_get_clean();
				}
				else {
					$result = 'error';
					$msg = __('Post not restored, please try again.', 'capsule');
					$html = '';
				}
				$response = compact('post_id', 'result', 'msg', 'html');
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
add_action('init', 'capsule_controller', 11);
