<?php
/**
 * BookmarkFixture
 *
 */
class BookmarkFixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'key' => 'primary'),
		'user_id' => array('type' => 'integer', 'null' => false, 'default' => null),
		'entry_id' => array('type' => 'integer', 'null' => false, 'default' => null),
		'comment' => array('type' => 'string', 'null' => false, 'charset' => 'utf8'),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1)
		),
			'tableParameters' => [
					'engine' => 'MyISAM',
					'charset' => 'utf8',
					'collate' => 'utf8_general_ci'
			]
	);

/**
 * Records
 *
 * @var array
 */
	public $records = array(
		array(
			'id' => 1,
			'user_id' => 3,
			'entry_id' => 1,
			'comment' => '',
			'created' => '2012-08-07 09:51:45',
			'modified' => '2012-08-07 09:51:45'
		),
		array(
			'id' => 2,
			'user_id' => 3,
			'entry_id' => 3,
			'comment' => '< Comment 2',
			'created' => '2012-08-07 19:51:45',
			'modified' => '2012-08-07 19:51:45'
		),
		array(
			'id' => 3,
			'user_id' => 1,
			'entry_id' => 1,
			'comment' => 'Comment 3',
			'created' => '2012-08-07 09:51:45',
			'modified' => '2012-08-07 09:51:45'
		),
		array(
			'id' => 4,
			'user_id' => 2,
			'entry_id' => 4,
			'comment' => 'Comment 4',
			'created' => '2012-08-07 09:51:45',
			'modified' => '2012-08-07 09:51:45'
		),
		[
				'id' => 5,
				'user_id' => 3,
				'entry_id' => 11,
				'comment' => '<BookmarkComment',
				'created' => '2012-08-07 09:51:45',
				'modified' => '2012-08-07 09:51:45'
		]
	);

}
