<?php 

class Capsule_Client {

	var $post_type_prefix = '_cc_';
	var $server_term_id_key = '_cap_server_term_id';
	var $server_term_tax_key = '_cap_server_term_tax';
	var $server_term_slug_key = '_cap_server_term_slug';

	var $server_api_key = '_cap_server_api_key';
	var $server_url_key = '_cap_server_url';

	function __construct() {
		$this->user_id = get_current_user_id();
	}

	function add_actions() {
		add_action('wp_loaded', array($this, 'request_handler'));
		// Priority 11 to come after taxonomy registration
		add_action('init', array($this, 'register_post_types'), 11);
		add_action('admin_menu', array($this, 'add_menu_pages'));
		add_action('wp_insert_post', array($this,'insert_post'), 10, 2);
		add_action('admin_enqueue_scripts', array($this,'admin_enqueue_scripts'), 10, 2);
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

	public function request_handler() {
		if (isset($_POST['capsule_client_action'])) {
			switch ($_POST['capsule_client_action']) {
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
				case 'delete_server':
					if (wp_verify_nonce($_POST['_delete_server_nonce'], '_cap_client_delete_server')) {
						$this->delete_server($_POST['server_id']);
					}
					break;
				case 'delete_server_ajax':
					$error = 'error';
					if (wp_verify_nonce($_POST['_delete_server_nonce'], '_cap_client_delete_server')) {
						if ($this->delete_server($_POST['server_id']) !== false) {
							echo json_encode(array('result' => 'success'));
							die();
						}
					}
					echo json_encode(array('result' => 'error'));
					die();
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
				case 'update_server':
					if (wp_verify_nonce($_POST['_update_server_nonce'], '_cap_client_update_server')) {
						if ($server_id = $_POST['server_id']) {
							$data['server_name'] = isset($_POST['server_name']) ? $_POST['server_name'] : '';
							$data['api_key'] = isset($_POST['api_key']) ? $_POST['api_key'] : '';
							$data['server_url'] = isset($_POST['server_url']) ? $_POST['server_url'] : '';
							$this->update_server($server_id, $data);
						}
					}
					break;
				case 'get_terms':
					if (wp_verify_nonce($_POST['_get_terms_nonce'], '_cap_client_get_terms')) {
						$this->get_server_terms();
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
	 * Get a list of servers that this user has set up
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
	 * Potential feature - servers owned by users for multi user support
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

	public function register_post_types() {
		// Register the server type
		$default_args = array(
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true, 
			'show_in_menu' => true, 
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
			)
		); 
		
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

	function add_menu_pages() {
		add_options_page(__('Capsule Term Mapping', 'capsule_client'), __('Capsule Term Mapping', 'capsule_client'), 'manage_options', 'capsule-term-mapping', array($this, 'term_mapping_page'));
		add_options_page(__('Capsule Servers Management', 'capsule_client'), __('Capsule Servers Management', 'capsule_client'), 'manage_options', 'capsule-server-management', array($this, 'server_management_page'));
	}

	public function server_management_page() {
		$servers = $this->get_servers();
?>
<div class="wrap">
	<div id="icon-options-general" class="icon32"></div>
	<h2><?php _e('Capsule Server Management', 'capsule-client'); ?></h2>
	<div id="cap-servers">
		<form method="post" id="capsule-add-server">
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
			</table>
		</form>

		<div id="capsule-servers" class="wp-list-table widefat fixed posts">
			
		<?php
			$class = ''; 
			foreach ($servers as $server_post) {
				$class = ($class == '') ? ' alternate' : '';
				echo $this->server_row_markup($server_post, $class);
			} 
		?>
		</table>
	</div>
</div>
<?php
	}	

	public function server_row_markup($server_post, $class) {
		//@Todo nonce
		$html = '
<div class="'.esc_attr('server-item'.$class).'">
	<form class="update-server" method="post">
		<input type="text" name="server_name" value="'.esc_attr($server_post->post_title).'" />
		<input name="api_key" type="text" value="'.$server_post->api_key.'" />
		<input type="text" name="server_url" value="'.esc_attr($server_post->url).'" />
		<input type="submit" class="button-primary" value="Update" />
		<input type="hidden" name="server_id" value="'.esc_attr($server_post->ID).'" />
		<input type="hidden" name="capsule_client_action" value="update_server" />
		'.wp_nonce_field('_cap_client_update_server', '_update_server_nonce', true, false).'
	</form>
	<form class="delete-server" method="post">
		<input type="submit" value="'.__('Delete', 'capsule-client').'" />
		<input type="hidden" name="server_id" value="'.esc_attr($server_post->ID).'" />
		<input type="hidden" name="capsule_client_action" value="delete_server" />
		'.wp_nonce_field('_cap_client_delete_server', '_delete_server_nonce', true, false).'
	</form>
</div>';
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
	 **/
	public function get_server_terms() {
		// Query the servers, hits endpoint via request handler - requires API key
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
			if (is_wp_error($request) || $request['response']['code'] != '200') {
				print_r($request);
				die();
				// @TODO Handle this error
			}
			else {
				// Response is serialized string of taxonomies as keys with values of array of terms (ID, name, slug, description)
				$terms = @unserialize($request['body']);
				$this->process_server_terms($terms, $this->post_type_slug($server_post->post_name));
			}
		}
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
	 * @return array Empty array or an array of posts for the server name
	 **/
	function get_server_term_posts($server_post_type) {
		$post_query = new WP_Query(array(
			'posts_per_page' => -1,
			'post_type' => $server_post_type,
			'post_status' => 'publish',
		));

		if (is_array($post_query->posts)) {
			return $post_query->posts;
		}
		else {
			return array();
		}
	}

	/**
	 * Markup and logic for the term mapping page
	 **/
	function term_mapping_page() {
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
<div class="wrap">
	<div id="icon-options-general" class="icon32"></div>
	<h2><?php _e('Capsule Server Term Mappings', 'capsule-client'); ?></h2>
	<div id="cap-term-mappings">
		<form method="post">
<?php 
		$servers = $this->get_servers();
		foreach	($servers as $server_post) {
			echo '<h3>'.esc_html($server_post->post_title).'</h3>';
			$posts = $this->get_server_term_posts($this->post_type_slug($server_post->post_name));
			foreach ($posts as $post) {
				echo '<div>';
				echo $post->post_title.': ';
				$terms = get_the_terms($post, 'projects');

				$selected_id = (is_array($terms) && !empty($terms)) ? array_shift($terms)->term_id : 0;

				echo $this->term_select_markup($post->ID, 'projects', $taxonomy_array['projects'], $selected_id);
				echo '</div>';
			}
		}
?>
			<input type="submit" value="<?php _e('Submit', 'capsule-client'); ?>">
			<input type="hidden" name="capsule_client_action" value="save_mapping" />
			<?php wp_nonce_field('_cap_client_save_mapping', '_save_mapping_nonce', true, true); ?>
		</form>
		<form method="post">
			<input type="submit" value="<?php _e('Fetch Terms', 'capsule-client'); ?>">
			<input type="hidden" name="capsule_client_action" value="get_terms" />
			<?php wp_nonce_field('_cap_client_get_terms', '_get_terms_nonce', true, true); ?>
		</form>
		
	</div>
</div>

<?php 
	}

	/**
	 * Generate markup for a term mapping select box
	 *
	 * @param int $post_id ID of the post 
	 * @param string $taxonomy Taxonomy name to select from
	 * @param arary $terms Terms in the taxonomy
	 * @param int $selected_id Term ID that is selected
	 *
	 * @return string HTML markup for the select box
	 **/
	function term_select_markup($post_id, $taxonomy, $terms, $selected_id) {
		$output = '
<select name="'.esc_attr('cap_client_mapping['.$post_id.']['.$taxonomy.']').'">
	<option value="0">'.__('No Mapping', 'capsule-client').'</option>';

		foreach ($terms as $term) {
			$output .= '<option value="'.esc_attr($term->term_id).'"'.selected($selected_id, $term->term_id, false).'>'.esc_html($term->name).'</option>';
		}
		$output .= '</select>';

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
				foreach ($mapping as $taxonomy => $term_id) {
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

					$tax = compact('taxonomies', 'tax_input');

					$api_key = get_post_meta($server_post->ID, $this->server_api_key, true);
					$endpoint = get_post_meta($server_post->ID, $this->server_url_key, true);

					$this->send_post($postarr, $tax, $api_key, $endpoint);
				}
			}
		}
	}

	function has_server_mapping($post, $server) {
		// Get the taxonomies that are mapped
		$mapped_taxonomies = $this->taxonomies_to_map();

		foreach ($mapped_taxonomies as $taxonomy) {
			$post_terms = wp_get_object_terms($post->ID, $taxonomy, array('fields' => 'slug'));

			if (!empty($post_terms)) {
				$query = new WP_Query(array(
					'tax_query' => array(
						array(
							'taxonomy' => 'people',
							'field' => 'slug',
							'terms' => 'bob'
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

	function format_terms_to_send($post, $taxonomies, $server_post_type) {
		$mapped_taxonomies = $this->taxonomies_to_map();

		$tax_input = array();

		if (!empty($taxonomies)) {
			// Get posts of all the mapped terms
			$mapped_term_posts = $this->get_server_term_posts($server_post_type);

			foreach ($taxonomies as $tax_name) {
				$tax_input[$tax_name] = array();
				$terms = wp_get_object_terms($post->ID, $tax_name);
				if (is_array($terms) && !empty($terms)) {
					// Taxonomy has been mapped, need to get the slug/term_id thats on the server
					if (in_array($taxonomy, $mapped_taxonomies)) {
						$term_ids = array();
						foreach ($terms as $term) {
							$term_ids[] = $term->term_id;
						}

						$term_objects = get_objects_in_term($term_ids, $taxonomy);

						foreach ($term_objects as $object) {
							if (isset($object->post_type) && $object->post_type == $server_post_type) {
								if (is_taxonomy_hierarchical($taxonomy)) {
									$server_term_id = get_post_meta($object->ID, $this->server_term_id_key, true);
									if ($server_term_id) {
										$tax_input[$tax_name][] = $server_term_id;
									}
								}
								else {
									$server_term_slug = get_post_meta($object->ID, $this->server_term_slug_key, true);
									if ($server_term_slug) {
										$tax_input[$tax_name][] = $server_term_slug;
									}
								}
							}
						}
						
					}
					else {
						foreach ($terms as $term) {
							// check if heirarchical, wp_insert_post handles them differently
							// @TODO server wont know about the term ids!
							$tax_input[$tax_name][] = is_taxonomy_hierarchical($taxonomy) ? $term->term_id : $term->name;
						}
					}
				}
				else {
					// So data gets sent through POST
					$tax_input[$tax_name][] = null;
				}
			}
		}
		return $tax_input;
	}
}

$cap_client = new Capsule_Client;
$cap_client->add_actions();