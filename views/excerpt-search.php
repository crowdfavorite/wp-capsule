<article <?php post_class('search-excerpt clearfix'); ?>>
	<div class="date"><?php the_time(); ?></div>
	<div class="content"><?php the_excerpt(); ?></div>
	<div class="edit"><?php edit_post_link(__('Edit', 'rutter'), '<span class="edit-link">', '</span>'); ?></div>
</article>
