<article id="post-edit-<?php echo $post->ID; ?>" data-post-id="<?php echo $post->ID; ?>" <?php post_class('edit clearfix' . (is_sticky($post->ID) ? ' sticky' : '')); ?>>
	<div class="post-date">
		<a href="<?php the_permalink(); ?>">
			<ul>
				<li class="day"><?php the_time('j'); ?></li>
				<li class="month"><?php the_time('M'); ?></li>
				<li class="year"><?php the_time('Y'); ?></li>
			</ul>
		</a>
	</div>
	<div class="post-meta">
<?php
echo capsule_term_list(get_the_ID(), 'projects');
echo capsule_term_list(get_the_ID(), 'post_tag');
echo capsule_term_list(get_the_ID(), 'code');

?>
	</div>
	<div class="post-content">
		<div class="status">
			<p>
				<?php printf(__('Last Saved: <span class="post-last-saved">%s</span>', 'capsule'), get_the_modified_date('g:i a')); ?>
				<span class="dirty-indicator">&bull;</span>
				<span class="saving-indicator">&hellip;</span>
			</p>
		</div>
		<header>
			<a href="#" class="post-close-link" title="<?php _e('Close', 'capsule'); ?>">&#59393;</a>
		</header>
		<div id="ace-editor-<?php echo $post->ID; ?>" class="ace-editor"></div>
	</div>
</article>
