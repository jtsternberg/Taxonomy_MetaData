Taxonomy_MetaData
=================

WordPress Helper Class for saving pseudo-metadata for taxonomy terms. This is not recommend if using on a very large number of terms, UNLESS using a plugin like [wp-large-options](https://github.com/voceconnect/wp-large-options/). The third parameter is used to override the get/set/delete options callbacks so that you can use [wp-large-options](https://github.com/voceconnect/wp-large-options/) (or something like it).

Includes an extended class for using [CMB](https://github.com/WebDevStudios/Custom-Metaboxes-and-Fields-for-WordPress) to generate the actual form fields. This is handy if you need more advanced fields, or do not want to create your own field `render_cb`s.

Originally inspired by [@williamsba](http://github.com/williamsba). Read his article here: [How To: Save Taxonomy Meta Data as an Options Array in WordPress](http://strangework.com/2010/07/01/how-to-save-taxonomy-meta-data-as-an-options-array-in-wordpress/).


#### Available methods for retrieving data:

* Get all meta for a taxonomy term:
	```php
	Taxonomy_MetaData::get( 'category', 3 );
	```

* Get specific term meta data:
	```php
	Taxonomy_MetaData::get( 'category', 3, 'about_text' );
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

	require_once( 'Taxonomy_MetaData/Taxonomy_MetaData.php' );
	require_once( 'wp-large-options/wp-large-options.php' );

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

	// get/set/delete overrides using wp-large-options
	$overrides = array(
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

#### To use Taxonomy_MetaData with [Custom Metaboxes and Fields](https://github.com/WebDevStudios/Custom-Metaboxes-and-Fields-for-WordPress):
```php
<?php

function cmb_taxonomy_meta_initiate() {

	require_once( 'Taxonomy_MetaData/Taxonomy_MetaData_CMB.php' );

	/**
	 * Semi-standard CMB metabox/fields array
	 */
	$meta_box = array(
		'id'         => 'cat_options',
		'show_on'    => array( 'key' => 'options-page', 'value' => array( 'unknown', ), ),
		'show_names' => true, // Show field names on the left
		'fields'     => array(
			array(
				'name' => __( 'Category Archive Featured Post', 'taxonomy-metadata' ),
				'desc' => __( 'Enter Post ID', 'taxonomy-metadata' ),
				'id'   => 'feat_post', // no prefix needed since the options are one option array.
				'type' => 'text',
			),
			array(
				'name' => __( 'Test Checkbox', 'taxonomy-metadata' ),
				'desc' => __( 'field description (optional)', 'taxonomy-metadata' ),
				'id'   => 'test_checkbox',
				'type' => 'checkbox',
			),
 			array(
				'name' => __( 'Test Text Small', 'taxonomy-metadata' ),
				'desc' => __( 'field description (optional)', 'taxonomy-metadata' ),
				'id'   => 'test_textsmall',
				'type' => 'text_small',
				// 'repeatable' => true,
			),
			array(
				'name'    => __( 'Test wysiwyg', 'taxonomy-metadata' ),
				'desc'    => __( 'field description (optional)', 'taxonomy-metadata' ),
				'id'      => 'test_wysiwyg',
				'type'    => 'wysiwyg',
				'options' => array( 'textarea_rows' => 5, ),
			),
		)
	);

	// (Recommneded) Use wp-large-options
	require_once( 'wp-large-options/wp-large-options.php' );
	$overrides = array(
		'get_option'    => 'wlo_get_option',
		'update_option' => 'wlo_update_option',
		'delete_option' => 'wlo_delete_option',
	);

	/**
	 * Instantiate our taxonomy meta class
	 */
	$cats = new Taxonomy_MetaData_CMB( 'category', $meta_box, __( 'Category Settings', 'taxonomy-metadata' ), $overrides );
}
cmb_taxonomy_meta_initiate();
```
