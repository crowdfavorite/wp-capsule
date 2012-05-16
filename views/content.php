<article id="post-content-<?php the_ID(); ?>" data-post-id="<?php the_ID(); ?>" <?php post_class('content clearfix'); ?>>
	<header>
		<a href="<?php the_permalink(); ?>" class="post-link"><?php the_time(); ?></a>
		<?php edit_post_link(__('Edit', 'capsule'), '', ''); ?>
	</header>
	<div class="meta">
		<h3><?php _e('Projects', 'capsule'); ?></h3>
		<?php echo capsule_term_list(get_the_ID(), 'projects'); ?>
		<br>
		<h3><?php _e('Tags', 'capsule'); ?></h3>
		<?php echo capsule_term_list(get_the_ID(), 'post_tag'); ?>
		<br>
		<h3><?php _e('Code', 'capsule'); ?></h3>
		<?php echo capsule_term_list(get_the_ID(), 'code'); ?>
	</div>
	<div class="content"><?php the_content(); ?></div>
</article>
