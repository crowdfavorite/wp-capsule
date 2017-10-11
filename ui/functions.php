<?php

define('CAPSULE_URL_VERSION', '2.3');
define('CAPSULE_TAX_PREFIX_PROJECT', '@');
define('CAPSULE_TAX_PREFIX_TAG', '#');
define('CAPSULE_TAX_PREFIX_CODE', '`');

show_admin_bar(false);
remove_post_type_support('post', 'post-formats');

include_once('lib/wp-taxonomy-filter/taxonomy-filter.php');

function is_capsule_server() {
	return (defined('CAPSULE_SERVER') && CAPSULE_SERVER);
}

function capsule_mode() {
	if (!defined('CAPSULE_MODE')) {
		define('CAPSULE_MODE', 'prod');
	}
	return CAPSULE_MODE;
}
function capsule_gatekeeper_capability($capability) {
	return 'read';
}

function capsule_gatekeeper() {
	$keep_out = apply_filters('capsule_gatekeeper_enabled', true);
	if ($keep_out) {
		include_once('lib/cf-gatekeeper/cf-gatekeeper.php');
		add_filter('cf_gatekeeper_capability', 'capsule_gatekeeper_capability');
	}
}	
add_action('after_setup_theme', 'capsule_gatekeeper');

if (!is_capsule_server()) {
	include('controller.php');
}

function capsule_login_duration() {
    return 2592000; // 30 * 24 * 60 * 60 = 30 days
}
add_filter('auth_cookie_expiration', 'capsule_login_duration');

function capsule_unauthorized_json() {
	header('Content-type: application/json');
	echo json_encode(array(
		'result' => 'unauthorized',
		'msg' => __('Please log in.', 'capsule'),
		'login_url' => wp_login_url(),
	));
	die();
}

function capsule_resources_prod() {
	$template_url = trailingslashit(get_template_directory_uri()).'ui/';
	$assets_url = trailingslashit($template_url . 'assets');

	// Styles
	wp_enqueue_style(
		'capsule_styles',
		$assets_url.'css/style.css',
		array(),
		CAPSULE_URL_VERSION
	);

	// Scripts
	wp_enqueue_script('jquery');
	wp_enqueue_script('suggest');
	wp_enqueue_script(
		'capsule',
		$assets_url.'js/optimized.js',
		array(),
		CAPSULE_URL_VERSION,
		true
	);
	wp_localize_script('capsule', 'capsuleL10n', array(
		'endpointAjax' => home_url('index.php'),
		'loading' => __('Loading...', 'capsule'),
	));
	wp_localize_script('capsule', 'requirejsL10n', array(
		'capsule' => $assets_url,
		'ace' => $template_url.'lib/ace',
		'lib' => $template_url.'lib',
		'cachebust' => urlencode(CAPSULE_URL_VERSION),
	));
	if (!is_capsule_server()) {
		wp_enqueue_script('heartbeat');
	}
}

