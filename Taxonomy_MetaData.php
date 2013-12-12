<?php

if ( ! class_exists( 'Taxonomy_MetaData' ) ) {
/**
 * Adds pseudo term meta functionality
 */
class Taxonomy_MetaData {

	public static $cat_opts  = array();
	public $term_meta_fields = array();

	/**
	 * Get Started
	 */
	public function __construct( $taxonomy, $term_meta_fields ) {
		$this->taxonomy                    = $taxonomy;
		$this->term_meta_fields            = $term_meta_fields;
		$this->id                          = strtolower( __CLASS__ ) . '_' . $this->taxonomy;
		self::$cat_opts[ $this->taxonomy ] = array();

		add_action( 'admin_init', array( $this, 'hooks' )  );
	}

	/**
	 * Hook into our term edit & new term forms
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
	 * @param  int|object $term Term object, or Taxonomy name
	 * @param  string $taxonomy if term object is passed in, this is the taxonomy
	 */
	public function metabox_edit( $term, $taxonomy = '' ) {

		$editpage = isset( $_GET['tag_ID'] ) ? true : false;

		$taxonomy = $taxonomy ? $taxonomy : $term;

		$tax = get_taxonomy( $taxonomy );
		if ( !current_user_can( $tax->cap->edit_terms ) )
			return;

		$opts = $editpage ? $this->get_tax_opt( $term->slug ) : array();

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

		echo '<h3>Category Settings</h3>

		<table class="form-table"><tbody>';

		foreach ( $this->term_meta_fields as $metakey => $metainfo ) {

			$val = isset( $opts[ $metakey ] ) ? $opts[ $metakey ] : '';
			$placeholder = '';

			// If we want to display a placeholder
			if ( isset( $metainfo['default'] ) ) {

				$placeholder = $metainfo['default'] === true
					? $this->get_tax_opt( 'default', $metakey )
					: $metainfo['default'];
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
	 * Save the data from the taxonomy forms to our site option
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
	 * Checks for a sanitization callback or defaults to sanitizing via sanitize_text_field
	 * @param  string $metakey term_meta_fields array key to check for callback
	 * @param  string $val     value to be sanitized
	 * @return string          sanitized value
	 */
	public function sanitize( $metakey, $val ) {
		$sanitize = isset( $this->term_meta_fields[ $metakey ]['sanitize'] ) ? $this->term_meta_fields[ $metakey ]['sanitize'] : 'sanitize_text_field';

		if ( method_exists( $this, $sanitize ) )
			return call_user_func( array( $this, $sanitize ), trim( $val ) );

		return call_user_func( $sanitize, trim( $val ) );
	}

	/**
	 * Sanitizes to a boolean
	 * @param  mixed  $data data to sanitize
	 * @return bool        sanitzed to a boolean
	 */
	public function parse_boolean( $data ) {
		return filter_var( $data, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Returns the $this->taxonomy site option with options to return a subset
	 */
	function get_tax_opt( $section = false, $subsection = false ) {

		self::$cat_opts[ $this->taxonomy ] = ! empty( self::$cat_opts[ $this->taxonomy ] ) ? self::$cat_opts[ $this->taxonomy ] : (array) get_option( $this->id );

		if ( empty( self::$cat_opts[ $this->taxonomy ] ) )
			return false;

		if ( ! $section )
			return self::$cat_opts[ $this->taxonomy ];

		if ( ! $subsection )
			return is_array( self::$cat_opts[ $this->taxonomy ] ) && array_key_exists( $section, self::$cat_opts[ $this->taxonomy ] ) ? self::$cat_opts[ $this->taxonomy ][ $section ] : false;

		if (
			array_key_exists( $section, self::$cat_opts[ $this->taxonomy ] )
			&& array_key_exists( $subsection, self::$cat_opts[ $this->taxonomy ][ $section ] )
		)
			return self::$cat_opts[ $this->taxonomy ][ $section ][ $subsection ];

		return false;
	}

}
new Taxonomy_MetaData( 'category', array(
	'sidebar' => array(
		'label' => 'Enable sidebar for this issue',
		'sanitize' => 'parse_boolean',
		'type' => 'checkbox',
	),
) );
new Taxonomy_MetaData( 'post_tag', array(
	'arbitrary_text' => array(
		'label' => 'Arbitrary text for tags',
	),
) );

} // end class_exists check
