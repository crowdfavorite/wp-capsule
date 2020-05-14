<?php //phpcs:disable Files.SideEffects

/**
 * Capsule controller.
 *
 * @package capsule
 */

/**
 * Capsule controller function.
 *
 * @return void
 */
function capsule_controller()
{
	$action_get = filter_input(INPUT_GET, 'capsule_action');
	if (! empty($action_get)) {
		capsule_controller_action_get($action_get);
	}

	$action_post = filter_input(INPUT_POST, 'capsule_action');
	if (! empty($action_post)) {
		if (! current_user_can('edit_posts')) {
			capsule_unauthorized_json();
		}

		capsule_controller_action_post($action_post);
	}
}
add_action('init', 'capsule_controller', 11);

/**
 * Process GET actions.
 *
 * @param string $action Current action.
 * @return void
 */
function capsule_controller_action_get($action)
{
	if ('search' === $action) {
		global $wpdb;
		$q = filter_input(INPUT_GET, 'q');
		if (! empty($q) && in_array($q[0], array( '@', '#', '`' ), true)) {
			$prefix = $q[0];
			switch ($prefix) {
				case '@':
					$taxonomy = 'projects';
					break;

				case '#':
					$taxonomy = 'post_tag';
					break;

				case '`':
					$taxonomy = 'code';
					break;

				default:
					$taxonomy = null;
					break;
			}

			$term_name = stripslashes(substr($q, 1, strlen($q)));
			if (! strlen($term_name) < 1) {
				// Taken from wp_ajax_ajax_tag_search().
				$results = $wpdb->get_col(
					$wpdb->prepare(
						'
						SELECT t.name
						FROM ' . $wpdb->term_taxonomy . ' AS tt
						INNER JOIN ' . $wpdb->terms . ' AS t ON tt.term_id = t.term_id
						WHERE tt.taxonomy = %s AND t.name LIKE (%s)
						AND tt.count > 0',
						$taxonomy,
						$wpdb->esc_like($term_name) . '%'
					)
				);
				$html    = '';
				foreach ($results as $result) {
					$html .= $prefix . $result . "\n";
				}
				echo esc_html($html);
			}
		}
		die();
	}

	if (0 === strpos($action, 'queue_')) {
		$api_key = filter_input(INPUT_GET, 'api_key');
		if (stripslashes($api_key) === capsule_queue_api_key()) {
			switch ($action) {
				case 'queue_run':
					capsule_queue_run();
					break;

				case 'queue_post_to_server':
					// Required params: post_id.
					$post_id = (int) filter_input(INPUT_GET, 'post_id', FILTER_VALIDATE_INT);
					if ($post_id > 0) {
						capsule_queue_post_to_server($post_id);
					}
					break;

				default:
					break;
			}
			die();
		}
	}

	if (! current_user_can('edit_posts')) {
		capsule_unauthorized_json();
	}
	switch ($action) {
		case 'post_excerpt':
		case 'post_content':
			// Required params: post_id.
			$post_id = (int) filter_input(INPUT_GET, 'post_id', FILTER_VALIDATE_INT);
			if ($post_id > 0) {
				global $post;
				$post = get_post($post_id);
				setup_postdata($post);
				$view = str_replace('post_', '', $action);
				ob_start();
				include get_template_directory() . '/ui/views/' . $view . '.php';
				$html     = ob_get_clean();
				$response = compact('html');
				header('Content-type: application/json');
				wp_send_json($response);
			}
			break;

		case 'post_editor':
			// Required params: post_id.
			$post_id = (int) filter_input(INPUT_GET, 'post_id', FILTER_VALIDATE_INT);
			if ($post_id > 0) {
				global $post;
				$post = get_post($post_id);
				setup_postdata($post);
				ob_start();
				include get_template_directory() . '/ui/views/edit.php';
				$html     = ob_get_clean();
				$response = array(
					'html'    => $html,
					'content' => $post->post_content,
				);
				header('Content-type: application/json');
				wp_send_json($response);
			}
			break;

		default:
			do_action('capsule_controller_action_get', $action);
			break;
	}
}

/**
 * Process POST actions.
 *
 * @param string $action Current action.
 * @return void
 */