function capsule_resources_dev() {
	$template_url = trailingslashit(get_template_directory_uri()).'ui/';
	$assets_url = trailingslashit($template_url . 'assets');

	// Styles
	wp_enqueue_style(
		'capsule_styles',
		$assets_url.'css/style.css',
		array(),
		CAPSULE_URL_VERSION
	);

	// Scripts
	wp_enqueue_script('jquery');
	wp_enqueue_script(
		'hotkeys',
		$template_url.'lib/jquery.hotkeys/jquery.hotkeys.js',
		array('jquery'),
		CAPSULE_URL_VERSION,
		true
	);
	wp_enqueue_script('suggest');
	if (!is_capsule_server()) {
		wp_enqueue_script('heartbeat');
	}

	// require.js enforces JS module dependencies, heavily used in
	// loading Ace and related code
	wp_enqueue_script(
		'requirejslib',
		$template_url.'lib/require.js',
		array(),
		CAPSULE_URL_VERSION,
		true
	);
	wp_enqueue_script(
		'requirejs',
		$assets_url.'js/requirejs_config.js',
		array('requirejslib'),
		CAPSULE_URL_VERSION,
		true
	);
	wp_localize_script('requirejs', 'requirejsL10n', array(
		'capsule' => $assets_url,
		'ace' => $template_url.'lib/ace',
		'lib' => $template_url.'lib',
		'cachebust' => urlencode(CAPSULE_URL_VERSION),
	));
	wp_enqueue_script(
		'capsulebundle',
		$assets_url.'js/capsule.js',
		array('requirejs'),
		CAPSULE_URL_VERSION,
		true
	);

	wp_enqueue_script(
		'capsule',
		$assets_url.'js/load.js',
		array('jquery', 'twitter-text', 'suggest', 'capsulebundle'),
		CAPSULE_URL_VERSION,
		true
	);
	wp_localize_script('capsule', 'capsuleL10n', array(
		'endpointAjax' => home_url('index.php'),
		'loading' => __('Loading...', 'capsule'),
	));

	wp_enqueue_script(
		'php-date',
		$template_url.'lib/phpjs/functions/datetime/date.js',
		array(),
		CAPSULE_URL_VERSION,
		true
	);
	wp_enqueue_script(
		'twitter-text',
		$template_url.'lib/twitter-text-js/twitter-text.js',
		array('jquery'),
		CAPSULE_URL_VERSION,
		true
	);

	wp_enqueue_script(
		'json',
		$template_url.'lib/json-js/json2.js',
		array(),
		CAPSULE_URL_VERSION,
		true
	);
	wp_enqueue_script(
		'jquery-scrollintoview',
		$template_url.'lib/jquery-scrollintoview/jquery.scrollintoview.min.js',
		array('jquery'),
		CAPSULE_URL_VERSION,
		true
	);
	wp_enqueue_script(
		'sidr',
		$template_url.'lib/sidr/dist/jquery.sidr.js',
		array('jquery'),
		CAPSULE_URL_VERSION,
		true
	);
	wp_enqueue_script(
		'sidr',
		$template_url.'lib/sidr/dist/jquery.sidr.js',
		array('jquery'),
		CAPSULE_URL_VERSION,
		true
	);
	wp_enqueue_script(
		'linkify',
		$template_url.'lib/linkify/1.0/jquery.linkify-1.0-min.js',
		array('jquery'),
		CAPSULE_URL_VERSION,
		true
	);
}
if (capsule_mode() == 'dev' || (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG)) {
	add_action('wp_enqueue_scripts', 'capsule_resources_dev');
}
else {
	add_action('wp_enqueue_scripts', 'capsule_resources_prod');
}

function capsule_register_taxonomies() {
	register_taxonomy(
		'projects',
		'post',
		array(
			'hierarchical' => false,
			'labels' => array(
				'name' => __('Projects', 'capsule'),
				'singular_name' => __('Project', 'capsule'),
				'search_items' => __('Search Projects', 'capsule'),
				'popular_items' => __('Popular Projects', 'capsule'),
				'all_items' => __('All Projects', 'capsule'),
				'parent_item' => __('Parent Project', 'capsule'),
				'parent_item_colon' => __('Parent Project:', 'capsule'),
				'edit_item' => __('Edit Project', 'capsule'),
				'update_item' => __('Update Project', 'capsule'),
				'add_new_item' => __('Add New Project', 'capsule'),
				'new_item_name' => __('New Project Name', 'capsule'),
			),
			'sort' => true,
			'args' => array('orderby' => 'term_order'),
			'rewrite' => array(
				'slug' => 'projects',
				'with_front' => false,
			),
		)
	);
	register_taxonomy(
		'code',
		'post',
		array(
			'hierarchical' => false,
			'labels' => array(
				'name' => __('Code Languages', 'capsule'),
				'singular_name' => __('Code Language', 'capsule'),
				'search_items' => __('Search Code Languages', 'capsule'),
				'popular_items' => __('Popular Code Languages', 'capsule'),
				'all_items' => __('All Code Languages', 'capsule'),
				'parent_item' => __('Parent Code Language', 'capsule'),
				'parent_item_colon' => __('Parent Code Language:', 'capsule'),
				'edit_item' => __('Edit Code Language', 'capsule'),
				'update_item' => __('Update Code Language', 'capsule'),
				'add_new_item' => __('Add New Code Language', 'capsule'),
				'new_item_name' => __('New Code Language Name', 'capsule'),
			),
			'sort' => true,
			'args' => array('orderby' => 'term_order'),
			'rewrite' => array(
				'slug' => 'code',
				'with_front' => false,
			),
		)
	);
	register_taxonomy(
		'evergreen',
		'post',
		array(
			'hierarchical' => true,
			'labels' => array(
				'name' => __('Evergreen', 'capsule'),
				'singular_name' => __('Evergreen Status', 'capsule'),
				'search_items' => __('Search Evergreen Status', 'capsule'),
				'popular_items' => __('Popular Evergreen Status', 'capsule'),
				'all_items' => __('All Evergreen Status', 'capsule'),
				'parent_item' => __('Parent Evergreen Status', 'capsule'),
				'parent_item_colon' => __('Parent Evergreen Status:', 'capsule'),
				'edit_item' => __('Edit Evergreen Status', 'capsule'),
				'update_item' => __('Update Evergreen Status', 'capsule'),
				'add_new_item' => __('Add New Evergreen Status', 'capsule'),
				'new_item_name' => __('New Evergreen Status Name', 'capsule'),
			),
			'sort' => true,
			'args' => array('orderby' => 'term_order'),
			'rewrite' => array(
				'slug' => 'evergreen',
				'with_front' => false,
			),
			'show_ui' => false,
		)
	);
}
add_action('init', 'capsule_register_taxonomies');

