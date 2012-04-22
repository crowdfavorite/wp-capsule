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
</head>
<body>

<header>
	<form action="" method="get">
		<input type="text" name="s" value="" />
		<input type="submit" name="search_submit" value="<?php _e('search', 'exhaust'); ?>" />
	</form>
</header>

<div class="body">
<?php

if (have_posts()) {
	while (have_posts()) {
		the_post();
?>
	<h2 class="h4"><?php the_date(); ?></h2>
<?php
		include('excerpt.php');
	}
}

?>
</div>

<footer></footer>

</body>
</html>
