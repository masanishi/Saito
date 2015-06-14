<?php

namespace App\Model\Table;

use App\Lib\Model\Table\AppTable;
use Cake\Cache\Cache;
use Cake\Event\Event;
use Cake\Network\Exception\ForbiddenException;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use Saito\App\Registry;
use Saito\Posting\Posting;
use Saito\RememberTrait;
use Saito\User\ForumsUserInterface;
use \Stopwatch\Lib\Stopwatch;

/**
 *
 *
 * Model notes
 * ===========
 *
 * Entry.name
 * ----------
 *
 * Came from mlf. Is still used in fulltext index.
 *
 * Entry.edited_by
 * ---------------
 *
 * Came from mlf.
 *
 * @td After mlf is gone `edited_by` should conatin a User.id, not the username
 *     string.
 *
 */
class EntriesTable extends AppTable
{

    use RememberTrait;

    public $actsAs = [
        // @todo Search
        //'Search.Searchable'
    ];

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

    public $hasMany = [
        'UserRead' => [
            'foreignKey' => 'entry_id',
            'dependent' => true
        ]
    ];

    /**
     * Fields allowed in public output
     *
     * @var array
     */
    protected $_allowedPublicOutputFields = [
        'Entries.id',
        'Entries.pid',
        'Entries.tid',
        'Entries.time',
        'Entries.last_answer',
        'Entries.edited',
        'Entries.edited_by',
        'Entries.user_id',
        'Entries.name',
        'Entries.subject',
        'Entries.category_id',
        'Entries.text',
        'Entries.locked',
        'Entries.fixed',
        'Entries.views',
        'Users.username'
    ];

    /**
     * field list necessary for displaying a thread_line
     *
     * Entry.text determine if Entry is n/t
     *
     * @var string
     */
    public $threadLineFieldList = [
        'Entries.id',
        'Entries.pid',
        'Entries.tid',
        'Entries.subject',
        'Entries.text',
        'Entries.time',
        'Entries.fixed',
        'Entries.last_answer',
        'Entries.views',
        'Entries.user_id',
        'Entries.locked',
        'Entries.name',
        'Entries.solves',
        'Users.username',
        'Categories.id',
        'Categories.accession',
        'Categories.category',
        'Categories.description'
    ];

    /**
     * fields additional to $threadLineFieldList to show complete entry
     *
     * @var string
     */
    public $showEntryFieldListAdditional = [
        'Entries.edited',
        'Entries.edited_by',
        'Entries.ip',
        'Entries.category_id',
        'Users.id',
        'Users.avatar',
        'Users.signature',
        'Users.user_place'
    ];

    /**
     * Allowed external user input
     *
     * @var array
     */
    public $allowedInputFields = [
        'create' => [
            'category_id',
            'pid',
            'subject',
            'text'
        ],
        'update' => [
            'id',
            'category_id',
            'subject',
            'text'
        ]
    ];

    protected $_settings = [
        'edit_period' => 20,
        'subject_maxlength' => 100
    ];

    /**
     * {@inheritDoc}
     */
    public function initialize(array $config)
    {
        $this->addBehavior('IpLogging');
        $this->addBehavior('Markup');
        $this->addBehavior('Timestamp');
        $this->addBehavior('Tree');

        // cache how many postings a user has
        $this->addBehavior('CounterCache', ['Users' => ['entry_count']]);

        $this->belongsTo('Categories', ['foreignKey' => 'category_id']);
        $this->belongsTo('Users', ['foreignKey' => 'user_id']);

        $this->hasMany(
            'Bookmarks',
            ['foreignKey' => 'entry_id', 'dependent' => true]
        );
        // @todo 3.0 Notif
        // Is this condition working implicetly?
        //	'conditions' => array('Esevent.subject' => 'Entry.id'),
        $this->hasMany('Esevents', ['foreignKey' => 'subject']);
    }

