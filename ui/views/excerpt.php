<?php
/**
 * Post excerpt template.
 *
 * @package capsule
 */

$pushed            = '';
$is_capsule_server = is_capsule_server();
if ( ! $is_capsule_server ) {
	$pushed = capsule_last_pushed( get_the_ID() );
}

?>
<article id="post-content-<?php the_ID(); ?>" data-post-id="<?php the_ID(); ?>" <?php post_class( 'content clearfix excerpt' . ( is_sticky() ? ' sticky' : '' ) ); ?>>
	<div class="post-date">
		<a href="<?php the_permalink(); ?>">
			<ul>
				<li class="day"><?php the_time( 'j' ); ?></li>
				<li class="month"><?php the_time( 'M' ); ?></li>
				<li class="year"><?php the_time( 'Y' ); ?></li>
			</ul>
		</a>
		<?php if ( ! empty( $pushed ) ) : ?>
		<div class="push-server-meta">
			<span class="trigger">&#59254;</span>
			<?php echo wp_kses_post( $pushed ); ?>
		</div>
		<?php endif; ?>
	</div>
	<div class="post-meta">
	<?php
		echo wp_kses_post( capsule_term_list( get_the_ID(), 'projects' ) );
		echo wp_kses_post( capsule_term_list( get_the_ID(), 'post_tag' ) );
		echo wp_kses_post( capsule_term_list( get_the_ID(), 'code' ) );
	?>
	<?php if ( is_capsule_server() ) : ?>
		<p class="author">
			<?php echo get_avatar( get_the_author_meta( 'email' ), 20 ); ?>
			<a href="<?php echo esc_url( get_author_posts_url( $post->post_author ) ); ?>"><?php echo esc_html( get_the_author_meta( 'display_name' ) ); ?></a>
		</p>
	<?php endif; ?>
	</div>
	<div class="post-content">
		<?php the_content(); ?>
		<nav class="post-menu">
			<?php do_action( 'capsule_post_menu_before', get_the_ID() ); ?>
			<a href="#" class="post-sticky-link" title="<?php esc_html_e( 'Star', 'capsule' ); ?>">&#57391;</a>
			<a href="#" class="post-unsticky-link" title="<?php esc_html_e( 'Un-Star', 'capsule' ); ?>">&#57393;</a>
			<span class="post-sticky-loading" title="<?php esc_html_e( 'Loading...', 'capsule' ); ?>">&#59441;</span>
			<?php edit_post_link( '&#57535;', '', '' ); ?>
			<a href="#" class="post-delete-link" title="<?php esc_html_e( 'Trash', 'capsule' ); ?>">&#59177;</a>
			<?php do_action( 'capsule_post_menu_after', get_the_ID() ); ?>
		</nav>
		<div class="post-toggle"></div>
	</div>
</article>
