<?php

declare(strict_types=1);

/**
 * Saito - The Threaded Web Forum
 *
 * @copyright Copyright (c) the Saito Project Developers
 * @link https://github.com/Schlaefer/Saito
 * @license http://opensource.org/licenses/MIT
 */

namespace App\Test\TestCase\Controller;

use Cake\Cache\Cache;
use Saito\Exception\SaitoForbiddenException;
use Saito\Test\IntegrationTestCase;

/**
 * App\Controller\ToolsController Test Case
 */
class AdminControllerTest extends IntegrationTestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Category',
        'app.Entry',
        'app.Setting',
        'app.User',
        'app.UserBlock',
        'app.UserIgnore',
        'app.UserOnline',
        'app.UserRead',
    ];

    /**
     * testAdminEmptyCaches method
     *
     * @return void
     */
    public function testAdminEmptyCachesNonAdmin()
    {
        $url = '/admin/admins/emptyCaches';
        $this->get($url);
        $this->assertRedirectLogin($url);
    }

    public function testAdminEmptyCachesUser()
    {
        $this->_loginUser(2);
        $url = '/admin/admins/emptyCaches';
        $this->expectException(SaitoForbiddenException::class);
        $this->get($url);
    }

    public function testAdminEmptyCaches()
    {
        $this->_loginUser(1);
        Cache::write('foo', 'bar');
        $this->assertEquals('bar', Cache::read('foo'));
        $this->get('admin/admins/emptyCaches');
        $this->assertEmpty(Cache::read('foo'));
    }

    public function testPhpInfoUserAllowence()
    {
        $this->assertRouteForRole('/admin/admins/phpinfo', 'admin');
    }
}
