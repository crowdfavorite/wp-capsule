<article id="post-excerpt-<?php the_ID(); ?>" <?php post_class('excerpt'); ?>>
	<div class="content"><?php the_excerpt(); ?></div>
	<div class="meta">
<?php
printf(__('<h4>Projects</h4> %s', 'exhaust'), get_the_term_list($post->ID, 'projects'));
the_tags(__('<h4>Tags</h4> ', 'exhaust'), ', ', '');
?>
		<a href="<?php the_permalink(); ?>"><?php the_time(); ?></a>
	</div>
</article>