// check for taxonomy support in permalink patterns
function capsule_permalink_check() {
	$rewrite_rules = get_option('rewrite_rules');
	if ($rewrite_rules == '') {
		return;
	}
	global $wp_rewrite;
	$pattern = 'projects/';
	if (substr($pattern, 0, 1) == '/') {
		$pattern = substr($pattern, 1);
	}
	// check for 'projects' in rewrite rules
	foreach ($rewrite_rules as $rule => $params) {
		if (substr($rule, 0, strlen($pattern)) == $pattern) {
			return;
		}
	}
	// flush rules if not found above
	flush_rewrite_rules();
}
add_action('admin_init', 'capsule_permalink_check');

function capsule_get_the_terms($terms, $id, $taxonomy) {
	if (is_array($terms) && count($terms)) {
		$prefix = null;
		switch ($taxonomy) {
			case 'projects':
				$prefix = CAPSULE_TAX_PREFIX_PROJECT;
				break;
			case 'post_tag':
				$prefix = CAPSULE_TAX_PREFIX_TAG;
				break;
			case 'code':
				$prefix = CAPSULE_TAX_PREFIX_CODE;
				break;
		}
		$_terms = array();
		foreach ($terms as $term_id => $term) {
			if (!empty($prefix)) {
				if (substr($term->name, 0, strlen($prefix)) != $prefix) {
					$term->name = $prefix.$term->name;
				}
			}
			$_terms[$term_id] = $term;
		}
		$terms = $_terms;
	}
	return $terms;
}
add_filter('get_the_terms', 'capsule_get_the_terms', 10, 3);

function capsule_term_list($post_id, $taxonomy) {
	if (($tax_terms = get_the_terms($post_id, $taxonomy)) != false) {
		if ($taxonomy == 'post_tag') {
			return get_the_term_list($post_id, $taxonomy, '<ul class="post-meta-tags"><li>', '</li><li>', '</li></ul>');
		} else {
			return get_the_term_list($post_id, $taxonomy, '<ul><li>', '</li><li>', '</li></ul>');
		}
	}
	else {
		return '';
	}
}

function capsule_the_content_markdown($content) {
	include_once(get_template_directory().'/ui/lib/php-markdown/markdown_extended.php');
	return MarkdownExtended($content);
}
add_filter('the_content', 'capsule_the_content_markdown', 6);
remove_filter('the_content', 'wpautop');
remove_filter('the_content', 'wptexturize');

function capsule_trim_excerpt($excerpt) {
	$max = 500;
	if (strlen($excerpt) > $max) {
		$excerpt = substr($excerpt, 0, $max);
	}
	return $excerpt;
}
add_filter('get_the_excerpt', 'capsule_trim_excerpt');

// Generate the searchurl for the frontend
function capsule_header_js() {
?>
<script type="text/javascript">
var capsuleSearchURL = '<?php echo home_url(); ?>';
</script>
<?php
}
if (!is_admin()) {
	add_action('wp_head', 'capsule_header_js');
}

