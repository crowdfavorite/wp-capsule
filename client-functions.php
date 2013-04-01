<?php 

// Class here is mostly for namespacing of methods and properties
class Capsule_Client {

	var $post_type_prefix = '_cc_';
	var $server_term_id_key = '_cap_server_term_id';
	var $server_term_tax_key = '_cap_server_term_tax';
	var $server_term_slug_key = '_cap_server_term_slug';

	var $server_api_key = '_cap_server_api_key';
	var $server_url_key = '_cap_server_url';

	function __construct() {
		// Used for debugging, add this constant to your local-config or wp-config.php to override
		// Shows hidden taxonomies and post types.
		@define('CAP_CLIENT_DEBUG', false);
	}

	function add_actions() {
		// Come in after post types and taxonomies registered
		add_action('wp_loaded', array($this, 'request_handler'));

		// Priority 11 to come after taxonomy registration
		add_action('init', array($this, 'register_post_types'), 11);
		add_action('admin_menu', array($this, 'add_menu_pages'));
		add_action('wp_insert_post', array($this,'insert_post'), 10, 2);
		add_action('admin_enqueue_scripts', array($this,'admin_enqueue_scripts'), 10, 2);
		add_action('admin_head-settings_page_capsule-server-management', array($this, 'admin_css'));
		add_action('admin_head-settings_page_capsule-term-mapping', array($this, 'admin_css'));
		add_action('admin_notices', array($this,'capsule_admin_notice'));
	}

	public function admin_enqueue_scripts() {
		$template_url = trailingslashit(get_template_directory_uri());

		wp_enqueue_script(
			'capsule-client-admin',
			$template_url.'js/client-admin.js',
			array('jquery'),
			CAPSULE_URL_VERSION,
			true
		);
	}

	// Handles all client actions including those coming in via ajax
	public function request_handler() {
		if (isset($_REQUEST['capsule_client_action'])) {
			switch ($_REQUEST['capsule_client_action']) {
				case 'add_server':
					if (wp_verify_nonce($_POST['_add_server_nonce'], '_cap_client_add_server')) {
						$server_data = array(
							'server_name' => isset($_POST['server_name']) ? $_POST['server_name'] : '',
							'api_key' => isset($_POST['server_api_key']) ? $_POST['server_api_key'] : '',
							'server_url' => isset($_POST['server_url']) ? $_POST['server_url'] : '',
						);
						$post = $this->add_server($server_data);
					}
					break;
				case 'add_server_ajax':
					$error = 'error';
					if (wp_verify_nonce($_POST['_add_server_nonce'], '_cap_client_add_server')) {
						$server_data = array(
							'server_name' => isset($_POST['server_name']) ? $_POST['server_name'] : '',
							'api_key' => isset($_POST['server_api_key']) ? $_POST['server_api_key'] : '',
							'server_url' => isset($_POST['server_url']) ? $_POST['server_url'] : '',
						);
						$post = $this->add_server($server_data);
						if ($post) {
							echo json_encode(array(
								'result' => 'success',
								'html' => $this->server_row_markup($post, ''),
							));
							die();
						}
						else {
							$error = 'error'; //@TODO maybe something more informative
						}
					}
					else {
						echo json_encode(array(
							'results' => 'error',
							'html' => $error,
						));
					}
					die();
					
					break;
				// Delete server handled slightly differently, with a link and GET params
				case 'delete_server':
					if (wp_verify_nonce($_REQUEST['_wpnonce'], '_cap_client_delete_server')) {
						$result = $this->delete_server($_GET['server_id']);
						if (isset($_GET['doing_ajax'])) {
							if ( $result !== false) {
								echo json_encode(array('result' => 'success'));
							}
							else {
								echo json_encode(array('result' => 'error'));
							}
							die();
						}
					}
					break;
				case 'update_server_ajax':
					$error = 'error';
					if (wp_verify_nonce($_POST['_update_server_nonce'], '_cap_client_update_server')) {
						if ($server_id = $_POST['server_id']) {
							$data['server_name'] = isset($_POST['server_name']) ? $_POST['server_name'] : '';
							$data['api_key'] = isset($_POST['api_key']) ? $_POST['api_key'] : '';
							$data['server_url'] = isset($_POST['server_url']) ? $_POST['server_url'] : '';

							if ($this->update_server($server_id, $data)) {
								echo json_encode(array('result' => 'success'));
								die();
							}
							else {
								$error = 'error'; //@TODO maybe something more informative
							}
						}
						else {
							$error = 'error'; //@TODO maybe something more informative
						}
					}
					echo json_encode(array(
						'result' => 'error',
						'html' => $error,
					));
					die();
					break;
				// Non ajax way, have to update all servers
				case 'update_servers':
					if (wp_verify_nonce($_POST['_update_server_nonce'], '_cap_client_update_server')) {
						$servers = isset($_POST['servers']) ? $_POST['servers'] : array();
						error_log(print_r($servers,1));
						if (!empty($servers)) {
							foreach ($servers as $server_id => $server_data) {
								$this->update_server($server_id, $server_data);
							}
						}
					}
					break;
				case 'save_mapping':
					if (wp_verify_nonce($_POST['_save_mapping_nonce'], '_cap_client_save_mapping')) {
						$this->save_mapping($_POST['cap_client_mapping']);
					}
				default:
					break;
			}
		}
	}

