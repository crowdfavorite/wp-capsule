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
</head>
<body>

<div id="wrap">

	<header>
		<form action="<?php echo esc_url(home_url()); ?>" method="get">
			<input type="text" name="s" value="" />
			<input type="submit" name="search_submit" value="<?php _e('Search', 'exhaust'); ?>" />
		</form>
	</header>

	<div class="body">
<?php

if (have_posts()) {
	while (have_posts()) {
		the_post();
		the_date('F j, Y', '<h2 class="date-title">', '</h2>');
		include('views/excerpt.php');
	}
}

?>
	</div>

	<footer>FOOTER CONTENT HERE</footer>

</div>

</body>
</html>
