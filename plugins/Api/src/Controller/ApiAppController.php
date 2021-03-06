<?php

declare(strict_types=1);

/**
 * Saito - The Threaded Web Forum
 *
 * @copyright Copyright (c) the Saito Project Developers
 * @link https://github.com/Schlaefer/Saito
 * @license http://opensource.org/licenses/MIT
 */

namespace Api\Controller;

use App\Controller\AppController;
use Cake\Controller\Component\AuthComponent;
use Cake\Core\Configure;
use Cake\Event\Event;

/**
 * Api App Controller
 */
class ApiAppController extends AppController
{
    /**
     * {@inheritDoc}
     */
    public function initialize()
    {
        parent::initialize();

        if ($this->components()->has('Csrf')) {
            $this->components()->unload('Csrf');
        }
        if ($this->components()->has('Security')) {
            $this->components()->unload('Security');
        }
    }
}
