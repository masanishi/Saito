<?php

declare(strict_types=1);

/**
 * Saito - The Threaded Web Forum
 *
 * @copyright Copyright (c) the Saito Project Developers
 * @link https://github.com/Schlaefer/Saito
 * @license http://opensource.org/licenses/MIT
 */

namespace App\Model\Table;

use App\Lib\Model\Table\AppTable;
use App\Model\Entity\Entry;
use App\Model\Table\CategoriesTable;
use App\Model\Table\DraftsTable;
use Bookmarks\Model\Table\BookmarksTable;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Saito\Posting\PostingInterface;
use Saito\User\CurrentUser\CurrentUserInterface;
use Saito\Validation\SaitoValidationProvider;
use Search\Manager;

/**
 * Stores postings
 *
 * Field notes:
 * - `edited_by` - Came from mylittleforum. @td Should by migrated to User.id.
 * - `name` - Came from mylittleforum. Is still used in fulltext index.
 *
 * @property BookmarksTable $Bookmarks
 * @property CategoriesTable $Categories
 * @property DraftsTable $Drafts
 * @method array treeBuild(array $postings)
 * @method createPosting(array $data, CurrentUserInterface $CurrentUser)
 * @method updatePosting(Entry $posting, array $data, CurrentUserInterface $CurrentUser)
 * @method array prepareChildPosting(BasicPostingInterface $parent, array $data)
 * @method array getRecentPostings(CurrentUserInterface $CU, ?array $options = [])
 * @method bool deletePosting(int $id)
 * @method array postingsForThreads(array $tids, ?array $order = null, ?CurrentUserInterface $CU)
 * @method PostingInterface postingsForThread(int $tid, ?bool $complete = false, ?CurrentUserInterface $CU)
 */
class EntriesTable extends AppTable
{
    /**
     * Max subject length.
     *
     * Constrained to 191 due to InnoDB index max-length on MySQL 5.6.
     */
    public const SUBJECT_MAXLENGTH = 191;

    /**
     * Fields for search plugin
     *
     * @var array
     */
    public $filterArgs = [
        'subject' => ['type' => 'like'],
        'text' => ['type' => 'like'],
        'name' => ['type' => 'like'],
        'category' => ['type' => 'value'],
    ];

    protected $_defaultConfig = [
        'subject_maxlength' => 100
    ];

    /**
     * {@inheritDoc}
     */
    public function initialize(array $config)
    {
        $this->setPrimaryKey('id');

        $this->addBehavior('Posting');
        $this->addBehavior('IpLogging');
        $this->addBehavior('Timestamp');

        $this->addBehavior(
            'CounterCache',
            [
                // cache how many postings a user has
                'Users' => ['entry_count'],
                // cache how many threads a category has
                'Categories' => [
                    'thread_count' => function ($event, Entry $entity, $table, $original) {
                        if (!$entity->isRoot()) {
                            return false;
                        }
                        // posting is moved to new category…
                        if ($original) {
                            // update old category (should decrement counter)
                            $categoryId = $entity->getOriginal('category_id');
                        } else {
                            // update new category (increment counter)
                            $categoryId = $entity->get('category_id');
                        }

                        $query = $table->find('all', ['conditions' => [
                            'pid' => 0, 'category_id' => $categoryId
                        ]]);
                        $count = $query->count();

                        return $count;
                    }
                ]
            ]
        );

        $this->belongsTo('Categories', ['foreignKey' => 'category_id']);
        $this->belongsTo('Users', ['foreignKey' => 'user_id']);

        $this->hasMany(
            'Bookmarks',
            ['foreignKey' => 'entry_id', 'dependent' => true]
        );

        // Releation never queried. Just for quick access to the table.
        $this->hasOne('Drafts');
    }

    /**
     * {@inheritDoc}
     */
    public function validationDefault(Validator $validator)
    {
        $validator->setProvider('saito', SaitoValidationProvider::class);

        /// category_id
        $categoryRequiredL10N = __('vld.entries.categories.notEmpty');
        $validator
            ->notEmpty('category_id', $categoryRequiredL10N)
            ->requirePresence('category_id', 'create', $categoryRequiredL10N)
            ->add(
                'category_id',
                [
                    'numeric' => ['rule' => 'numeric'],
                    'assoc' => [
                        'rule' => ['validateAssoc', 'Categories'],
                        'last' => true,
                        'provider' => 'saito'
                    ]
                ]
            );

        /// last_answer
        $validator
            ->requirePresence('last_answer', 'create')
            ->notEmptyDateTime('last_answer', null, 'create');

        /// name
        $validator
            ->requirePresence('name', 'create')
            ->notEmptyString('name', null, 'create');

        /// pid
        $validator->requirePresence('pid', 'create');

        /// subject
        $subjectRequiredL10N = __('vld.entries.subject.notEmpty');
        $validator
            ->notEmptyString('subject', $subjectRequiredL10N)
            ->requirePresence('subject', 'create', $subjectRequiredL10N)
            ->add(
                'subject',
                [
                    'maxLength' => [
                        'rule' => ['maxLength', $this->getConfig('subject_maxlength')],
                        'message' => __('vld.entries.subject.maxlength')
                    ]
                ]
            );

        /// time
        $validator
            ->requirePresence('time', 'create')
            ->notEmptyDateTime('time', null, 'create');

        /// user_id
        $validator
            ->requirePresence('user_id', 'create')
            ->add('user_id', ['numeric' => ['rule' => 'numeric']]);

        /// views
        $validator->add(
            'views',
            ['comparison' => ['rule' => ['comparison', '>=', 0]]]
        );

        return $validator;
    }

