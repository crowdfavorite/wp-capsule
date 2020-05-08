<?php
/**
 * Capsule UI entry point.
 *
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

if ( ! isset( $body_classes ) ) {
	$body_classes = array();
}

global $cap_client;
$cap_servers = $cap_client ? $cap_client->get_servers() : array();

$blog_desc         = get_bloginfo( 'description' );
$title_description = ( is_home() && ! empty( $blog_desc ) ? ' - ' . $blog_desc : '' );

$permalink_structure = get_option( 'permalink_structure' );
$search_permastruct  = ( empty( $permalink_structure ) ) ? '0' : '1';

if ( function_exists( 'cftf_is_filter' ) && cftf_is_filter() ) {
	$body_classes[] = 'filters-on';
}

$theme_url = trailingslashit( get_template_directory_uri() );

$title = '';

if ( is_home() || is_front_page() ) {
	$title = __( 'Home', 'capsule' );
} elseif ( function_exists( 'cftf_is_filter' ) && cftf_is_filter() ) {
	$title = __( 'Filter', 'capsule' );
} elseif ( is_search() ) {
	// Translators: %s is the search term.
	$title = sprintf( __( 'Search: %s', 'capsule' ), esc_html( get_query_var( 's' ) ) );
} elseif ( is_tag() ) {
	$term = get_queried_object();
	// Translators: %s is the taxonomy term name.
	$title = sprintf( __( '#%s', 'capsule' ), $term->name );
} elseif ( is_tax( 'projects' ) ) {
	$term = get_queried_object();
	// Translators: %s is the taxonomy term name.
	$title = sprintf( __( '@%s', 'capsule' ), $term->name );
} elseif ( is_tax( 'code' ) ) {
	$term = get_queried_object();
	// Translators: %s is the taxonomy term name.
	$title = sprintf( __( '`%s', 'capsule' ), $term->name );
} elseif ( is_tax( 'code' ) ) {
	$term = get_queried_object();
	// Translators: %s is the taxonomy term name.
	$title = sprintf( __( '`%s', 'capsule' ), $term->name );
} elseif ( is_author() ) {
	$author = get_queried_object();
	// Translators: %s is the author name.
	$title = sprintf( __( 'Author: %s', 'capsule' ), $author->display_name );
}
$title = apply_filters( 'capsule_page_title', $title );
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

	<title>
	<?php
		wp_title( '|', true, 'right' );
		echo esc_html( get_bloginfo( 'name' ), 1 ) . esc_html( $title_description );
	?>
	</title>

	<link rel="icon" href="<?php echo esc_url( $theme_url ); ?>ui/assets/icon/capsule-16.png" sizes="16x16">
	<link rel="icon" href="<?php echo esc_url( $theme_url ); ?>ui/assets/icon/capsule-32.png" sizes="32x32">
	<link rel="icon" href="<?php echo esc_url( $theme_url ); ?>ui/assets/icon/capsule-48.png" sizes="48x48">
	<link rel="icon" href="<?php echo esc_url( $theme_url ); ?>ui/assets/icon/capsule-128.png" sizes="128x128">
	<link rel="fluid-icon" href="<?php echo esc_url( $theme_url ); ?>ui/assets/icon/capsule.icns" title="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">

	<?php wp_head(); ?>
</head>
<body <?php body_class( implode( ' ', $body_classes ) ); ?>>
<div class="container">
	<nav class="main-nav">
		<ul>
			<?php do_action( 'capsule_main_nav_before' ); ?>
			<li>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="home icon capsule-icon-home" title="<?php esc_attr_e( 'Home', 'capsule' ); ?>">
					<span class="sr-only"><?php esc_html_e( 'Home', 'capsule' ); ?></span>
				</a>
			</li>
			<li>
				<a href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>" class="post-new-link icon capsule-icon-plus-circle" title="<?php esc_attr_e( 'Post New', 'capsule' ); ?>">
					<span class="sr-only"><?php esc_html_e( 'Post New', 'capsule' ); ?></span>
				</a>
			</li>
			<li>
				<a href="#projects" class="projects" title="<?php esc_attr_e( 'Projects', 'capsule' ); ?>">
					@
					<span class="sr-only"><?php esc_html_e( 'Projects', 'capsule' ); ?></span>
				</a>
			</li>
			<li>
				<a href="#tags" class="tags icon capsule-icon-numbersign" title="<?php esc_attr_e( 'Tags', 'capsule' ); ?>">
					<span class="sr-only"><?php esc_html_e( 'Tags', 'capsule' ); ?></span>
				</a>
			</li>
			<?php if ( ! empty( $cap_servers ) ) : ?>
			<li>
				<a href="#servers" class="servers icon capsule-icon-globe" title="<?php esc_attr_e( 'Servers', 'capsule' ); ?>">
					<span class="sr-only"><?php esc_html_e( 'Servers', 'capsule' ); ?></span>
				</a>
			</li>
			<?php endif; ?>

			<li>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=capsule' ) ); ?>" class="icon capsule-icon-cog-wheel" title="<?php esc_attr_e( 'Capsule Help', 'capsule' ); ?>">
					<span class="sr-only"><?php esc_html_e( 'Capsule Help', 'capsule' ); ?></span>
				</a>
			</li>
			<?php do_action( 'capsule_main_nav_after' ); ?>
			<li><span class="spacer"></span></li>
		</ul>
	</nav>

	<div id="wrap">
		<header id="header">
			<div class="inner">
				<h1><?php echo esc_html( $title ); ?></h1>
				<form class="search clearfix" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" data-permastruct="<?php echo esc_attr( $search_permastruct ); ?>">
					<a href="#" class="filter-toggle"><?php esc_html_e( 'Filters', 'capsule' ); ?></a>
					<input type="text" class="js-search" name="s" value="<?php echo esc_attr( get_query_var( 's' ) ); ?>" placeholder="<?php esc_attr_e( 'Search @projects, #tags, `code, etc&hellip;', 'capsule' ); ?>" />
					<input type="submit" value="<?php esc_attr_e( 'Search', 'capsule' ); ?>" />
				</form>
			</div>
			<div class="filter clearfix">
			<?php capsule_taxonomy_filter(); ?>
			</div>
		</header>
		<div class="body">
		<?php if ( have_posts() ) : ?>
			<?php
			while ( have_posts() ) :
				the_post();

				if ( is_singular() ) {
					include 'views/content.php';
				} else {
					include 'views/excerpt.php';
				}
			endwhile;
			?>
			<?php if ( $wp_query->max_num_pages > 1 ) : ?>
			<nav class="pagination clearfix">
				<div class="nav-previous"><?php next_posts_link( __( 'Older posts <span class="meta-nav">&rarr;</span>', 'capsule' ) ); ?></div>
				<div class="nav-next"><?php previous_posts_link( __( '<span class="meta-nav">&larr;</span> Newer posts', 'capsule' ) ); ?></div>
			</nav>
			<?php endif; ?>
		<?php elseif ( is_search() ) : ?>
			<p class="search-no-results-msg"><?php esc_html_e( 'Nothing to see here&hellip; move along.', 'capsule' ); ?></p>
		<?php endif; ?>
		</div>
	</div>
</div>

<footer>
	<p><a href="http://crowdfavorite.com/capsule/">Capsule</a> by <a href="http://crowdfavorite.com">Crowd Favorite</a> &middot; <?php wp_loginout( home_url() ); ?></p>
</footer>

<div id="projects">
	<h2><?php esc_html_e( 'Projects', 'capsule' ); ?></h2>
	<ul>
	<?php
		wp_list_categories(
			array(
				'show_option_none' => '<span class="none">' . __( '(none)', 'capsule' ) . '</none>',
				'taxonomy'         => 'projects',
				'title_li'         => '',
			)
		);
	?>
	</ul>
</div>
<div id="tags">
	<h2><?php esc_html_e( 'Tags', 'capsule' ); ?></h2>
	<ul>
	<?php
		wp_list_categories(
			array(
				'show_option_none' => '<span class="none">' . __( '(none)', 'capsule' ) . '</none>',
				'taxonomy'         => 'post_tag',
				'title_li'         => '',
			)
		);
	?>
	</ul>
</div>
<div id="servers">
	<h2><?php esc_html_e( 'Capsule Servers', 'capsule' ); ?></h2>
	<ul>
	<?php
		// Don't handle 0 server situations here because the menu item is hidden in that situation.
		// $cap_servers set at top of page.
	?>
	<?php foreach ( $cap_servers as $cap_server ) : ?>
		<?php $server = $cap_client->process_server( $cap_server ); ?>
		<li>
			<a href="<?php echo esc_url( $server->url ); ?>" target="_blank">
				<i class="capsule-icon-open-in-new"></i>
				<?php echo esc_html( $server->post_title ); ?>
			</a>
		</li>
	<?php endforeach; ?>
	</ul>
</div>
<div class="connection-error"><?php esc_html_e( 'Lost connection to server.', 'capsule' ); ?></div>

<?php if ( ! is_capsule_server() && ! current_user_can( 'unfiltered_html' ) ) : ?>
	<div class="permissions-error"><?php esc_html_e( 'Capsule requires the <code>unfiltered_html</code> capability to work as expected. <a href="https://github.com/crowdfavorite/wp-capsule/issues/15">Learn more</a>.', 'capsule' ); ?></div>
<?php endif; ?>

<?php wp_footer(); ?>

</body>
</html>
