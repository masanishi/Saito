<?php
/* Category Fixture generated on: 2010-06-21 07:06:27 : 1277098587 */
class CategoryFixture extends CakeTestFixture {
	var $name = 'Category';

	var $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
		'category_order' => array('type' => 'integer', 'null' => false, 'default' => '0'),
		'category' => array('type' => 'string', 'null' => true, 'default' => NULL),
		'description' => array('type' => 'string', 'null' => true, 'default' => NULL),
		'accession' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => 4),
		'standard_category' => array('type' => 'integer', 'null' => true, 'default' => '1', 'length' => 4),
//		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1)),
//		'tableParameters' => array()
	);

	var $records = array(
		array(
			'id' => 1,
			'category_order' => 1,
			'category' => 'Admin',
			'description' => '',
			'accession' => 2,
			'standard_category' => 1
		),
		array(
			'id' => 2,
			'category_order' => 3,
			'category' => 'Ontopic',
			'description' => '',
			'accession' => 0,
			'standard_category' => 1
		),
		array(
			'id' => 3,
			'category_order' => 2,
			'category' => 'Another Ontopic',
			'description' => '',
			'accession' => 0,
			'standard_category' => 1
		),
		array(
			'id' => 4,
			'category_order' => 4,
			'category' => 'Offtopic',
			'description' => '',
			'accession' => 1,
			'standard_category' => 1
		),
		array(
			'id' => 5,
			'category_order' => 4,
			'category' => 'Trash',
			'description' => '',
			'accession' => 1,
			'standard_category' => 0
		),
	);
}
?>