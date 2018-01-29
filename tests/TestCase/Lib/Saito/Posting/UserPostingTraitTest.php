<?php

namespace Saito\Test\Posting;

use Cake\Core\Configure;
use Cake\I18n\Time;
use Saito\Test\SaitoTestCase;
use Saito\User\SaitoUser;

class UserPostingTraitClassMock extends \Saito\Posting\Posting
{

    public function __construct()
    {
    }

    public function set($key, $val = null)
    {
        if ($val === null) {
            $this->_rawData = $key;
            return;
        }
        $this->_rawData[$key] = $val;
    }
}

class UserPostingTraitTest extends SaitoTestCase
{

    public $editPeriod = 20;

    public $fixtures = ['app.category'];

    public function setUp()
    {
        parent::setUp();
        $this->editPeriodGlob = Configure::read('Saito.Settings.edit_period');
        Configure::write('Saito.Settings.edit_period', $this->editPeriod);
        $this->Mock = new UserPostingTraitClassMock();
    }

    public function tearDown()
    {
        parent::tearDown();
        unset($this->Mock);
        Configure::write(
            'Saito.Settings.edit_period',
            $this->editPeriodGlob
        );
    }

    public function testIsAnsweringForbiddenLock()
    {
        $this->Mock->set('category', ['id' => 2]);

        $user = ['id' => 1, 'user_type' => 'admin'];
        $this->Mock->setCurrentUser(new SaitoUser($user));

        $this->Mock->set('locked', 0);
        $result = $this->Mock->isAnsweringForbidden();
        $expected = false;
        $this->assertSame($result, $expected);

        $this->Mock->set('locked', '0');
        $result = $this->Mock->isAnsweringForbidden();
        $expected = false;
        $this->assertSame($result, $expected);

        $this->Mock->set('locked', false);
        $result = $this->Mock->isAnsweringForbidden();
        $expected = false;
        $this->assertSame($result, $expected);
    }

    public function testIsEditingForbiddenSuccess()
    {
        $entry = [
            'user_id' => 1,
            'time' => new Time(time() - ($this->editPeriod * 60) + 1),
            'locked' => 0
        ];
        $this->Mock->set($entry);

        $user = ['id' => 1, 'user_type' => 'user'];
        $this->Mock->setCurrentUser(new SaitoUser($user));

        $result = $this->Mock->isEditingAsCurrentUserForbidden();
        $this->assertFalse($result);
    }

    public function testIsEditingForbiddenEmptyUser()
    {
        $entry = [
            'user_id' => 1,
            'time' => new Time(time() - ($this->editPeriod * 60) + 1),
            'locked' => 0,
        ];
        $this->Mock->set($entry);
        $this->Mock->setCurrentUser(new SaitoUser);
        $result = $this->Mock->isEditingAsCurrentUserForbidden();
        $this->assertTrue($result);
    }

    public function testIsEditingForbiddenAnon()
    {
        $entry = [
            'user_id' => 1,
            'time' => new Time(),
        ];
        $this->Mock->set($entry);
        $user = [
            'id' => null,
            'user_type' => 'anon',
        ];
        $this->Mock->setCurrentUser(new SaitoUser($user));
        $result = $this->Mock->isEditingAsCurrentUserForbidden();
        $this->assertTrue($result);
    }

    public function testIsEditingForbiddenWrongUser()
    {
        $entry = [
            'user_id' => 1,
            'time' => new Time(),
        ];
        $this->Mock->set($entry);
        $user = [
            'id' => 2,
            'user_type' => 'user',
        ];
        $this->Mock->setCurrentUser(new SaitoUser($user));
        $result = $this->Mock->isEditingAsCurrentUserForbidden();
        $this->assertEquals($result, 'user');
    }

    public function testIsEditingForbiddenToLate()
    {
        $editPeriod = 20;
        Configure::write('Saito.Settings.edit_period', $editPeriod);
        $entry = [
            'user_id' => 1,
            'locked' => false,
            'time' => new Time(time() - ($this->editPeriod * 60) - 1)
        ];
        $this->Mock->set($entry);
        $user = [
            'id' => 1,
            'user_type' => 'user',
        ];
        $this->Mock->setCurrentUser(new SaitoUser($user));
        $result = $this->Mock->isEditingAsCurrentUserForbidden();
        $this->assertEquals($result, 'time');
    }

    public function testIsEditingForbiddenLocked()
    {
        $entry = [
            'user_id' => 1,
            'time' => new Time(),
            'locked' => 1,
        ];
        $this->Mock->set($entry);
        $user = [
            'id' => 1,
            'user_type' => 'user',
        ];
        $this->Mock->set($entry);
        $this->Mock->setCurrentUser(new SaitoUser($user));
        $result = $this->Mock->isEditingAsCurrentUserForbidden();
        $this->assertEquals($result, 'locked');
    }

    public function testIsEditingForbiddenModToLateNotFixed()
    {
        $entry = [
            'user_id' => 1,
            'time' => new Time(time() - ($this->editPeriod * 60) - 1),
            'fixed' => false,
        ];
        $user = [
            'id' => 1,
            'user_type' => 'mod',
        ];
        $this->Mock->set($entry);
        $this->Mock->setCurrentUser(new SaitoUser($user));
        $result = $this->Mock->isEditingAsCurrentUserForbidden();
        $this->assertEquals($result, 'time');
    }

    public function testIsEditingForbiddenModToLateFixed()
    {
        $editPeriod = Configure::read('Saito.Settings.edit_period') * 60;
        $entry = [
            'user_id' => 1,
            'time' => new Time(time() - $editPeriod - 1),
            'fixed' => true,
        ];
        $user = [
            'id' => 1,
            'user_type' => 'mod',
        ];
        $this->Mock->set($entry);
        $this->Mock->setCurrentUser(new SaitoUser($user));
        $result = $this->Mock->isEditingAsCurrentUserForbidden();
        $this->assertFalse($result);
    }

    public function testIsEditingForbiddenAdminToLateNotFixed()
    {
        $entry = [
            'user_id' => 1,
            'time' => new Time(time() - ($this->editPeriod * 60) - 1),
            'fixed' => false,
        ];
        $user = [
            'id' => 1,
            'user_type' => 'admin',
        ];
        $this->Mock->set($entry);
        $this->Mock->setCurrentUser(new SaitoUser($user));
        $result = $this->Mock->isEditingAsCurrentUserForbidden();
        $this->assertFalse($result);
    }
}