<?php
/**
 * Class implementing main functionality for taxonomy filter.
 *
 * @package cf-taxonomy-filter
 */

/**
 * Taxonomy filter class.
 */
class CF_Taxonomy_Filter {
	/**
	 * Constructor.
	 *
	 * @param array $args Additional arguments.
	 */
	public function __construct( $args ) {
		// These keys are always required. Set them here so we don't have to think about existence later.
		$default_keys  = array(
			'form_options'   => array(),
			'submit_options' => array(),
		);
		$this->options = array_merge( $default_keys, $args );
	}

	/**
	 * Add actions hook.
	 *
	 * @return void
	 */
	public static function add_actions() {
		add_action( 'pre_get_posts', array( 'CF_Taxonomy_Filter', 'pre_get_posts' ) );
	}

	/**
	 * Builds a form based on arguments passed into the constructor.
	 *
	 * @return void
	 */
	public function build_form() {
		self::start_form( $this->options['form_options'] );

		if ( ! empty( $this->options['taxonomies'] ) ) {
			foreach ( $this->options['taxonomies'] as $taxonomy => $args ) {
				if ( is_array( $args ) ) {
					self::tax_filter( $taxonomy, $args );
				} else {
					// Just passed in taxonomy name with no options.
					self::tax_filter( $args );
				}
			}
		}

		if ( ! empty( $this->options['authors'] ) ) {
			$author_options = ! empty( $this->options['author_options'] ) ? $this->options['author_options'] : array();
			self::author_select( $author_options );
		}

		if ( ! empty( $this->options['date'] ) ) {
			$start_options = ! empty( $this->options['date_options']['start'] ) ? $this->options['date_options']['start'] : array();
			$end_options   = ! empty( $this->options['date_options']['end'] ) ? $this->options['date_options']['end'] : array();
			self::date_filter( $start_options, $end_options );
		}

		self::submit_button( $this->options['submit_options'] );

		self::end_form();
	}

	/**
	 * Echo a date range filter form element.
	 *
	 * @param array $start_args Optional array of arguments for start range input. All options are attributes on the element.
	 * @param array $end_args   Optional array of arguments for end range input. All options are attributes on the element.
	 * @return void
	 **/
	public static function date_filter( $start_args = array(), $end_args = array() ) {
		$start_defaults = array(
			'placeholder' => __( 'From', 'cftf' ),
		);
		$end_defaults   = array(
			'placeholder' => __( 'To', 'cftf' ),
		);

		$start_args = array_merge( $start_defaults, $start_args );
		$start_args = self::add_class( 'cftf-date', $start_args );
		if ( isset( $_GET['cftf_date']['start'] ) ) {
			$start_args['value'] = $_GET['cftf_date']['start'];
		}

		$end_args = array_merge( $end_defaults, $end_args );
		$end_args = self::add_class( 'cftf-date', $end_args );
		if ( isset( $_GET['cftf_date']['end'] ) ) {
			$end_args['value'] = $_GET['cftf_date']['end'];
		}
		?>
		<input type="text" name="cftf_date[start]" autocomplete="off" <?php echo wp_kses( self::build_attrib_string( $start_args ), array() ); ?> />
		<span class="cftf-date-sep"><?php esc_html_x( 'to', 'start date range input TO end date range input', 'cftf' ); ?></span>
		<input type="text" name="cftf_date[end]" autocomplete="off" <?php echo wp_kses( self::build_attrib_string( $end_args ), array() ); ?> />
		<?php
	}

	/**
	 * Echo a taxonomy filter form element.
	 *
	 * @param string $taxonomy The taxonomy slug to generate the form for.
	 * @param array  $args Optional array of arguments.
	 *      'data-placeholder' is placeholder text for the input
	 *      'prefix' is a prefix added to the term dropdown. For typeahead support, users will
	 *          have to type the prefix as well.
	 *      'multiple' Determines whether or not multiple terms can be selected
	 *      'selected' is an array of term names which are preselected on initial form generation
	 *      all additional arguments are attributes of the select box. see allowed_attributes();
	 *      'hide_empty' Whether to display empty terms.
	 * @return void
	 **/
	public static function tax_filter( $taxonomy, $args = array() ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return;
		}

		$tax_obj = get_taxonomy( $taxonomy );

		$defaults = array(
			'prefix'           => '',
			'multiple'         => true,
			'selected'         => array(),
			'data-placeholder' => $tax_obj->labels->name,
			'hide_empty'       => true,
		);

		$args = array_merge( $defaults, $args );
		// Always need cftf-tax-filter as a class so chosen can target it.
		$args = self::add_class( 'cftf-tax-select', $args );

