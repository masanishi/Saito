<?php

declare(strict_types=1);

/**
 * Saito - The Threaded Web Forum
 *
 * @copyright Copyright (c) the Saito Project Developers
 * @link https://github.com/Schlaefer/Saito
 * @license http://opensource.org/licenses/MIT
 */

namespace Saito\Event;

use Cake\Event\Event;
use Cake\Event\EventListenerInterface;

/**
 * Event-manager for Saito
 *
 * A thin event manager with maximum dispatch speed.  It's called a
 * few hundred times on the front-page and gives roughly 3x over
 * the CakeEventManager in Cake 2.
 */
class SaitoEventManager implements EventListenerInterface
{

    protected static $_Instance;

    protected $_listeners = [];

    /**
     * Return instance
     *
     * @return SaitoEventManager
     */
    public static function getInstance()
    {
        if (self::$_Instance === null) {
            self::$_Instance = new SaitoEventManager();
        }

        return self::$_Instance;
    }

    /**
     * {@inheritDoc}
     */
    public function implementedEvents()
    {
        return [
            'Controller.initialize' => 'cakeEventPassThrough',
            'Model.initialize' => 'cakeEventPassThrough',
            'View.beforeRender' => 'cakeEventPassThrough'
        ];
    }

    /**
     * Pass event throuh
     *
     * @param Event $event event
     * @return void
     */
    public function cakeEventPassThrough(Event $event)
    {
        $data = ($event->getData()) ?: [];
        $data += ['subject' => $event->getSubject()];
        $name = 'Event.Saito.' . $event->getName();
        $this->dispatch($name, $data);
    }

    /**
     * attaches event-listener
     *
     * @param string|SaitoEventListener $key key
     * @param null $callable function if $key is set
     * @return void
     * @throws \InvalidArgumentException
     */
    public function attach($key, $callable = null)
    {
        if ($key instanceof SaitoEventListener) {
            foreach ($key->implementedSaitoEvents() as $eventKey => $callable) {
                $this->attach($eventKey, [$key, $callable]);
            }

            return;
        }
        if (empty($key)) {
            throw new \InvalidArgumentException();
        }
        $this->_listeners[$key][] = [
            'func' => $callable,
            'type' => gettype($callable) === 'array' ? 'object' : 'closure'
        ];
    }

    /**
     * dispatches event
     *
     * @param string $key key
     * @param array $data data
     * @return array|null
     */
    public function dispatch($key, $data = [])
    {
        // Stopwatch::start("SaitoEventManager::dispatch $key");
        if (!isset($this->_listeners[$key])) {
        // Stopwatch::stop("SaitoEventManager::dispatch $key");
            return [];
        }
        $results = [];
        foreach ($this->_listeners[$key] as $listener) {
            if ($listener['type'] === 'object') {
                // faster than call_user_func
                $result = $listener['func'][0]->{$listener['func'][1]}($data);
            } else {
                $result = $listener['func']($data);
            }
            if ($result !== null) {
                $results[] = $result;
            }
        }

        // Stopwatch::stop("SaitoEventManager::dispatch $key");
        return $results;
    }
}
