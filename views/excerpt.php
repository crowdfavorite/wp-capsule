<?php

$terms = array();

$taxes = array('projects', 'post_tag');
foreach ($taxes as $tax) {
	if (($tax_terms = get_the_terms($post->ID, $tax)) != false) {
		$terms = array_merge($terms, $tax_terms);
	}
}

if (count($terms)) {
	$tags = get_the_term_list($post->ID, 'projects')
		.' '
		.get_the_term_list($post->ID, 'post_tag');
}
else {
	$tags = '<span class="none">'.__('(no tags)', 'rutter').'</span>';
}

?>
<article id="post-excerpt-<?php the_ID(); ?>" data-post-id="<?php the_ID(); ?>" <?php post_class('excerpt clearfix'); ?>>
	<header>
		<a href="<?php the_permalink(); ?>" class="post-link"><?php the_time(); ?></a>
<?php
echo $tags;
edit_post_link(__('Edit', 'rutter')); 
?>
	</header>
	<div class="content"><?php the_excerpt(); ?></div>
</article>
