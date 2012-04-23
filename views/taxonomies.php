<?php

$terms = array_merge(
	get_the_terms($post->ID, 'projects'),
	get_the_terms($post->ID, 'post_tag')
);
if (count($terms)) {
	echo get_the_term_list($post->ID, 'projects')
		.' '
		.get_the_term_list($post->ID, 'post_tag');
}
else {
	echo '<span class="none">'.__('(no tags)', 'rutter').'</span>';
}
