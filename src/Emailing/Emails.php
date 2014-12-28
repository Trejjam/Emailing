<?php
/**
 * Created by PhpStorm.
 * User: jam
 * Date: 23.11.14
 * Time: 17:39
 */

namespace Trejjam\Emailing;

use Nette;


class Emails
{
	/**
	 * @var Nette\Database\Context
	 */
	protected $database;
	/**
	 * @var Groups
	 */
	protected $groups;

	/**
	 * @var array
	 */
	protected $config;

	/**
	 * @var Email[]
	 */
	protected $emails = [];

	public function __construct(Nette\Database\Context $database) {
		$this->database = $database;
	}
	public function setConfig(array $config) {
		$this->config = $config;
	}
	public function connectGroups(Groups $groups) {
		$this->groups = $groups;
	}
	/**
	 * @param string $email
	 * @return null|Email
	 * @throws \Exception
	 */
	public function addEmail($email) {
		$emailObject = $this->getEmail($email);

		if (is_null($emailObject)) {
			$emailDb = $this->database->table($this->config['emails']['table'])
									  ->insert([
										  $this->config['emails']['email'] => $email,
									  ]);

			$this->emails[$emailDb->{$this->getTableInfo()["id"]}] = $emailObject = new Email($emailDb, $this->getTableInfo());

			return $emailObject;
		}
		else {
			throw new \Exception("Email '$email' already exist");
		}
	}
	/**
	 * @param string $email
	 * @return null|Email
	 */
	public function getEmail($email) {
		$this->initEmail();

		foreach ($this->emails as $v) {
			if ($v->getEmail() == $email) {
				return $v;
			}
		}

		return NULL;
	}
	protected function initEmail() {
		if (!count($this->emails)) {
			foreach ($this->database->table($this->config['emails']['table']) as $v) {
				$this->emails[$v->{$this->getTableInfo()["id"]}] = new Email($v, $this->getTableInfo());
			}
		}
	}
	protected function getTableInfo() {
		$tableInfo = $this->config['emails'];
		unset($tableInfo["table"]);

		return $tableInfo;
	}
	/**
	 * @param int $id
	 * @return null|Email
	 */
	public function getEmailById($id) {
		$this->initEmail();

		return isset($this->emails[$id]) ? $this->emails[$id] : NULL;
	}
	/**
	 * @param string $email
	 * @param bool   $removeFromGroups
	 * @throws \Exception
	 */
	public function removeEmail($email, $removeFromGroups = FALSE) {
		$emailObject = $this->getEmail($email);

		if (is_null($emailObject)) {
			throw new \Exception("Email '$email' not exist");
		}

		if ($removeFromGroups) {
			$groups = $this->getGroups();

			foreach ($groups->findGroupContains($emailObject) as $v) {
				$this->removeEmailFromGroup($emailObject, $v);
			}

			$groups->invalidateCache(TRUE);
		}

		$emailDb = $this->database->table($this->config['emails']['table'])->get($emailObject->getId());
		if ($emailDb) {
			$emailDb->delete();
		}
	}

	protected function getGroups() {
		if (!isset($this->groups)) {
			throw new \Exception("Please register groups by 'connectGroups' or make instanceof Groups");
		}

		return $this->groups;
	}
	public function removeEmailFromGroup(Email $email, Group $group) {
		$groups = $this->getGroups();

		if ($group->containEmail($email)) {
			$this->database->table($this->config['group_email']['table'])->where([
				$this->config['group_email']['groupId'] => $group->getId(),
				$this->config['group_email']['emailId'] => $email->getId(),
			])->delete();
		}
		else {
			throw new \Exception("Email '" . $email->getEmail() . "' is not in group '" . $group->getName() . "'");
		}

		$groups->invalidateCache(TRUE);
	}
	public function addEmailToGroup(Email $email, Group $group) {
		$groups = $this->getGroups();

		if (!$group->containEmail($email)) {
			$this->database->table($this->config['group_email']['table'])->insert([
				$this->config['group_email']['groupId'] => $group->getId(),
				$this->config['group_email']['emailId'] => $email->getId(),
			]);
		}
		else {
			throw new \Exception("Email '" . $email->getEmail() . "' is already in group '" . $group->getName() . "'");
		}

		$group->registerEmail($email);

		$groups->invalidateCache();
	}
}

class Email
{
	protected
		$id,
		$email;

	/**
	 * @param Nette\Database\Table\IRow $row
	 * @param array                     $tableInfo
	 */
	function __construct(Nette\Database\Table\IRow $row, array $tableInfo) {
		foreach ($tableInfo as $k => $v) {
			$this->{is_numeric($k) ? $v : $k} = $row->$v;
		}
	}

	/**
	 * @return int
	 */
	function getId() {
		return $this->id;
	}
	/**
	 * @return string
	 */
	function getEmail() {
		return $this->email;
	}
}