    /**
     * {@inheritDoc}
     */
    public function buildRules(RulesChecker $rules)
    {
        $rules = parent::buildRules($rules);

        $rules->addUpdate(
            function ($entity) {
                if ($entity->isDirty('category_id')) {
                    return $entity->isRoot();
                }

                return true;
            },
            'checkCategoryChangeOnlyOnRootPostings',
            [
                'errorField' => 'category_id',
                'message' => 'Cannot change category on non-root-postings.',
            ]
        );

        return $rules;
    }

    /**
     * Advanced search configuration from SaitoSearch plugin
     *
     * @see https://github.com/FriendsOfCake/search
     *
     * @return Manager
     */
    public function searchManager(): Manager
    {
        /** @var Manager $searchManager */
        $searchManager = $this->getBehavior('Search')->searchManager();
        $searchManager
        ->like('subject', [
            'before' => true,
            'after' => true,
            'fieldMode' => 'OR',
            'comparison' => 'LIKE',
            'wildcardAny' => '*',
            'wildcardOne' => '?',
            'field' => ['subject'],
            'filterEmpty' => true,
        ])
        ->like('text', [
            'before' => true,
            'after' => true,
            'fieldMode' => 'OR',
            'comparison' => 'LIKE',
            'wildcardAny' => '*',
            'wildcardOne' => '?',
            'field' => ['text'],
            'filterEmpty' => true,
        ])
        ->value('name', ['filterEmpty' => true]);

        return $searchManager;
    }

    /**
     * Shorthand for reading an entry with full da516ta
     *
     * @param int $primaryKey key
     * @param array $options options
     * @return mixed Posting if found false otherwise
     */
    public function get($primaryKey, $options = [])
    {
        /** @var Entry */
        $result = $this->find('entry', ['complete' => true])
            ->where([$this->getAlias() . '.id' => $primaryKey])
            ->first();

        // @td throw exception here
        return empty($result) ? false : $result;
    }

    /**
     * Implements the custom find type 'entry'
     *
     * @param Query $query query
     * @param array $options options
     * - 'complete' bool controls fieldset selected as in getFieldset($complete)
     * @return Query
     */
    public function findEntry(Query $query, array $options = [])
    {
        $options += ['complete' => false];
        $query
            ->select($this->getFieldset($options['complete']))
            ->contain(['Users', 'Categories']);

        return $query;
    }

    /**
     * Get list of fields required to display posting.:w
     *
     * You don't want to fetch every field for performance reasons.
     *
     * @param bool $complete Threadline if false; Full posting if true
     * @return array The fieldset
     */
    public function getFieldset(bool $complete = false): array
    {
        // field list necessary for displaying a thread_line
        $threadLineFieldList = [
            'Categories.accession',
            'Categories.category',
            'Categories.description',
            'Categories.id',
            'Entries.fixed',
            'Entries.id',
            'Entries.last_answer',
            'Entries.locked',
            'Entries.name',
            'Entries.pid',
            'Entries.solves',
            'Entries.subject',
            // Entry.text determines if Entry is n/t
            'Entries.text',
            'Entries.tid',
            'Entries.time',
            'Entries.user_id',
            'Entries.views',
            'Users.username',
        ];

        // fields additional to $threadLineFieldList to show complete entry
        $showEntryFieldListAdditional = [
            'Entries.category_id',
            'Entries.edited',
            'Entries.edited_by',
            'Entries.ip',
            'Users.avatar',
            'Users.id',
            'Users.signature',
            'Users.user_place'
        ];

        $fields = $threadLineFieldList;
        if ($complete) {
            $fields = array_merge($fields, $showEntryFieldListAdditional);
        }

        return $fields;
    }