		// Set the initially selected arguments. Try for previous queried, if none exists, get the id of the term names passed in.
		if ( ! empty( $_GET['cftf_action'] ) ) {
			$args['selected'] = isset( $_GET['cfct_tax'][ $taxonomy ] ) ? (array) $_GET['cfct_tax'][ $taxonomy ] : array();
			$args['selected'] = array_map(
				function( $elem ) {
					return (int) $elem;
				},
				$args['selected']
			);
		} elseif ( ! empty( $args['selected'] ) ) {
			$selected_names   = (array) $args['selected'];
			$args['selected'] = array();
			foreach ( $selected_names as $term_name ) {
				$term = get_term_by( 'name', $term_name, $taxonomy );
				if ( $term ) {
					$args['selected'][] = $term->term_id;
				}
			}
		}

		$terms = get_terms( $taxonomy, array( 'hide_empty' => $args['hide_empty'] ) );

		$multiple = ( $args['multiple'] ) ? 'multiple' : '';
		// Build the select form element.
		?>
		<select name="<?php echo esc_attr( 'cfct_tax[' . $taxonomy . '][]' ); ?>" <?php echo wp_kses( self::build_attrib_string( $args ), array() ); ?> <?php echo esc_attr( $multiple ); ?>>
			<!-- Empty option for single select removal for Chosen. -->
			<option value=""></option>
			<?php foreach ( $terms as $term ) : ?>
			<option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( in_array( $term->term_id, $args['selected'], true ), true, true ); ?>><?php echo esc_html( $args['prefix'] . $term->name ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Echo a user filter form element.
	 *
	 * @param array $args Optional array of arguments.
	 *      'data-placeholder' is placeholder text for the input
	 *      'user_query' is an array of WP_User_Query arguments to override which
	 *           users are selectable (no backend enforcing of these)
	 *      'selected' is an array of user ids which are preselected on initial form generation
	 *      all additional arguments are attributes of the select box. see allowed_attributes().
	 * @return void
	 **/
	public static function author_select( $args = array() ) {
		$defaults = array(
			'multiple'         => true,
			'selected'         => array(),
			'data-placeholder' => __( 'Author', 'cftf' ),
			'user_query'       => array(
				'orderby' => 'display_name',
			),
		);

		$args = array_merge( $defaults, $args );

		// Already queried, repopulate the form with selected items.
		if ( ! empty( $_GET['cftf_action'] ) ) {
			$args['selected'] = isset( $_GET['cftf_authors'] ) ? $_GET['cftf_authors'] : array();
		}
		$args['selected'] = (array) $args['selected'];

		// Always need cftf-author-filter as a class so chosen can target it.
		$args = self::add_class( 'cftf-author-select', $args );

		$user_query = new WP_User_Query( $args['user_query'] );
		if ( ! empty( $user_query->results ) ) {
			$users = apply_filters( 'cftf_users', $user_query->results );
		} else {
			$users = array();
		}

		// Only output the author filter if we have more than one author.
		if ( 1 === count( $users ) ) {
			return;
		}
		$multiple = ( $args['multiple'] ) ? 'multiple' : '';
		?>
		<select name="cftf_authors[]" <?php echo wp_kses( self::build_attrib_string( $args ), array() ); ?> <?php echo esc_attr( $multiple ); ?>>
			<!-- Empty option for single select removal support. -->
			<option value=""></option>
			<?php foreach ( $users as $user ) : ?>
				<option value="<?php echo (int) $user->ID; ?>" <?php selected( in_array( $user->ID, $args['selected'], true ), true, true ); ?>><?php echo esc_html( $user->display_name ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Echo a submit form element.
	 *
	 * @param array $args Optional array of arguments. 'text' is the submit button value,
	 * all additional arguments are attributes of the input. see allowed_attributes().
	 * @return void
	 **/
	public static function submit_button( $args = array() ) {
		$defaults = array(
			'value' => __( 'Apply', 'cftf' ),
			'class' => '',
			'id'    => '',
		);
		$args     = array_merge( $defaults, $args );

		echo '<input type="submit"' . wp_kses( self::build_attrib_string( $args ), array() ) . ' />';
	}

	/**
	 * Opens the form tag.
	 *
	 * @param array $args Option argument array, each of which are just attributes on the form element.
	 * @return void
	 **/
	public static function start_form( $args = array() ) {
		$defaults = array(
			'id'     => 'cftf-filter',
			'class'  => '',
			'action' => home_url( '?s=' ),
		);

		$args = array_merge( $defaults, $args );
		// Used in js for URL cleanup.
		$args = self::add_class( 'cftf-filter', $args );

		echo '<form method="GET"' . wp_kses( self::build_attrib_string( $args ), array() ) . '>';
	}

	/**
	 * Closes the form and adds the action.
	 *
	 * @return void
	 **/
	public static function end_form() {
		echo '<input type="hidden" name="cftf_action" value="filter" />
			</form>';
	}

	/**
	 * Adds a class to a set of arguemnts. Adds the class to the end
	 * of existing classes if they exist, otherwise just sets the argument
	 *
	 * @param string $class Class to append (on the class index).
	 * @param array  $args  Array of arguments.
	 * @return array        Argument array passed in with the additional class
	 **/
	private static function add_class( $class, $args ) {
		if ( ! empty( $args['class'] ) ) {
			$args['class'] .= ' ' . $class;
		} else {
			$args['class'] = $class;
		}

		return $args;
	}

	/**
	 * Build an attribute string for an HTML element, only attributes from
	 * allowed_attributes will be allowed.
	 *
	 * @param array $attributes Array of attributes.
	 * @return string           Attributes as string.
	 **/
	private static function build_attrib_string( $attributes ) {
		if ( ! is_array( $attributes ) ) {
			return '';
		}

		$components = array();

		$allowed_attributes = self::allowed_attributes();

		foreach ( $attributes as $attribute => $value ) {
			if ( ! empty( $value ) && in_array( $attribute, $allowed_attributes, true ) ) {
				$components[] = esc_attr( $attribute ) . '="' . esc_attr( $value ) . '"';
			}
		}

		$string = implode( ' ', $components );
		if ( ! empty( $string ) ) {
			$string = ' ' . $string . ' ';
		}

		return $string;
	}

	/**
	 * What attributes can be placed on the various form elements, filterable.
	 *
	 * @return array Array of allowed attributes.
	 **/
	private static function allowed_attributes() {
		return apply_filters(
			'cftf_allowed_attributes', array(
				'class',
				'id',
				'method',
				'action',
				'value',
				'name',
				'style',
				'placeholder',
				'data-placeholder',
				'tabindex',
				'value',
			)
		);
	}

	/**
	 * Filter the WHERE clause in the query as WP_Query does not support a range function as of 3.5.
	 *
	 * @param  string $where Initial WHERE condition.
	 * @return string        Updated WHERE condition.
	 **/
	public static function posts_where( $where ) {
		remove_filter( 'posts_where', array( 'CF_Taxonomy_Filter', 'posts_where' ) );
		global $wpdb;

		if ( ! empty( $_GET['cftf_date']['start'] ) ) {
			$php_date   = strtotime( $_GET['cftf_date']['start'] );
			$mysql_date = date( 'Y-m-d H:i:s', $php_date );
			$date_where = $wpdb->prepare( "AND $wpdb->posts.post_date > %s", $mysql_date );
			if ( ! empty( $where ) ) {
				$where .= ' ' . $date_where;
			} else {
				$where = $date_where;
			}
		}

		if ( ! empty( $_GET['cftf_date']['end'] ) ) {
			$php_date   = strtotime( $_GET['cftf_date']['end'] . ' 23:59:59' );
			$mysql_date = date( 'Y-m-d H:i:s', $php_date );
			$date_where = $wpdb->prepare( "AND $wpdb->posts.post_date < %s", $mysql_date );
			if ( ! empty( $where ) ) {
				$where .= ' ' . $date_where;
			} else {
				$where = $date_where;
			}
		}

		return $where;
	}

	/**
	 * Override default query with the filtered values.
	 *
	 * @param WP_Query $query_obj Current query object.
	 * @return void
	 **/
	public static function pre_get_posts( $query_obj ) {
		if ( ! $query_obj->is_main_query() || ! isset( $_GET['cftf_action'] ) || 'filter' !== $_GET['cftf_action'] ) {
			return;
		}
		remove_action( 'pre_get_posts', array( 'CF_Taxonomy_Filter', 'pre_get_posts' ) );

		// Make WordPress think this is a search and render the search page.
		$query_obj->is_search     = true;
		$query_obj->is_home       = false;
		$query_obj->is_front_page = false;
		$query_obj->is_page       = false;

		if ( ! empty( $_GET['cftf_authors'] ) ) {
			// WP_Query doesnt accept an array of authors, sad panda 8:(.
			$query_obj->query_vars['author'] = implode( ',', (array) $_GET['cftf_authors'] );
		}

		if ( ! empty( $_GET['cfct_tax'] ) && is_array( $_GET['cfct_tax'] ) ) {
			foreach ( $_GET['cfct_tax'] as $taxonomy => $terms ) {
				$query_obj->query_vars['tax_query'][] = array(
					'taxonomy'         => $taxonomy,
					'field'            => 'ids',
					'terms'            => $terms,
					'include_children' => false,
					'operator'         => 'AND',
				);
			}

			$query_obj->query_vars['tax_query']['relation'] = 'AND';
		}

		// Have to manually filter date range.
		if ( ! empty( $_GET['cftf_date']['start'] ) || ! empty( $_GET['cftf_date']['end'] ) ) {
			$query_obj->query_vars['suppress_filters'] = 0;
			add_filter( 'posts_where', array( 'CF_Taxonomy_Filter', 'posts_where' ) );
		}
	}
}