    /**
     * {@inheritDoc}
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            //= category_id
            ->notEmpty('category_id')
            ->add(
                'category_id',
                [
                    'isAllowed' => [
                        'rule' => [
                            $this,
                            'validateCategoryIsAllowed'
                        ]
                    ],
                    'numeric' => ['rule' => 'numeric']
                ]
            )
            //= subject
            ->notEmpty('subject', __d('validation', 'entries.subject.notEmpty'))
            ->add(
                'subject',
                [
                    'maxLength' => [
                        'rule' => [$this, 'validateSubjectMaxLength'],
                        'message' => __d(
                            'validation',
                            'entries.subject.maxlength'
                        )
                    ]
                ]
            )
            //= user_id
            ->add('user_id', ['numeric' => ['rule' => 'numeric']])
            //= views
            ->add(
                'views',
                ['comparison' => ['rule' => ['comparison', '>=', 0]]]
            );
        return $validator;
    }

    /**
     * get recent entries
     *
     * @param ForumsUserInterface $User user
     * @param array $options options
     * @return array|mixed
     */
    public function getRecentEntries(
        ForumsUserInterface $User,
        array $options = []
    ) {
        Stopwatch::start('Model->User->getRecentEntries()');

        $options += [
            // @bogus why shouldn't that be tied to $User?
            'user_id' => null,
            'limit' => 10,
            'category_id' => $User->Categories->getAll('read')
        ];

        $read = function () use ($options) {
            $conditions = [];
            if ($options['user_id'] !== null) {
                $conditions[]['Entries.user_id'] = $options['user_id'];
            }
            if ($options['category_id'] !== null) {
                $conditions[]['Entries.category_id IN'] = $options['category_id'];
            };

            $result = $this
                ->find(
                    'all',
                    [
                        'contain' => ['Users', 'Categories'],
                        'fields' => $this->threadLineFieldList,
                        'conditions' => $conditions,
                        'limit' => $options['limit'],
                        'order' => ['time' => 'DESC']
                    ]
                )
                ->all();
            return $result;
        };

        $key = 'Entry.recentEntries-' . md5(serialize($options));
        $result = Cache::remember($key, $read, 'entries');

        Stopwatch::stop('Model->User->getRecentEntries()');
        return $result;
    }

    /**
     * Finds the thread-id for a posting
     *
     * @param int $id Posting-Id
     * @return int Thread-Id
     * @throws \UnexpectedValueException
     */
    public function getThreadId($id)
    {
        $entry = $this->find(
            'all',
            ['conditions' => ['id' => $id], 'fields' => 'tid']
        )->first();
        if (empty($entry)) {
            throw new \UnexpectedValueException(
                'Posting not found. Posting-Id: ' . $id
            );
        }
        return $entry->get('tid');
    }

    /**
     * Shorthand for reading an entry with full data
     *
     * @param int $primaryKey key
     * @param array $options options
     * @return mixed Posting if found false otherwise
     */
    public function get($primaryKey, $options = [])
    {
        $options += ['return' => 'Posting'];
        $return = $options['return'];
        unset($options['return']);

        $result = $this->find('entry')
            ->where([$this->alias() . '.id' => $primaryKey])
            ->first();

        if (!$result) {
            return false;
        }

        switch ($return) {
            case 'Posting':
                return $result->toPosting();
            case 'Entity':
            default:
                return $result;
        }
    }

    /**
     * get parent id
     *
     * @param int $id id
     * @return mixed
     * @throws \UnexpectedValueException
     */
    public function getParentId($id)
    {
        $entry = $this->find()->select('pid')->where(['id' => $id])->first();
        if (!$entry) {
            throw new \UnexpectedValueException(
                'Posting not found. Posting-Id: ' . $id
            );
        }
        return $entry->get('pid');
    }

