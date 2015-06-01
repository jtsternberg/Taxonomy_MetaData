Taxonomy_MetaData
=================

WordPress Helper Class for saving pseudo-metadata for taxonomy terms. This is not recommend if using on a very large number of terms, UNLESS using a plugin like [wp-large-options](https://github.com/voceconnect/wp-large-options/). The third parameter is used to override the get/set/delete options callbacks so that you can use [wp-large-options](https://github.com/voceconnect/wp-large-options/) (or something like it).

Includes an extended class for using [CMB2](https://github.com/WebDevStudios/CMB2) to generate the actual form fields. This is handy if you need more advanced fields, or do not want to create your own field `render_cb`s.

Originally inspired by [@williamsba](http://github.com/williamsba). Read his article here: [How To: Save Taxonomy Meta Data as an Options Array in WordPress](http://strangework.com/2010/07/01/how-to-save-taxonomy-meta-data-as-an-options-array-in-wordpress/).


#### Available methods for retrieving data:

* Get all meta for a taxonomy term:
	```php
	$taxonomy = 'category';
	$term_id = 3;
	Taxonomy_MetaData::get( $taxonomy, $term_id );
	```

* Get specific term meta data:
	```php
	$taxonomy = 'category';
	$term_id = 3;
	$meta_key = 'about_text';
	Taxonomy_MetaData::get( $taxonomy, $term_id, $meta_key );
	```

#### To initate Taxonomy_MetaData and add fields to a taxonomy:
```php
<?php

function taxonomy_meta_initiate() {

	require_once( 'Taxonomy_MetaData/Taxonomy_MetaData.php' );

	/**
	 * Instantiate our taxonomy meta class
	 */
	$cats = new Taxonomy_MetaData( 'category', array(
		// Term option key
		'about_text' => array(
			// Field label
			'label'    => __( 'About section for this term.', 'taxonomy-metadata' ),
			// Sanitization callback
			'sanitize_cb' => 'wp_kses_post',
			// Render callback
			'render_cb' => 'taxonomy_metadata_textarea',
		),
	), __( 'Category Settings', 'taxonomy-metadata' ) /* Settings heading */ );

	$tags = new Taxonomy_MetaData( 'post_tag', array(
		'arbitrary_text' => array(
			'label'       => __( 'Arbitrary text for tags', 'taxonomy-metadata' ),
			// Optional input description
			'desc'        => __( 'A description for this field', 'taxonomy-metadata' ),
			// Optional placeholder text if no value is saved
			'placeholder' => __( 'Placeholder text', 'taxonomy-metadata' ),
		),
	) );

}
taxonomy_meta_initiate();

/**
 * Textarea callback for Taxonomy_MetaData
 * @param  array  $field Field config
 * @param  mixed  $value Field value
 */
function taxonomy_metadata_textarea( $field, $value ) {
	echo '<textarea class="taxonomy-metadata-textarea" name="'. $field->id .'" id="'. $field->id .'">'. esc_textarea( $value ) .'</textarea>';
	if ( isset( $field->desc ) && $field->desc )
		echo "\n<p class=\"description\">{$field->desc}</p>\n";

}
```

#### To use `wp_large_options` (recommended):
```php
<?php

function taxonomy_meta_initiate() {

	// Including Taxonomy_MetaData .php. Update to reflect your file structure
	if ( ! class_exists( 'Taxonomy_MetaData' ) ) {
		require_once( 'Taxonomy_MetaData/Taxonomy_MetaData.php' );
	}

	// Form title
	$title = __( 'Category Archive Options', 'taxonomy-metadata' );

	// Category fields
	$fields = array(
		'arbitrary_text' => array(
			'label'       => __( 'Arbitrary text for tags', 'taxonomy-metadata' ),
			'desc'        => __( 'A description for this field', 'taxonomy-metadata' ),
			'placeholder' => __( 'Placeholder text', 'taxonomy-metadata' ),
		),
	);

	// (Recommended) Use wp-large-options
	if ( ! defined( 'wlo_update_option' ) ) {
		require_once( 'wp-large-options/wp-large-options.php' );
	}

	// get/set/delete overrides using wp-large-options
	$wlo_overrides = array(
		'get_option'    => 'wlo_get_option',
		'update_option' => 'wlo_update_option',
		'delete_option' => 'wlo_delete_option',
	);

	/**
	 * Instantiate our taxonomy meta object
	 */
	new Taxonomy_MetaData( 'category', $fields, $title, $overrides );
}
taxonomy_meta_initiate();
```

#### To use Taxonomy_MetaData with [CMB2](https://github.com/WebDevStudios/CMB2):
```php
<?php

// Including CMB2's init file (unless you have CMB2 installed as a plugin)
require_once( 'CMB2/init.php' );

function taxonomy_metadata_cmb2_init() {

	// Including Taxonomy_MetaData_CMB2.php. Update to reflect your file structure
	if ( ! class_exists( 'Taxonomy_MetaData_CMB2' ) ) {
		require_once( 'Taxonomy_MetaData/Taxonomy_MetaData_CMB2.php' );
	}

	$metabox_id = 'cat_options';

	/**
	 * Semi-standard CMB metabox/fields registration
	 */
	$cmb = new_cmb2_box( array(
		'id'           => $metabox_id,
		'object_types' => array( 'key' => 'options-page', 'value' => array( 'unknown', ), ),
	) );

	$cmb->add_field( array(
		'name' => __( 'Category Archive Featured Post', 'taxonomy-metadata' ),
		'desc' => __( 'Enter Post ID', 'taxonomy-metadata' ),
		'id'   => 'feat_post', // no prefix needed since the options are one option array.
		'type' => 'text',
	) );

	$cmb->add_field( array(
		'name' => __( 'Test Checkbox', 'taxonomy-metadata' ),
		'desc' => __( 'field description (optional)', 'taxonomy-metadata' ),
		'id'   => 'test_checkbox',
		'type' => 'checkbox',
	) );

	$cmb->add_field( array(
		'name' => __( 'Test Text Small', 'taxonomy-metadata' ),
		'desc' => __( 'field description (optional)', 'taxonomy-metadata' ),
		'id'   => 'test_textsmall',
		'type' => 'text_small',
		// 'repeatable' => true,
	) );

	$cmb->add_field( array(
		'name'    => __( 'Test wysiwyg', 'taxonomy-metadata' ),
		'desc'    => __( 'field description (optional)', 'taxonomy-metadata' ),
		'id'      => 'test_wysiwyg',
		'type'    => 'wysiwyg',
		'options' => array( 'textarea_rows' => 5, ),
	) );

	// (Recommended) Use wp-large-options
	if ( ! defined( 'wlo_update_option' ) ) {
		require_once( 'wp-large-options/wp-large-options.php' );
	}

	// wp-large-options overrides
	$wlo_overrides = array(
		'get_option'    => 'wlo_get_option',
		'update_option' => 'wlo_update_option',
		'delete_option' => 'wlo_delete_option',
	);

	/**
	 * Instantiate our taxonomy meta class
	 */
	$cats = new Taxonomy_MetaData_CMB2( 'category', $metabox_id, __( 'Category Settings', 'taxonomy-metadata' ), $wlo_overrides );
}
add_action( 'cmb2_init', 'taxonomy_metadata_cmb2_init' );
```

#### Changelog

* 1.0.0
	* Update `Taxonomy_MetaData_CMB2` to use new CMB2 API for adding metaboxes/fields, and update `display_form` to closer mimic `cmb2_print_metabox_form`. Also update readme to demonstrate.
	* Add WordPress plugin headers so library can be installed as a plugin.

* 0.2.2
	* Add filter to disable form in taxonomy add-new section. [Props @billerickson](https://github.com/jtsternberg/Taxonomy_MetaData/pull/20).

* 0.2.1
	* Make Taxonomy_MetaData::get() work the same way when using CMB2 version, aka, return all options for a term if no key is provided.

* 0.2.0
	* Make CMB extended class use CMB2 instead.

* 0.1.4
	* Fix Taxonomy_MetaData_CMB title parameters now work and fixed get_meta to work properly.
	* Fix term ID not returned correctly

* 0.1.3
	* Add CMB extended class

* 1.0.0
	* Hello World!
