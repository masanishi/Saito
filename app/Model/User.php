<?php

	App::uses('AppModel', 'Model');
	App::uses('CakeEvent', 'Event');

	/*
	 * Authentication methods
	 */
	App::uses('BlowfishPasswordHasher', 'Controller/Component/Auth');
	App::uses('MlfPasswordHasher', 'Controller/Component/Auth');
	App::uses('Mlf2PasswordHasher', 'Controller/Component/Auth');

/**
 * @td verify that old pw is needed for changing pw(?) [See user.test.php both validatePw tests]
 */
	class User extends AppModel {

		public $name = 'User';

		public $actsAs = [
				'Containable',
				'Cron.Cron' => [
						'registerGc' => [
								'id' => 'User.registerGc',
								'due' => 'daily',
						]
				]
		];

		public $hasOne = array(
			'UserOnline' => array(
				'className' => 'UserOnline',
				'foreignKey' => 'user_id',
				'conditions' => array(
					'UserOnline.user_id REGEXP "^-?[0-9]+$"'
				)
			),
		);

		public $hasMany = array(
			'Bookmark' => array(
				'foreignKey' => 'user_id',
				'dependent' => true
			),
			'Esnotification' => array(
				'foreignKey' => 'user_id'
			),
			'Entry' => array(
				'className' => 'Entry',
				'foreignKey' => 'user_id'
			),
			'UserRead' => [
					'className' => 'UserRead',
					'foreignKey' => 'user_id',
					'dependent' => true
			],
			'Upload' => array(
				'className' => 'Upload',
				'foreignKey' => 'user_id'
			)
		);

		public $validate = [
				'username' => [
						'isUnique' => ['rule' => 'isUnique'],
						'notEmpty' => ['rule' => 'notEmpty'],
						'hasAllowedChars' => ['rule' => ['validateHasAllowedChars']]
				],
				'user_type' => [
						'allowedChoice' => ['rule' => ['inList', ['user', 'admin', 'mod']]]
				],
				'password' => [
						'notEmpty' => ['rule' => 'notEmpty'],
						'pwConfirm' => [
								'rule' => ['validateConfirmPassword'],
								'message' => 'validation_error_pwConfirm'
						]
				],
				'password_old' => [
						'notEmpty' => [
								'rule' => 'notEmpty',
								'last' => 'true',
						],
						'pwCheckOld' => [
								'rule' => ['validateCheckOldPassword'],
								'last' => 'true',
								'message' => 'validation_error_pwCheckOld'
						]
				],
				'user_email' => [
						'isUnique' => ['rule' => 'isUnique', 'last' => 'true'],
						'isEmail' => ['rule' => ['email', true], 'last' => 'true']
				],
				'hide_email' => ['rule' => ['boolean']],
				'logins' => ['rule' => 'numeric'],
				'personal_messages' => ['rule' => ['boolean']],
				'user_lock' => ['rule' => ['boolean']],
				'activate_code' => [
						'numeric' => ['rule' => 'numeric', 'allowEmpty' => false],
						'between' => ['rule' => ['between', 0, 9999999]]
				],
				'user_signatures_hide' => ['rule' => ['boolean']],
				'user_signature_images_hide' => ['rule' => ['boolean']],
				'user_forum_refresh_time' => [
						'numeric' => ['rule' => 'numeric'],
						'greaterNull' => ['rule' => ['comparison', '>=', 0]],
						'maxLength' => ['rule' => ['maxLength', 3]],
				],
				'user_automaticaly_mark_as_read' => ['rule' => ['boolean']],
				'user_sort_last_answer' => ['rule' => ['boolean']],
				'user_show_own_signature' => ['rule' => ['boolean']],
				'user_color_new_postings' => ['rule' => '/^#?[a-f0-9]{0,6}$/i'],
				'user_color_old_postings' => [
						'rule' => '/^#?[a-f0-9]{0,6}$/i',
						'message' => '*'
				],
				'user_place_lat' => [
					'numeric' => ['rule' => 'numeric', 'allowEmpty' => true],
					'roundedGeoFormat' => ['rule' => '/^\d{1,2}(\.\d{0,4})?$/']
				],
				'user_place_lng' => [
					'numeric' => ['rule' => 'numeric', 'allowEmpty' => true],
					'roundedGeoFormat' => ['rule' => '/^\d{1,2}(\.\d{0,4})?$/']
				],
				'user_place_zoom' => [
					'numeric' => ['rule' => ['naturalNumber', 0], 'allowEmpty' => true],
					'between' => ['rule' => ['between', 0, 25]]
				],
				'user_color_actual_posting' => ['rule' => '/^#?[a-f0-9]{0,6}$/i']
		];

		public $findMethods = [
				'latest' => true
		];

		protected $_passwordHasher = [
			'BlowfishPasswordHasher',
			'Mlf2PasswordHasher',
			'MlfPasswordHasher'
		];

		/**
		 * @var array chars not allowed in username
		 */
		protected $_disallowedCharsInUsername = ['\'', ';', '&', '<', '>' ];

/**
 * @param null $lastRefresh
 *
 * @throws Exception
 */
		public function setLastRefresh($lastRefresh = null) {
			Stopwatch::start('Users->setLastRefresh()');
			$data[$this->alias]['last_refresh_tmp'] = date("Y-m-d H:i:s");

			if ($lastRefresh) {
				$data[$this->alias]['last_refresh'] = $lastRefresh;
			}

			$this->contain();
			$success = $this->save($data,
					[
							'callbacks' => false,
							'counterCache' => false,
							'validate' => false,
							'fieldList' => ['last_refresh_tmp', 'last_refresh']
					]);
			if ($success == false) {
				throw new Exception("Updating last user refresh failed.");
			}
			Stopwatch::end('Users->setLastRefresh()');
		}

		public function numberOfEntries() {
			/*
			  # @mlf change after mlf is gone, we only use `entry_count` then
			  $count = $this->data['User']['entry_count'];
			  if ( $count == 0 )
			 */ {
				$count = $this->Entry->find('count',
					array(
						'contain' => false,
						'conditions' => array('Entry.user_id' => $this->id),
					)
				);
			}
			return $count;
		}

		public function incrementLogins($id, $amount = 1) {
			$data = [
				$this->alias => [
					'id' => $id,
					'logins' => $this->field('logins') + $amount,
					'last_login' => date('Y-m-d H:i:s')
				]
			];
			if ($this->save($data, true, ['logins', 'last_login']) == false) {
				throw new Exception('Increment logins failed.');
			}
		}

/**
 * Removes a user and all his data execpt for his entries
 *
 * @param int $id user-ID
 * @return boolean
 */
		public function deleteAllExceptEntries($id) {
			if ($id == 1) {
				return false;
			}

			$success = true;
			$success = $success && $this->Upload->deleteAllFromUser($id);
			$success = $success && $this->Esnotification->deleteAllFromUser($id);
			$success = $success && $this->Entry->anonymizeEntriesFromUser($id);
			$success = $success && $this->UserOnline->deleteAll(
						['user_id' => $id],
						false
					);
			$success = $success && $this->delete($id, true);
			return $success;
		}

		public function autoUpdatePassword($id, $password) {
			$this->contain();
			$data = $this->read(null, $id);
			$oldPassword = $data[$this->alias]['password'];
			$blowfishHashIdentifier = '$2a$';
			if (strpos($oldPassword, $blowfishHashIdentifier) !== 0):
				$this->saveField('password', $password);
			endif;
		}

		public function afterFind($results, $primary = false) {
			$results = parent::afterFind($results, $primary);

			if (isset($results[0][$this->alias])) {
				if (array_key_exists('user_color_new_postings',
						$results[0][$this->alias])
				) {
					//* @td refactor this shit
					if (empty($results[0][$this->alias]['user_color_new_postings'])) {
						$results[0][$this->alias]['user_color_new_postings'] = '#';
						$results[0][$this->alias]['user_color_old_postings'] = '#';
						$results[0][$this->alias]['user_color_actual_posting'] = '#';
					}
				}

				if (isset($results[0][$this->alias]['user_category_custom'])) {
					if (empty($results[0][$this->alias]['user_category_custom'])) {
						$results[0][$this->alias]['user_category_custom'] = [];
					} else {
						$results[0][$this->alias]['user_category_custom'] =
								unserialize($results[0][$this->alias]['user_category_custom']);
					}
				}
			}

			return $results;
		}

		public function afterSave($created, $options = []) {
			if ($created === false && isset($this->data[$this->alias]['username'])) {
				$this->_dispatchEvent('Model.User.username.change');
			}
		}

		public function beforeSave($options = array()) {
			parent::beforeSave($options);
			if (isset($this->data[$this->alias]['password'])) {
				if (!empty($this->data[$this->alias]['password'])) {
					$this->data[$this->alias]['password'] = $this->_hashPassword($this->data[$this->alias]['password']);
				}
			}

			if (isset($this->data[$this->alias]['user_category_custom'])) {
				$this->data[$this->alias]['user_category_custom'] =
						serialize($this->data[$this->alias]['user_category_custom']);
			}

			return true;
		}

		public function beforeValidate($options = array()) {
			parent::beforeValidate($options);

			if (isset($this->data[$this->alias]['user_forum_refresh_time'])
					&& empty($this->data[$this->alias]['user_forum_refresh_time'])) {
				$this->data[$this->alias]['user_forum_refresh_time'] = 0;
			}
		}

		public function validateCheckOldPassword($data) {
			$this->contain('UserOnline');
			$oldPw = $this->field('password');
			return $this->_checkPassword($data['password_old'], $oldPw);
		}

		public function validateConfirmPassword($data) {
			$valid = false;
			if (isset($this->data[$this->alias]['password_confirm'])
					&& $data['password'] == $this->data[$this->alias]['password_confirm']) {
				$valid = true;
			}
			return $valid;
		}

		public function validateHasAllowedChars($data) {
			foreach ($this->_disallowedCharsInUsername as $char) {
				if (mb_strpos($data['username'], $char) !== false) {
					return false;
				}
			}
			return true;
		}

/**
 * Registers new user
 *
 * @param array $data
 * @return bool true if user got registred false otherwise
 */
		public function register($data) {
			$defaults = array(
				'registered' => date("Y-m-d H:i:s"),
				'user_type' => 'user',
				'activate_code' => 0,
			);
			$data = array_merge($defaults, $data[$this->alias]);

			$this->create();
			$out = $this->save($data);

			return $out;
		}

/**
 * Garbage collection for registration
 *
 * Deletes all timed out and unactivated registrations
 */
		public function registerGc() {
			$this->deleteAll([
							'activate_code REGEXP "^[0-9][0-9]+$"',
							'registered <' => date('Y-m-d H:i:s', time() - 86400)
					],
					false);
		}

		public function activate() {
			$success = $this->saveField('activate_code', 0);

			if ($success) :
				$this->contain();
				$user = $this->read();
				$this->getEventManager()->dispatch(
						new CakeEvent(
								'Model.User.afterActivate',
								$this,
								array('User' => $user['User'])
						)
				);
			endif;

			return $success;
		}

/**
 *
 * @param int $id user-id
 * @return bool|array false if not found, array otherwise
 */
		public function getProfile($id) {
			$user = $this->find(
				'first',
				['contain' => false, 'conditions' => ['id' => $id]]
			);
			if ($user) {
				$user = $user[$this->alias];
			}
			return $user;
		}

		public function countSolved($id) {
			$count = $this->Entry->find('count',
					[
							'contain' => false,
							'conditions' => [
									'Entry.user_id' => $id,
									'Entry.solves >' => '0'
							],
						// only count if user is not thread starter/don't count self-answers
							'joins' => [
									[
											'table' => $this->Entry->table,
											'alias' => 'Root',
											'type' => 'INNER',
											'conditions' => [
													'Root.id = Entry.solves',
													'Root.user_id != Entry.user_id'
											]
									]
							]
					]);
			return $count;
		}

/**
 * Set view categories preferences
 *
 * ## $category
 *
 * - 'all': set to all categories
 * - array: (cat_id1 => true|1|'1', cat_id2 => true|1|'1')
 * - int: set to single category_id
 *
 * @param string|int|array $category
 * @throws InvalidArgumentException
 */
		public function setCategory($category) {
			if ($category === 'all') {
				// set meta category 'all'
				$this->set('user_category_active', -1);
				$this->save();
			} elseif (is_array($category)) {
				// set custom set
				$availableCats = $this->Entry->Category->find('list');
				$categories = array_intersect_key($category, $availableCats);
				$newCats = array();
				if (count($categories) === 0) {
					throw new InvalidArgumentException();
				} else {
					foreach ($categories as $cat => $v) {
						$newCats[$cat] = ($v === true || $v === 1 || $v === '1');
					}
				}
				$this->set('user_category_active', 0);
				$this->set('user_category_custom', $newCats);
				$this->save();
			} else {
				$category = (int)$category;
				if ($category > 0 && $this->Entry->Category->exists((int)$category)) {
					$this->set('user_category_active', $category);
					$this->save();
				} else {
					throw new InvalidArgumentException();
				}
			}
		}

/**
 * Checks if password is valid against all supported auth methods
 *
 * @param string $password
 * @param string $hash
 * @return boolean TRUE if password match FALSE otherwise
 */
		protected function _checkPassword($password, $hash) {
			$valid = false;
			foreach ($this->_passwordHasher as $passwordHasher) {
				$PasswordHasherInstance = new $passwordHasher();
				// @: if hash is not valid hash blowfish Security::_crypt() triggers warnings
				if (@$PasswordHasherInstance->check($password, $hash)) {
					$valid = true;
					break;
				}
			}
			return $valid;
		}

/**
 * Custom hash function used for authentication with Auth component
 *
 * @param string $password
 * @return string hashed password
 */
		protected function _hashPassword($password) {
			$auth = new BlowfishPasswordHasher();
			return $auth->hash($password);
		}

		/**
		 * Find the latest, successfully registered user
		 */
		protected function _findLatest($state, $query, $results = []) {
			if ($state === 'before') {
				$query['contain'] = false;
				$query['limit'] = 1;
				$query['conditions'][$this->alias . '.activate_code'] = 0;
				$query['order'] = $this->alias . '.id DESC';
				return $query;
			}
			if (empty($results[0])) {
				return [];
			}
			return $results[0];
		}

	}
