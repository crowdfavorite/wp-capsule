<?php

define('RUTTER_URL_VERSION', '1');
define('RUTTER_TAX_PREFIX_PROJECT', '@');
define('RUTTER_TAX_PREFIX_TAG', '#');
define('RUTTER_TAX_PREFIX_CODE', '`');

include('controller.php');

show_admin_bar(false);

function cfrutter_gatekeeper() {
	if (!current_user_can('publish_posts')) {
		$login_page = site_url('wp-login.php');
		is_ssl() ? $proto = 'https://' : $proto = 'http://';
		$requested = $proto.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		if (substr($requested, 0, strlen($login_page)) != $login_page) {
			auth_redirect();
		}
	}
}
add_action('init', 'cfrutter_gatekeeper');	

function cfrutter_resources() {
	wp_enqueue_script('jquery');
	wp_enqueue_script(
		'rutter',
		trailingslashit(get_bloginfo('template_url')).'js/rutter.js',
		array('jquery', 'ace'),
		RUTTER_URL_VERSION,
		true
	);
	wp_localize_script('rutter', 'rutterL10n', array(
		'endpointAjax' => home_url('index.php'),
		'loading' => __('Loading...', 'rutter'),
	));
	wp_enqueue_script(
		'ace',
		trailingslashit(get_bloginfo('template_url')).'lib/ace/build/src/ace.js',
		array('jquery'),
		RUTTER_URL_VERSION,
		true
	);
	wp_enqueue_script(
		'cfmarkdown',
		trailingslashit(get_bloginfo('template_url')).'js/syntax/cfmarkdown.js',
		array('jquery', 'ace'),
		RUTTER_URL_VERSION,
		true
	);
	wp_enqueue_script(
		'php-date',
		trailingslashit(get_bloginfo('template_url')).'lib/phpjs/functions/datetime/date.js',
		array(),
		RUTTER_URL_VERSION,
		true
	);
	wp_enqueue_script(
		'twitter-text',
		trailingslashit(get_bloginfo('template_url')).'lib/twitter-text-js/twitter-text.js',
		array('jquery'),
		RUTTER_URL_VERSION,
		true
	);
	wp_enqueue_script(
		'json',
		trailingslashit(get_bloginfo('template_url')).'lib/json-js/json2.js',
		array(),
		RUTTER_URL_VERSION,
		true
	);
}
add_action('wp_enqueue_scripts', 'cfrutter_resources');

function cfrutter_wp_head() {
?>
<style>
.spinner {
	background: #fff url(<?php echo admin_url('images/loading.gif'); ?>) no-repeat center center;
}
</style>
<?php
}
add_action('wp_head', 'cfrutter_wp_head');

function cfrutter_register_taxonomies() {
	register_taxonomy(
		'projects',
		'post',
		array(
			'hierarchical' => false,
			'labels' => array(
				'name' => __('Projects', 'rutter'),
				'singular_name' => __('Project', 'rutter'),
				'search_items' => __('Search Projects', 'rutter'),
				'popular_items' => __('Popular Projects', 'rutter'),
				'all_items' => __('All Projects', 'rutter'),
				'parent_item' => __('Parent Project', 'rutter'),
				'parent_item_colon' => __('Parent Project:', 'rutter'),
				'edit_item' => __('Edit Project', 'rutter'),
				'update_item' => __('Update Project', 'rutter'),
				'add_new_item' => __('Add New Project', 'rutter'),
				'new_item_name' => __('New Project Name', 'rutter'),
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
				'name' => __('Code Languages', 'rutter'),
				'singular_name' => __('Code Language', 'rutter'),
				'search_items' => __('Search Code Languages', 'rutter'),
				'popular_items' => __('Popular Code Languages', 'rutter'),
				'all_items' => __('All Code Languages', 'rutter'),
				'parent_item' => __('Parent Code Language', 'rutter'),
				'parent_item_colon' => __('Parent Code Language:', 'rutter'),
				'edit_item' => __('Edit Code Language', 'rutter'),
				'update_item' => __('Update Code Language', 'rutter'),
				'add_new_item' => __('Add New Code Language', 'rutter'),
				'new_item_name' => __('New Code Language Name', 'rutter'),
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
				'name' => __('Evergreen', 'rutter'),
				'singular_name' => __('Evergreen Status', 'rutter'),
				'search_items' => __('Search Evergreen Status', 'rutter'),
				'popular_items' => __('Popular Evergreen Status', 'rutter'),
				'all_items' => __('All Evergreen Status', 'rutter'),
				'parent_item' => __('Parent Evergreen Status', 'rutter'),
				'parent_item_colon' => __('Parent Evergreen Status:', 'rutter'),
				'edit_item' => __('Edit Evergreen Status', 'rutter'),
				'update_item' => __('Update Evergreen Status', 'rutter'),
				'add_new_item' => __('Add New Evergreen Status', 'rutter'),
				'new_item_name' => __('New Evergreen Status Name', 'rutter'),
			),
			'sort' => true,
			'args' => array('orderby' => 'term_order'),
			'rewrite' => array(
				'slug' => 'evergreen',
				'with_front' => false,
			),
		)
	);
}
add_action('init', 'cfrutter_register_taxonomies');

function cfrutter_get_the_terms($terms, $id, $taxonomy) {
	// this was getting called twice for post_tag, not sure why
	global $RUTTER_TAX_FILTERED;
	if (!isset($RUTTER_TAX_FILTERED)) {
		$RUTTER_TAX_FILTERED = array();
	}
	if (is_array($terms) && count($terms) && !in_array($taxonomy, $RUTTER_TAX_FILTERED)) {
		$RUTTER_TAX_FILTERED[] = $taxonomy;
		switch ($taxonomy) {
			case 'projects':
				$_terms = array();
				foreach ($terms as $term_id => $term) {
					$term->name = RUTTER_TAX_PREFIX_PROJECT.$term->name;
					$_terms[$term_id] = $term;
				}
				$terms = $_terms;
				break;
			case 'post_tag':
				$_terms = array();
				foreach ($terms as $term_id => $term) {
					$term->name = RUTTER_TAX_PREFIX_TAG.$term->name;
					$_terms[$term_id] = $term;
				}
				$terms = $_terms;
				break;
			case 'code':
				$_terms = array();
				foreach ($terms as $term_id => $term) {
					$term->name = RUTTER_TAX_PREFIX_CODE.$term->name;
					$_terms[$term_id] = $term;
				}
				$terms = $_terms;
				break;
		}
	}
	return $terms;
}
add_filter('get_the_terms', 'cfrutter_get_the_terms', 10, 3);

function cfrutter_term_list($post_id, $taxonomy) {
	if (($tax_terms = get_the_terms($post_id, $taxonomy)) != false) {
		return get_the_term_list($post_id, $taxonomy, '<ul><li>', '</li><li>', '</li></ul>'); 
	}
	else {
		return '<ul><li class="none">'.__('(none)', 'rutter').'</li></ul>';
	}
}

function cfrutter_parse_content_for_tags() {
// TODO
// find projects
// find tags
// replace projects and tags with found terms
}
// add_filter('save_post', 'cfrutter_parse_content_for_tags');

function cfrutter_the_content_markdown($content) {
	include_once(STYLESHEETPATH.'/lib/php-markdown/markdown_extended.php');
	return MarkdownExtended($content);
}
add_filter('the_content', 'cfrutter_the_content_markdown');
remove_filter('the_content', 'wpautop');
remove_filter('the_content', 'wptexturize');

