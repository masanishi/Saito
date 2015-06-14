<?php

namespace Api\Controller;

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Network\Exception\BadRequestException;
use Saito\Shouts\ShoutsDataTrait;

class ApiShoutsController extends ApiAppController
{
    use ShoutsDataTrait;

    public $helpers = ['Shouts'];

    public function shoutsGet() {
        $this->set('shouts', $this->getShouts());
    }

    /**
     * Adds a new shout
     *
     * @throws BadRequestException
     * @return void
     */
    public function shoutsPost()
    {
        $this->autoLayout = false;
        if (!isset($this->request->data['text'])) {
            throw new BadRequestException('Missing text.');
        }
        $data = [
            'text' => $this->request->data['text'],
            'user_id' => $this->CurrentUser->getId()
        ];
        if ($this->pushShout($data)) {
            $this->set('shouts', $this->getShouts());
            $this->view = 'Api.shouts_get';
        } else {
            throw new BadRequestException(
                'Tried to save entry but failed for unknown reason.'
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        if (Configure::read('Saito.Settings.shoutbox_enabled') == false) {
            throw new \MethodNotAllowedException();
        }
        $this->_checkLoggedIn();
    }
}