	/**
	 * Get a list of servers
	 * Returns an array of servers with the key as the post type slug:
	 * array(
	 *	'server-1' => array(
	 * 		'api-key' => '12345abc',
	 *		'url' => 'http://capsule-server-1.com';		
	 *	),
	 *	'server-2' => array(
	 * 		'api-key' => 'A4d*DYnohiO',
	 *		'url' => 'http://capsule-server-2.com';		
	 *	)
	 * )
	 * 
	 * Potential feature - Filter servers based on owner for multi-user support
	 **/
	function get_servers() {
		$query = new WP_Query(array(
			'post_type' => 'server',
			'posts_per_page' => -1,
		));

		$servers = array();
		if ($query->have_posts()) {
			foreach ($query->posts as $post) {
				$api_key = get_post_meta($post->ID, $this->server_api_key, true);
				$url = get_post_meta($post->ID, $this->server_url_key, true);
				$post->api_key = $api_key;
				$post->url = $url;
				$servers[] = $post;
			}
		}

		return $servers;
	}

	/**
	* Get the post type name thats generated from a post name
	* Note this does not return the post type of the post, but
	* the post type which was generated from/for the post
	* 
	*/ 
	public function post_type_slug($post_name) {
		// 20 Character limit on post type names
		return substr(sha1($this->post_type_prefix.$post_name), 0, 20);
	}

	/**
	 * Registers required post types. These include the server post type
	 * and one post type for each server which stores the term mappings
	 **/ 
	public function register_post_types() {
		// Register the server type
		$default_args = array(
			'public' => CAP_CLIENT_DEBUG,
			'publicly_queryable' => true,
			'show_ui' => false,
			'show_in_menu' => false,
			'query_var' => true,
			'capability_type' => 'post',
			'has_archive' => true, 
			'hierarchical' => false,
			'menu_position' => null,
		); 

		$args = $default_args;
		$args['label'] = __('Servers', 'capsule-client');

		register_post_type('server', $args);

		$servers = $this->get_servers();
		$args = array_merge($default_args, array(
			'taxonomies' => $this->taxonomies_to_map(),
		));
		
		// Generate post types for each of the servers, this is where the server
		// terms are stored. Must have unique, reproducable names.
		foreach ($servers as $server_post) {
			$args['label'] = $server_post->post_title;
			register_post_type($this->post_type_slug($server_post->post_name), $args);
		}
	}

	/**
	 * Get a list of taxonomies to pull from the capsule server and map local
	 * terms to. 
	 *
	 * Filterable with 'capsule_client_taxonomies_to_map'
	 * 
	 * @return array Array of taxonomy slugs
	 **/
	public function taxonomies_to_map() {
		$taxonomies = array(
			'projects',
		);

		return apply_filters('capsule_client_taxonomies_to_map', $taxonomies);
	}

	// Add menu pages
	public function add_menu_pages() {
		add_menu_page(__('Capsule', 'capsule_client'), __('Capsule', 'capsule_client'), 'manage_options', 'capsule', array($this, 'capsule_options') );
		add_submenu_page('capsule', __('Projects', 'capsule_client'), __('Projects', 'capsule_client'), 'manage_options', 'capsule-term-mapping', array($this, 'term_mapping_page'));
		add_submenu_page('capsule', __('Servers', 'capsule_client'), __('Servers', 'capsule_client'), 'manage_options', 'capsule-server-management', array($this, 'server_management_page'));
	}

