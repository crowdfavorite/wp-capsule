<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<title><?php wp_title(); ?></title>
</head>
<body>

<?php

if (have_posts()) {
	$i = 0;
	while (have_posts()) {
		the_post();
		include('excerpt.php');
	}
}

?>

</body>
</html>
