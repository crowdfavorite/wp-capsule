<?php 

class Capsule_Client_Exporter extends Capsule_Client {

	public function add_actions() {
		add_action('wp_insert_post', array($this,'insert_post'), 10, 2);
	}

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

	function insert_post($post_id, $post) {
	if ((!defined('DOING_AUTOSAVE') || !DOING_AUTOSAVE) && $post->post_status == 'publish') {
		$user_id = get_current_user_id();
		$api_key = cap_client_get_api_key($user_id);
		$endpoint = cap_client_get_endpoint($user_id);

		$postarr = (array) $post;

		$taxonomies = get_object_taxonomies($post->post_type);
		$tax_input = cap_client_get_terms($post, $taxonomies);
		$tax = compact('taxonomies', 'tax_input');

		// @ TODOSend taxonomies as well, as tax_input can be empty
		$this->send_post($postarr, $tax, $api_key, $endpoint);

		}
	}
}

class Capsule_Client_Taxonomy_Mapper extends Capsule_Client {

	public function add_actions() {
		add_action('admin_menu', array($this, 'add_menu_page'));
	}

	function add_menu_page() {
		add_options_page(__('Capsule Term Mapping', 'capsule_client'), __('Capsule Term Mapping', 'capsule_client'), 'manage_options', 'capsule-term-mapping', array($this, 'term_mapping_page'));
	}

