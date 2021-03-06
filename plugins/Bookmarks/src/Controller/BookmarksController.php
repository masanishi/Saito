<?php

declare(strict_types=1);

/**
 * Saito - The Threaded Web Forum
 *
 * @copyright Copyright (c) the Saito Project Developers
 * @link https://github.com/Schlaefer/Saito
 * @license http://opensource.org/licenses/MIT
 */

namespace Bookmarks\Controller;

use Api\Controller\ApiAppController;
use Api\Error\Exception\GenericApiException;
use Bookmarks\Model\Table\BookmarksTable;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Saito\Exception\SaitoForbiddenException;
use Saito\User\Permission\ResourceAI;

/**
 * Bookmarks Controller
 *
 * @property BookmarksTable $Bookmarks
 */
class BookmarksController extends ApiAppController
{
    /**
     * Gets all bookmarks for a logged in user
     *
     * @return void
     */
    public function index()
    {
        $categories = $this->CurrentUser->getCategories()->getAll('read');
        $bookmarks = $this->Bookmarks->find(
            'all',
            [
                'contain' => ['Entries' => ['Categories', 'Users']],
                'conditions' => [
                    'Bookmarks.user_id' => $this->CurrentUser->getId(),
                    'Entries.category_id IN' => $categories
                ],
                'order' => 'Bookmarks.id DESC',
            ]
        );
        $this->set('bookmarks', $bookmarks);
    }

    /**
     * Add a new bookmark.
     *
     * @return void
     * @throws MethodNotAllowedException
     * @throws BadRequestException
     */
    public function add()
    {
        $data = [
            'user_id' => $this->CurrentUser->getId(),
            'entry_id' => $this->request->getData('entry_id'),
        ];

        $bookmark = $this->Bookmarks->createBookmark($data);
        if (!$bookmark) {
            throw new GenericApiException('The bookmark could not be created.');
        }

        $this->set('bookmark', $bookmark);
    }

    /**
     * Edit a bookmark.
     *
     * @param int $id bookmark-ID
     * @throws MethodNotAllowedException
     * @return void
     */
    public function edit($id)
    {
        $id = (int)$id;
        $bookmark = $this->getBookmark($id);

        $this->Bookmarks->patchEntity(
            $bookmark,
            $this->request->getData(),
            ['fields' => ['comment']]
        );

        if (!$this->Bookmarks->save($bookmark)) {
            throw new GenericApiException('The bookmark could not be saved.');
        }

        $this->autoRender = false;
        $this->response = $this->response->withStatus(204);
    }

    /**
     * Delete a single bookmark.
     *
     * @param int $id bookmark-ID
     * @return void
     */
    public function delete($id)
    {
        $id = (int)$id;
        $bookmark = $this->getBookmark($id);
        if (!$this->Bookmarks->delete($bookmark)) {
            throw new GenericApiException('The bookmark could not be deleted.');
        }

        $this->autoRender = false;
        $this->response = $this->response->withStatus(204);
    }

    /**
     * {@inheritdoc}
     *
     * @param Event $event An Event instance
     * @return void
     */
    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->Security->setConfig('unlockedActions', ['add']);
    }

    /**
     * Get a single bookmark
     *
     * @param int $bookmarkId bookmark-ID
     * @throws NotFoundException
     * @throws SaitoForbiddenException
     * @return EntityInterface
     */
    private function getBookmark(int $bookmarkId): EntityInterface
    {
        $bookmark = $this->Bookmarks->find()
            ->where(['id' => $bookmarkId])
            ->first();

        if (!$bookmark) {
            throw new NotFoundException(__('Invalid bookmark.'));
        }

        $allowed = $this->CurrentUser->permission(
            'saito.plugin.bookmarks.delete',
            (new ResourceAI())->onOwner($bookmark->get('user_id'))
        );
        if (!$allowed) {
            throw new SaitoForbiddenException(
                "Attempt to access bookmark $bookmarkId.",
                ['CurrentUser' => $this->CurrentUser]
            );
        }

        return $bookmark;
    }
}