    /**
     * creates a new root or child entry for a node
     *
     * fields in $data are filtered
     *
     * @param array $data data
     * @return Entity on success, false otherwise
     */
    public function createPosting($data)
    {
        if (!isset($data['pid'])) {
            $data['pid'] = 0;
        }

        if ($data['pid'] == 0 && isset($data['subject']) === false) {
            return false;
        }

        try {
            $this->prepare($data, ['preFilterFields' => 'create']);
        } catch (Exception $e) {
            return false;
        }

        $CurrentUser = Registry::get('CU');
        $data['user_id'] = $CurrentUser->getId();
        $data['name'] = $CurrentUser->get('username');

        $data['time'] = bDate();
        $data['last_answer'] = bDate();

        $posting = $this->newEntity($data);
        $errors = $posting->errors();
        if (!empty($errors)) {
            return $posting;
        }

        $newPostingEntity = $this->save($posting);
        if (!$newPostingEntity) {
            return false;
        }

        $newPostingId = $newPostingEntity->get('id');
        $newPosting = $this->get($newPostingId);

        if ($newPosting->isRoot()) {
            //= posting is start of new thread
            $newPosting = $this->patchEntity(
                $newPostingEntity,
                ['tid' => $newPostingId]
            );
            if (!$this->save($newPosting)) {
                return false;
            }
        } else {
            // update last answer time of root entry
            // @td rise error and/or roll back on failure
            $this->updateAll(
                ['last_answer' => $newPosting->get('last_answer')],
                ['id' => $newPosting->get('tid')]
            );

            $this->_dispatchEvent(
                'Model.Entry.replyToEntry',
                [
                    'subject' => $newPosting->get('pid'),
                    'data' => $newPosting
                ]
            );
            $this->_dispatchEvent(
                'Model.Entry.replyToThread',
                [
                    'subject' => $newPosting->get('tid'),
                    'data' => $newPosting
                ]
            );
        }

        return $newPostingEntity;
    }

    /**
     * Updates a posting
     *
     * fields in $data are filtered except for $id!
     *
     * @param Entity $posting Entity
     * @param array $data data
     * @return array|mixed
     * @throws \InvalidArgumentException
     * @throws NotFoundException
     */
    public function update(Entity $posting, $data)
    {
        if (empty($data['id'])) {
            throw new \InvalidArgumentException(
                'Missing entry id in arguments.'
            );
        }

        $this->prepare($data, ['preFilterFields' => 'update']);

        // prevents normal user of changing category of complete thread when answering
        // @todo this should be refactored together with the change category handling in beforeSave()
        if (!$posting->isRoot()) {
            unset($data['category_id']);
        }

        $CurrentUser = Registry::get('CU');

        $data['edited'] = bDate();
        $data['edited_by'] = $CurrentUser->get('username');

        // add editing validator
        $data['time'] = $posting->get('time');
        $data['user_id'] = $posting->get('user_id');
        $data['locked'] = $posting->get('locked');
        $this->validator()->add(
            'edited_by',
            'isEditingAllowed',
            ['rule' => [$this, 'validateEditingAllowed']]
        );

        $this->patchEntity($posting, $data);
        $result = $this->save($posting);

        if ($result) {
            $this->_dispatchEvent(
                'Model.Entry.update',
                [
                    'subject' => $posting->get('id'),
                    'data' => $posting
                ]
            );
        }

        return $result;
    }

    /**
     * Update view counter on all postings in a thread
     *
     * @param int $tid thread-ID
     * @param int $uid entries with this user-id are not updated
     * @return void
     */
    public function threadIncrementViews($tid, $uid = null)
    {
        Stopwatch::start('EntriesTable::threadIncrementViews()');
        $where = ['tid' => $tid];
        if ($uid && is_int($uid)) {
            $where['user_id !='] = $uid;
        }
        $this->increment($where, 'views');
        Stopwatch::stop('EntriesTable::threadIncrementViews()');
    }

    /**
     * Update view counter on a single posting
     *
     * @param int $id id
     * @param int $amount amount
     * @return void
     * @throws \App\Lib\Model\Table\InvalidArgumentException
     */
    public function incrementViews($id, $amount = 1)
    {
        $this->increment($id, 'views', $amount);
    }

    /**
     * tree of a single node and its subentries
     *
     * $options = array(
     *    'root' => true // performance improvements if it's a known thread-root
     * );
     *
     * @param int $id id
     * @param array $options options
     * @return array tree
     */
    public function treeForNode($id, $options = [])
    {
        $options += [
            'root' => false,
            'complete' => false
        ];

        if ($options['root']) {
            $tid = $id;
        } else {
            $tid = $this->getThreadId($id);
        }

        $fields = null;
        if ($options['complete']) {
            $fields = array_merge(
                $this->threadLineFieldList,
                $this->showEntryFieldListAdditional
            );
        }

        $tree = $this->treesForThreads([$tid], null, $fields);

        if (!$tree) {
            return $tree;
        }

        $tree = reset($tree);

        //= extract subtree
        if ((int)$tid !== (int)$id) {
            $tree = $tree->getThread()->get($id);
        }

        return $tree;
    }