function capsule_taxonomy_filter() {
	if (!class_exists('CF_Taxonomy_Filter')) {
		return;
	}
	$args = array(
		'taxonomies' => array(
			'projects' => array(
				'prefix' => '@',
			),
			'post_tag' => array(
				'prefix' => '#',
			),
			'code' => array(
				'prefix' => '`',
			),
		),
	);

	CF_Taxonomy_Filter::start_form();

	echo '<div class="cftf-options"><span class="label">'.__('Options', 'capsule').'</span>';

	foreach ($args['taxonomies'] as $taxonomy => $tax_args) {
		if (is_array($args)) {
			CF_Taxonomy_Filter::tax_filter($taxonomy, $tax_args);
		}
		// Just passed in taxonomy name with no options
		else {
			CF_Taxonomy_Filter::tax_filter($args);
		}
	}

	CF_Taxonomy_Filter::author_select();

	echo '</div>';
	echo '<div class="cftf-dates"><span class="label">'.__('Date Range', 'capsule').'</span>';

	CF_Taxonomy_Filter::date_filter();

	echo '</div>';
	echo '<div class="cftf-submit">';

	CF_Taxonomy_Filter::submit_button();

	echo '</div>';

	CF_Taxonomy_Filter::end_form();
}

function capsule_tax_filter_url() {
	return get_template_directory_uri().'/ui/lib/wp-taxonomy-filter/';
}
add_filter('cftf_url', 'capsule_tax_filter_url');

function capsule_credits() {
?>
		<ul>
			<li><a href="http://ajaxorg.github.io/ace/">Ace Code Editor</a> (<a href="https://github.com/ajaxorg/ace">GitHub</a>)</li>
			<li><a href="http://harvesthq.github.io/chosen/">Chosen</a> (<a href="https://github.com/harvesthq/chosen">GitHub</a>)</li>
			<li><a href="http://michelf.ca/projects/php-markdown/extra/">PHP Markdown Extra</a> (<a href="https://github.com/michelf/php-markdown">GitHub</a>)</li>
			<li>Twitter Text JS (<a href="https://github.com/twitter/twitter-text-js">GitHub</a>)</li>
			<li>jQuery .scrollintoview() (<a href="https://github.com/litera/jquery-scrollintoview">GitHub</a>)</li>
			<li>JSON in JavaScript (<a href="https://github.com/douglascrockford/JSON-js">GitHub</a>)</li>
			<li><a href="http://requirejs.org/">RequireJS</a> (<a href="https://github.com/jrburke/requirejs">GitHub</a>)</li>
			<li><a href="http://www.berriart.com/sidr/">Sidr</a> (<a href="https://github.com/artberri/sidr">GitHub</a>)</li>
			<li>Linkify (<a href="https://github.com/maranomynet/linkify">GitHub</a>)</li>
			<li><a href="http://sass-lang.com/">Sass</a> (<a href="https://github.com/nex3/sass">GitHub</a>)</li>
			<li>Capsule Icon by <a href="http://dribbble.com/matthewspiel">Matthew Spiel</a></li>
			<li><a href="http://www.google.com/fonts/specimen/Source+Sans+Pro">Source Sans Pro</a> (<a href="https://github.com/adobe/source-sans-pro">GitHub</a>)</li>
			<li><a href="http://www.google.com/fonts/specimen/Source+Code+Pro">Source Code Pro</a> (<a href="https://github.com/adobe/source-code-pro">GitHub</a>)</li>
			<li><a href="http://fontello.com">Fontello</a> (<a href="https://github.com/fontello/fontello">GitHub</a>) &amp; Fontelico (<a href="https://github.com/fontello/fontelico.font">GitHub</a>)</li>
			<li><a href="http://aristeides.com/">Elusive</a> (<a href="https://github.com/aristath/elusive-iconfont">GitHub</a>)</li>
			<li><a href="http://entypo.com/">Entypo</a> (<a href="https://github.com/danielbruce/entypo">GitHub</a>)</li>
			<li><a href="http://somerandomdude.com/work/iconic/">Iconic</a> (<a href="https://github.com/somerandomdude/Iconic">GitHub</a>)</li>
			<li><a href="http://www.justbenicestudio.com/studio/websymbols/">Web Symbols</a></li>
		</ul>
<?php
}