    /**
     * Finds the thread-IT for a posting.
     *
     * @param int $id Posting-Id
     * @return int Thread-Id
     * @throws RecordNotFoundException If posting isn't found
     */
    public function getThreadId($id)
    {
        $entry = $this->find(
            'all',
            ['conditions' => ['id' => $id], 'fields' => 'tid']
        )->first();
        if (empty($entry)) {
            throw new RecordNotFoundException(
                'Posting not found. Posting-Id: ' . $id
            );
        }

        return $entry->get('tid');
    }

    /**
     * creates a new root or child entry for a node
     *
     * fields in $data are filtered
     *
     * @param array $data data
     * @return Entry|null on success, null otherwise
     */
    public function createEntry(array $data): ?Entry
    {
        $data['time'] = bDate();
        $data['last_answer'] = bDate();

        /** @var Entry */
        $posting = $this->newEntity($data);
        $errors = $posting->getErrors();
        if (!empty($errors)) {
            return $posting;
        }

        $posting = $this->save($posting);
        if (!$posting) {
            return null;
        }

        $id = $posting->get('id');
        /** @var Entry */
        $posting = $this->get($id, ['return' => 'Entity']);

        if ($posting->isRoot()) {
            // posting started a new thread, so set thread-ID to posting's own ID
            /** @var Entry */
            $posting = $this->patchEntity($posting, ['tid' => $id]);
            if (!$this->save($posting)) {
                return $posting;
            }

            $this->_dispatchEvent('Model.Thread.create', ['subject' => $id, 'data' => $posting]);
        } else {
            // update last answer time of root entry
            $this->updateAll(
                ['last_answer' => $posting->get('last_answer')],
                ['id' => $posting->get('tid')]
            );

            $eventData = ['subject' => $posting->get('pid'), 'data' => $posting];
            $this->_dispatchEvent('Model.Entry.replyToEntry', $eventData);
            $this->_dispatchEvent('Model.Entry.replyToThread', $eventData);
        }

        return $posting;
    }

    /**
     * Updates a posting
     *
     * fields in $data are filtered except for $id!
     *
     * @param Entry $posting Entity
     * @param array $data data
     * @return Entry|null
     */
    public function updateEntry(Entry $posting, array $data): ?Entry
    {
        $data['id'] = $posting->get('id');
        $data['edited'] = bDate();

        /** @var Entry */
        $patched = $this->patchEntity($posting, $data);
        $errors = $patched->getErrors();
        if (!empty($errors)) {
            return $patched;
        }

        /** @var Entry */
        $new = $this->save($posting);
        if (empty($new)) {
            return null;
        }

        $this->_dispatchEvent(
            'Model.Entry.update',
            ['subject' => $posting->get('id'), 'data' => $posting]
        );

        return $new;
    }

