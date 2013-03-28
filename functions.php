<?php

define('CAPSULE_URL_VERSION', '2');
define('CAPSULE_TAX_PREFIX_PROJECT', '@');
define('CAPSULE_TAX_PREFIX_TAG', '#');
define('CAPSULE_TAX_PREFIX_CODE', '`');

include('controller.php');
include('client-functions.php');
show_admin_bar(false);

function capsule_gatekeeper() {
	if (!current_user_can('publish_posts')) {
		$login_page = wp_login_url();
		is_ssl() ? $proto = 'https://' : $proto = 'http://';
		$requested = $proto.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		if (substr($requested, 0, strlen($login_page)) != $login_page) {
			auth_redirect();
		}
	}
}
add_action('init', 'capsule_gatekeeper', 9999);

function capsule_unauthorized_json() {
	header('Content-type: application/json');
	echo json_encode(array(
		'result' => 'unauthorized',
		'msg' => __('Please log in.', 'capsule'),
		'login_url' => wp_login_url(),
	));
	die();
}

function capsule_resources() {
	$template_url = trailingslashit(get_template_directory_uri());
	
	wp_enqueue_script('jquery');
	wp_enqueue_script(
		'capsule',
		$template_url.'js/capsule.js',
		array('jquery', 'ace', 'statichighlight', 'cfmarkdown'),
		CAPSULE_URL_VERSION,
		true
	);
	wp_localize_script('capsule', 'capsuleL10n', array(
		'endpointAjax' => home_url('index.php'),
		'loading' => __('Loading...', 'capsule'),
	));
	wp_enqueue_script(
		'ace',
		$template_url.'lib/ace/build/src/ace.js',
		array('jquery'),
		CAPSULE_URL_VERSION,
		true
	);
	wp_enqueue_script(
		'statichighlight',
		$template_url.'js/static_highlight.js',
		array('ace'),
		CAPSULE_URL_VERSION,
		true
	);
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
		'cfmarkdown',
		$template_url.'js/syntax/cfmarkdown.js',
		array('jquery', 'ace', 'twitter-text'),
		CAPSULE_URL_VERSION,
		true
	);
	wp_enqueue_script(
		'cf_php_highlight_rules',
		$template_url.'js/syntax/cf_php_highlight_rules.js',
		array('ace'),
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
		'jquery-scrollto',
		$template_url.'js/jquery.scrollTo-1.4.2-min.js',
		array('jquery'),
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
}
add_action('wp_enqueue_scripts', 'capsule_resources');

function capsule_wp_head() {
?>
<style>
.spinner {
	background: #fff url(<?php echo admin_url('images/loading.gif'); ?>) no-repeat center center;
}
</style>
<?php
}
add_action('wp_head', 'capsule_wp_head');

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
		)
	);
}
add_action('init', 'capsule_register_taxonomies');

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
		return get_the_term_list($post_id, $taxonomy, '<ul><li>', '</li><li>', '</li></ul>'); 
	}
	else {
		return '<ul><li class="none">'.__('(none)', 'capsule').'</li></ul>';
	}
}

function capsule_the_content_markdown($content) {
	include_once(STYLESHEETPATH.'/lib/php-markdown/markdown_extended.php');
	return MarkdownExtended($content);
}
add_filter('the_content', 'capsule_the_content_markdown');
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