	// Menu page
	public function capsule_options() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		echo '<div class="wrap">';
		echo '<p>Capsule settings page</p>';
		echo '</div>';
	}

	public function capsule_admin_notice(){
		_e('<div class="updated"><p>Welcome to Capsule.</p></div>', 'capsule');
	}

	// Markup for server management
	public function server_management_page() {
		$servers = $this->get_servers();
?>
<div class="wrap capsule-admin">
	<div id="icon-options-general" class="icon32"></div>
	<h2><?php _e('Capsule Server Management', 'capsule-client'); ?></h2>
	<div id="cap-servers">
		<form method="post" id="js-capsule-add-server">
			<table class="wp-list-table widefat fixed posts">
				<thead>
					<tr>
						<th scope="col" class="manage-column column-label">
							<?php _e('Server Name', 'capsule-client'); ?>
						</th>
						<th scope="col" class="manage-column column-api-key">
							<?php _e('Server API Key', 'capsule-client'); ?>
						</th>
						<th scope="col" class="manage-column column-api-key">
							<?php _e('Server URL', 'capsule-client'); ?>
						</th>
						<th scope="col" class="manage-column column-actions">
							<?php _e('Actions', 'capsule-client'); ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><input name="server_name" type="text" /></td>
						<td><input name="server_api_key" type="text" /></td>
						<td><input name="server_url" type="text" /></td>
						<td>
							<input type="submit" class="button-primary" value="<?php _e('+ Add New', 'capsule-client'); ?>" />
							<input type="hidden" value="add_server" name="capsule_client_action" />
						</td>

						<?php wp_nonce_field('_cap_client_add_server', '_add_server_nonce', true, true); ?>
					</tr>
				</tbody>
			</table>
		</form>
		<div>
			<form method="post" id="js-capsule-update-servers">
				<table class="wp-list-table widefat fixed posts">
					<thead>
						<tr>
							<th scope="col" class="manage-column column-label" style="">
								<?php _e('Server Name', 'capsule-client'); ?>
							</th>
							<th scope="col" class="manage-column column-api-key" style="">
								<?php _e('Server API Key', 'capsule-client'); ?>
							</th>
							<th scope="col" class="manage-column column-api-key" style="">
								<?php _e('Server URL', 'capsule-client'); ?>
							</th>
							<th scope="col" class="manage-column column-actions" style="">
								<?php _e('Actions', 'capsule-client'); ?>
							</th>
						</tr>
					</thead>
					<tbody>
					<?php 
						$class = ''; 
						foreach ($servers as $server_post) {
							$class = ($class == '') ? ' alternate' : '';
							echo $this->server_row_markup($server_post, $class);
						}
					?>
					</tbody>
				</table>
				<?php wp_nonce_field('_cap_client_update_server', '_update_server_nonce', true, true); ?>
				<input type="hidden" name="capsule_client_action" value="update_servers" />
			</form>
		</div>
	</div>
</div>
<?php
	}	

	/**
	 * Markup for a 'row' representing a server.
	 *
	 * @param object $server_post Normal WP Post object, has api_key and url properties if set @see get_servers())
	 * @param string $class Additional class to put on the wrapper for the row
	 *
	 * @return string HTML for the row
	 **/
	public function server_row_markup($server_post, $class = '') {
		$delete_url = add_query_arg('capsule_client_action', 'delete_server', admin_url());
		$delete_url = add_query_arg('server_id', $server_post->ID, $delete_url);
		// wp_nonce_url does esc_html
		$delete_url = wp_nonce_url($delete_url, '_cap_client_delete_server');
		$name_base = 'servers['.$server_post->ID.']';
		$html = '
<tr id="'.esc_attr('js-server-item-'.$server_post->ID).'" class="'.esc_attr('server-item'.$class).'">
	<td><input id="'.esc_attr('js-server-name-'.$server_post->ID).'" type="text" name="'.$name_base.'[server_name]" value="'.esc_attr($server_post->post_title).'" /></td>
	<td><input id="'.esc_attr('js-server-api_key-'.$server_post->ID).'" name="'.$name_base.'[api_key]" type="text" value="'.esc_attr($server_post->api_key).'" /></td>
	<td><input id="'.esc_attr('js-server-url-'.$server_post->ID).'" type="text" name="'.$name_base.'[server_url]" value="'.esc_attr($server_post->url).'" /></td>
	<td>
		<input type="submit" data-server_id="'.esc_attr($server_post->ID).'" class="js-update-server button-primary" value="'.__('Update', 'capsule-client').'" />
		<input id="'.esc_attr('js-server-id-'.$server_post->ID).'" type="hidden" name="'.$name_base.'[id]" value="'.esc_attr($server_post->ID).'" />
		<a href="'.$delete_url.'" style="color:#ff0000;" data-server_id="'.esc_attr($server_post->ID).'" class="delete js-delete-server">'.__('Delete', 'capsule-client').'</a>
	</td>
</tr>';

		return $html;
	}

	/**
	 * Create a new server based on data
	 * @param array $data Array of data, see defaults for potential values
	 *
	 * @return bool True if server was added, false otherwise
	 **/
	public function add_server($data) {
		$defaults = array(
			'server_name' => null,
			'api_key' => null,
			'server_url' => null,
		);
		$post_data = $postarr = wp_parse_args($data, $defaults);

		$post_arr = array(
			'post_title' => $post_data['server_name'],
			'post_type' => 'server',
			'post_status' => 'publish',
		);

		$post_id = wp_insert_post($post_arr);
		if ($post_id) {
			update_post_meta($post_id, $this->server_api_key, $post_data['api_key']);
			update_post_meta($post_id, $this->server_url_key, $post_data['server_url']);
			$post = get_post($post_id);
			$post->api_key = $post_data['api_key'];
			$post->url = $post_data['server_url'];

			return $post;
		}

		return false;
	}

	/**
	 * Delete a server post.
	 * 
	 * @param in $server_id ID of the post to delete
	 * @return mixed False on failure (from wp_delete_post)
	 **/
	public function delete_server($server_id) {
		return wp_delete_post($server_id, true);
	}

	/**
	 * Update a server with relevant data. Does not create it if it doesnt exist
	 *
	 *
	 * @param int $server_id Post ID of the server to update
	 * @param array $data Array of data. Possible values include:
	 * 					'server_name' => post_title (label for server)
	 *					'api_key' => Server's api key, stored in post meta
	 *					'server_url' => Server's url, stored in post meta
	 * @return bool True if the update was successful, false if the post doesnt exist or something went wrong
	 */
	public function update_server($server_id, $data) {
		$post = get_post($server_id);
		if (!$post) {
			return false;
		}

		$post_arr = array(
			'ID' => $server_id, 
		);
		if (isset($data['server_name'])) {
			$post_arr['post_title'] = $data['server_name'];
		}
		$result = wp_update_post($post_arr);
		if ($result && !is_wp_error($result)) {
			if (isset($data['api_key'])) {
				update_post_meta($server_id, $this->server_api_key, $data['api_key']); 
			}
			if (isset($data['server_url'])) {
				update_post_meta($server_id, $this->server_url_key, $data['server_url']);
			}
			return true;
		}

		return false;
	}

	/*** Taxonomy Mapping ***/

	/**
	 * Fetch terms from the capsule server to be mapped locally.
	 * Passes taxonomies to get terms from and API key to validate against
	 *
	 * @return true|array True if everything went smoothly, array of errors with server post key as the id 
	 **/
	public function get_server_terms() {
		// Query the servers, hits endpoint via request handler - requires API key
		$errors = array();
		$servers = $this->get_servers();
		foreach	($servers as $server_post) {
			$args = array(
				'body' => array(
					'capsule_server_action' => 'get_terms',
					'capsule_client_post_data' => array(
						'api_key' => $server_post->api_key,
						'taxonomies' => $this->taxonomies_to_map(),
					),
				),
			);
			$request = wp_remote_post($server_post->url, $args);
			// Check for errors
			if (is_wp_error($request)) {
				foreach ($request->errors as $key => $wp_errors) {
					foreach ($wp_errors as $error) {
						$errors[$server_post->ID][] = $error;
					}
				}
			}
			else if ($request['response']['code'] != '200') {
				$errors[$server_post->ID][] = sprintf(__('Server said: "%s:%s" Please check the server credentials and connectivity and try again.', 'capsule-client'), $request['response']['code'], $request['response']['message']);
			}
			else {
				// Response is serialized string of taxonomies as keys with values of array of terms (ID, name, slug, description)
				$terms = @unserialize($request['body']);
				$this->process_server_terms($terms, $this->post_type_slug($server_post->post_name));
			}
		}

		return empty($errors) ? true : $errors;
	}

	/**
	 * Create new posts in the server's post type which corresponds to a term in the $terms array.
	 * It updates any posts in which there are differences such as the term name or term description,
	 * it also removes any post associations which are not found in the $terms array.
	 *
	 * @param $terms Array of terms which will be mapped to posts or update data in the post. Format:
	 * array(
	 *	'taxonomy_1' => array(
	 *		'term-slug' => array(
	 *			'id' => 1,
	 *			'name' => 'Amazing Term',
	 *			'description' => 'This term is amazing AND useful',
	 *		),
	 *		'term-slug-2' ...
	 * 	),
	 * 	'taxonomy_2' ....
	 *
	 * )
	 * @param $server_post_type Post type to associate terms with
	 */
	public function process_server_terms($terms, $server_post_type) {
		
		$post_term_array = array();

		$posts = $this->get_server_term_posts($server_post_type);

		foreach ($posts as $post) {
			$term_id = get_post_meta($post->ID, $this->server_term_id_key, true);
			$term_taxonomy = get_post_meta($post->ID, $this->server_term_tax_key, true);
			$term_slug = get_post_meta($post->ID, $this->server_term_slug_key, true);
			// Setup post data a little better to be processed by the terms
			// name, description here to tell wether or not the post needs an update
			// Need to include taxonomy in term key in case multiple taxonomies use the same term
			$post_term_array[$term_taxonomy.'_'.$term_id] = array(
				'post' => $post,
				'term_taxonomy' => $term_taxonomy,
				'term_name' => $post->post_title,
				'term_description' => $post->post_content,
				'term_slug' => $term_slug,
			);
		}

		if (is_array($terms)) {
			foreach ($terms as $taxonomy => $terms) {
				foreach ($terms as $term_slug => $term) {
					// array key matches above @TODO trim?
					$array_key = $term['taxonomy'].'_'.$term['id'];
					if (isset($post_term_array[$array_key])) {
						// Check for any differences, if there is update as necessary
						$existing_data = $post_term_array[$array_key];
						
						if ($existing_data['term_name'] != $term['name'] || $existing_data['term_description'] != $term['description']) {
							$existing_data['post']->post_title = $term['name'];
							$existing_data['post']->post_content = $term['description'];
							wp_update_post($existing_data['post']);
						}
						if ($existing_data['term_taxonomy'] != $term['taxonomy']) {
							update_post_meta($post_id, $this->server_term_tax_key, $term['taxonomy']);
						}
						if ($existing_data['term_slug'] != $term['slug']) {
							update_post_meta($post_id, $this->server_term_slug_key, $term['slug']);
						}
					}
					else {
						// Create post, it doesnt exist
						$post_id = wp_insert_post(array(
							'post_type' => $server_post_type,
							'post_content' => $term['description'],
							'post_title' => $term['name'],
							'post_status' => 'publish',
						));
						if ($post_id) {
							update_post_meta($post_id, $this->server_term_id_key, $term['id']);
							update_post_meta($post_id, $this->server_term_tax_key, $term['taxonomy']);
							update_post_meta($post_id, $this->server_term_slug_key, $term_slug);
						}
					}

					// Unset to know which posts no longer have terms associated with them and will be deleted
					unset($post_term_array[$array_key]);
				}
			}
		}

		// All terms that have come over have been processed and unset from post_term_array
		// meaning the remaining posts no longer have a corresponding term on the server
		foreach ($post_term_array as $data) {
			wp_delete_post($data['post']->ID, true);
		}
	}

	/**
	 * Return posts that represent taxonomy terms on a server
	 *
	 * @param string $server_name Name of the server to get the posts for (also the post type)
	 * @param array $args Array of WP_Query args
	 * @return array Empty array or an array of posts for the server name
	 **/
	function get_server_term_posts($server_post_type, $args = array()) {
		$defaults = array(
			'posts_per_page' => -1,
			'post_type' => $server_post_type,
			'post_status' => 'publish',
		);
		$query_args = array_merge($defaults, $args);

		$post_query = new WP_Query($query_args);

		if (is_array($post_query->posts)) {
			return $post_query->posts;
		}
		else {
			return array();
		}
	}

	function show_term_mapping_errors($errors, $post_id) {
		$error_html = '';
		if (isset($errors[$post_id]) && !empty($errors[$post_id])) {
			foreach ($errors[$post_id] as $error_message) {
				$error_html .= $error_message;
			}
			$html = sprintf(__('Error: %s', 'capsule-client'), $error_html);
		}

		echo $html;
	}

	/**
	 * Markup and logic for the term mapping page
	 **/
	function term_mapping_page() {
		// Fetch server terms on each page load
		// @TODO check what happens when disconnected
		$errors = $this->get_server_terms();

		// Get all the terms in taxonomies are mapped and sort them by taxonomy
		// For easier displaying later
		$taxonomies = $this->taxonomies_to_map();
		$terms = get_terms($taxonomies, array('hide_empty' => false));
		$taxonomy_array = array();

		if (is_array($terms)) {
			foreach ($terms as $term) {
				$taxonomy_array[$term->taxonomy][] = $term;
			}
		}
?>
 <div class="wrap capsule-admin">
	<div id="icon-options-general" class="icon32"></div>
	<h2><?php _e('Capsule Server Term Mappings', 'capsule-client'); ?></h2>
	<form method="post">
		<input type="submit" class="save-mappings button-primary" value="<?php _e('Save Mappings', 'capsule-client'); ?>">
 <?php 
		$servers = $this->get_servers();
		foreach ($servers as $server_post) {
?>
			<h3><?php echo esc_html($server_post->post_title); ?></h3><span class="error"><?php $this->show_term_mapping_errors($errors, $server_post->ID); ?></span>
<?php 
			// Default capsule functionality only includes projects here, but support for more taxonomies is present
			foreach ($taxonomies as $taxonomy) {
				$tax_obj = get_taxonomy($taxonomy);

				// Get all posts with this taxonomy, doesn't matter which term
				$posts = $this->get_server_term_posts(
					$this->post_type_slug($server_post->post_name), 
					array(
						'tax_query' => array(
							array(
								'operator' => 'NOT IN',
								'terms' => array(-1),
								'taxonomy' => $taxonomy,
							)
						),
					)
				);
?>
			<table class="wp-list-table widefat fixed posts">
				<thead>
					<tr>
						<th scope="col" class="manage-column column-label" style="">
							<?php echo sprintf(_x('%s Name', 'taxonomy name', 'capsule-client'), $tax_obj->labels->singular_name); ?>
						</th>
						<th scope="col" class="manage-column column-api-key" style="">
							<?php _e('Select your mapping', 'capsule-client'); ?>
						</th>
					</tr>
				</thead>
				<tbody>
<?php 
				foreach ($posts as $post) :
					// get_the_terms is cached by WP_Query, this isn't as expensive as it looks
					$terms = get_the_terms($post, $taxonomy);
					$selected_id = (is_array($terms) && !empty($terms)) ? array_shift($terms)->term_id : 0;
?>
					<tr>
						<td><?php echo esc_html($post->post_title); ?></td>
						<td><?php echo $this->term_select_markup($post, $taxonomy, $taxonomy_array[$taxonomy], $selected_id); ?></td>
					</tr>
				<?php endforeach;  ?>
				</tbody>
			</table>
		<?php 
		}
	}
		?>
		
			<input type="submit" class="save-mappings button-primary" value="<?php _e('Save Mappings', 'capsule-client'); ?>">
			<input type="hidden" name="capsule_client_action" value="save_mapping" />
			<?php wp_nonce_field('_cap_client_save_mapping', '_save_mapping_nonce', true, true); ?>
		</form>
</div>

<?php 
	}

	/**
	 * Generate markup for a term mapping select box. Adds a 'Create Term Locally' option if no
	 * term is found that matches the server term name
	 * 
	 * @param obj $post Post object
	 * @param string $taxonomy Taxonomy name to select from
	 * @param arary $terms Terms in the taxonomy
	 * @param int $selected_id Term ID that is selected
	 *
	 * @return string HTML markup for the select box
	 **/
	function term_select_markup($post, $taxonomy, $terms, $selected_id) {
		$options = '';
		$match = false;

		$output = '
<input type="hidden" name="'.esc_attr('cap_client_mapping['.$post->ID.']['.$taxonomy.'][server_term]').'" value="'.esc_attr($post->post_title).'">
<select name="'.esc_attr('cap_client_mapping['.$post->ID.']['.$taxonomy.'][term_id]').'">
	<option value="0">'.__('No Mapping', 'capsule-client').'</option>';

		foreach ($terms as $term) {
			if ($term->name == $post->post_title) {
				$match = true;
			}
			$options .= '<option value="'.esc_attr($term->term_id).'"'.selected($selected_id, $term->term_id, false).'>'.esc_html($term->name).'</option>';
		}
		// If there are no local terms that match the server term, provide a 'Create Term' option
		if (!$match) {
			$output .= '<option value="-1">'.__('Create Term Locally', 'capsule-client').'</option>';
		}

		$output .= $options.'</select>';

		return $output;
	}

	/**
	 * Save taxonomy mappings
	 * 
	 * @param array $mappings Array of mappings in the following format (only 1 term per taxonomy currently):
	 * Array ( 
	 *		[post_id] => Array ( 
	 *			[taxonomy] => term_id
	 *		) 
	 * )
	 */ 
	function save_mapping($mappings) {
		if (is_array($mappings)) {
			foreach ($mappings as $post_id => $mapping) {
				foreach ($mapping as $taxonomy => $term_data) {
					$term_id = $term_data['term_id'];

					// This is the create id see term_select_markup
					if ($term_data['term_id'] == -1) {
						// Create term
						$term_id = capsule_create_term($term_data['server_term'], $taxonomy);
					}

					wp_set_object_terms($post_id, (int) $term_id, $taxonomy);
				}
			}
		}
	}

	/*** Export/Sending Post ***/

	/**
	 * Sends post data to an external server
	 * 
	 * @param $post array array of post data 1:1 to wp_posts table columns
	 * @param $tax array Array containing all the post taxonomies and tax_input key which corresponds to 
	 *  				 terms in the taxonomies. Need to send the taxonomies seperately if there are no terms being set
	 * @param $api_key string Api key for a given user of a server
	 * @param $server_url string URL of the server to send the post to
	 * @return @TODO
	 **/
	public function send_post($post, $tax, $api_key, $server_url) {
		$args = array(
			'body' => array(
				'capsule_server_action' => 'insert_post',
				'capsule_client_post_data' => array(
					'api_key' => $api_key,
					'post' => $post,
					'tax' => $tax
				)
			),
		);
		wp_remote_post($server_url, $args);
		//@TODO response
	}

	/**
	 * Sends the post to any server in which it has mapped taxonomies with
	 * This is a hook on wp_insert_post
	 **/
	function insert_post($post_id, $post) {
		if ((!defined('DOING_AUTOSAVE') || !DOING_AUTOSAVE) && $post->post_status == 'publish') {
			$postarr = (array) $post;	

			//@TODO need to get specific servers to send to, not all of them!

			// Check if there are any posts in the post type
			$taxonomies = get_object_taxonomies($post->post_type);

			$servers = $this->get_servers();

			foreach ($servers as $server_post) {
				// Only send post if theres a term thats been mapped
				if ($this->has_server_mapping($post, $server_post)) {
					$tax_input = $this->format_terms_to_send($post, $taxonomies, $this->post_type_slug($server_post->post_name));
					$mapped_taxonomies = $this->taxonomies_to_map();

					$tax = compact('taxonomies', 'tax_input', 'mapped_taxonomies');

					$api_key = get_post_meta($server_post->ID, $this->server_api_key, true);
					$endpoint = get_post_meta($server_post->ID, $this->server_url_key, true);

					$this->send_post($postarr, $tax, $api_key, $endpoint);
				}
			}
		}
	}

	/** 
	 * Check to see if a post has a server term mapping for a given server
	 * 
	 * @param object $post
	 * @param object $server 
	 *
	 * @todo revisit this...
	 **/
	function has_server_mapping($post, $server) {
		// Get the taxonomies that are mapped
		$mapped_taxonomies = $this->taxonomies_to_map();

		foreach ($mapped_taxonomies as $taxonomy) {
			$post_terms = wp_get_object_terms($post->ID, $taxonomy, array('fields' => 'slugs'));

			if (!empty($post_terms)) {
				$query = new WP_Query(array(
					'post_type' => $this->post_type_slug($server->post_name),
					'tax_query' => array(
						array(
							'taxonomy' => $taxonomy,
							'field' => 'slug',
							'terms' => $post_terms,
						),
					),
					'update_post_term_cache' => false,
					'update_post_meta_cache' => false,
					'fields' => 'ids',					
				));

				// Cannot use have_posts here as fields=>ids prevents it from working
				// There is at least one term thats been mapped, so return
				if (!empty($query->posts)) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Format post term mappings to be sent to a server
	 * 
	 * @param object $post Post being sent to the server
	 * @param arary $taxonomies array of taxonomies to get data for from $post
	 * @param string $server_post_type Post of of the term mappings @see post_type_slug()
	 * 
	 * @return array Array of formatted taxonomy terms ready for transmission to a server
	 **/
	function format_terms_to_send($post, $taxonomies, $server_post_type) {
		$mapped_taxonomies = $this->taxonomies_to_map();

		$tax_input = array();

		if (!empty($taxonomies)) {
			// Get posts of all the mapped terms
			$mapped_term_posts = $this->get_server_term_posts($server_post_type);

			// For taxonomies that are mapped, we need to get the mapping data and set it
			// Otherwise, just send along the local data for the terms
			foreach ($taxonomies as $taxonomy) {
				$tax_input[$taxonomy] = array();
				$terms = wp_get_object_terms($post->ID, $taxonomy);

				if (is_array($terms) && !empty($terms)) {
					// Taxonomy has been mapped, need to get the slug/term_id thats on the server
					if (in_array($taxonomy, $mapped_taxonomies)) {
						$term_ids = array();
						foreach ($terms as $term) {
							$term_ids[] = $term->term_id;
						}

						// There may be one local term mapped to many server terms, so we handle them all
						$term_object_query = new WP_Query(array(
							'posts_per_page' => -1,
							'post_type' => $server_post_type,
							'post__in' => $term_object_ids,
							'post_status' => 'publish',
							'fields' => 'ids',
							'update_post_term_cache' => false,
							// Same number of queries if left as false. Default is true
							'update_post_meta_cache' => true,
							'tax_query' => array(
								array(
									'taxonomy' => $taxonomy,
									'field' => 'id',
									'terms' => $term_ids
								)
							)
						));

						if (!empty($term_object_query->posts)) {
							foreach ($term_object_query->posts as $term_mapping_post_id) {
								if (is_taxonomy_hierarchical($taxonomy)) {
									$server_term_id = get_post_meta($term_mapping_post_id, $this->server_term_id_key, true);
									if ($server_term_id) {
										$tax_input[$taxonomy][] = $server_term_id;
									}
								}
								else {
									$server_term_slug = get_post_meta($term_mapping_post_id, $this->server_term_slug_key, true);
									if ($server_term_slug) {
										$tax_input[$taxonomy][] = $server_term_slug;
									}
								}
							}
						}
					}
					else {
						foreach ($terms as $term) {
							// check if heirarchical, wp_insert_post handles them differently
							// wp_insert_post expects hierachical term to come in as IDs, server won't know about these,
							// have to send them as term names. strtolower for normalization.
							$tax_input[$taxonomy][] = $term->name;
						}
					}
				}
				else {
					// So data gets sent through POST
					$tax_input[$taxonomy][] = null;
				}
			}
		}
		return $tax_input;
	}

	function admin_css() {
		echo '
	<style type="text/css">
		.capsule-admin table {
			margin-top: 10px;
		}
		.error {
			color: #FF0000;
		}
		.capsule-admin input.save-mappings {
			margin-top: 20px
		}
	</style>';
	}
}

$cap_client = new Capsule_Client;
$cap_client->add_actions();