    /**
     * Marks a sub-entry as solution to a root entry
     *
     * @param Entry $posting posting to toggle
     * @return bool success
     */
    public function toggleSolve(Entry $posting)
    {
        if ($posting->get('solves')) {
            $value = 0;
        } else {
            $value = $posting->get('tid');
        }

        $this->patchEntity($posting, ['solves' => $value]);
        if (!$this->save($posting)) {
            return false;
        }

        $this->_dispatchEvent(
            'Model.Entry.update',
            ['subject' => $posting->get('id'), 'data' => $posting]
        );

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function toggle($id, $key)
    {
        $result = parent::toggle($id, $key);
        if ($key === 'locked') {
            $this->_threadLock($id, $result);
        }

        $entry = $this->get($id);
        $this->_dispatchEvent(
            'Model.Entry.update',
            [
                'subject' => $entry->get('id'),
                'data' => $entry
            ]
        );

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function beforeMarshal(Event $event, \ArrayObject $data, \ArrayObject $options)
    {
        /// Trim whitespace on subject and text
        $toTrim = ['subject', 'text'];
        foreach ($toTrim as $field) {
            if (!empty($data[$field])) {
                $data[$field] = trim($data[$field]);
            }
        }
    }

    /**
     * Deletes posting incl. all its subposting and associated data
     *
     * @param array $idsToDelete Entry ids which should be deleted
     * @return bool
     */
    public function deleteWithIds(array $idsToDelete): bool
    {
        $success = $this->deleteAll(['id IN' => $idsToDelete]);

        if (!$success) {
            return false;
        }

        // @td Should be covered by dependent assoc. Add tests.
        $this->Bookmarks->deleteAll(['entry_id IN' => $idsToDelete]);

        $this->dispatchSaitoEvent(
            'Model.Saito.Postings.delete',
            ['subject' => $idsToDelete, 'table' => $this]
        );

        return true;
    }

    /**
     * Anonymizes the entries for a user
     *
     * @param int $userId user-ID
     * @return void
     */
    public function anonymizeEntriesFromUser(int $userId): void
    {
        // remove username from all entries and reassign to anonyme user
        $success = (bool)$this->updateAll(
            [
                'edited_by' => null,
                'ip' => null,
                'name' => null,
                'user_id' => 0,
            ],
            ['user_id' => $userId]
        );

        if ($success) {
            $this->_dispatchEvent('Cmd.Cache.clear', ['cache' => 'Thread']);
        }
    }

    /**
     * Merge thread on to entry $targetId
     *
     * @param int $sourceId root-id of the posting that is merged onto another
     *     thread
     * @param int $targetId id of the posting the source-thread should be
     *     appended to
     * @return bool true if merge was successfull false otherwise
     */
    public function threadMerge($sourceId, $targetId)
    {
        $sourcePosting = $this->get($sourceId, ['return' => 'Entity']);

        // check that source is thread-root and not an subposting
        if (!$sourcePosting->isRoot()) {
            return false;
        }

        $targetPosting = $this->get($targetId);

        // check that target exists
        if (!$targetPosting) {
            return false;
        }

        // check that a thread is not merged onto itself
        if ($targetPosting->get('tid') === $sourcePosting->get('tid')) {
            return false;
        }

        // set target entry as new parent entry
        $this->patchEntity(
            $sourcePosting,
            ['pid' => $targetPosting->get('id')]
        );
        if ($this->save($sourcePosting)) {
            // associate all entries in source thread to target thread
            $this->updateAll(
                ['tid' => $targetPosting->get('tid')],
                ['tid' => $sourcePosting->get('tid')]
            );

            // appended source entries get category of target thread
            $this->_threadChangeCategory(
                $targetPosting->get('tid'),
                $targetPosting->get('category_id')
            );

            // update target thread last answer if source is newer
            $sourceLastAnswer = $sourcePosting->get('last_answer');
            $targetLastAnswer = $targetPosting->get('last_answer');
            if ($sourceLastAnswer->gt($targetLastAnswer)) {
                $targetRoot = $this->get(
                    $targetPosting->get('tid'),
                    ['return' => 'Entity']
                );
                $targetRoot = $this->patchEntity(
                    $targetRoot,
                    ['last_answer' => $sourceLastAnswer]
                );
                $this->save($targetRoot);
            }

            // propagate pinned property from target to source
            $isTargetPinned = $targetPosting->isLocked();
            $isSourcePinned = $sourcePosting->isLocked();
            if ($isSourcePinned !== $isTargetPinned) {
                $this->_threadLock($targetPosting->get('tid'), $isTargetPinned);
            }

            $this->_dispatchEvent(
                'Model.Thread.change',
                ['subject' => $targetPosting->get('tid')]
            );

            return true;
        }

        return false;
    }

    /**
     * Implements the custom find type 'index paginator'
     *
     * @param Query $query query
     * @param array $options finder options
     * @return Query
     */
    public function findIndexPaginator(Query $query, array $options)
    {
        $query
            ->select(['id', 'pid', 'tid', 'time', 'last_answer', 'fixed'])
            ->where(['Entries.pid' => 0]);

        if (!empty($options['counter'])) {
            $query->counter($options['counter']);
        }

        return $query;
    }

    /**
     * Un-/Locks thread: sets posting in thread $tid to $locked
     *
     * @param int $tid thread-ID
     * @param bool $locked flag
     * @return void
     */
    protected function _threadLock($tid, $locked)
    {
        $this->updateAll(['locked' => $locked], ['tid' => $tid]);
    }

    /**
     * {@inheritDoc}
     */
    public function beforeSave(Event $event, Entity $entity)
    {
        $success = true;

        /// change category of thread if category of root entry changed
        if (!$entity->isNew() && $entity->isDirty('category_id')) {
            $success &= $this->_threadChangeCategory(
                // rules checks that only roots are allowed to change category, so tid = id
                $entity->get('id'),
                $entity->get('category_id')
            );
        }

        if (!$success) {
            $event->stopPropagation();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function afterSave(Event $event, Entity $entity, \ArrayObject $options)
    {
        if ($entity->isNew()) {
            $this->Drafts->deleteDraftForPosting($entity);
        }
    }

    /**
     * Changes the category of a thread.
     *
     * Assigns the new category-id to all postings in that thread.
     *
     * @param int $tid thread-ID
     * @param int $newCategoryId id for new category
     * @return bool success
     * @throws NotFoundException
     */
    protected function _threadChangeCategory(int $tid, int $newCategoryId): bool
    {
        $exists = $this->Categories->exists($newCategoryId);
        if (!$exists) {
            throw new NotFoundException();
        }
        $affected = $this->updateAll(
            ['category_id' => $newCategoryId],
            ['tid' => $tid]
        );

        return $affected > 0;
    }
}
