<?php

declare(strict_types=1);

/**
 * Saito - The Threaded Web Forum
 *
 * @copyright Copyright (c) the Saito Project Developers
 * @link https://github.com/Schlaefer/Saito
 * @license http://opensource.org/licenses/MIT
 */

namespace App\Lib\Model\Table;

use App\Lib\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\ORM\Entity;

class AppSettingTable extends AppTable
{

    /**
     * afterSave callback
     *
     * @param Event $event event
     * @param Entity $entity entity
     * @param \ArrayObject $options options
     *  - 'clearCache' set to 'false' to prevent cache clearing
     * @return void
     */
    public function afterSave(Event $event, Entity $entity, \ArrayObject $options)
    {
        if (!isset($options['clearCache']) || $options['clearCache'] !== false) {
            $this->clearCache();
        }
    }

    /**
     * After delete
     *
     * @return void
     */
    public function afterDelete()
    {
        $this->clearCache();
    }

    /**
     * Clear cache
     *
     * @return void
     */
    public function clearCache()
    {
        $this->dispatchDbEvent(
            'Cmd.Cache.clear',
            ['cache' => ['Saito', 'Thread']]
        );
    }
}
