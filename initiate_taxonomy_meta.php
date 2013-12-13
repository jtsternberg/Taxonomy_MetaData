<?php

require_once( 'Taxonomy_MetaData.php' );

/**
 * Instantiate our taxonomy meta classes
 */
new Taxonomy_MetaData( 'category', array(
	// Term option key
	'sidebar' => array(
		// Option label
		'label'    => __( 'Enable sidebar for this issue', 'taxonomy-metadata' ),
		// Sanitization callback
		'sanitize' => 'parse_boolean',
		// Field type
		'type'     => 'checkbox',
	),
), __( 'Category Settings', 'taxonomy-metadata' ) /* Settings heading */ );

new Taxonomy_MetaData( 'post_tag', array(
	'arbitrary_text' => array(
		'label'       => __( 'Arbitrary text for tags', 'taxonomy-metadata' ),
		// Optional input description
		'desc'        => __( 'A description for this field', 'taxonomy-metadata' ),
		// Optional placeholder text if no value is saved
		'placeholder' => __( 'Placeholder text', 'taxonomy-metadata' ),
	),
) );

/* Available methods:

Taxonomy_MetaData::get( 'category' );
Taxonomy_MetaData::get( 'category', 'best-of', 'sidebar' );
Taxonomy_MetaData::get( 'post_tag' );
Taxonomy_MetaData::get( 'post_tag', 'on-the-go', 'arbitrary_text' );
Taxonomy_MetaData::delete( 'post_tag', 'on-the-go', 'arbitrary_text' );
$new_value = 'This is some new arbitrary text';
Taxonomy_MetaData::set( 'post_tag', 'on-the-go', 'arbitrary_text', $new_value );

*/
