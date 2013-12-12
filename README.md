Taxonomy_MetaData
=================

WordPress Helper Class for saving pseudo-metadata for taxonomy terms

#### Example Usage:
```php
<?php
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
```
