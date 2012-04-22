<?php

function cfdd_register_taxonomies() {
	register_taxonomy(
		'projects',
		'projects',
		array(
			'hierarchical' => true,
			'label' => __('Projects'),
			'sort' => true,
			'args' => array('orderby' => 'term_order'),
			'rewrite' => array(
				'slug' => 'projects',
				'with_front' => false,
			),
		)
	);
	register_taxonomy(
		'evergreen',
		'evergreen',
		array(
			'hierarchical' => true,
			'label' => __('Evergreen'),
			'sort' => true,
			'args' => array('orderby' => 'term_order'),
			'rewrite' => array(
				'slug' => 'evergreen',
				'with_front' => false,
			),
		)
	);
}
add_action('init', 'cfdd_register_taxonomies');
