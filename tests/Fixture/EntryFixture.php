<?php

namespace App\Test\Fixture;

use Cake\Database\Schema\TableSchema;
use Cake\TestSuite\Fixture\TestFixture;

class EntryFixture extends TestFixture
{
    protected $_common = [
        'edited' => null,
        'edited_by' => null,
        'locked' => 0,
        'solves' => 0
    ];

    public $fields = [
        'created' => [
            'type' => 'datetime',
            'null' => true,
            'default' => null,
            'comment' => ''
        ],
        'modified' => [
            'type' => 'datetime',
            'null' => true,
            'default' => null,
            'comment' => ''
        ],
        'id' => [
            'type' => 'integer',
            'null' => false,
            'default' => null,
            'comment' => ''
        ],
        'pid' => [
            'type' => 'integer',
            'null' => false,
            'default' => '0',
            'comment' => ''
        ],
        'tid' => [
            'type' => 'integer',
            'null' => false,
            'default' => '0',
            'comment' => ''
        ],
        'time' => [
            'type' => 'timestamp',
            'null' => false,
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => ''
        ],
        'last_answer' => [
            'type' => 'timestamp',
            'null' => true,
            'default' => null,
            'comment' => ''
        ],
        'edited' => [
            'type' => 'timestamp',
            'null' => true,
            'default' => null,
            'comment' => ''
        ],
        'edited_by' => [
            'type' => 'string',
            'null' => true,
            'default' => null,
            'comment' => '',
        ],
        'user_id' => [
            'type' => 'integer',
            'null' => true,
            'default' => '0',
            'comment' => ''
        ],
        'name' => [
            'type' => 'string',
            'null' => true,
            'default' => null,
            'comment' => '',
        ],
        'subject' => [
            'type' => 'string',
            'null' => true,
            'default' => null,
            'comment' => '',
        ],
        'category_id' => [
            'type' => 'integer',
            'null' => false,
            'default' => '0',
            'comment' => ''
        ],
        'text' => [
            'type' => 'text',
            'null' => true,
            'default' => null,
            'comment' => '',
        ],
        'locked' => [
            'type' => 'boolean',
            'null' => true,
            'default' => null,
            'comment' => ''
        ],
        'fixed' => [
            'type' => 'boolean',
            'null' => true,
            'default' => null,
            'comment' => ''
        ],
        'views' => [
            'type' => 'integer',
            'null' => false,
            'default' => '0',
            'comment' => ''
        ],
        'flattr' => [
            'type' => 'boolean',
            'null' => true,
            'default' => null,
            'comment' => ''
        ],
        'ip' => [
            'type' => 'string',
            'null' => true,
            'default' => null,
            'comment' => '',
        ],
        'reposts' => [
            'type' => 'integer',
            'null' => false,
            'default' => '0',
            'length' => 4
        ],
        'solves' => ['type' => 'integer', 'null' => false, 'default' => '0'],
        '_constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => ['id']
            ]
        ],
        '_indexes' => [
            'fulltext' => [
                'type' => TableSchema::INDEX_FULLTEXT,
                'columns' => ['name', 'subject', 'text'],
            ]
        ],
        '_options' => [
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
            'engine' => 'InnoDB',
        ]
    ];

    /**
     * - 1
     *    - 2
     *       - 3
     *       - 9
     *          - 7
     *    - 8
     * - 4
     *   - 5
     *   - 12
     * - 6
     * - 10
     * - 11
     * - 13
     * - 14
     *   - 15
     *
     * @var type array
     */
    public $records = [
        // thread 1
        // -------------------------------------
        [
            'id' => 1,
            'subject' => 'First_Subject',
            'text' => 'First_Text',
            'pid' => 0,
            'tid' => 1,
            'time' => '2000-01-01 20:00:00',
            'last_answer' => '2000-01-04 20:02:00',
            'category_id' => 2, // accession = 0
            'user_id' => 3,
            'name' => 'Alice',
        ],
        [
            'id' => 2,
            'subject' => 'Second_Subject',
            'text' => 'Second_Text',
            'pid' => 1,
            'tid' => 1,
            'time' => '2000-01-01 20:01:00',
            'last_answer' => '2000-01-01 20:01:00',
            'category_id' => 2,
            'user_id' => 2
        ],
        [
            'id' => 3,
            'subject' => 'Third_Subject',
            'text' => '< Third_Text',
            'pid' => 2,
            'tid' => 1,
            'time' => '2000-01-01 20:02:00',
            'last_answer' => '2000-01-01 20:02:00',
            'category_id' => 2,
            'user_id' => 3,
            'name' => 'Ulysses',
            'edited' => '2000-01-01 20:04:00',
            'edited_by' => 'Ulysses',
            'ip' => '1.1.1.1',
            'solves' => 1
        ],
        [
            'id' => 7,
            'subject' => 'Fouth_Subject',
            'text' => 'Fourth_Text',
            'pid' => 9,
            'tid' => 1,
            'time' => '2000-01-02 20:03:00',
            'last_answer' => '2000-01-02 20:03:00',
            'category_id' => 2,
            'user_id' => 3,
            'name' => 'Ulysses',
            'ip' => '1.1.1.1',
            'solves' => 1
        ],
        [
            'id' => 8,
            'subject' => 'Fifth_Subject',
            'text' => 'Fifth_Text',
            'pid' => 1,
            'tid' => 1,
            'time' => '2000-01-03 20:02:00',
            'last_answer' => '2000-01-03 20:02:00',
            'category_id' => 2,
            'user_id' => 3,
            'name' => 'Ulysses',
            'ip' => '1.1.1.1'
        ],
        [
            'id' => 9,
            'subject' => 'Sixth_Subject',
            'text' => 'Sixth_Text',
            'pid' => 2,
            'tid' => 1,
            'time' => '2000-01-04 20:02:00',
            'last_answer' => '2000-01-04 20:02:00',
            'category_id' => 2,
            'user_id' => 3,
            'name' => 'Ulysses',
            'ip' => '1.1.1.1'
        ],
        // thread 2
        // -------------------------------------
        [
            'id' => 4,
            'subject' => 'Second Thread First_Subject',
            'text' => '',
            'pid' => 0,
            'tid' => 4,
            'time' => '2000-01-01 10:00:00',
            'last_answer' => '2000-01-04 20:02:00',
            'category_id' => 4, // accession = 1
            'user_id' => 1,
            'locked' => 1
        ],
        [
            'id' => 5,
            'subject' => 'Second Thread Second_Subject',
            'text' => '',
            'pid' => 4,
            'tid' => 4,
            'time' => '2000-01-04 20:00:00',
            'last_answer' => '2000-01-04 20:02:00',
            'category_id' => 4,
            'user_id' => 3,
            'name' => 'Ulysses',
            'ip' => '1.1.1.1',
            'locked' => 1,
            'solves' => 4
        ],
        [
            'id' => 12,
            'subject' => 'Second Thread Third_Subject',
            'text' => '',
            'pid' => 4,
            'tid' => 4,
            'time' => '2000-01-04 20:02:00',
            'last_answer' => '2000-01-04 20:02:00',
            'category_id' => 4,
            'user_id' => 2,
            'locked' => 1,
            'solves' => 4
        ],
        // thread 3
        // -------------------------------------
        [
            'id' => 6,
            'subject' => 'Third Thread First_Subject',
            'text' => '',
            'pid' => 0,
            'tid' => 6,
            'time' => '2000-01-01 11:00:00',
            'last_answer' => '2000-01-01 11:00:00',
            'category_id' => 1, // accession = 2
            'user_id' => 1,
            'name' => 'Alice',
            'ip' => '1.1.1.3'
        ],
        // thread 4
        // -------------------------------------
        [
            'id' => 10,
            'subject' => 'First_Subject',
            'text' => "<script>alert('foo');<script>",
            'pid' => 0,
            'tid' => 10,
            'time' => '2000-01-01 10:59:00',
            'last_answer' => '2000-01-01 10:59:00',
            'category_id' => 2, // accession = 0
            'user_id' => 3,
            'locked' => 1
        ],
        // thread 5
        // -------------------------------------
        [
            'id' => 11,
            'subject' => '&<Subject',
            'text' => "&<Text",
            'pid' => 0,
            'tid' => 11,
            'time' => '2000-01-01 10:59:00',
            'last_answer' => '2000-01-01 10:59:00',
            'category_id' => 4, // accession = 1
            'user_id' => 7,
            'fixed' => true,
        ],
        // thread 6
        // -------------------------------------
        [
            'id' => 13,
            'subject' => 'Subject 13',
            'text' => "Text 13",
            'pid' => 0,
            'tid' => 13,
            'time' => '2000-01-01 12:00:00',
            'last_answer' => '2000-01-01 12:00:00',
            'category_id' => 2, // !important accession = 0
            'user_id' => 1
        ],
        // thread 7
        // -------------------------------------
        [
            'id' => 14,
            'subject' => 'Subject 14',
            'text' => "Text 14",
            'pid' => 0,
            'tid' => 14,
            'time' => '2000-01-01 12:01:00',
            'last_answer' => '2000-01-01 12:01:00',
            'category_id' => 5, // !important
            'user_id' => 1
        ],
        [
            'id' => 15,
            'subject' => 'Subject 15',
            'text' => "Text 15",
            'pid' => 14,
            'tid' => 14,
            'time' => '2000-01-01 12:02:00',
            'last_answer' => '2000-01-01 12:02:00',
            'category_id' => 5, // !important
            'user_id' => 1
        ],
    ];

    public function init()
    {
        foreach ($this->records as $k => $record) {
            $this->records[$k] += $this->_common;
        }

        return parent::init();
    }
}
