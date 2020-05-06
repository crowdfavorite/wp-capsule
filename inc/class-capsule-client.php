<?php

/**
 * Capsule client implementation.
 *
 * @package capsule
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */

namespace CrowdFavorite\Capsule;

/**
 * Capsule Client class.
 *
 * Class here is mostly for namespacing of methods and properties
 */
class CapsuleClient
{
	/**
	 * Post type prefix.
	 *
	 * @var string $post_type_prefix
	 */
	private $post_type_prefix = '_cc_';

	/**
	 * Server term id key.
	 *
	 * @var string $server_term_id_key
	 */
	private $server_term_id_key = '_cap_server_term_id';

	/**
	 * Server term taxonomy key.
	 *
	 * @var string $server_term_tax_key
	 */
	private $server_term_tax_key = '_cap_server_term_tax';

	/**
	 * Server term slug key.
	 *
	 * @var string $server_term_slug_key
	 */
	private $server_term_slug_key = '_cap_server_term_slug';

	/**
	 * Server API key.
	 *
	 * @var string $server_api_key
	 */
	public $server_api_key = '_cap_server_api_key';

	/**
	 * Server URL key.
	 *
	 * @var string $server_term_id_key
	 */
	public $server_url_key = '_cap_server_url';

	private const MAX_SERVERS = 50;

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		// Used for debugging, add this constant to your local-config or wp-config.php to override.
		// Shows hidden taxonomies and post types.
		if (! defined('CAP_CLIENT_DEBUG')) {
			define('CAP_CLIENT_DEBUG', false);
		}
	}

	/**
	 * Action hooks.
	 *
	 * @return void
	 */
	public function add_actions()
	{
		// Come in after post types and taxonomies registered.
		add_action('wp_loaded', array( $this, 'request_handler' ));

		// Priority 11 to come after taxonomy registration.
		add_action('init', array( $this, 'register_post_types' ), 11);
		add_action('init', array( $this, 'wp_cron' ));
		add_action('admin_menu', array( $this, 'add_menu_pages' ));

		add_action('wp_insert_post', array( $this, 'insert_post' ), 10, 2);
		add_action('admin_notices', array( $this, 'capsule_admin_notice' ));

		add_action('admin_enqueue_scripts', array( $this, 'admin_scripts_and_styles' ));
	}

	/**
	 * Cron jobs.
	 *
	 * @return void
	 */
	public function wp_cron()
	{
		if (! wp_next_scheduled('capsule_queue')) {
			wp_schedule_event(time(), 'hourly', 'capsule_queue');
		}
		add_action('capsule_queue', 'capsule_queue_run');
	}

	/**
	 * Admin scripts and styles.
	 *
	 * @param string $hook Current page.
	 * @return void
	 */
	public function admin_scripts_and_styles($hook)
	{
		wp_enqueue_style(
			'capsule-admin',
			get_template_directory_uri() . '/assets/css/admin.css',
			false,
			'20180215.1845'
		);

		if ('capsule_page_capsule-servers' === $hook) {
			wp_register_script(
				'server-management',
				get_template_directory_uri() . '/assets/js/server-management.js',
				array( 'jquery' ),
				'20180216.1830'
			);
			wp_localize_script(
				'server-management',
				'CapsuleServerManagementSettings',
				array(
					'errorPrefix' => esc_html__('Error: ', 'capsule'),
				)
			);
			wp_enqueue_script('server-management');
		}
	}

	/**
	 * Handles all client actions including those coming in via ajax
	 *
	 * @return void
	 */
	public function request_handler()
	{
		$action = ( ! empty($_REQUEST['capsule_client_action']) ) ? $_REQUEST['capsule_client_action'] : ''; //phpcs:ignore
		if (empty($action)) {
			return;
		}

		switch ($action) {
			case 'add_server':
				$nonce = filter_input(INPUT_POST, '_server_nonce');

				if (empty($nonce) || ! wp_verify_nonce($nonce, '_cap_client_server_management')) {
					break;
				}
				$server_name = filter_input(INPUT_POST, 'server_name', FILTER_SANITIZE_STRING);
				$api_key     = filter_input(INPUT_POST, 'api_key', FILTER_SANITIZE_STRING);
				$server_url  = filter_input(INPUT_POST, 'server_url', FILTER_SANITIZE_URL);

				$server_data = array(
					'server_name' => $server_name,
					'api_key'     => $api_key,
					'server_url'  => $server_url,
				);

				$this->add_server($server_data);
				break;

			case 'add_server_ajax':
				$nonce = filter_input(INPUT_POST, '_server_nonce');

				$errors = array();

				if (! empty($nonce) && wp_verify_nonce($nonce, '_cap_client_server_management')) {
					$server_name = filter_input(INPUT_POST, 'server_name', FILTER_SANITIZE_STRING);
					$api_key     = filter_input(INPUT_POST, 'server_api_key', FILTER_SANITIZE_STRING);
					$server_url  = filter_input(INPUT_POST, 'server_url', FILTER_SANITIZE_URL);

					$server_data = array(
						'server_name' => $server_name,
						'api_key'     => $api_key,
						'server_url'  => $server_url,
					);

					$test_errors       = $this->test_credentials($api_key, $server_url);
					$duplicate_errors  = $this->duplicate_server_check($server_name, $server_url);
					$validation_errors = array_merge($test_errors, $duplicate_errors);

					if (empty($validation_errors)) {
						$post = $this->add_server($server_data);
						ob_start();
						$this->server_row_markup($post, '');
						$html = ob_get_clean();
						wp_send_json(array(
							'result' => 'success',
							'html'   => $html,
						));
					} else {
						$errors = $validation_errors;
					}
				} else {
					$errors[] = array(
						'message' => __('Could not save server data', 'capsule'),
						'type'    => 'general',
					);
				}
				$results = array(
					'result' => 'error',
					'errors' => $errors,
					'data'   => array(
						'name'    => $server_data['server_name'],
						'api_key' => $server_data['api_key'],
						'url'     => $server_data['server_url'],
					),
				);

				// Something didn't go quite right.
				wp_send_json($results);

				break;

			// Delete server handled slightly differently, with a link and GET params.
			case 'delete_server':
				$nonce = filter_input(INPUT_POST, '_wpnonce');
				if (empty($nonce)) {
					$nonce = filter_input(INPUT_GET, '_wpnonce');
				}
				if (! wp_verify_nonce($nonce, '_cap_client_delete_server')) {
					break;
				}
				$server_id  = filter_input(INPUT_GET, 'server_id', FILTER_SANITIZE_NUMBER_INT);
				$doing_ajax = filter_input(INPUT_GET, 'doing_ajax');

				$result = $this->delete_server($server_id);
				if (empty($doing_ajax)) {
					break;
				}

				$result = ( false !== $result ) ? 'success' : 'error';
				wp_send_json(array( 'result' => $result ));

				break;

			case 'update_server_ajax':
				$args                = array();
				$args['server_name'] = filter_input(INPUT_POST, 'server_name', FILTER_SANITIZE_STRING);
				$args['api_key']     = filter_input(INPUT_POST, 'api_key', FILTER_SANITIZE_STRING);
				$args['server_id']   = filter_input(INPUT_POST, 'server_id', FILTER_SANITIZE_NUMBER_INT);
				$args['server_url']  = filter_input(INPUT_POST, 'server_url', FILTER_SANITIZE_URL);
				$args['nonce']       = filter_input(INPUT_POST, '_server_nonce');
				$this->process_update_server_info($args);
				break;

			// Non ajax way, have to update all servers.
			case 'update_servers':
				$nonce = filter_input(INPUT_POST, '_server_nonce');
				if (empty($nonce) || ! wp_verify_nonce($nonce, '_cap_client_server_management')) {
					break;
				}
				$servers = isset($_POST['servers']) ? $_POST['servers'] : array();
				if (! empty($servers)) {
					foreach ($servers as $server_id => $server_data) {
						$this->update_server($server_id, $server_data);
					}
				}
				break;

			case 'save_mapping':
				$nonce = filter_input(INPUT_POST, '_save_mapping_nonce');
				if (! empty($nonce) && wp_verify_nonce($nonce, '_cap_client_save_mapping')) {
					$mapping = ( ! empty($_POST['cap_client_mapping']) ) ? $_POST['cap_client_mapping'] : array();
					if (! empty($mapping)) {
						$this->save_mapping($mapping);
					}
				}
				break;

			case 'another_project_mapping_ajax':
				$post_id  = (int) filter_input(INPUT_POST, 'post_id', FILTER_SANITIZE_NUMBER_INT);
				$taxonomy = filter_input(INPUT_POST, 'taxonomy', FILTER_SANITIZE_STRING);
				if ($post_id > 0 && ! empty($taxonomy)) {
					$post = get_post($post_id);
					if (empty($post)) {
						die(esc_html__('Invalid post', 'capsule'));
					}
					$terms = $this->get_taxonomy_terms($taxonomy);

					$this->term_select_markup($post, $taxonomy, $terms, 0);
				}
				die();

			default:
				break;
		}
	}

	/**
	 * Process server info update request (on AJAX).
	 *
	 * @param array $args Request args.
	 * @return void
	 */
	private function process_update_server_info($args)
	{
		$errors = array();
		if (empty($args['nonce']) || ! wp_verify_nonce($args['nonce'], '_cap_client_server_management')) {
			wp_send_json(array(
				'result' => 'error',
				'errors' => array(
					array(
						'message' => esc_html__('Invalid request.', 'capsule'),
						'type'    => 'general',
					),
				),
				'data'   => array(
					'name'    => $args['server_name'],
					'api_key' => $args['api_key'],
					'url'     => $args['server_url'],
				),
			));
		}
		$server_id = (int) $args['server_id'];
		if ($server_id > 0) {
			$test_errors       = $this->test_credentials($args['api_key'], $args['server_url']);
			$duplicate_errors  = $this->duplicate_server_check($args['server_name'], $args['server_url'], $server_id);
			$validation_errors = array_merge($test_errors, $duplicate_errors);

			if (empty($validation_errors)) {
				$server = $this->update_server($server_id, $args);
				if ($server) {
					wp_send_json(array(
						'data'   => array(
							'name'    => $server->post_title,
							'api_key' => $server->api_key,
							'url'     => $server->url,
						),
						'result' => 'success',
					));
				} else {
					$errors[] = array(
						'message' => esc_html__('Could not save server.', 'capsule'),
						'type'    => 'general',
					);
				}
			} else {
				$errors = $validation_errors;
			}
		} else {
			$errors[] = array(
				'message' => esc_html__('No server ID found.', 'capsule'),
				'type'    => 'general',
			);
		}

		wp_send_json(array(
			'result' => 'error',
			'errors' => $errors,
			'data'   => array(
				'name'    => $args['server_name'],
				'api_key' => $args['api_key'],
				'url'     => $args['server_url'],
			),
		));
	}

	/**
	 * Return all terms in a group of taxonomies
	 *
	 * @param string|array $taxonomies Taxonomy name or list of Taxonomy names.
	 * @return array                   Array of term objects.
	 **/
	private function get_taxonomy_terms($taxonomies)
	{
		return get_terms($taxonomies, array(
			'hide_empty' => false,
			'orderby'    => 'slug',
			'order'      => 'ASC',
		));
	}

	/**
	 * Get a list of servers
	 * Returns an array of servers with the key as the post type slug:
	 * array(
	 *    'server-1' => array(
	 *        'api-key' => '12345abc',
	 *        'url' => 'http://capsule-server-1.com';
	 *    ),
	 *    'server-2' => array(
	 *        'api-key' => 'A4d*DYnohiO',
	 *        'url' => 'http://capsule-server-2.com';
	 *    )
	 * )
	 *
	 * Potential feature - Filter servers based on owner for multi-user support
	 **/
	public function get_servers()
	{
		$query = new \WP_Query(array(
			'post_type'      => 'server',
			'posts_per_page' => self::MAX_SERVERS,
			'order'          => 'ASC',
			'orderby'        => 'name',
		));

		$servers = array();
		if ($query->have_posts()) {
			foreach ($query->posts as $post) {
				$servers[] = $this->process_server($post);
			}
		}

		return $servers;
	}

	/**
	 * Process server data.
	 *
	 * @param object|integer $server Post object or post id.
	 * @return object|null Post object with additional API key and URL. Return null if the server does not exist.
	 */
	public function process_server($server)
	{
		if (! is_object($server)) {
			$server = get_post($server);
			if (is_null($server)) {
				return null;
			}
		}

		$api_key         = get_post_meta($server->ID, $this->server_api_key, true);
		$url             = get_post_meta($server->ID, $this->server_url_key, true);
		$server->api_key = $api_key;
		$server->url     = $url;
		return $server;
	}

	/**
	 * Get the post type name thats generated from a post name.
	 * Note this does not return the post type of the post, but
	 * the post type which was generated from/for the post.
	 *
	 * @param string $post_name Post name.
	 * @return string           Post slug.
	 */
	public function post_type_slug($post_name)
	{
		// 20 Character limit on post type names.
		return substr(sha1($this->post_type_prefix . $post_name), 0, 20);
	}

	/**
	 * Registers required post types. These include the server post type
	 * and one post type for each server which stores the term mappings
	 **/
	public function register_post_types()
	{
		// Register the server type.
		$default_args = array(
			'public'             => CAP_CLIENT_DEBUG,
			'publicly_queryable' => true,
			'show_ui'            => false,
			'show_in_menu'       => false,
			'query_var'          => true,
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
		);

		$args          = $default_args;
		$args['label'] = __('Servers', 'capsule');

		register_post_type('server', $args);

		$servers = $this->get_servers();
		$args    = array_merge($default_args, array(
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
	public function taxonomies_to_map()
	{
		$taxonomies = array(
			'projects',
		);

		return apply_filters('capsule_client_taxonomies_to_map', $taxonomies);
	}

	/**
	 * Menu pages.
	 *
	 * @return void
	 */
	public function add_menu_pages()
	{
		global $menu;
		$menu['3'] = array( '', 'read', 'separator-capsule', '', 'wp-menu-separator' );
		add_menu_page(
			__('Capsule', 'capsule'),
			__('Capsule', 'capsule'),
			'read',
			'capsule',
			array( $this, 'capsule_help' ),
			'',
			'3.1'
		);
		// Needed to make separator show up.
		ksort($menu);
		add_submenu_page(
			'capsule',
			__('Servers', 'capsule'),
			__('Servers', 'capsule'),
			'manage_options',
			'capsule-servers',
			array( $this, 'server_management_page' )
		);
		add_submenu_page(
			'capsule',
			__('Projects', 'capsule'),
			__('Projects', 'capsule'),
			'manage_options',
			'capsule-projects',
			array( $this, 'term_mapping_page' )
		);
	}

	/**
	 * Display help page.
	 *
	 * @return void
	 */
	public function capsule_help()
	{
		include get_template_directory() . '/inc/help.php';
	}

	/**
	 * Display admin notice.
	 *
	 * @return void
	 */
	public function capsule_admin_notice()
	{
		$page = filter_input(INPUT_GET, 'page');
		if (empty($page) || 'capsule' !== $page) {
			return;
		}
		?>
<section class="capsule-welcome">
	<h1><?php esc_html_e('Welcome to Capsule', 'capsule'); ?></h1>
	<p>
		<?php
		echo wp_kses_post(
			sprintf(
				// Translators: %s is the Capsule admin page URL.
				__('Please read the overview, FAQs and more about <a href="%s">how Capsule works</a>.', 'capsule'),
				esc_url(admin_url('admin.php?page=capsule'))
			)
		);
		?>
	</p>
</section>
		<?php
	}

	/**
	 * Manage remote servers.
	 *
	 * @return void
	 */
	public function server_management_page()
	{
		$servers = $this->get_servers();
		?>
		<div class="wrap capsule-admin">
			<div id="icon-options-general" class="icon32"></div>
			<h2><?php esc_html_e('Capsule: Servers', 'capsule'); ?></h2>
			<p class="description">
			<?php
				echo wp_kses_post(
					sprintf(
						// Translators: %s is the Capsule admin page URL.
						__(
							'Connect to one or more Capsule Servers to replicate selected content to those servers.
							<a href="%s">Learn More</a>',
							'capsule'
						),
						esc_url(admin_url('admin.php?page=capsule'))
					)
				);
			?>
			</p>
			<div id="cap-servers">
				<form method="post" id="js-cap-servers">
					<table class="wp-list-table widefat fixed posts">
						<thead>
							<tr>
								<th scope="col" class="manage-column column-label" width="15%">
									<?php esc_html_e('Server Name', 'capsule'); ?>
								</th>
								<th scope="col" class="manage-column column-api-key" width="35%">
									<?php esc_html_e('Server URL', 'capsule'); ?>
								</th>
								<th scope="col" class="manage-column column-api-key" width="40%">
									<?php esc_html_e('Server API Key', 'capsule'); ?>
								</th>
								<th scope="col" class="manage-column column-actions" width="10%">
									&nbsp;
								</th>
							</tr>
						</thead>
						<tbody>
						<?php
						$class = '';
						foreach ($servers as $server_post) {
							$class = ( '' === $class ) ? ' alternate' : '';
							$this->server_row_markup($server_post, $class);
						}
						$class = ( '' === $class ) ? ' alternate' : '';
						?>
						<tr id="js-server-item-new" class="<?php echo esc_attr($class); ?>">
							<td>
								<div>
									<input
										type="text"
										class="widefat js-cap-server-name"
										name="server_name"
										value=""
										placeholder="<?php esc_html_e('Server Name', 'capsule'); ?>"
									/>
								</div>
							</td>
							<td>
								<div>
									<input
										type="text"
										class="widefat js-cap-server-url"
										name="server_url"
										value=""
										placeholder="<?php esc_html_e('Server URL', 'capsule'); ?>"
									/>
								</div>
							</td>
							<td>
								<div>
									<input
										type="text"
										class="widefat js-cap-server-api-key"
										name="server_api_key"
										value=""
										placeholder="<?php esc_html_e('API Key', 'capsule'); ?>"
									/>
								</div>
							</td>
							<td>
								<div>
									<input
										type="submit"
										class="js-cap-add capsule-float-left button"
										value="<?php esc_html_e('Add Server', 'capsule'); ?>"
									/>
									<span class="capsule-float-left capsule-spinner"></span>
									<input type="hidden" value="add_server" name="capsule_client_action" />
									<?php
									wp_nonce_field(
										'_cap_client_server_management',
										'_server_nonce',
										true,
										true
									);
									?>
								</div>
							</td>
						</tr>
						</tbody>
					</table>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Markup for a 'row' representing a server.
	 *
	 * @param object $server_post Normal WP Post object, has api_key and url properties if set @see get_servers()).
	 * @param string $class       Additional class to put on the wrapper for the row.
	 *
	 * @return void
	 **/
	public function server_row_markup($server_post, $class = '')
	{
		$delete_url = add_query_arg('capsule_client_action', 'delete_server', admin_url());
		$delete_url = add_query_arg('server_id', $server_post->ID, $delete_url);
		// wp_nonce_url does esc_html.
		$delete_url = wp_nonce_url($delete_url, '_cap_client_delete_server');
		$name_base  = 'servers[' . $server_post->ID . ']';
		$class_name = 'js-static-server-name-' . $server_post->ID;
		$class_url = 'js-static-server-url-' . $server_post->ID;
		$class_api = 'js-static-server-api-' . $server_post->ID;
		?>
		<tr
			id="<?php echo esc_attr('js-server-item-' . $server_post->ID); ?>"
			class="<?php echo esc_attr('server-item' . $class); ?>"
		>
			<td>
				<div class="js-cap-not-editable cap-not-editable <?php echo esc_attr($class_name); ?>">
					<?php echo esc_html($server_post->post_title); ?>
				</div>
				<div class="js-cap-editable">
					<input type="text" class="widefat js-cap-editable cap-editable js-cap-server-name"
						id="<?php echo esc_attr('js-server-name-' . $server_post->ID); ?>"
						name="<?php echo esc_attr($name_base) . '[server_name]'; ?>"
						value="<?php echo esc_attr($server_post->post_title); ?>" />
				</div>
			</td>
			<td>
				<div class="js-cap-not-editable cap-not-editable <?php echo esc_attr($class_url); ?>">
					<?php echo esc_html($server_post->url); ?>
				</div>
				<div class="js-cap-editable">
					<input type="text" class="widefat js-cap-editable cap-editable js-cap-server-url"
						id="<?php echo esc_attr('js-server-url-' . $server_post->ID); ?>"
						name="<?php echo esc_attr($name_base) . '[server_url]'; ?>"
						value="<?php echo esc_attr($server_post->url); ?>" />
				</div>
			</td>
			<td>
				<div class="cap-api-key js-cap-not-editable cap-not-editable <?php echo esc_attr($class_api); ?>">
					<?php echo esc_html($server_post->api_key); ?>
				</div>
				<div class="js-cap-editable cap-editable">
					<input type="text" class="widefat js-cap-editable js-cap-server-api-key"
						id="<?php echo esc_attr('js-server-api_key-' . $server_post->ID); ?>"
						name="<?php echo esc_attr($name_base) . '[api_key]'; ?>"
						value="<?php echo esc_attr($server_post->api_key); ?>" />
				</div>
			</td>
			<td>
				<div class="js-cap-not-editable cap-not-editable cap-edit-server-actions">
					<a
						href="#"
						class="js-cap-edit-server"
						data-server_id="<?php echo (int) $server_post->ID; ?>"
					>
						<?php esc_html_e('Edit', 'capsule'); ?>
					</a> |
					<a href="<?php echo esc_url_raw($delete_url); ?>"
						data-server_id="<?php echo (int) $server_post->ID; ?>"
						class="delete js-server-delete cap-delete"><?php esc_html_e('Delete', 'capsule'); ?></a>
				</div>
				<div class="js-cap-editable cap-editable">
					<a href="#" class="capsule-float-left js-cap-save-server button-primary"
						data-server_id="<?php echo (int) $server_post->ID; ?>"><?php esc_html_e('Save', 'capsule'); ?>
					</a><span class="capsule-float-left capsule-spinner"></span>
				</div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Create a new server based on data.
	 *
	 * @param array $data Array of data, see defaults for potential values.
	 * @return bool True if server was added, false otherwise.
	 **/
	public function add_server($data)
	{
		$defaults = array(
			'server_name' => null,
			'api_key'     => null,
			'server_url'  => null,
		);

		$postarr   = wp_parse_args($data, $defaults);
		$post_data = $postarr;

		$post_arr = array(
			'post_title'  => $post_data['server_name'],
			'post_type'   => 'server',
			'post_status' => 'publish',
		);

		$post_id = wp_insert_post($post_arr);
		if ($post_id) {
			update_post_meta($post_id, $this->server_api_key, $post_data['api_key']);
			update_post_meta($post_id, $this->server_url_key, $post_data['server_url']);
			$post          = get_post($post_id);
			$post->api_key = $post_data['api_key'];
			$post->url     = $post_data['server_url'];

			return $post;
		}

		return false;
	}

	/**
	 * Delete a server post.
	 *
	 * @param integer $server_id ID of the post to delete.
	 * @return mixed False on failure (from wp_delete_post)
	 **/
	public function delete_server($server_id)
	{
		return wp_delete_post($server_id, true);
	}

	/**
	 * Update a server with relevant data. Does not create it if it doesn't exist.
	 *
	 * @param int   $server_id Post ID of the server to update.
	 * @param array $data      Array of data. Possible values include:
	 *                  'server_name' => post_title (label for server)
	 *                  'api_key' => Server's api key, stored in post meta
	 *                  'server_url' => Server's url, stored in post meta.
	 * @return object|false Return the updated server or false on error.
	 */
	public function update_server($server_id, $data)
	{
		$post = get_post($server_id);
		if (! $post) {
			return false;
		}

		$post_arr = array(
			'ID' => $server_id,
		);
		if (isset($data['server_name'])) {
			$post_arr['post_title'] = $data['server_name'];
		}
		$result = wp_update_post($post_arr);
		if ($result && ! is_wp_error($result)) {
			if (isset($data['api_key'])) {
				update_post_meta($server_id, $this->server_api_key, $data['api_key']);
			}
			if (isset($data['server_url'])) {
				update_post_meta($server_id, $this->server_url_key, $data['server_url']);
			}
			$server = $this->process_server($server_id);
			return $server;
		}

		return false;
	}

	/**
	 * Check if the server already esists.
	 *
	 * @param string  $server_name Server name.
	 * @param string  $server_url  Server url.
	 * @param integer $server_id   Server id.
	 * @return array               Array of errors if server exists. Empty array if server is new.
	 */
	public function duplicate_server_check($server_name, $server_url, $server_id = 0)
	{
		$errors = array();

		$all_servers = $this->get_servers();

		if (is_array($all_servers)) {
			foreach ($all_servers as $server) {
				// Dont check against self.
				if ((int) $server->ID === (int) $server_id) {
					continue;
				}
				if (
					strtolower(trim($server->url, ' \t\n\r\0\x0B/')) === strtolower(trim($server_url, ' \t\n\r\0\x0B/'))
				) {
					$errors['url'] = array(
						'message' => __('Duplicate server url.', 'capsule'),
						'type'    => 'url',
					);
				}
				if (trim($server->post_title) === trim($server_name)) {
					$errors['name'] = array(
						'message' => __('Duplicate server name.', 'capsule'),
						'type'    => 'name',
					);
				}
			}
		}

		return $errors;
	}

	/**
	 * Test credentials.
	 *
	 * @param string $api_key API key.
	 * @param string $url     Server url.
	 * @return array          Array of errors.
	 */
	public function test_credentials($api_key, $url)
	{
		$errors = array();

		$args = array(
			'body'      => array(
				'capsule_server_action'    => 'test_credentials',
				'capsule_client_post_data' => array(
					'api_key' => $api_key,
				),
			),
			'sslverify' => false,
		);

		$request = wp_safe_remote_post($url, $args);
		// Check for errors.
		if (is_wp_error($request)) {
			foreach ($request->errors as $key => $wp_errors) {
				foreach ($wp_errors as $error) {
					$errors[] = array(
						'message' => $error,
						'type'    => 'url',
					);
				}
			}
		} elseif (401 === $request['response']['code']) {
			$errors[] = array(
				// Translators: %s is the API key.
				'message' => sprintf(__('Unauthorized using the api key \'<em>%s</em>\'.', 'capsule'), $api_key),
				'type'    => 'credentials',
			);
		} elseif (200 !== (int) $request['response']['code']) {
			$errors[] = array(
				// Translators: %1$s is the response code, %2$s is the error message.
				'message' => sprintf(
					__('Server said "%1$s : %2$s".', 'capsule'),
					$request['response']['code'],
					$request['response']['message']
				),
				'type'    => 'url',
			);
		} elseif ('authorized' !== $request['body']) {
			// Request successful, should return 'authorized'.
			$errors[] = array(
				'message' => __('Server theme not active', 'capsule'),
				'type'    => 'url',
			);
		}

		return $errors;
	}

	/*** Taxonomy Mapping ***/

	/**
	 * Fetch terms from the capsule server to be mapped locally.
	 * Passes taxonomies to get terms from and API key to validate against
	 *
	 * @return true|array True if everything went smoothly, array of errors with server post key as the id
	 **/
	public function get_server_terms()
	{
		// Query the servers, hits endpoint via request handler - requires API key.
		$errors  = array();
		$servers = $this->get_servers();
		foreach ($servers as $server_post) {
			$args = array(
				'body'      => array(
					'capsule_server_action'    => 'get_terms',
					'capsule_client_post_data' => array(
						'api_key'    => $server_post->api_key,
						'taxonomies' => $this->taxonomies_to_map(),
					),
				),
				'sslverify' => false,
				'timeout'   => 30,
			);

			$request = wp_safe_remote_post($server_post->url, $args);
			// Check for errors.
			if (is_wp_error($request)) {
				foreach ($request->errors as $key => $wp_errors) {
					foreach ($wp_errors as $error) {
						$errors[ $server_post->ID ][] = $error;
					}
				}
			} elseif (200 !== (int) $request['response']['code']) {
				$errors[ $server_post->ID ][] = sprintf(
					// Translators: %1$s is the response code, %2$s is the error message.
					__(
						'Server said: "%1$s:%2$s" Please check the server credentials and connectivity and try again.',
						'capsule'
					),
					$request['response']['code'],
					$request['response']['message']
				);
			} else {
				// Response is an object with taxonomies as keys
				// with values of objects with terms (ID, name, slug, description).
				$terms = json_decode($request['body']);
				$this->process_server_terms((array) $terms, $this->post_type_slug($server_post->post_name));
			}
		}

		return empty($errors) ? true : $errors;
	}

	/**
	 * Create new posts in the server's post type which corresponds to a term in the $terms array.
	 * It updates any posts in which there are differences such as the term name or term description,
	 * it also removes any post associations which are not found in the $terms array.
	 *
	 * @param array  $terms Array of terms which will be mapped to posts or update data in the post.
	 * Format:
	 * array(
	 *    'taxonomy_1' => array(
	 *        'term-slug' => array(
	 *            'id' => 1,
	 *            'name' => 'Amazing Term',
	 *            'description' => 'This term is amazing AND useful',
	 *    ),
	 *    'term-slug-2' ...
	 *    ),
	 *    'taxonomy_2' ....
	 *    )
	 * ).
	 * @param string $server_post_type Post type to associate terms with.
	 * @return void
	 */
	public function process_server_terms($terms, $server_post_type)
	{
		$post_term_array = array();

		$posts = $this->get_server_term_posts($server_post_type);

		foreach ($posts as $post) {
			$term_id       = get_post_meta($post->ID, $this->server_term_id_key, true);
			$term_taxonomy = get_post_meta($post->ID, $this->server_term_tax_key, true);
			$term_slug     = get_post_meta($post->ID, $this->server_term_slug_key, true);
			// Setup post data a little better to be processed by the terms
			// name, description here to tell wether or not the post needs an update
			// Need to include taxonomy in term key in case multiple taxonomies use the same term.
			$post_term_array[ $term_taxonomy . '_' . $term_id ] = array(
				'post'             => $post,
				'term_taxonomy'    => $term_taxonomy,
				'term_name'        => $post->post_title,
				'term_description' => $post->post_content,
				'term_slug'        => $term_slug,
			);
		}

		if (is_array($terms)) {
			foreach ($terms as $taxonomy => $terms) {
				foreach ($terms as $term_slug => $term) {
					$term = (array) $term;
					// array key matches above @TODO trim?
					$array_key = $term['taxonomy'] . '_' . $term['id'];

					if (isset($post_term_array[ $array_key ])) {
						// Check for any differences, if there is update as necessary.
						$existing_data = $post_term_array[ $array_key ];
						$post_id       = $existing_data['post']->ID;
						if (
							$existing_data['term_name'] !== $term['name'] ||
							$existing_data['term_description'] !== $term['description']
						) {
							$existing_data['post']->post_title   = $term['name'];
							$existing_data['post']->post_content = $term['description'];
							wp_update_post($existing_data['post']);
						}
						if ($existing_data['term_taxonomy'] !== $term['taxonomy']) {
							update_post_meta($post_id, $this->server_term_tax_key, $term['taxonomy']);
						}

						if ($existing_data['term_slug'] !== $term_slug) {
							update_post_meta($post_id, $this->server_term_slug_key, $term_slug);
						}
					} else {
						// Create post, it doesnt exist.
						$post_id = wp_insert_post(array(
							'post_type'    => $server_post_type,
							'post_content' => $term['description'],
							'post_title'   => $term['name'],
							'post_status'  => 'publish',
						));
						if ($post_id) {
							update_post_meta($post_id, $this->server_term_id_key, $term['id']);
							update_post_meta($post_id, $this->server_term_tax_key, $term['taxonomy']);
							update_post_meta($post_id, $this->server_term_slug_key, $term_slug);
						}
					}

					// Unset to know which posts no longer have terms associated with them and will be deleted.
					unset($post_term_array[ $array_key ]);
				}
			}
		}

		// All terms that have come over have been processed and unset from post_term_array
		// meaning the remaining posts no longer have a corresponding term on the server.
		foreach ($post_term_array as $data) {
			wp_delete_post($data['post']->ID, true);
		}
	}

	/**
	 * Return posts that represent taxonomy terms on a server
	 *
	 * @param string $server_post_type Post type to use.
	 * @param array  $args             Array of WP_Query args.
	 * @return array                   Empty array or an array of posts.
	 **/
	private function get_server_term_posts($server_post_type, $args = array())
	{
		$defaults = array(
			'posts_per_page' => -1,
			'post_type'      => $server_post_type,
			'post_status'    => 'publish',
			'orderby'        => 'name',
			'order'          => 'ASC',
		);

		$query_args = array_merge($defaults, $args);
		$post_query = new \WP_Query($query_args);

		if (is_array($post_query->posts)) {
			return $post_query->posts;
		} else {
			return array();
		}
	}

	/**
	 * Display term mapping errors.
	 *
	 * @param array   $errors  Array of errors.
	 * @param integer $post_id Post id.
	 * @return void
	 */
	private function show_term_mapping_errors($errors, $post_id)
	{
		if (empty($errors[ $post_id ])) {
			return;
		}
		?>
		<div class="capsule-error">
		<?php foreach ($errors[ $post_id ] as $error_message) : ?>
			<p><?php echo esc_html($error_message); ?></p>
		<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Markup and logic for the term mapping page
	 **/
	public function term_mapping_page()
	{
		// Fetch server terms on each page load.
		// @TODO check what happens when disconnected.
		$errors = $this->get_server_terms();

		// Get all the terms in taxonomies are mapped and sort them by taxonomy
		// for easier displaying later.
		$taxonomies     = $this->taxonomies_to_map();
		$terms          = $this->get_taxonomy_terms($taxonomies);
		$taxonomy_array = [
			'projects' => []
		];

		if (is_array($terms)) {
			foreach ($terms as $term) {
				$taxonomy_array[ $term->taxonomy ][] = $term;
			}
		}
		$servers = $this->get_servers();
		?>
<div class="wrap capsule-admin">
	<div id="icon-options-general" class="icon32"></div>
	<h2><?php esc_html_e('Capsule: Server Projects', 'capsule'); ?></h2>
	<p class="description">
		<?php
		echo wp_kses_post(
			sprintf(
				// Translators: %s is the Capsule admin page URL.
				__(
					'When you map a local project to one on a server project,
					all posts related to that project will be replicated to that server.
					<a href="%s">Learn More</a>',
					'capsule'
				),
				esc_url(admin_url('admin.php?page=capsule'))
			)
		);
		?>
	</p>
	<form method="post">
		<?php foreach ($servers as $server_post) : ?>
		<h3><?php echo esc_html($server_post->post_title); ?></h3>
			<?php
			$this->show_term_mapping_errors($errors, $server_post->ID);
			// Default capsule functionality only includes projects here,
			// but support for more taxonomies is present.
			?>
			<?php foreach ($taxonomies as $taxonomy) : ?>
				<?php
				$this->term_mapping_display_taxonomy_terms($taxonomy, $taxonomy_array, $server_post);
				?>
			<?php endforeach; ?>
		<?php endforeach; ?>
		<p>
			<input type="submit" class="save-mappings button-primary" value="<?php esc_html_e('Save', 'capsule'); ?>">
		</p>
		<input type="hidden" name="capsule_client_action" value="save_mapping" />
		<?php wp_nonce_field('_cap_client_save_mapping', '_save_mapping_nonce', true, true); ?>
	</form>
</div>
<script type="text/javascript">
(function($) {
	$(function() {
		$('body').on('click', '.js-cap-another-project', function(e) {
			var $el = $(this);
			// Get markup for another select box
			$.post('<?php echo esc_js(admin_url()); ?>',
				{
					capsule_client_action : 'another_project_mapping_ajax',
					post_id : $el.data('post-id'),
					taxonomy : $el.data('taxonomy')
				},
				function(data) {
					$el.before(data);
				}
			);
		});
	});
})(jQuery);
</script>

		<?php
	}

	/**
	 * Display taxonomy terms in the mapping page.
	 *
	 * @param string $taxonomy       Taxonomy name.
	 * @param array  $taxonomy_array Array of taxonomy terms indexed by taxonomy.
	 * @param object $server_post    Server object.
	 * @return void
	 */
	private function term_mapping_display_taxonomy_terms($taxonomy, $taxonomy_array, $server_post)
	{
		$tax_obj = get_taxonomy($taxonomy);
		// Get all posts with this taxonomy, doesn't matter which term.
		$posts = $this->get_server_term_posts(
			$this->post_type_slug($server_post->post_name),
			array(
				'tax_query' => array(
					array(
						'operator' => 'NOT IN',
						'terms'    => array( -1 ),
						'taxonomy' => $taxonomy,
					),
				),
			)
		);
		?>
	<table class="wp-list-table widefat fixed posts">
		<thead>
			<tr>
				<th scope="col" class="manage-column column-label">
					<?php
						printf(
							// Translators: %s is the server name.
							esc_html_x('Server %s', 'taxonomy name', 'capsule'),
							esc_html($tax_obj->labels->singular_name)
						);
					?>
				</th>
				<th scope="col" class="manage-column column-api-key">
					<?php
						printf(
							// Translators: %s is the taxonomy (project) name.
							esc_html_x('Local %s', 'taxonomy name', 'capsule'),
							esc_html($tax_obj->labels->singular_name)
						);
					?>
				</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($posts as $post) : ?>
			<?php
			// get_the_terms is cached by WP_Query, this isn't as expensive as it looks.
			$terms        = get_the_terms($post, $taxonomy);
			$selected_ids = array();
			if (is_array($terms) && ! empty($terms)) {
				foreach ($terms as $term) {
					$selected_ids[] = $term->term_id;
				}
			} else {
				// Set this to none, easier to loop over with single element.
				$selected_ids = array( 0 );
			}
			?>
			<tr>
				<td><?php echo esc_html($post->post_title); ?></td>
				<td>
					<?php foreach ($selected_ids as $selected_id) : ?>
						<?php
						$this->term_select_markup(
							$post,
							$taxonomy,
							$taxonomy_array[$taxonomy],
							$selected_id
						);
						?>
					<?php endforeach; ?>
					<a
						href="#"
						data-taxonomy="<?php echo esc_attr($taxonomy); ?>"
						data-post-id="<?php echo esc_attr($post->ID); ?>"
						class="button cap-another-project js-cap-another-project"
					>
						+
					</a>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
		<?php
	}

	/**
	 * Generate and output markup for a term mapping select box.
	 * Adds a 'Create Term Locally' option if no term is found that matches the server term name.
	 *
	 * @param obj    $post        Post object.
	 * @param string $taxonomy    Taxonomy name to select from.
	 * @param arary  $terms       Terms in the taxonomy.
	 * @param int    $selected_id Term ID that is selected.
	 *
	 * @return void
	 **/
	private function term_select_markup($post, $taxonomy, $terms, $selected_id)
	{
		$options = '';
		$match   = false;
		$tax_obj = get_taxonomy($taxonomy);
		// Translators: %s is the taxonomy name.
		$group_label = sprintf(__('Local %s', 'capsule'), $tax_obj->labels->name);
		include get_template_directory() . '/inc/term-select.php';
	}

	/**
	 * Save taxonomy mappings
	 *
	 * @param array $mappings Array of mappings in the following format (only 1 term per taxonomy currently):
	 * Array (
	 *    [post_id] => Array (
	 *        [taxonomy] => term_id
	 *    )
	 * ).
	 */
	private function save_mapping($mappings)
	{
		if (! is_array($mappings)) {
			return;
		}

		foreach ($mappings as $post_id => $mapping) {
			foreach ($mapping as $taxonomy => $term_data) {
				$terms_to_add = array();
				foreach ($term_data['term_ids'] as $term_id) {
					// This is the create id see term_select_markup.
					if (-1 === (int) $term_id) {
						// Create term.
						$term_id = capsule_create_term($term_data['server_term'], $taxonomy);
					}
					$terms_to_add[] = (int) $term_id;
				}
				wp_set_object_terms($post_id, $terms_to_add, $taxonomy);
			}
		}
	}

	/*** Export/Sending Post ***/

	/**
	 * Sends post data to an external server
	 *
	 * @param array  $post       Array of post data 1:1 to wp_posts table columns.
	 * @param array  $tax        Array containing post taxonomies and terms in the taxonomies.
	 * @param string $api_key    Api key for a given user of a server.
	 * @param string $server_url URL of the server to send the post to.
	 * @return @TODO
	 **/
	public function send_post($post, $tax, $api_key, $server_url)
	{
		$args = array(
			'body'      => array(
				'capsule_server_action'    => 'insert_post',
				'capsule_client_post_data' => array(
					'api_key' => $api_key,
					'post'    => $post,
					'tax'     => $tax,
				),
			),
			'sslverify' => false,
		);

		$response = wp_safe_remote_post($server_url, $args);
		if (! is_wp_error($response) && isset($response['body'])) {
			return json_decode($response['body']);
		}
		return false;
	}

	/**
	 * Sends the post to any server in which it has mapped taxonomies with
	 * This is a hook on wp_insert_post
	 *
	 * @param integer $post_id Post id.
	 * @param WP_Post $post    WP_Post object.
	 **/
	public function insert_post($post_id, $post)
	{
		if (( ! defined('DOING_AUTOSAVE') || ! DOING_AUTOSAVE ) && 'publish' === $post->post_status) {
			// Check if there are any posts in the post type.
			$taxonomies = get_object_taxonomies($post->post_type);
			$servers    = $this->get_servers();
			$postarr    = (array) $post;

			$push = 0;
			foreach ($servers as $server_post) {
				// Only send post if theres a term thats been mapped.
				if ($this->has_server_mapping($post, $server_post)) {
					capsule_queue_add($post_id);
					++$push;
				}
			}
			if ($push) {
				capsule_queue_start();
			}
		}
	}

	/**
	 * Check to see if a post has a server term mapping for a given server.
	 *
	 * @param object $post   Post object.
	 * @param object $server Server object.
	 *
	 * @todo revisit this...
	 **/
	public function has_server_mapping($post, $server)
	{
		// Get the taxonomies that are mapped.
		$mapped_taxonomies = $this->taxonomies_to_map();

		foreach ($mapped_taxonomies as $taxonomy) {
			$post_terms = wp_get_object_terms($post->ID, $taxonomy, array( 'fields' => 'slugs' ));
			if (! empty($post_terms)) {
				$query = new \WP_Query(array(
					'post_type'              => $this->post_type_slug($server->post_name),
					'tax_query'              => array(
						array(
							'taxonomy' => $taxonomy,
							'field'    => 'slug',
							'terms'    => $post_terms,
						),
					),
					'update_post_term_cache' => false,
					'update_post_meta_cache' => false,
					'fields'                 => 'ids',
				));

				// Cannot use have_posts here as fields=>ids prevents it from working.
				// There is at least one term thats been mapped, so return.
				if (! empty($query->posts)) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Format post term mappings to be sent to a server
	 *
	 * @param object $post             Post being sent to the server.
	 * @param arary  $taxonomies       Array of taxonomies to get data for from $post.
	 * @param string $server_post_type Post of of the term mappings @see post_type_slug().
	 *
	 * @return array Array of formatted taxonomy terms ready for transmission to a server.
	 **/
	public function format_terms_to_send($post, $taxonomies, $server_post_type)
	{
		$mapped_taxonomies = $this->taxonomies_to_map();

		$tax_input = array();

		if (! empty($taxonomies)) {
			// Get posts of all the mapped terms.
			$mapped_term_posts = $this->get_server_term_posts($server_post_type);
			// For taxonomies that are mapped, we need to get the mapping data and set it.
			// Otherwise, just send along the local data for the terms.
			foreach ($taxonomies as $taxonomy) {
				$tax_input[ $taxonomy ] = array();
				$terms                  = wp_get_object_terms($post->ID, $taxonomy);

				if (is_array($terms) && ! empty($terms)) {
					// Taxonomy has been mapped, need to get the slug/term_id thats on the server.
					if (in_array($taxonomy, $mapped_taxonomies, true)) {
						$term_ids = array();
						foreach ($terms as $term) {
							$term_ids[] = $term->term_id;
						}

						// There may be one local term mapped to many server terms, so we handle them all.
						$term_object_query = new \WP_Query(array(
							'posts_per_page'         => -1,
							'post_type'              => $server_post_type,
							'post_status'            => 'publish',
							'fields'                 => 'ids',
							'update_post_term_cache' => false,
							// Same number of queries if left as false. Default is true.
							'update_post_meta_cache' => true,
							'tax_query'              => array(
								array(
									'taxonomy' => $taxonomy,
									'field'    => 'id',
									'terms'    => $term_ids,
								),
							),
						));

						if (! empty($term_object_query->posts)) {
							foreach ($term_object_query->posts as $term_mapping_post_id) {
								if (is_taxonomy_hierarchical($taxonomy)) {
									$server_term_id = get_post_meta(
										$term_mapping_post_id,
										$this->server_term_id_key,
										true
									);
									if ($server_term_id) {
										$tax_input[ $taxonomy ][] = $server_term_id;
									}
								} else {
									$server_term_slug = get_post_meta(
										$term_mapping_post_id,
										$this->server_term_slug_key,
										true
									);
									if ($server_term_slug) {
										$tax_input[ $taxonomy ][] = $server_term_slug;
									}
								}
							}
						}
					} else {
						foreach ($terms as $term) {
							// check if heirarchical, wp_insert_post handles them differently
							// wp_insert_post expects hierachical term to come in as IDs, server won't know about these,
							// have to send them as term names. strtolower for normalization.
							$tax_input[ $taxonomy ][] = $term->name;
						}
					}
				} else {
					// So data gets sent through POST.
					$tax_input[ $taxonomy ][] = null;
				}
			}
		}
		return $tax_input;
	}
}
