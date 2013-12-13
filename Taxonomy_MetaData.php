<?php

if ( ! class_exists( 'Taxonomy_MetaData' ) ) {
/**
 * Adds pseudo term meta functionality
 * @version 0.1.0
 * @author  Justin Sternberg
 */
class Taxonomy_MetaData {

	/**
	 * Session-cached taxonomy option
	 * @since  0.1.0
	 * @var array
	 */
	protected static $cat_opts = array();

	/**
	 * Stores every instance created with this class.
	 * @since  0.1.0
	 * @var array
	 */
	protected static $taxonomy_objects = array();

	/**
	 * Meta fields array passed in when instantiating the calss
	 * @since  0.1.0
	 * @var array
	 */
	public $term_meta_fields = array();

	/**
	 * Meta fields heading (optional)
	 * @since  0.1.0
	 * @var string
	 */
	public $label = '';

	/**
	 * Unique ID string for each taxonomy
	 * @since  0.1.0
	 * @var string
	 */
	protected $id = '';

	/**
	 * Get Started
	 * @since  0.1.0
	 */
	public function __construct( $taxonomy, $term_meta_fields, $label = '' ) {
		$this->taxonomy                      = $taxonomy;
		$this->term_meta_fields              = $term_meta_fields;
		$this->label                         = $label;
		$this->id                            = strtolower( __CLASS__ ) . '_' . $this->taxonomy;
		self::$cat_opts[ $taxonomy ]         = array();
		self::$taxonomy_objects[ $taxonomy ] = $this;
		add_action( 'admin_init', array( $this, 'hooks' )  );
	}

	/**
	 * Hook into our term edit & new term forms
	 * @since  0.1.0
	 */
	public function hooks() {

		// Display our form data
		add_action( $this->taxonomy .'_edit_form', array( $this, 'metabox_edit' ), 8, 2 );
		add_action( $this->taxonomy .'_add_form_fields', array( $this, 'metabox_edit' ), 8, 2 );

		// Save our form data
		add_action( 'created_'. $this->taxonomy, array( $this, 'save_data' ) );
		add_action( 'edited_'. $this->taxonomy, array( $this, 'save_data' ) );

		// Delete it if necessary
		add_action( 'delete_'. $this->taxonomy, array( $this, 'delete_data' ), 10, 3 );

	}

	/**
	 * Displays Taxonomy Term form fields for meta
	 * @since  0.1.0
	 * @param  int|object $term     Term object, or Taxonomy name
	 * @param  string     $taxonomy If term object is passed in, this is the taxonomy
	 */
	public function metabox_edit( $term, $taxonomy = '' ) {

		$editpage = isset( $_GET['tag_ID'] ) ? true : false;

		$taxonomy = $taxonomy ? $taxonomy : $term;

		$tax = get_taxonomy( $taxonomy );
		if ( !current_user_can( $tax->cap->edit_terms ) )
			return;

		$opts = $editpage ? $this->get_meta( $term->slug ) : array();

		if ( ! $editpage ) {
			echo '</table>
			<style type="text/css">
			div.form-checkbox {
				position: relative;
				padding-bottom: 1.2em;
				overflow: hidden;
			}
			div.form-checkbox label {
				display: block;
				position: absolute;
				top: -1px;
				left: 25px;
			}
			</style>';
		}

		wp_nonce_field( $this->id.'save_term_meta', $this->id.'save_term_meta' );

		if ( $this->label )
			printf( '<h3>%s</h3>', $this->label );

		echo '<table class="form-table"><tbody>';

		foreach ( $this->term_meta_fields as $metakey => $metainfo ) {

			$val = isset( $opts[ $metakey ] ) ? $opts[ $metakey ] : '';
			$placeholder = '';

			// If we want to display a placeholder
			if ( isset( $metainfo['placeholder'] ) ) {

				$placeholder = $metainfo['placeholder'] === true
					? $this->get_meta( 'placeholder', $metakey )
					: $metainfo['placeholder'];
			}

			$id = $this->id . $metakey;
			$type = isset( $metainfo['type'] ) ? $metainfo['type'] : 'text" size="40" placeholder="'. esc_attr( $placeholder );
			$val = $type == 'checkbox' ? 1 : $val;
			$class = $type == 'checkbox' ? 'form-checkbox' : 'form-field';

			echo ( $editpage ) ? '<tr class="'. $class .'">' : '<div class="'. $class .'">';
				echo ( $editpage ) ? '<th scope="row" valign="top">' : '';
					echo '<label for="'. $id .'">'. $metainfo['label'] .'</label>';
				echo ( $editpage ) ? '</th><td>' : '';
					echo '<input name="'. $this->id .'['. $metakey .']" id="'. $id .'" type="'. $type .'" value="'. esc_attr( $val ) .'"';
					if ( $type == 'checkbox' )
						checked( isset( $opts[ $metakey ] ) );
					echo '/>';
					echo isset( $metainfo['desc'] )
						? '<p class="description">'. $metainfo['desc'] .'</p>'
						: '';
					echo ( $editpage ) ? '</td>' : '';
			echo ( $editpage ) ? '</tr>' : '</div>';

		}

		echo '</tbody></table>';

	}

	/**
	 * Save the data from the taxonomy forms to the taxonomy site option
	 * @since  0.1.0
	 * @param  int $term_id Term's ID
	 */
	public function save_data( $term_id ) {

		// Make sure we have the right priveleges
		if (
			!isset( $_POST[$this->id.'save_term_meta'] )
			|| ! isset( $_POST['taxonomy'] )
			// check nonce
			|| ! wp_verify_nonce( $_POST[$this->id.'save_term_meta'], $this->id.'save_term_meta' )
		)
			return;

		// Can the user edit this term?
		if ( ! ( $tax = get_taxonomy( $_POST['taxonomy'] ) ) || ! current_user_can( $tax->cap->edit_terms ) )
			return;

		if ( ! ( $term = get_term( $term_id, $_POST['taxonomy'] ) ) )
			return;

		$opts = get_option( $this->id );

		if ( ! isset( $_POST[ $this->id ] ) || ! is_array( $_POST[ $this->id ] ) ) {
			unset( $opts[ $term->slug ] );
			update_option( $this->id, $opts );
			return;
		}

		foreach ( $_POST[ $this->id ] as $metakey => $val ) {

			// Sanitize value and add to our options array
			$opts[ $term->slug ][ $metakey ] = $this->sanitize( $metakey, $val );
		}
		// OK, save it
		update_option( $this->id, $opts );
	}

	/**
	 * Remove associated term meta when deleting a term
	 * @since  0.1.0
	 * @param int $term_id      Term's ID
	 * @param int $tt_id        Term Taxonomy ID
	 * @param obj $deleted_term Deleted term object
	 */
	public function delete_data( $term, $tt_id, $deleted_term ) {
		$opts = get_option( $this->id );
		unset( $opts[ $deleted_term->slug ] );
		update_option( $this->id, $opts );
	}

	/**
	 * Delete meta for whole taxonomy all term meta, or specific meta field for term
	 * @since  0.1.0
	 * @param  string $taxonomy  Taxonomy slug
	 * @param  string $term_slug The slug of the term whose option we're getting
	 * @param  string $key       Term meta key to check
	 * @return bool              False if option was not updated or true
	 */
	public static function delete( $taxonomy, $term_slug = '', $key = '' ) {
		// Get taxonomy instance
		if ( ! ( $instance = self::get_instance( $taxonomy ) ) )
			return false;

		// If no term slug, delete entire option
		if ( ! $term_slug ) {
			return delete_option( $instance->id );
		}

		$opts = get_option( $instance->id );

		// If no term meta keay, delete all the term's meta
		if ( ! $key ) {
			unset( $opts[ $term_slug ] );
			return update_option( $instance->id, $opts );
		}

		// Delete just this term key's meta
		unset( $opts[ $term_slug ][ $key ] );
		return update_option( $instance->id, $opts );

	}

	/**
	 * Set associated term meta when deleting a term
	 * @since  0.1.0
	 * @param  string $taxonomy  Taxonomy slug
	 * @param  string $term_slug The slug of the term whose option we're getting
	 * @param  string $key       Term meta key to check
	 * @param  mixed  $val       Term meta value to set
	 * @return bool              False if option was not updated or true
	 */
	public static function set( $taxonomy, $term_slug, $key, $value ) {
		// Get taxonomy instance
		if ( ! ( $instance = self::get_instance( $taxonomy ) ) )
			return false;

		$opts = get_option( $instance->id );

		// Sanitize value and add to our options array
		$opts[ $term_slug ][ $key ] = $instance->sanitize( $key, $value );

		// OK, save it
		update_option( $instance->id, $opts );
	}

	/**
	 * Checks for a sanitization callback or defaults to sanitizing via sanitize_text_field
	 * @since  0.1.0
	 * @param  string  $metakey Term meta key to check
	 * @param  mixed   $val     Term meta value to sanitize
	 * @return mixed            Sanitized value
	 */
	public function sanitize( $metakey, $val ) {
		$sanitize = isset( $this->term_meta_fields[ $metakey ]['sanitize'] ) ? $this->term_meta_fields[ $metakey ]['sanitize'] : 'sanitize_text_field';

		if ( method_exists( $this, $sanitize ) )
			return call_user_func( array( $this, $sanitize ), trim( $val ) );

		return call_user_func( $sanitize, trim( $val ) );
	}

	/**
	 * Sanitizes to a boolean
	 * @since  0.1.0
	 * @param  mixed  $data Data to sanitize
	 * @return bool         Ristricted to a boolean
	 */
	public function parse_boolean( $data ) {
		return filter_var( $data, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Returns the $this->taxonomy site option with options to return a subset
	 * @since  0.1.0
	 * @param  string  $term_slug  The slug of the term whose option we're getting
	 * @param  string  $key        Term meta key to check
	 * @return mixed               Requested value | false
	 */
	public function get_meta( $term_slug = '', $key = '' ) {

		self::$cat_opts[ $this->taxonomy ] = ! empty( self::$cat_opts[ $this->taxonomy ] ) ? self::$cat_opts[ $this->taxonomy ] : (array) get_option( $this->id );

		if ( empty( self::$cat_opts[ $this->taxonomy ] ) )
			return false;

		if ( ! $term_slug )
			return self::$cat_opts[ $this->taxonomy ];

		if ( ! $key )
			return is_array( self::$cat_opts[ $this->taxonomy ] ) && array_key_exists( $term_slug, self::$cat_opts[ $this->taxonomy ] ) ? self::$cat_opts[ $this->taxonomy ][ $term_slug ] : false;

		if (
			array_key_exists( $term_slug, self::$cat_opts[ $this->taxonomy ] )
			&& array_key_exists( $key, self::$cat_opts[ $this->taxonomy ][ $term_slug ] )
		)
			return self::$cat_opts[ $this->taxonomy ][ $term_slug ][ $key ];

		return false;
	}

	/**
	 * Public method for getting term meta
	 * @since  0.1.0
	 * @param  string $taxonomy  Taxonomy slug
	 * @param  string $term_slug The slug of the term whose option we're getting
	 * @param  string $key       Term meta key to check
	 * @return mixed             Requested value | false
	 */
	public static function get( $taxonomy, $term_slug = '', $key = '' ) {
		// Get taxonomy instance
		$instance = self::get_instance( $taxonomy );
		// Return the meta, or false if the taxonomy object doesn't exist
		return $instance ? $instance->get_meta( $term_slug, $key ) : false;
	}

	/**
	 * Public method for getting an instanciated instance of this class by taxonomy
	 * @since  0.1.0
	 * @param  string $taxonomy  Taxonomy slug
	 * @return object            Taxonomy_MetaData instance or false
	 */
	public static function get_instance( $taxonomy ) {
		// If the object instance doesn't exist, bail
		if ( ! isset( self::$taxonomy_objects[ $taxonomy ] ) )
			return false;
		// Ok, send it back.
		return self::$taxonomy_objects[ $taxonomy ];
	}

}

} // end class_exists check
