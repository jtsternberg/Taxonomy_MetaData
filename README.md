Taxonomy_MetaData
=================

WordPress Helper Class for saving pseudo-metadata for taxonomy terms. This is not recommend if using on a very large number of terms.

Originally inspired by [@williamsba](http://github.com/williamsba). Read his article here: [How To: Save Taxonomy Meta Data as an Options Array in WordPress](http://strangework.com/2010/07/01/how-to-save-taxonomy-meta-data-as-an-options-array-in-wordpress/).


#### To initate Taxonomy_MetaData:
```php
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
$Taxonomy_MetaData = Taxonomy_MetaData::start( $app );
```

#### Available methods:

* Get all meta for a taxonomy:
	```php
	Taxonomy_MetaData::get( 'category' );
	```

* Get all meta for a taxonomy term:
	```php
	Taxonomy_MetaData::get( 'category', 'best-of' );
	```

* Get specific term meta data:
	```php
	Taxonomy_MetaData::get( 'category', 'best-of', 'show_on_front' );
	```

* Delete all meta for a taxonomy:
	```php
	Taxonomy_MetaData::delete( 'category' );
	```

* Delete all meta for a taxonomy term:
	```php
	Taxonomy_MetaData::delete( 'category', 'best-of' );
	```

* Delete specific term meta data:
	```php
	Taxonomy_MetaData::delete( 'category', 'best-of', 'show_on_front' );
	```

* Set specific term meta data:
	```php
	$new_value = true;
	Taxonomy_MetaData::set( 'category', 'best-of', 'show_on_front', $new_value );
	```