    /**
     * trees for multiple tids
     *
     * @param array $ids ids
     * @param array $order order
     * @param array $fieldlist fieldlist
     * @return array|bool false if no threads or array of Postings
     */
    public function treesForThreads($ids, $order = null, $fieldlist = null)
    {
        if (empty($ids)) {
            return [];
        }

        Stopwatch::start('EntriesTable::treesForThreads()');
        if (empty($order)) {
            $order = ['last_answer' => 'ASC'];
        }

        if ($fieldlist === null) {
            $fieldlist = $this->threadLineFieldList;
        }

        $postings = $this->_getThreadEntries(
            $ids,
            ['order' => $order, 'fields' => $fieldlist]
        );


        $threads = false;
        if ($postings->count()) {
            $threads = [];
            $postings = $this->treeBuild($postings);
            foreach ($postings as $thread) {
                $id = $thread['tid'];
                $threads[$id] = $thread;
                $threads[$id] = Registry::newInstance(
                    '\Saito\Posting\Posting',
                    ['rawData' => $thread]
                );
            }
        }

        Stopwatch::stop('EntriesTable::treesForThreads()');

        return $threads;
    }

    /**
     * Returns all entries of threads $tid
     *
     * @param array $tid ids
     * @param array $params params
     * - 'fields' array of thread-ids: [1, 2, 5]
     * - 'order' sort order for threads ['time' => 'ASC'],
     * @return mixed unhydrated result set
     */
    protected function _getThreadEntries(array $tid, array $params = [])
    {
        $params += [
            'fields' => $this->threadLineFieldList,
            'order' => ['last_answer' => 'ASC']
        ];

        $threads = $this
            ->find(
                'all',
                [
                    'conditions' => ['tid IN' => $tid],
                    'contain' => ['Users', 'Categories'],
                    'fields' => $params['fields'],
                    'order' => $params['order']
                ]
            )
            // hydrating kills performance
            ->hydrate(false);

        return $threads;
    }

