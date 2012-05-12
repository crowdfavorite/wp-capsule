<?php

$terms = array();

$taxes = array('projects', 'post_tag', 'code');
foreach ($taxes as $tax) {
	if (($tax_terms = get_the_terms($post->ID, $tax)) != false) {
		$terms = array_merge($terms, $tax_terms);
	}
}

if (count($terms)) {
	$tags = get_the_term_list($post->ID, 'projects', '<span class="tag-group">', ', ', '</span>')
		.get_the_term_list($post->ID, 'post_tag', '<span class="tag-group">', ', ', '</span>')
		.get_the_term_list($post->ID, 'code', '<span class="tag-group">', ', ', '</span>');
}
else {
	$tags = '<span class="none">'.__('(no tags)', 'capsule').'</span>';
}

?>
<article id="post-excerpt-<?php the_ID(); ?>" data-post-id="<?php the_ID(); ?>" <?php post_class('excerpt clearfix'); ?>>
	<header>
		<a href="<?php the_permalink(); ?>" class="post-link"><?php the_time(); ?></a>
<?php
echo $tags;
edit_post_link(__('Edit', 'capsule')); 
?>
	</header>
	<div class="content"><?php the_excerpt(); ?></div>
	<a href="<?php echo admin_url('post.php?post='.$post->ID.'&action=trash'); ?>" class="post-delete-link"><?php _e('Delete', 'capsule'); ?></a>
</article>
