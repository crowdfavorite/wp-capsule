<?php

/**
 * @package rutter
 *
 * This file is part of the Rutter Theme for WordPress
 * http://crowdfavorite.com/rutter/
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

$blog_desc = get_bloginfo('description');
$title_description = (is_home() && !empty($blog_desc) ? ' - '.$blog_desc : '');

if (get_option('permalink_structure') != '') {
	$search_onsubmit = "location.href=this.action+'search/'+encodeURIComponent(this.s.value).replace(/%20/g, '+'); return false;";
}
else {
	$search_onsubmit = '';
}

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
	<link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo('stylesheet_url'); ?>" />
<?php
wp_head();
?>
</head>
<body>

<header id="header">
	<div class="inner">
		<form class="clearfix" action="<?php echo esc_url(home_url('/')); ?>" method="get" onsubmit="<?php echo $search_onsubmit; ?>">
			<input type="text" name="s" value="" placeholder="<?php _e('Search', 'rutter'); ?>" />
			<input type="submit" name="search_submit" value="<?php _e('Search', 'rutter'); ?>" />
			<a href="<?php echo esc_url(admin_url('post-new.php')); ?>" class="post-new-link"><?php _e('New', 'rutter'); ?></a>
		</form>
	</div>
</header>

<div id="wrap">
	<div class="body">
<?php

if (is_search() || is_archive()) {
	include(STYLESHEETPATH.'/lib/cf-archive-title/cf-archive-title.php');
	cfpt_page_title('<h2 class="page-title">', '</h2>');
}

if (have_posts()) {
	while (have_posts()) {
		the_post();
		$ymd = get_the_time('Ymd', $post);
		the_date('F j, Y', '<h2 class="date-title date-'.$ymd.'">', '</h2>');
		if (is_singular()) {
			include('views/content.php');
		}
		else {
			include('views/excerpt.php');
		}
	}
	if ( $wp_query->max_num_pages > 1 ) {
?>
		<nav class="pagination">
			<div class="nav-previous"><?php next_posts_link( __( 'Older posts <span class="meta-nav">&rarr;</span>', 'twentyeleven' ) ); ?></div>
			<div class="nav-next"><?php previous_posts_link( __( '<span class="meta-nav">&larr;</span> Newer posts', 'twentyeleven' ) ); ?></div>
		</nav>
<?php
	}

}

?>
	</div>
</div>

<footer id="footer"><a href="http://crowdfavorite.com/rutter/">Rutter</a> by <a href="http://crowdfavorite.com">Crowd Favorite</a></footer>

<?php
wp_footer();
?>

</body>
</html>