// Similar functionality of wp_create_term but wp_create_term is in wp-admin includes which are not loaded for api calls
function capsule_create_term($tag_name, $taxonomy) {
	if ($term_info = term_exists($tag_name, $taxonomy)) {
		if (is_array($term_info)) {
			return $term_info['term_id'];
		}
			return false;
		}
		$term_info = wp_insert_term($tag_name, $taxonomy);
		if (is_array($term_info)) {
			return $term_info['term_id'];
		}
		return false;
}

// redirect to front-page by default
function capsule_login_redirect($redirect_to, $request_str) {
	if (empty($request_str)) {
		$redirect_to = home_url('/');
	}
	return $redirect_to;
}
add_action('login_redirect', 'capsule_login_redirect', 10, 2);

function capsule_queue_api_key() {
	return sha1('capsule_queue'.AUTH_KEY.AUTH_SALT);
}

function capsule_queue_add($post_id) {
	$post_id = intval($post_id);
	if (!$post_id) {
		return;
	}
	$queue = get_option('capsule_queue');
	if (!is_array($queue)) {
		$queue = array();
	}
	$queue[] = $post_id;
	$queue = array_unique($queue);
	update_option('capsule_queue', $queue);
}

function capsule_queue_remove($post_id) {
	$post_id = intval($post_id);
	if (!$post_id) {
		return;
	}
	$_queue = get_option('capsule_queue');
	if (!is_array($_queue)) {
		return;
	}
	$queue = array();
	foreach ($_queue as $_post_id) {
		if ($_post_id != $post_id) {
			$queue[] = $_post_id;
		}
	}
	$queue = array_unique($queue);
	update_option('capsule_queue', $queue);
}

function capsule_queue_start() {
	$url = add_query_arg(array(
		'capsule_action' => 'queue_run',
		'api_key' => capsule_queue_api_key()
	), site_url('index.php'));
	wp_remote_get(
		$url,
		array(
			'blocking' => false,
			'sslverify' => false,
			'timeout' => 0.01,
		)
	);
}

function capsule_queue_run() {
	set_time_limit(0);
	// this is a very weak "lock" mechanism, but may be suitable for
	// low request situations like Capsule
	$lock = get_option('capsule_queue_lock');
	if (!empty($lock) && $lock > strtotime('now')) {
		return;
	}
	update_option('capsule_queue_lock', strtotime('+5 minutes'));
	$queue = get_option('capsule_queue');
	for ($i = 0; $i < count($queue) && $i < 10; $i++) {
		$url = add_query_arg(array(
			'capsule_action' => 'queue_post_to_server',
			'post_id' => $queue[$i],
			'api_key' => capsule_queue_api_key()
		), site_url('index.php'));
		wp_remote_get(
			$url,
			array(
				'blocking' => false,
				'sslverify' => false,
				'timeout' => 0.01,
			)
		);
	}
	if (count(queue) > 10) {
		capsule_queue_start();
	}
	update_option('capsule_queue_lock', '');
}

function capsule_queue_post_to_server($post_id) {
	global $cap_client;

	$post = get_post($post_id);

	// Check if there are any posts in the post type
	$taxonomies = get_object_taxonomies($post->post_type);
	$servers = $cap_client->get_servers();
	$postarr = (array) $post;

	$errors = 0;
	foreach ($servers as $server_post) {
		// Only send post if theres a term thats been mapped
		if ($cap_client->has_server_mapping($post, $server_post)) {
			$tax_input = $cap_client->format_terms_to_send(
				$post,
				$taxonomies,
				$cap_client->post_type_slug($server_post->post_name)
			);
			$mapped_taxonomies = $cap_client->taxonomies_to_map();

			$tax = compact('taxonomies', 'tax_input', 'mapped_taxonomies');

			$api_key = get_post_meta($server_post->ID, $cap_client->server_api_key, true);
			$endpoint = get_post_meta($server_post->ID, $cap_client->server_url_key, true);

			$response = $cap_client->send_post($postarr, $tax, $api_key, $endpoint);

			if (!$response || !isset($response->result) || $response->result != 'success') {
				$errors++;
			}
			// success
			else {
				// Older server theme versions will not send the permalink
				$permalink = isset($response->data->permalink) ? $response->data->permalink : '';

				$server_statuses = get_post_meta($post_id, '_cap_client_server_statuses', true);
				$server_statuses = is_array($server_statuses) ? $server_statuses : array();
				$server_statuses[$server_post->ID] = array(
					'gmt_time' => gmmktime(),
					'permalink' => $permalink,
				);
				update_post_meta($post_id, '_cap_client_server_statuses', $server_statuses);
			}
		}
	}
	if (empty($errors)) {
		// "send at least once" style queue - only remove if all sends are successful
		capsule_queue_remove($post_id);
	}
}

