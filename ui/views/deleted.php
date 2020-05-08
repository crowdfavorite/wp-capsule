<?php

/**
 * Deleted post template.
 *
 * @package capsule
 */

?>
<article
	id="post-deleted-<?php echo (int) $post->ID; ?>"
	data-post-id="<?php echo (int) $post->ID; ?>"
	<?php post_class('deleted clearfix', $post->ID); ?>
>
	<div class="post-content">
		<span class="msg">
			<?php esc_html_e('Post successfully deleted.', 'capsule'); ?>
		</span>
		<a
			href="<?php echo esc_url(admin_url('edit.php?post_status=trash&post_type=post')); ?>"
			class="post-undelete-link"
		>
			<?php esc_html_e('Recover from Trash', 'capsule'); ?>
		</a>
	</div>
<article>
