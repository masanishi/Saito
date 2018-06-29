<?php

declare(strict_types = 1);

/**
 * Saito - The Threaded Web Forum
 *
 * @copyright Copyright (c) the Saito Project Developers 2014-2018
 * @link https://github.com/Schlaefer/Saito
 * @license http://opensource.org/licenses/MIT
 */

namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Saito\App\Registry;
use Saito\Smiley\SmileyLoader;
use Saito\User\Userlist\UserlistModel;

class ParserComponent extends Component
{
    /**
     * {@inheritDoc}
     */
    public function initialize(array $config)
    {
        $smilies = new SmileyLoader();
        $this->getController()->set('smiliesData', $smilies);

        $settings = Configure::read('Saito.Settings');

        $markup = Registry::get('MarkupSettings');
        $markup->set([
                'autolink' => $settings['autolink'],
                'bbcode_img' => $settings['bbcode_img'],
                'quote_symbol' => $settings['quote_symbol'],
                'smilies' => $settings['smilies'],
                'smiliesData' => $smilies,
                'server' => Router::fullBaseUrl(),
                'text_word_maxlength' => $settings['text_word_maxlength'],
                'UserList' => new UserlistModel(TableRegistry::get('Users')),
                'video_domains_allowed' => $settings['video_domains_allowed'],
                'webroot' => $this->request->getAttribute('webroot')
        ]);
    }
}
