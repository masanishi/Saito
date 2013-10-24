<?php

	App::uses('Controller', 'Controller');
	App::uses('AppController', 'Controller');
	App::uses('SaitoControllerTestCase', 'Lib');

	class AppControllerTest extends SaitoControllerTestCase {

		public $fixtures = [
			'app.bookmark',
			'app.category',
			'app.ecach',
			'app.entry',
			'app.esevent',
			'app.esnotification',
			'app.setting',
			'app.shout',
			'app.smiley',
			'app.smiley_code',
			'app.upload',
			'app.user',
			'app.user_online'
		];

		public function testSetTitleForLayout() {
			// test empty title_for_layout
			$result = $this->testAction(
				'/entries/index',
				array('return' => 'vars')
			);
			$this->assertEqual($result['title_for_layout'], 'Forum – macnemo');

			// test nonempty title_for_layout
			$result = $this->testAction(
				'/entries/view/1',
				array('return' => 'vars')
			);
			$this->assertEqual($result['title_for_layout'], 'First_Subject – macnemo');

			// test empty title for layout with page_titles.po set
			$this->_loginUser(1);
			$result = $this->testAction(
				'/entries/add',
				array('return' => 'vars')
			);
			$this->assertEqual($result['title_for_layout'], 'New Entry – macnemo');
		}

		public function testLocalReferer() {
			$this->testAction('/entries/index');

			$_SERVER['HTTP_REFERER'] = FULL_BASE_URL . $this->controller->webroot . '/entries/index';
			$result = $this->controller->localReferer();
			$expected = '/entries/index';
			$this->assertIdentical($result, $expected);

			$_SERVER['HTTP_REFERER'] = FULL_BASE_URL . $this->controller->webroot . '/entries/view';
			$result = $this->controller->localReferer('action');
			$expected = 'view';
			$this->assertIdentical($result, $expected);

			$_SERVER['HTTP_REFERER'] = FULL_BASE_URL . $this->controller->webroot . '/some/path';
			$result = $this->controller->localReferer('controller');
			$expected = 'some';
			$this->assertIdentical($result, $expected);

			$_SERVER['HTTP_REFERER'] = FULL_BASE_URL . $this->controller->webroot . '/some/';
			$result = $this->controller->localReferer('action');
			$expected = 'index';
			$this->assertIdentical($result, $expected);

			$_SERVER['HTTP_REFERER'] = FULL_BASE_URL . $this->controller->webroot . '';
			$result = $this->controller->localReferer('action');
			$expected = 'index';
			$this->assertIdentical($result, $expected);

			$_SERVER['HTTP_REFERER'] = FULL_BASE_URL . $this->controller->webroot . '';
			$result = $this->controller->localReferer('controller');
			if (Configure::read('Saito.installed')) :
				$expected = 'entries';
			else:
				$expected = 'install';
			endif;
			$this->assertIdentical($result, $expected);

			//* external referer
			$_SERVER['HTTP_REFERER'] = 'http://heise.de/foobar/baz.html';
			$result = $this->controller->localReferer('controller');
			if (Configure::read('Saito.installed')) :
				$expected = 'entries';
			else:
				$expected = 'install';
			endif;
			$this->assertIdentical($result, $expected);

			$_SERVER['HTTP_REFERER'] = 'http://heise.de/foobar/baz.html';
			$result = $this->controller->localReferer('action');
			$expected = 'index';
			$this->assertIdentical($result, $expected);
		}

		public function testCurrentUser() {
			//* check there's no current user
			$result = $this->testAction('/entries/index', array('return' => 'vars'));

			$this->assertTrue(is_null($result['CurrentUser']->getId()));
			$this->assertFalse($result['CurrentUser']->isLoggedIn());

			//* loginUser
			$Entries = $this->generate('Entries');
			$this->_loginUser(3);
			$result = $this->testAction(
				'/entries/index',
				array('return' => 'vars')
			);
			$this->assertEqual($result['CurrentUser']->getId(), 3);
			$this->assertTrue($result['CurrentUser']->isLoggedIn());
		}

	}
