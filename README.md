Taxonomy_MetaData
=================

WordPress Helper Class for saving pseudo-metadata for taxonomy terms. This is not recommend if using on a very large number of terms, UNLESS using a plugin like [wp-large-options](https://github.com/voceconnect/wp-large-options/).

Originally inspired by [@williamsba](http://github.com/williamsba). Read his article here: [How To: Save Taxonomy Meta Data as an Options Array in WordPress](http://strangework.com/2010/07/01/how-to-save-taxonomy-meta-data-as-an-options-array-in-wordpress/).


#### To initate Taxonomy_MetaData:
```php
<?php

function taxonomy_meta_initiate() {

	require_once( 'Taxonomy_MetaData.php' );

	/**
	 * Instantiate our taxonomy meta classes
	 */
	$cats = new Taxonomy_MetaData( 'category', array(
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
```

#### Available methods:

* Get all meta for a taxonomy term:
	```php
	Taxonomy_MetaData::get( 'category', 3 );
	```

* Get specific term meta data:
	```php
	Taxonomy_MetaData::get( 'category', 3, 'text_box' );
	```

* Set specific term meta data:
	```php
	$new_value = true;
	Taxonomy_MetaData::set( 'category', 3, 'text_box', $new_value );
	```