function capsule_wp_editor_warning() {
?>
<style type="text/css">
.capsule-editor-warning {
	background: #222;
	color: #fff;
	display: none;
	font-size: 15px;
	left: 1%;
	line-height: 180%;
	opacity: 0.8;
	padding: 100px 50px 125px;
	position: absolute;
	text-align: center;
	top: 20px;
	width: 89%;
	z-index: 99999999;
}
.capsule-editor-warning h3 {
	font-size: 36px;
}
.capsule-editor-warning a,
.capsule-editor-warning a:visited {
	color: #eee;
}
.capsule-editor-warning a:active,
.capsule-editor-warning a:hover {
	color: #fff;
}
.capsule-editor-warning .bypass {
	font-size: 13px;
	padding-top: 30px;
}
#post {
	position: relative;
}
</style>
<div class="capsule-editor-warning">
	<h3><?php _e('Whoa Cowboy!', 'capsule'); ?></h3>
	<p><?php printf(__('<b>Capsule is designed for front-end editing only.</b><br />Changes to projects, tags, etc. here will be overwritten when this post is edited on the front-end.<br /><a href="%s">Let\'s head back over there.</a>', 'capsule'), esc_url(home_url())); ?>
	<p class="bypass"><?php _e('Ok, ok - I get it. <a href="#">Let me in anyway</a>.', 'capsule'); ?>
</div>
<script type="text/javascript">
jQuery(function($) {
	var $warning = $('.capsule-editor-warning');
	$warning.prependTo($('#post')).fadeIn()
		.find('.bypass a').on('click', function() {
		$warning.fadeOut();
	});
});
</script>
<?php
}
add_action('edit_form_after_title', 'capsule_wp_editor_warning');

function capsule_last_pushed($post_id) {
	global $cap_client;
	$html = '';
	$meta = get_post_meta($post_id, '_cap_client_server_statuses', true);
	if (!empty($meta) && is_array($meta)) {
		$html .= '<ul class="push-server-list">';
		foreach ($meta as $server_id => $data) {
			if (!empty($data['gmt_time'])) {
				// Server should have sent permalink, but if the server theme is running an older version it will be empty, set it to the server URL
				$permalink = !empty($data['permalink']) ? $data['permalink'] : get_post_meta($server_id, $cap_client->server_url_key, true);
				$wp_time = capsule_gmt_to_wp_time($data['gmt_time']);
				$date_format = apply_filters('capsule_server_push_date_format', 'M j, Y @ g:ia');
				$html .= '<li><a href="'.esc_url($permalink).'"><span class="push-server-name">'.get_the_title($server_id).'</span><span class="push-server-date">'.date($date_format, $wp_time).'</span></a></li>';
			}
		}
		$html .= '</ul>';
	}
	return $html;
}

function capsule_gmt_to_wp_time($gmt_time) {
	$timezone_string = get_option('timezone_string');
	if (!empty($timezone_string)) {
		// Not using get_option('gmt_offset') because it gets the offset for the
		// current date/time which doesn't work for timezones with daylight savings time.
		$gmt_date = date('Y-m-d H:i:s', $gmt_time);
		$datetime = new DateTime($gmt_date);
		$datetime->setTimezone(new DateTimeZone(get_option('timezone_string')));
		$offset_in_secs = $datetime->getOffset();
		
		return $gmt_time + $offset_in_secs;
	}
	else {
		return $gmt_time + (get_option('gmt_offset') * 3600);
	}
}