    /**
     * Marks a sub-entry as solution to a root entry
     *
     * @param int $id id
     * @return bool
     * @throws \InvalidArgumentException
     * @throws ForbiddenException
     */
    public function toggleSolve($id)
    {
        $posting = $this->get($id, ['return' => 'Entity']);
        if (empty($posting) || $posting->isRoot()) {
            throw new \InvalidArgumentException;
        }

        $rootId = $posting->get('tid');
        $CurrentUser = Registry::get('CU');
        $rootPosting = $this->get($rootId);
        if ($rootPosting->get('user_id') !== $CurrentUser->getId()) {
            throw new ForbiddenException;
        }

        if ($posting->get('solves')) {
            $value = 0;
        } else {
            $value = $rootId;
        }

        $this->patchEntity($posting, ['solves' => $value]);
        $success = $this->save($posting);
        if (!$success) {
            return $success;
        }
        $this->_dispatchEvent(
            'Model.Entry.update',
            ['subject' => $id, 'data' => $posting]
        );
        return $success;
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

        /* @todo 3.0
         * $this->contain();
         * $entry = $this->read();
         *
         * $this->_dispatchEvent(
         * 'Model.Entry.update',
         * [
         * 'subject' => $entry[$this->alias]['id'],
         * 'data' => $entry
         * ]
         * );
         */

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function beforeValidate(
        Event $event,
        Entity $entity,
        \ArrayObject $options,
        Validator $validator
    ) {
        //= in n/t posting delete unnecessary body text
        if ($entity->dirty('text')) {
            $entity->set('text', rtrim($entity->get('text')));
        }
    }

    /**
     * Deletes posting incl. all its subposting and associated data
     *
     * @param int $id id
     * @throws InvalidArgumentException
     * @throws \Exception
     * @return bool
     */
    public function treeDeleteNode($id)
    {
        $root = $this->treeForNode($id);

        if (empty($root)) {
            throw new \Exception;
        }

        $nodesToDelete[] = $root;
        $nodesToDelete = array_merge($nodesToDelete, $root->getAllChildren());

        $idsToDelete = [];
        foreach ($nodesToDelete as $node) {
            $idsToDelete[] = $node->get('id');
        };

        $success = $this->deleteAll(['id IN' => $idsToDelete]);

        if (!$success) {
            return false;
        }

        /*
        // @todo 3.0 Esevent
        if ($root->isRoot()) {
            $this->Esevents->deleteSubject($id, 'thread');
        }
        foreach ($idsToDelete as $postingId) {
            $this->Esevents->deleteSubject($postingId, 'entry');
        }
        */
        $this->Bookmarks->deleteAll(['entry_id IN' => $idsToDelete]);

        $this->dispatchSaitoEvent(
            'Model.Saito.Posting.delete',
            ['subject' => $root, 'table' => $this]
        );

        return true;
    }

    /**
     * Anonymizes the entries for a user
     *
     * @param string $userId user-ID
     * @return bool success
     */
    public function anonymizeEntriesFromUser($userId)
    {
        // remove username from all entries and reassign to anonyme user
        $success = $this->updateAll(
            [
                'name' => "NULL",
                'edited_by' => "NULL",
                'ip' => "NULL",
                'user_id' => 0,
            ],
            ['user_id' => $userId]
        );

        if ($success) {
            $this->_dispatchEvent('Cmd.Cache.clear', ['cache' => 'Thread']);
        }

        return $success;
    }

    /**
     * Maps all elements in $tree to function $func
     *
     * @param array $leafs leafs
     * @param callable $func func
     * @param null $context context
     * @param null $tree tree
     *
     * @return string
     *
     * @todo 3.0 remove
     */
    public static function mapTreeElements(
        &$leafs,
        callable $func,
        $context = null,
        &$tree = null
    ) {
        if ($tree === null) {
            $tree = &$leafs;
        }
        foreach ($leafs as &$leaf) {
            $result = $func($tree, $leaf, $context);
            if ($result === 'break') {
                return 'break';
            }
            if (isset($leaf['_children'])) {
                $result = self::mapTreeElements(
                    $leaf['_children'],
                    $func,
                    $context,
                    $tree
                );
                if ($result === 'break') {
                    return 'break';
                }
            }
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

            /* @todo 3.0 Esevent
             * $this->Esevent->transferSubjectForEventType(
             * $sourceId,
             * $targetPosting[$this->alias]['tid'],
             * 'thread'
             * );
             * $this->_dispatchEvent(
             * 'Model.Thread.change',
             * ['subject' => $targetPosting[$this->alias]['tid']]
             * );
             */
            return true;
        }
        return false;
    }

    /**
     * Check if posting is thread-root.
     *
     * @param array $id posting-ID or posting data
     * @return mixed
     */
    protected function _isRoot(array $id)
    {
        if (isset($id['pid'])) {
            $pid = $id['pid'];
        } else {
            if (is_array($id) && isset($id['id'])) {
                $id = $id['id'];
            } elseif (empty($id)) {
                throw new \InvalidArgumentException();
            }
            $entry = $this->find()->where(['id' => $id])->first();
            $pid = $entry->get('pid');
        }
        return empty($pid);
    }

    /**
     * Preprocesses posting data before saving it
     *
     * @param array $data data
     * @param array $options options
     * @return void
     * @throws \InvalidArgumentException
     */
    public function prepare(&$data, array $options = [])
    {
        if (isset($options['preFilterFields'])) {
            $org = $data;
            $this->filterFields($data, $options['preFilterFields']);
            if (isset($org['Event'])) {
                $data['Event'] = $org['Event'];
            }
        }
        unset($options['preFilterFields']);

        // adds info from parent entry to an answer
        if (!$this->_isRoot($data)) {
            if (!isset($data['pid'])) {
                $pid = $this->getParentId($data['id']);
            } else {
                $pid = $data['pid'];
            }
            $parent = $this->get($pid);
            if (!$parent) {
                throw new \InvalidArgumentException;
            }

            // if new subject is empty use the parent's subject
            if (empty($data['subject'])) {
                $data['subject'] = $parent->get('subject');
            }

            $data['tid'] = $parent->get('tid');
            $data['category_id'] = $parent->get('category_id');
        }

        //= markup preprocessing
        if (empty($data['text']) === false) {
            $data['text'] = $this->prepareMarkup($data['text']);
        }
    }

    /**
     * Implements the custom find type 'entry'
     *
     * @param Query $query query
     * @return Query
     */
    public function findEntry(Query $query)
    {
        $fields = array_merge(
            $this->threadLineFieldList,
            $this->showEntryFieldListAdditional
        );
        $query->select($fields)->contain(['Users', 'Categories']);
        return $query;
    }

    /**
     * Implements the custom find type 'index paginator'
     *
     * @param Query $query query
     * @return Query
     */
    public function findIndexPaginator(Query $query)
    {
        $query
            ->select(['id', 'pid', 'tid', 'time', 'last_answer', 'fixed'])
            ->where(['Entries.pid' => 0]);
        return $query;
    }

    /**
     * Implements the custom find type 'feed'
     *
     * Add parameters for generating a rss/json-feed with find('feed', …)
     *
     * @param Query $query query
     * @return Query
     */
    public function findFeed(Query $query)
    {
        return $query->contain('Users')
            ->select($this->_allowedPublicOutputFields)
            ->limit(10);
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

        //= change category of thread if category of root entry changed
        if ($entity->dirty('category_id')) {
            $oldEntry = $this->find()
                ->select(['pid', 'tid', 'category_id'])
                ->where(['id' => $entity->get('id')])
                ->first();

            if ($oldEntry && $oldEntry->isRoot()) {
                $newCateogry = $entity->get('category_id');
                $oldCategory = $oldEntry->get('category_id');
                if ($newCateogry !== $oldCategory) {
                    $success = $success && $this
                            ->_threadChangeCategory(
                                $oldEntry->get('tid'),
                                $entity->get('category_id')
                            );
                }
            }
        }

        if (!$success) {
            $event->stopPropagation();
        }
    }

    /**
     * check that entries are only in existing and allowed categories
     *
     * @param mixed $categoryId value
     * @param array $context context
     * @return bool
     */
    public function validateCategoryIsAllowed($categoryId, $context)
    {
        if ($this->_isRoot($context['data'])) {
            $action = 'thread';
        } else {
            $action = 'answer';
        }
        $CurrentUser = Registry::get('CU');
        return $CurrentUser->Categories->permission($action, $categoryId);
    }

    /**
     * check editing allowed
     *
     * @param mixed $check value
     * @param array $context context
     * @return bool|void
     */
    public function validateEditingAllowed($check, $context)
    {
        /* @var \Saito\Posting\Posting $Posting */
        $Posting = Registry::newInstance(
            '\Saito\Posting\Posting',
            ['rawData' => $context['data']]
        );
        $forbidden = $Posting->isEditingAsCurrentUserForbidden();
        return $forbidden === false;
    }

    /**
     * check subject max length
     *
     * @param mixed $subject subject
     * @return bool
     */
    public function validateSubjectMaxLength($subject)
    {
        return mb_strlen($subject) <= $this->_setting('subject_maxlength');
    }

    /**
     * Changes the category of a thread.
     *
     * Assigns the new category-id to all postings in that thread.
     *
     * @param null $tid thread-ID
     * @param null $newCategoryId id for new category
     * @return bool success
     * @throws \NotFoundException
     * @throws \InvalidArgumentException
     */
    protected function _threadChangeCategory($tid = null, $newCategoryId = null)
    {
        if (empty($tid)) {
            throw new \InvalidArgumentException;
        }
        $exists = $this->Categories->exists($newCategoryId);
        if (!$exists) {
            throw new \NotFoundException;
        }
        $success = $this->updateAll(
            ['category_id' => $newCategoryId],
            ['tid' => $tid]
        );
        return $success;
    }
}
