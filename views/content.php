<article id="post-content-<?php the_ID(); ?>" data-post-id="<?php the_ID(); ?>" <?php post_class('content clearfix'); ?>>
	<header>
		<a href="<?php the_permalink(); ?>" class="post-link"><?php the_time(); ?></a>
		<?php edit_post_link(__('Edit', 'rutter'), '', ''); ?>
	</header>
	<div class="meta">
		<h3><?php _e('Projects', 'rutter'); ?></h3>
		<?php echo cfrutter_term_list(get_the_ID(), 'projects'); ?>
		<br>
		<h3><?php _e('Tags', 'rutter'); ?></h3>
		<?php echo cfrutter_term_list(get_the_ID(), 'post_tag'); ?>
	</div>
	<div class="content"><?php the_content(); ?></div>
</article>
