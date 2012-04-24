<?php

define('RUTTER_TAX_PREFIX_PROJECT', '@');
define('RUTTER_TAX_PREFIX_TAG', '#');

function cfrutter_register_taxonomies() {
	register_taxonomy(
		'projects',
		'post',
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
		'post',
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
add_action('init', 'cfrutter_register_taxonomies');

function cfrutter_get_the_terms($terms, $id, $taxonomy) {
	// this was getting called twice for post_tag, not sure why
	global $RUTTER_TAX_FILTERED;
	if (!isset($RUTTER_TAX_FILTERED)) {
		$RUTTER_TAX_FILTERED = array();
	}
	if (is_array($terms) && count($terms) && !in_array($taxonomy, $RUTTER_TAX_FILTERED)) {
		$RUTTER_TAX_FILTERED[] = $taxonomy;
		switch ($taxonomy) {
			case 'projects':
				$_terms = array();
				foreach ($terms as $term_id => $term) {
					$term->name = RUTTER_TAX_PREFIX_PROJECT.$term->name;
					$_terms[$term_id] = $term;
				}
				$terms = $_terms;
				break;
			case 'post_tag':
				$_terms = array();
				foreach ($terms as $term_id => $term) {
					$term->name = RUTTER_TAX_PREFIX_TAG.$term->name;
					$_terms[$term_id] = $term;
				}
				$terms = $_terms;
				break;
		}
	}
	return $terms;
}
add_filter('get_the_terms', 'cfrutter_get_the_terms', 10, 3);
