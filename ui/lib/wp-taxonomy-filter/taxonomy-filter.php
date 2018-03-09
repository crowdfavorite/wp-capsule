<?php
/**
 * Taxonomy filter library.
 *
 * @package cf-taxonomy-filter
 */

require 'class-cf-taxonomy-filter.php';

CF_Taxonomy_Filter::add_actions();

/**
 * Build and echo a posts filter form. See README for constructing one manually and examples.
 *
 * @param array $args Array of arguments.
 * Note that all 'option' arguments accept any
 * additional key value pair that is permitted through CF_Taxonomy_Filter::allowed_attributes.
 *
 * form_options     Array of options, strictly attributes on the opening form element
 *
 * taxonomies       Array of arrays with the taxonomy name being the key and the second array
 *                  an options array for that taxonomy. An options array is not required and will
 *                  use default values if not set.
 *                  'multiple'          => controls wether or not multiple terms can be selected.
 *                  'selected'          => an array term names which should be pre selected on form output.
 *                  'data-placeholder'  => placeholder text. Defaults to taxonomy label.
 *                  'prefix'            => allows you to add a prefix to all the term names for displayed
 *                  'hide_empty'        => Whether or not to show empty terms
 *
 * authors          true/false whether or not to display the author filter feature
 *
 * author_options   Array of options controlling author filter output
 *                  'multiple'          => key controls wether or not multiple authors can be selected.
 *                  'user_query'        => array of WP_User_Query arguments to control which users are shown
 *                                         for selection. Default is all users.
 *
 * submit_options   Array of options
 *                  'value'             =>  Defaults to 'Submit'
 *
 * date             true/false whether to show a date range filter
 *
 * date_options     Array of arrays with 'start' and 'end' as keys. Nested arrays are options
 *                  for 'start' or 'end'
 *                  'text'              => Placeholder text (withing option array for start or end)
 *                                         defaults to 'Start Date' and 'End Date'.
 **/
function cftf_build_form( $args = array() ) {
	$cftf = new CF_Taxonomy_Filter( $args );
	$cftf->build_form();
}

/**
 * Determines if the current page is a filter page. Use this like 'is_search' or 'is_home'.
 *
 * @return boolean True if the current page is a filter page.
 **/
function cftf_is_filter() {
	return ( isset( $_REQUEST['cftf_action'] ) && 'filter' === $_REQUEST['cftf_action'] );
}

/**
 * Title filter.
 *
 * @param string $title       Title.
 * @param string $sep         Separator.
 * @param string $seplocation Separator location.
 * @return string             Updated title.
 */
function cftf_wp_title( $title, $sep, $seplocation ) {
	if ( cftf_is_filter() ) {
		$title = __( 'Filter Results', 'cftf' );

		if ( 'right' === $seplocation ) {
			$title = $title . ' ' . $sep;
		} else {
			$title = $sep . ' ' . $title;
		}
	}
	return $title;
}
add_filter( 'wp_title', 'cftf_wp_title', 10, 3 );

/**
 * Enqueue scripts.
 *
 * @return void
 */
function cftf_enqueue_scripts() {
	// Figure out the URL for this file.
	$parent_dir = trailingslashit( get_template_directory() );
	$child_dir  = trailingslashit( get_stylesheet_directory() );

	$plugin_dir = trailingslashit( basename( dirname( __FILE__ ) ) );
	$file       = basename( __FILE__ );

	if ( file_exists( $parent_dir . 'functions/' . $plugin_dir . $file ) ) {
		$url = trailingslashit( get_template_directory_uri() ) . 'functions/' . $plugin_dir;
	} elseif ( file_exists( $parent_dir . 'plugins/' . $plugin_dir . $file ) ) {
		$url = trailingslashit( get_template_directory_uri() ) . 'plugins/' . $plugin_dir;
	} elseif ( $child_dir !== $parent_dir && file_exists( $child_dir . 'functions/' . $plugin_dir . $file ) ) {
		$url = trailingslashit( get_stylesheet_directory_uri() ) . 'functions/' . $plugin_dir;
	} elseif ( $child_dir !== $parent_dir && file_exists( $child_dir . 'plugins/' . $plugin_dir . $file ) ) {
		$url = trailingslashit( get_stylesheet_directory_uri() ) . 'plugins/' . $plugin_dir;
	} else {
		$url = plugin_dir_url( __FILE__ );
	}

	// In case the end user has not used one of the usual suspects.
	$url = trailingslashit( apply_filters( 'cftf_url', $url ) );

	wp_enqueue_script( 'jquery-ui-datepicker' );
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'chosen', $url . 'lib/chosen/chosen.jquery.min.js', array( 'jquery' ), null, true );
	wp_enqueue_script( 'cftf', $url . '/taxonomy-filter.js', array( 'jquery', 'chosen', 'jquery-ui-datepicker' ), '20180206.1250', true );

	wp_enqueue_style( 'chosen', $url . '/lib/chosen/chosen.css', array(), null, 'all' );
}
add_action( 'wp_enqueue_scripts', 'cftf_enqueue_scripts' );