	public function get_server_terms() {
		// Query the servers, hits endpoint via request handler - requires API key
		$servers = $this->get_servers();
		foreach	($servers as $server_name => $server_data) {
			$args = array(
				'body' => array(
					'capsule_server_action' => 'get_terms',
					'capsule_client_post_data' => array(
						'api_key' => $server_data['api_key'],
					),
				),
			);
			$request = wp_remote_post($server_data['url'], $args);
			// Check for errors
			if (is_wp_error($request) || $request['response']['code'] != '200') {
				print_r($request);
				die();
				// @TODO Handle this error
			}
			else {
				// Response is serialized string of taxonomies as keys with values of array of terms (ID, name, slug, description)
				$terms = @unserialize($request['body']);
				$this->process_server_terms($terms, $server_name);
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
	 * @param $server_name Name of the server the terms originate from, also used as the post type for lookup purposes
	 */
	public function process_server_terms($terms, $server_name) {
		
		$post_term_array = array();

		$posts = $this->get_server_term_posts($server_name);

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
							'post_type' => $server_name,
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
			wp_delete_post($data['post']->ID);
		}
	}

	/**
	 * Return posts that represent taxonomy terms on a server
	 *
	 * @param string $server_name Name of the server to get the posts for (also the post type)
	 * @return array Empty array or an array of posts for the server name
	 **/
	function get_server_term_posts($server_name) {
		$post_query = new WP_Query(array(
			'posts_per_page' => -1,
			'post_type' => $server_name,
			'post_status' => 'publish',
		));

		if (is_array($post_query->posts)) {
			return $post_query->posts;
		}
		else {
			return array();
		}
	}

	function term_mapping_page() {

		

		$args = array(
			'object_type' => array('post'),
		);
		$taxonomies = get_taxonomies($args);
		print_r($taxonomies);
		// No Need to map post formats
		

		$terms = get_terms($taxonomies, array('hide_empty' => false));
		$tax_terms = array();


		foreach ($terms as $term) {
			// No need to map post formats
			$taxonomy_array[$term->taxonomy][$term->slug] = array(
				'id' => $term->term_id, 
				'name' => $term->name,
				'description' => $term->description,
				'taxonomy' => $term->taxonomy,
			);
		}
?>
<div class="wrap">
	<div id="icon-options-general" class="icon32"></div>
	<h2><?php _e('Capsule Server Term Mappings', 'capsule-client'); ?></h2>
	<div id="cap-servers">
		<form method="post">
<?php 
		$servers = $this->get_servers();
		foreach	($servers as $server_name => $server_data) {
			echo $server_name;
			$posts = $this->get_server_term_posts($server_name);
			foreach ($posts as $post) {
				
			}
		}
?>
			<input type="submit" value="Submit">
			<input type="hidden" name="capsule_client_action" value="get_terms" />
		</form>
	</div>
</div>

<?php 
	}
}

class Capsule_Client {

	static $classname = 'Capsule_Client';

	// @TODO do we really need a prefix? Probably
	var $post_type_prefix = 'cc_';
	var $server_term_id_key = '_cap_server_term_id';
	var $server_term_tax_key = '_cap_server_term_tax';
	var $server_term_slug_key = '_cap_server_term_slug';

	function __construct() {
		//@TODO multisite instance support
		$this->servers_meta_key = '_capsule_servers';
		$this->user_id = get_current_user_id();
	}

	function add_actions() {
		add_action('wp_loaded', array($this, 'request_handler'));
		add_action('init', array($this, 'register_post_types'));
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
	 **/
	function get_servers() {
		$servers = get_user_meta($this->user_id, $this->servers_meta_key, true);

		if (!is_array($servers)) {
			$servers = array();
		}
		return $servers;
	}

	public static function server_markup($label, $url, $api_key) {
?>
	<div>
        <label for="<?php echo esc_attr('label-'.$label); ?>"><?php _e('Server Label', 'capsule-client'); ?>
			<input id="<?php echo esc_attr('label-'.$label); ?>" size="10" name="<?php echo esc_attr('label-'.$label); ?>" value="<?php echo esc_attr($label); ?>" />
    	</label>
             
    	<label for="<?php echo esc_attr('label-'.$url); ?>"><?php _e('Server URL', 'capsule-client'); ?>
			<input id="<?php echo esc_attr('label-'.$url); ?>" size="10" name="<?php echo esc_attr('label-'.$url); ?>" value="<?php echo esc_attr($url); ?>" />
    	</label>

    	<label for="<?php echo esc_attr('label-'.$api_key); ?>"><?php _e('API Key', 'capsule-client'); ?>
			<input id="<?php echo esc_attr('label-'.$api_key); ?>" size="10" name="<?php echo esc_attr('label-'.$api_key); ?>" value="<?php echo esc_attr($api_key); ?>" />
    	</label>

	</div>
<?php
	}

	public static function new_server_markup($label = null, $url = null, $api_key = null) {
?>
	<form method="post">
		<fieldgroup>
			<div>
				<label> <?php _e('Label', 'capsule-client'); ?>
					<input type="text" value="<?php echo esc_attr($label); ?>" name="capsule_server[label]" />
				</label>
			</div>
			<div>
				<label> <?php _e('Server URL', 'capsule-client'); ?>
					<input type="text" value="<?php echo esc_attr($url); ?>" name="capsule_server[url]" />
				</label>
			</div>
			<div>
				<label> <?php _e('API Key', 'capsule-client'); ?>
					<input type="text" value="<?php echo esc_attr($api_key); ?>" name="capsule_server[api_key]" />
				</label>
			</div>
		</fieldgroup>
		<input type="submit" value="<?php _e('Submit', 'capsule-client'); ?>" />
		<input type="hidden" name="capsule_client_action" value="add_server" />
	<form>
<?php 
	}

	public function add_menu_items() {
		add_options_page(__('Capsule Servers', 'capsule_client'), __('Capsule Servers', 'capsule_client'), 'manage_options', 'capsule-servers', array($this, 'servers_page'));
	}

	public function servers_page() {
		$servers = $this->get_servers();
?>
<div class="wrap">
	<div id="icon-plugins" class="icon32"></div>
	<h2><?php _e('Capsule Servers', 'capsule-client'); ?></h2>
	<div id="cap-servers">
<?php
		foreach ($servers as $label => $server) {
			self::server_markup($label, $server['url'], $server['api_key']);
		}
?>
	</div>
<?php
		$this->new_server_markup()
?>
	<a href="#" id="cap-add-new-server"><?php _e('Add New Server', 'capsule-client'); ?></a>
<?php 	
	}

	public function add_server($server) {
		if (!is_array($server)) {
			$server = array();
		}

		$servers = $this->get_servers();

		//@TODO check if server already exists
		if (isset($server['url']) && isset($server['label']) && isset($server['api_key'])) {
			$post_type = $server['label'];
			$url = $server['url'];
			$api_key = $server['api_key'];

			$sanitized_post_type = sanitize_key($post_type);

			// WP enforces 20 character limit, prefix counts for 3.
			if ($sanitized_post_type != $post_type || strlen($sanitized_post_type) > 17) {
				return new WP_Error('post_type', __('Invalid label', 'capsule-client'));
			}

			// Check if already registered
			if (post_type_exists($post_type) || array_key_exists($sanitized_post_type, $servers)) {
				return new WP_Error('post_type_exists', __('Label already exists', 'capsule-client'));
			}

			// Relevant if ajax request?
			register_post_type($post_type, array('public' => false));

			// Register post type for future additions and update user settings.
			$servers[$post_type] = array(
				'url' => $url,
				'api_key' => $api_key,
			);
			update_user_meta($this->user_id, $this->servers_meta_key, $servers);
		}
	}

	public function request_handler() {
		if (isset($_POST['capsule_client_action'])) {
			switch ($_POST['capsule_client_action']) {
				case 'add_server':
					$server = isset($_POST['capsule_server']) ? $_POST['capsule_server'] : array();
					$this->add_server($server);
					break;
				case 'update_server':
					$server = isset($_POST['capsule_server']) ? $_POST['capsule_server'] : array();
					// @TODO get old server data
					$this->update_server($server);
					break;
				case 'get_terms':
					$this->get_server_terms();
					break;
				default:
					break;
			}
		}
	}

	public function register_post_types() {
		$servers = $this->get_servers();
		$args = array(
			'label' => "Server",
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
		foreach ($servers as $server_label => $server_data) {
			register_post_type($server_label, $args);
		}
	}	
}

$cap_client = new Capsule_Client;
$cap_client->add_actions();

$cap_client_tax_mapper = new Capsule_Client_Taxonomy_Mapper;
$cap_client_tax_mapper->add_actions();

$cap_client_exporter = new Capsule_Client_Exporter;
$cap_client_exporter->add_actions();


function cap_client_get_api_key($user_id) {
	return get_user_meta($user_id, '_cap-api-key', true);
}

function cap_client_get_endpoint($user_id) {
	return get_user_meta($user_id, '_cap-endpoint', true);
}

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
function cap_client_send_post($post, $tax, $api_key, $server_url) {
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

function cap_client_user_profile($user_data) {
	$api_key = get_user_meta($user_data->ID, '_cap-api-key', true);
	$api_endpoint = get_user_meta($user_data->ID, '_cap-endpoint', true);
?>
<h3><?php _e('Capsule Server Credentials', 'capsule-client'); ?></h3>
<table class="form-table">
	<tr id="capsule-api-key">
		<th><label for="cap-api-key"><?php _e('Capsule API Key', 'capsule-client'); ?></label></th>
		<td><input type="text" name="cap-api-key" id="cap-api-key" size="35" value="<?php echo esc_attr($api_key); ?>" />
		</td>
	</tr>
	<tr id="capsule-endpoint">
		<th><label for="cap-endpoint"><?php _e('Capsule API Endpoint', 'capsule-client'); ?></label></th>
		<td><input type="text" name="cap-endpoint" id="cap-endpoint" size="35" value="<?php echo $api_endpoint; ?>" />
		</td>
	</tr>
	<input type="hidden" name="cap-server-credentials-update" value="1" />
</table>
<?php 
}
add_action('show_user_profile', 'cap_client_user_profile');

function cap_client_user_profile_update($user_id) {
	// Check for capabilities and nonce done prior to this hook being fired
	if (isset($_POST['cap-server-credentials-update'])) {
		update_user_meta($user_id, '_cap-api-key', $_POST['cap-api-key']);
		update_user_meta($user_id, '_cap-endpoint', $_POST['cap-endpoint']);
	}
}
add_action('personal_options_update', 'cap_client_user_profile_update');

function cap_client_insert_post($post_id, $post) {
	if ((!defined('DOING_AUTOSAVE') || !DOING_AUTOSAVE) && $post->post_status == 'publish') {
		$user_id = get_current_user_id();
		$api_key = cap_client_get_api_key($user_id);
		$endpoint = cap_client_get_endpoint($user_id);

		$postarr = (array) $post;

		$taxonomies = get_object_taxonomies($post->post_type);
		$tax_input = cap_client_get_terms($post, $taxonomies);
		$tax = compact('taxonomies', 'tax_input');

		// @ TODOSend taxonomies as well, as tax_input can be empty

		cap_client_send_post($postarr, $tax, $api_key, $endpoint);

	}
}
add_action('wp_insert_post', 'cap_client_insert_post', 10, 2);

function cap_client_get_terms($post, $taxonomies) {
	$tax_input = array();
	
	if (!empty($taxonomies)) {
		foreach ($taxonomies as $tax_name) {
			$tax_input[$tax_name] = array();
			$terms = wp_get_object_terms($post->ID, $tax_name, array('fields' => 'names'));
			if (is_array($terms) && !empty($terms)) {
				foreach ($terms as $term_name) {
					$tax_input[$tax_name][] = $term_name;
				}
			}
			else {
				// So data gets sent through POST
				$tax_input[$tax_name][] = null;
			}
		}
	}
	//error_log('tax_input '.print_r($tax_input,1));
	return $tax_input;
}
