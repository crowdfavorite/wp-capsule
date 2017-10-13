<?php

/**
 * @package capsule
 *
 * This file is part of the Capsule Theme for WordPress
 * http://crowdfavorite.com/capsule/
 *
 * Copyright (c) 2012 Crowd Favorite, Ltd. All rights reserved.
 * http://crowdfavorite.com
 *
 * **********************************************************************
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 * **********************************************************************
 */

if (!isset($body_classes)) {
	$body_classes = array();
}

global $cap_client;
$cap_servers = $cap_client ? $cap_client->get_servers() : array(); 

$blog_desc = get_bloginfo('description');
$title_description = (is_home() && !empty($blog_desc) ? ' - '.$blog_desc : '');

if (get_option('permalink_structure') != '') {
	$search_permastruct = "1";
}
else {
	$search_permastruct = "0";
}

if (function_exists('cftf_is_filter') && cftf_is_filter()) {
	$body_classes[] = 'filters-on';
}

$theme_url = trailingslashit(get_template_directory_uri());

?>
<!DOCTYPE html>
<!--[if IE 6]>
<html id="ie6" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 7]>
<html id="ie7" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 8]>
<html id="ie8" <?php language_attributes(); ?>>
<![endif]-->
<!--[if !(IE 6) | !(IE 7) | !(IE 8)  ]><!-->
<html <?php language_attributes(); ?>>
<!--<![endif]-->
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width" />
	
	<title><?php wp_title( '|', true, 'right' ); echo esc_html( get_bloginfo('name'), 1 ).$title_description; ?></title>
	
	<link rel="icon" href="<?php echo $theme_url; ?>ui/assets/icon/capsule-16.png" sizes="16x16">
	<link rel="icon" href="<?php echo $theme_url; ?>ui/assets/icon/capsule-32.png" sizes="32x32">
	<link rel="icon" href="<?php echo $theme_url; ?>ui/assets/icon/capsule-48.png" sizes="48x48">
	<link rel="icon" href="<?php echo $theme_url; ?>ui/assets/icon/capsule-128.png" sizes="128x128">
	<link rel="fluid-icon" href="<?php echo $theme_url; ?>ui/assets/icon/capsule.icns" title="<?php echo esc_attr( get_bloginfo('name') ); ?>">
	
<?php wp_head(); ?>
</head>
<body <?php body_class(implode(' ', $body_classes)); ?>>
<div class="container">
	<nav class="main-nav">
		<ul>
			<?php do_action('capsule_main_nav_before'); ?>
			<li><a href="<?php echo esc_url(home_url('/')); ?>" class="home icon">&#59392;</a></li>
			<li><a href="<?php echo esc_url(admin_url('post-new.php')); ?>" class="post-new-link icon">&#59396;</a></li>
			<li><a href="#projects" class="projects"><?php _e('@', 'capsule'); ?></a></li>
			<li><a href="#tags" class="tags icon"><?php _e('#', 'capsule'); ?></a></li>
<?php
if (!empty($cap_servers)) {
?>
			<li><a href="#servers" class="servers icon">&#59254;</a></li>
<?php
}
?>
			<li><a href="<?php echo esc_url(admin_url('admin.php?page=capsule')); ?>" class="icon">&#59400;</a></li>
			<?php do_action('capsule_main_nav_after'); ?>
			<li><span class="spacer"></span></li>
		</ul>
	</nav>
	
	<div id="wrap">
		<header id="header">
			<div class="inner">
<?php

$title = '';

if (is_home() || is_front_page()) {
	$title = __('Home', 'capsule');
}
else if (function_exists('cftf_is_filter') && cftf_is_filter()) {
	$title = __('Filter', 'capsule');
}
else if (is_search()) {
	$title = sprintf(__('Search: %s', 'capsule'), esc_html(get_query_var('s')));
}
else if (is_tag()) {
	$term = get_queried_object();
	$title = sprintf(__('#%s', 'capsule'), esc_html($term->name));
}
else if (is_tax('projects')) {
	$term = get_queried_object();
	$title = sprintf(__('@%s', 'capsule'), esc_html($term->name));
}
else if (is_tax('code')) {
	$term = get_queried_object();
	$title = sprintf(__('`%s', 'capsule'), esc_html($term->name));
}
else if (is_tax('code')) {
	$term = get_queried_object();
	$title = sprintf(__('`%s', 'capsule'), esc_html($term->name));
}
else if (is_author()) {
	$author = get_queried_object();
	$title = sprintf(__('Author: %s', 'capsule'), esc_html($author->display_name));
}
$title = apply_filters('capsule_page_title', $title);

