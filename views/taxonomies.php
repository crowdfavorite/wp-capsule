<?php

$terms = array();

$taxes = array('projects', 'post_tag');
foreach ($taxes as $tax) {
	if (($tax_terms = get_the_terms($post->ID, $tax)) != false) {
		$terms = array_merge($terms, $tax_terms);
	}
}

if (count($terms)) {
	echo get_the_term_list($post->ID, 'projects')
		.' '
		.get_the_term_list($post->ID, 'post_tag');
}
else {
	echo '<span class="none">'.__('(no tags)', 'rutter').'</span>';
}
