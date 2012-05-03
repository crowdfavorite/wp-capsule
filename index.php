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
	<title><?php wp_title(); ?></title>
	<link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo('stylesheet_url'); ?>" />
<?php
wp_head();
?>
</head>
<body>

<header id="header">
	<form action="<?php echo esc_url(home_url('/')); ?>" method="get">
		<input type="text" name="s" value="" />
		<input type="submit" name="search_submit" value="<?php _e('Search', 'rutter'); ?>" />
	</form>
</header>

<div id="wrap">

	<div class="body">
<?php

if (have_posts()) {
	while (have_posts()) {
		the_post();
		the_date('F j, Y', '<h2 class="date-title">', '</h2>');
		if (is_singular()) {
			include('views/content.php');
		}
		else {
			include('views/excerpt.php');
		}
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