?>
				<h1><?php echo $title; ?></h1>
				<form class="search clearfix" action="<?php echo esc_url(home_url('/')); ?>" method="get" data-permastruct="<?php echo $search_permastruct; ?>">
					<a href="#" class="filter-toggle"><?php _e('Filters', 'capsule'); ?></a>
					<input type="text" class="js-search" name="s" value="<?php echo esc_attr(get_query_var('s')); ?>" placeholder="<?php _e('Search @projects, #tags, `code, etc&hellip;', 'capsule'); ?>" />
					<input type="submit" value="<?php _e('Search', 'capsule'); ?>" />
				</form>
			</div>
			<div class="filter clearfix">
			<?php capsule_taxonomy_filter(); ?>
			</div>
		</header>
		<div class="body">
<?php

if (have_posts()) {
	while (have_posts()) {
		the_post();
		
		if (is_singular()) {
			include('views/content.php');
		}
		else {
			include('views/excerpt.php');
		}
	}
	if ( $wp_query->max_num_pages > 1 ) {
?>
			<nav class="pagination clearfix">
				<div class="nav-previous"><?php next_posts_link( __( 'Older posts <span class="meta-nav">&rarr;</span>', 'capsule' ) ); ?></div>
				<div class="nav-next"><?php previous_posts_link( __( '<span class="meta-nav">&larr;</span> Newer posts', 'capsule' ) ); ?></div>
			</nav>
<?php
	}
}
else if (is_search()) {
?>
			<p class="search-no-results-msg"><?php _e('Nothing to see here&hellip; move along.', 'capsule'); ?></p>
<?php
}

?>
		</div>
	</div>
	
</div>

<footer>
	<p><a href="http://crowdfavorite.com/capsule/">Capsule</a> by <a href="http://crowdfavorite.com">Crowd Favorite</a> &middot; <?php wp_loginout(home_url()); ?></p>
</footer>

<div id="projects">
	<h2><?php _e('Projects', 'capsule'); ?></h2>
	<ul>
<?php
wp_list_categories(array(
	'show_option_none' => '<span class="none">'.__('(none)', 'capsule').'</none>',
	'taxonomy' => 'projects',
	'title_li' => ''
));
?>
	</ul>
</div>
<div id="tags">
	<h2><?php _e('Tags', 'capsule'); ?></h2>
	<ul>
<?php
wp_list_categories(array(
	'show_option_none' => '<span class="none">'.__('(none)', 'capsule').'</none>',
	'taxonomy' => 'post_tag',
	'title_li' => ''
));
?>
	</ul>
</div>
<div id="servers">
	<h2><?php _e('Capsule Servers', 'capsule'); ?></h2>
	<ul>
<?php

// don't handle 0 server situations here because the menu item is hidden in that situation

// $cap_servers set at top of page
foreach ($cap_servers as $cap_server) {
	$server = $cap_client->process_server($cap_server);
?>
		<li><a href="<?php echo esc_url($server->url); ?>"><?php echo esc_html($server->post_title); ?></a></li>
<?php
}

?>
</div>
<div class="connection-error"><?php _e('Lost connection to server.', 'capsule'); ?></div>

<?php

if (!is_capsule_server() && !current_user_can('unfiltered_html')) {
?>
<div class="permissions-error"><?php _e('Capsule requires the <code>unfiltered_html</code> capability to work as expected. <a href="https://github.com/crowdfavorite/wp-capsule/issues/15">Learn more</a>.', 'capsule'); ?></div>
<?php
}

wp_footer();

?>

</body>
</html>
