<article id="post-edit-<?php echo $post->ID; ?>" data-post-id="<?php echo $post->ID; ?>" <?php post_class('edit clearfix', $post->ID); ?>>
	<header>
		<a href="<?php echo get_permalink($post->ID); ?>" class="post-link"><?php echo get_the_time('', $post); ?></a>
		<span class="post-dirty">&#9733;</span>
		<span class="post-last-saved">&nbsp;</span>
		<a href="#" class="post-close-link"><?php _e('Close', 'capsule'); ?></a>
		<a href="#" class="post-save-link"><?php _e('Save', 'capsule'); ?></a>
		<img src="<?php echo esc_url(admin_url('images/wpspin_dark.gif')); ?>" class="save-indicator" />
	</header>
	<div id="ace-editor-<?php echo $post->ID; ?>" class="ace-editor"></div>
</article>
