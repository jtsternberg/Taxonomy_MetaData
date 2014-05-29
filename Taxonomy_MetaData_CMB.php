<?php

require_once( 'Taxonomy_MetaData.php' );

if ( ! class_exists( 'Taxonomy_MetaData_CMB' ) ) :
/**
 * Adds pseudo term meta functionality
 * @version 0.1.4
 * @author  Justin Sternberg
 */
class Taxonomy_MetaData_CMB extends Taxonomy_MetaData {

	public function __construct( $taxonomy, $fields, $title = '', $option_callbacks = array() ) {

		// If a title was passed in
		if ( $title ) {
			// Then add a title field to the list of fields for CMB
			array_unshift( $fields['fields'], array(
				'name' => $title,
				'id'   => sanitize_title( $title ),
				'type' => 'title',
			) );
		}

		parent::__construct( $taxonomy, $fields, '', $option_callbacks );
	}

	/**
	 * Displays form markup
	 * @since  0.1.3
	 * @param  int  $term_id Term ID
	 */
	public function display_form( $term_id ) {
		if ( ! class_exists( 'cmb_Meta_Box' ) )
			return;
		$this->do_override_filters( $term_id );

		// Fill in the mb defaults
		$meta_box = cmb_Meta_Box::set_mb_defaults( $this->fields() );

		// Make sure that our object type is explicitly set by the metabox config
		cmb_Meta_Box::set_object_type( cmb_Meta_Box::set_mb_type( $meta_box ) );

		// Add object id to the form for easy access
		printf( '<input type="hidden" name="term_opt_name" value="%s">', $this->id( $term_id ) );

		// Show cmb form
		cmb_print_metabox( $meta_box, $this->id( $term_id ) );

	}

	/**
	 * Handles saving of the $_POST data
	 * @since  0.1.3
	 * @param  int $term_id Term's ID
	 */
	public function do_save( $term_id ) {
		if ( ! class_exists( 'cmb_Meta_Box' ) )
			return;

		if (
			// check nonce
			! isset( $_POST['term_opt_name'], $_POST['wp_meta_box_nonce'], $_POST['action'] )
			|| ! wp_verify_nonce( $_POST['wp_meta_box_nonce'], cmb_Meta_Box::nonce() )
		)
			return;

		$this->do_override_filters( $term_id );
		// Save the metabox if it's been submitted
		cmb_save_metabox_fields( $this->fields(), $this->id() );
	}

	/**
	 * Filters CMB setting/getting
	 * @since  0.1.3
	 */
	public function do_override_filters( $term_id ) {

		// Override CMB's getter
		add_filter( 'cmb_override_option_get_'. $this->id( $term_id ), array( $this, 'use_get_override' ), 10, 2 );
		// Override CMB's setter
		add_filter( 'cmb_override_option_save_'. $this->id( $term_id ), array( $this, 'use_update_override' ), 10, 2 );


		$this->filters_added = true;
	}

	/**
	 * Replaces get_option with our override
	 * @since  0.1.3
	 */
	public function use_get_override( $test, $default = false ) {
		return call_user_func( $this->get_option, $this->id(), $default );
	}

	/**
	 * Replaces update_option with our override
	 * @since  0.1.3
	 */
	public function use_update_override( $test, $option_value ) {
		return call_user_func( $this->update_option, $this->id(), $option_value );
	}

	/**
	 * Returns term meta with options to return a subset
	 * @since  0.1.3
	 * @param  string  $term_id  The term id for the options we're getting
	 * @param  string  $key      Term meta key to check
	 * @return mixed             Requested value | false
	 */
	public function get_meta( $term_id, $key = '' ) {
		if ( ! class_exists( 'cmb_Meta_Box' ) )
			return;

		$this->do_override_filters( $term_id );

		return cmb_get_option( $this->id( $term_id ), $key );
	}

}

endif; // end class_exists check