function capsule_controller_action_post($action)
{
	switch ($action) {
		case 'create_post':
			global $post;
			$post_id = wp_insert_post(
				array(
					'post_title'   => time(),
					'post_status'  => 'draft',
					'post_content' => '',
				),
				true
			);
			if (is_wp_error($post_id)) {
				$result   = 'error';
				$msg      = $post_id->get_error_message();
				$response = compact('result', 'msg');
			} else {
				$result = 'success';
				$msg    = __('Post created.', 'capsule');
				$post   = get_post($post_id);
				setup_postdata($post);
				ob_start();
				include get_template_directory() . '/ui/views/edit.php';
				$html     = ob_get_clean();
				$ymd      = get_the_time('Ymd', $post);
				$response = array(
					'post_id' => $post_id,
					'result'  => $result,
					'msg'     => $msg,
					'html'    => $html,
					'content' => $post->post_content,
				);
			}
			header('Content-type: application/json');
			wp_send_json($response);
			break;

		case 'update_post':
			// Required params: post_id, content.
			$post_id = (int) filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
			if ($post_id <= 0) {
				die();
			}
			$post_title = '';
			$taxonomies = array(
				'projects' => array(),
				'post_tag' => array(),
				'code'     => array(),
			);
			foreach ($taxonomies as $tax => $terms) {
				$submitted_tax = filter_input(INPUT_POST, $tax);
				$terms         = json_decode($submitted_tax);
				// There is no easy WP way assign terms by name to a post on the fly
				// they must be created first and then use the slug (or ID for heirarchial).
				foreach ($terms as $term_name) {
					$term = get_term_by('name', $term_name, $tax);
					if (! $term) {
						$term_data = wp_insert_term($term_name, $tax);
						if (! is_wp_error($term_data)) {
							$term                 = get_term_by('id', $term_data['term_id'], $tax);
							$taxonomies[ $tax ][] = $term->slug;
						}
					} else {
						$taxonomies[ $tax ][] = $term->slug;
					}
				}
				$post_title .= ' ' . implode(' ', $terms);
			}
			// If the content is empty, wp_update_post fails.
			$content = filter_input(INPUT_POST, 'content');
			if (empty($content)) {
				$content = ' ';
			}
			$update = wp_update_post(
				array(
					'ID'           => $post_id,
					'post_title'   => trim($post_title),
					'post_content' => $content,
					'post_status'  => 'publish',
				)
			);
			if ($update) {
				foreach ($taxonomies as $tax => $terms) {
					wp_set_post_terms($post_id, $terms, $tax);
				}
				$result = 'success';
				$msg    = 'Post saved.';
			} else {
				$result = 'error';
				$msg    = 'Saving post #' . $post_id . ' failed.';
			}
			$projects_html = capsule_term_list($post_id, 'projects');
			$tags_html     = capsule_term_list($post_id, 'post_tag');
			$code_html     = capsule_term_list($post_id, 'code');
			$response      = compact('post_id', 'result', 'msg', 'projects_html', 'tags_html', 'code_html');
			header('Content-type: application/json');
			wp_send_json($response);
			break;

		case 'delete_post':
			// Required params: post_id.
			$post_id = (int) filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
			$delete  = wp_delete_post($post_id);
			if (false !== $delete) {
				$post = get_post($post_id);
				setup_postdata($post);
				$result = 'success';
				$msg    = __('Post deleted', 'capsule');
				ob_start();
				include get_template_directory() . '/ui/views/deleted.php';
				$html = ob_get_clean();
			} else {
				$result = 'error';
				$msg    = __('Post not deleted, please try again.', 'capsule');
				$html   = '';
			}
			$response = compact('post_id', 'result', 'msg', 'html');
			header('Content-type: application/json');
			wp_send_json($response);
			break;

		case 'undelete_post':
			// Required params: post_id.
			global $post;
			$post_id = (int) filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
			$post    = wp_untrash_post($post_id);
			if (false !== $post) {
				$post = get_post($post_id);
				setup_postdata($post);
				$result = 'success';
				$msg    = __('Post recovered from trash.', 'capsule');
				ob_start();
				include get_template_directory() . '/ui/views/excerpt.php';
				$html = ob_get_clean();
			} else {
				$result = 'error';
				$msg    = __('Post not restored, please try again.', 'capsule');
				$html   = '';
			}
			$response = compact('post_id', 'result', 'msg', 'html');
			header('Content-type: application/json');
			wp_send_json($response);
			break;

		case 'stick_post':
			// Required params: post_id.
			$post_id = (int) filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
			$post    = get_post($post_id);
			if (! $post) {
				die();
			}
			stick_post($post_id);
			$response = array(
				'post_id' => $post_id,
				'result'  => 'success',
				'msg'     => __('Post stuck.', 'capsule'),
				'html'    => '',
			);
			header('Content-type: application/json');
			wp_send_json($response);
			break;

		case 'unstick_post':
			// Required params: post_id.
			$post_id = (int) filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
			if ($post_id <= 0) {
				die();
			}
			unstick_post($post_id);
			$response = array(
				'post_id' => $post_id,
				'result'  => 'success',
				'msg'     => __('Post unstuck.', 'capsule'),
				'html'    => '',
			);
			header('Content-type: application/json');
			wp_send_json($response);
			break;

		case 'split_post':
			// Required params: post_id, content, new_post_content.
			// @TODO - implement.
			break;

		case 'merge_posts':
			// Required params: post_ids (array).
			// @TODO - implement.
			break;

		default:
			do_action('capsule_controller_action_post', $action);
			break;
	}
}
