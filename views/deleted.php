<article id="post-deleted-<?php echo $post->ID; ?>" data-post-id="<?php echo $post->ID; ?>" <?php post_class('deleted clearfix', $post->ID); ?>>
	<div class="content"><?php printf(__('<span class="msg">Post successfully deleted.</span> <a href="%s" class="post-undelete-link">Recover from Trash</a>', 'capsule'), admin_url('edit.php?post_status=trash&post_type=post')); ?></div>
</article>
