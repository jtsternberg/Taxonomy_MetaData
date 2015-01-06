<?php

require_once( 'Taxonomy_MetaData.php' );

if ( ! class_exists( 'Taxonomy_MetaData_CMB2' ) ) :
/**
 * Adds pseudo term meta functionality
 * @version 0.2.1
 * @author  Justin Sternberg
 */
class Taxonomy_MetaData_CMB2 extends Taxonomy_MetaData {

	public function __construct( $taxonomy, $metabox, $title = '', $option_callbacks = array() ) {

		$this->metabox = $metabox;

		// If a title was passed in
		if ( $title ) {
			// Then add a title field to the list of fields for CMB
			array_unshift( $metabox['fields'], array(
				'name' => $title,
				'id'   => sanitize_title( $title ),
				'type' => 'title',
			) );
		}

		parent::__construct( $taxonomy, $metabox, '', $option_callbacks );
	}

	/**
	 * Displays form markup
	 * @since  0.1.3
	 * @param  int  $term_id Term ID
	 */
	public function display_form( $term_id ) {
		if ( ! class_exists( 'CMB2' ) ) {
			return;
		}
		$this->do_override_filters( $term_id );

		$object_id = $this->id( $term_id );
		$cmb = cmb2_get_metabox( $this->metabox, $object_id );

		// if passing a metabox ID, and that ID was not found
		if ( ! $cmb ) {
			return;
		}

		// Hard-code object type
		$cmb->object_type( 'options-page' );

		// Enqueue JS/CSS
		if ( $cmb->prop( 'cmb_styles' ) ) {
			CMB2_hookup::enqueue_cmb_css();
		}
		CMB2_hookup::enqueue_cmb_js();

		// Add object id to the form for easy access
		printf( '<input type="hidden" name="term_opt_name" value="%s">', $object_id );
		printf( '<input type="hidden" name="object_id" value="%s">', $object_id );

		// Show cmb form
		$cmb->show_form();
	}

	/**
	 * Handles saving of the $_POST data
	 * @since  0.1.3
	 * @param  int $term_id Term's ID
	 */
	public function do_save( $term_id ) {

		if ( ! class_exists( 'CMB2' ) ) {
			return;
		}

		$object_id = $this->id( $term_id );
		$cmb = cmb2_get_metabox( $this->metabox, $object_id );

		if (
			// check nonce
			isset( $_POST[ $cmb->nonce() ] )
			&& wp_verify_nonce( $_POST[ $cmb->nonce() ], $cmb->nonce() )
		) {

			$this->do_override_filters( $term_id );
			$cmb->save_fields( $object_id, 'options-page', $_POST );
		}
	}

	/**
	 * Filters CMB setting/getting
	 * @since  0.1.3
	 */
	public function do_override_filters( $term_id ) {

		// Override CMB's getter
		add_filter( 'cmb2_override_option_get_'. $this->id( $term_id ), array( $this, 'use_get_override' ), 10, 2 );
		// Override CMB's setter
		add_filter( 'cmb2_override_option_save_'. $this->id( $term_id ), array( $this, 'use_update_override' ), 10, 2 );


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
		if ( ! class_exists( 'CMB2' ) )
			return;

		$this->do_override_filters( $term_id );

		$value = $key
			? cmb2_get_option( $this->id( $term_id ), $key )
			: cmb2_options( $this->id( $term_id ) )->get_options();

		return $value;
	}

}

endif; // end class_exists check
