<article id="post-edit-<?php the_ID(); ?>" data-post-id="<?php the_ID(); ?>" <?php post_class('edit clearfix'); ?>>
	<header>
		<a href="<?php the_permalink(); ?>" class="post-link"><?php the_time(); ?></a>
		<a href="#" class="post-close-link"><?php _e('Close', 'rutter'); ?></a>
	</header>
	<div id="ace-editor-<?php the_ID(); ?>" class="ace-editor"></div>
</article>
