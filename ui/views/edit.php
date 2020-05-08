<?php
/**
 * Edit post template.
 *
 * @package capsule
 */

?>
<article id="post-edit-<?php echo (int) $post->ID; ?>" data-post-id="<?php echo (int) $post->ID; ?>" <?php post_class( 'edit clearfix' . ( is_sticky( $post->ID ) ? ' sticky' : '' ) ); ?>>
	<div class="post-date">
		<a href="<?php the_permalink(); ?>">
			<ul>
				<li class="day"><?php the_time( 'j' ); ?></li>
				<li class="month"><?php the_time( 'M' ); ?></li>
				<li class="year"><?php the_time( 'Y' ); ?></li>
			</ul>
		</a>
	</div>
	<div class="post-meta">
		<?php
			echo wp_kses_post( capsule_term_list( get_the_ID(), 'projects' ) );
			echo wp_kses_post( capsule_term_list( get_the_ID(), 'post_tag' ) );
			echo wp_kses_post( capsule_term_list( get_the_ID(), 'code' ) );
		?>
	</div>
	<div class="post-content">
		<div class="status">
			<p>
				<?php esc_html_e( 'Last Saved', 'capsule' ); ?>: <span class="post-last-saved"><?php echo esc_html( get_the_modified_date( 'g:i a' ) ); ?></span>
				<span class="dirty-indicator">&bull;</span>
				<span class="saving-indicator">&hellip;</span>
			</p>
		</div>
		<header>
			<a href="#" class="post-close-link capsule-icon-x-circle" title="<?php esc_html_e( 'Close', 'capsule' ); ?>">
				<span class="sr-only"><?php esc_html_e( 'Close', 'capsule' ); ?></span>
			</a>
		</header>
		<div id="ace-editor-<?php echo (int) $post->ID; ?>" class="ace-editor"></div>
	</div>
</article>
