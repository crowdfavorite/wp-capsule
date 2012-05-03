<article id="post-edit-<?php echo esc_attr($post_id); ?>" data-post-id="<?php echo esc_attr($post_id); ?>" <?php post_class('edit clearfix'); ?>>
	<header>
		&nbsp;
		<a href="#" class="post-close-link"><?php _e('Close', 'rutter'); ?></a>
	</header>
	<div id="ace-editor-<?php echo esc_attr($post_id); ?>" class="ace-editor"></div>
</article>
