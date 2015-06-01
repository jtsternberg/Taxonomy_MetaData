<?php
/**
 * @category     WordPress_Plugin
 * @package      Taxonomy_MetaData
 * @author       Jtsternberg
 * @license      GPL-2.0+
 * @link         https://github.com/jtsternberg/Taxonomy_MetaData
 *
 * Plugin Name:  Taxonomy_MetaData_CMB2
 * Plugin URI:   https://github.com/jtsternberg/Taxonomy_MetaData
 * Description:  Taxonomy_MetaData_CMB2 is a Helper Class for WordPress developers which allows registering/saving fields with CMB2 against taxonomy terms. Recommended to combine with wp-large-options.
 * Author:       Jtsternberg
 * Author URI:   http://jtsternberg.com/about
 * Version:      1.0.0
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * **********************************************************************
 */

require_once( 'Taxonomy_MetaData.php' );

if ( ! class_exists( 'Taxonomy_MetaData_CMB2' ) ) :
/**
 * Adds pseudo term meta functionality
 * @version 1.0.0
 * @author  Justin Sternberg
 */
class Taxonomy_MetaData_CMB2 extends Taxonomy_MetaData {

	/**
	 * CMB2 Object
	 *
	 * @var CMB2
	 */
	protected $cmb = null;

	/**
	 * Initiate CMB2 Taxonomy Meta
	 *
	 * @since 1.0.0
	 *
	 * @param string  $taxonomy          Taxonomy Slug
	 * @param mixed   $meta_box  Metabox config array or Metabox ID
	 * @param string  $title             Optional section title
	 * @param array   $option_callbacks  Override the option setting/getting
	 */
	public function __construct( $taxonomy, $metabox, $title = '', $option_callbacks = array() ) {

		$this->cmb = cmb2_get_metabox( $metabox );

		// if passing a metabox ID, and that ID was not found
		if ( ! $this->cmb ) {
			return;
		}

		// If a title was passed in
		if ( $title ) {
			// Then add a title field to the list of fields for CMB
			$this->cmb->add_field( array(
				'name' => $title,
				'id'   => sanitize_title( $title ),
				'type' => 'title',
			), 1 );
		}

		parent::__construct( $taxonomy, array(), '', $option_callbacks );
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

		// Hard-code object ID
		$this->cmb->object_id( $object_id );

		// Hard-code object type
		$this->cmb->object_type( 'options-page' );

		// Enqueue JS/CSS
		if ( $this->cmb->prop( 'cmb_styles' ) ) {
			CMB2_hookup::enqueue_cmb_css();
		}

		if ( $this->cmb->prop( 'enqueue_js' ) ) {
			CMB2_hookup::enqueue_cmb_js();
		}

		// Add object id to the form for easy access
		printf( '<input type="hidden" name="term_opt_name" value="%s">', $object_id );
		printf( '<input type="hidden" name="object_id" value="%s">', $object_id );

		// Show cmb form
		$this->cmb->show_form();
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

		if (
			// check nonce
			isset( $_POST[ $this->cmb->nonce() ] )
			&& wp_verify_nonce( $_POST[ $this->cmb->nonce() ], $this->cmb->nonce() )
		) {

			$this->do_override_filters( $term_id );
			$this->cmb->save_fields( $object_id, 'options-page', $_POST );
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
		if ( ! class_exists( 'CMB2' ) ) {
			return;
		}

		$this->do_override_filters( $term_id );

		$value = $key
			? cmb2_get_option( $this->id( $term_id ), $key )
			: cmb2_options( $this->id( $term_id ) )->get_options();

		return $value;
	}

}

endif; // end class_exists check
