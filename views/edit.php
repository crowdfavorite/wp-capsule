<article id="post-edit-<?php echo $post->ID; ?>" data-post-id="<?php echo $post->ID; ?>" <?php post_class('edit clearfix', $post->ID); ?>>
	<header>
		<a href="<?php echo get_permalink($post->ID); ?>" class="post-link"><?php echo get_the_time('', $post); ?></a>
		<a href="#" class="post-close-link"><?php _e('Close', 'rutter'); ?></a>
	</header>
	<div id="ace-editor-<?php echo $post->ID; ?>" class="ace-editor"></div>
</article>
