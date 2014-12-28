<?php
/**
 * Created by PhpStorm.
 * User: jam
 * Date: 28.12.14
 * Time: 12:19
 */

namespace Trejjam\Emailing;

use Nette,
	Nette\Caching;

class Groups
{
	const
		CACHE_GROUPS = "group_list";

	/**
	 * @var Caching\Cache
	 */
	protected $cache;

	/**
	 * @var Nette\Database\Context
	 */
	protected $database;
	/**
	 * @var Emails
	 */
	protected $emails;

	protected $config;
	protected $cacheParams = [];

	/**
	 * @var Group[]
	 */
	protected $groups = [];
	/**
	 * @var Group[]
	 */
	protected $rootGroups = [];

	public function __construct(Caching\Cache $cache = NULL, Nette\Database\Context $database, \Trejjam\Emailing\Emails $emails) {
		$this->cache = $cache;
		$this->database = $database;
		$this->emails = $emails;

		$emails->connectGroups($this);
	}
	public function setConfig(array $config) {
		$this->config = $config;
	}
	public function setCacheParams($cacheParams) {
		$this->cacheParams = $cacheParams;
	}

	protected function getTableInfo() {
		$tableInfo = $this->config['groups'];
		unset($tableInfo["table"]);
		$tableInfo["type"] = $tableInfo["type"]["name"];

		return $tableInfo;
	}
	protected function createGroupTree() {
		foreach ($this->database->table($this->config['groups']['table']) as $v) {
			$this->groups[$v->{$this->getTableInfo()["id"]}] = $group = new Group($v, $this->getTableInfo());

			foreach ($v->related($this->config['group_email']['table'], $this->config['group_email']['groupId']) as $v2) {
				$group->registerEmail($this->emails->getEmailById($v2->{$this->config['group_email']['emailId']}));
			}
		}

		foreach ($this->groups as $v) {
			$v->connectToParent($this->groups);

			if (!$v->hasParent()) {
				$this->rootGroups[$v->getId()] = $v;
			}
		}

		return $this->rootGroups;
	}
	/**
	 * @return Group[]
	 */
	public function getRootGroups() {
		if (!count($this->rootGroups)) {
			$this->rootGroups = $this->cacheLoad(self::CACHE_GROUPS, [$this, 'createGroupTree']);

			if (!count($this->groups)) {
				foreach ($this->rootGroups as $k => $v) {
					$this->rootGroups[$k]->fillArrays($this->groups);
				}
			}
		}

		return $this->rootGroups;
	}
	/**
	 * @return Group[]
	 */
	public function getGroups() {
		$this->getRootGroups();

		return $this->groups;
	}

	protected function validateType($type) {
		if (!in_array($type, $this->config['groups']['type']['options'])) {
			throw new \Exception("Type has invalid value");
		}
	}
	/**
	 * @param string $name
	 * @param null   $parent
	 * @param string $type
	 * @return null|Group
	 * @throws \Exception
	 */
	public function addGroup($name, $parent = NULL, $type = 'public') {
		$checkGroup = $this->getGroup($name);

		if (is_null($checkGroup)) {
			$parentGroup = NULL;
			if (!is_null($parent)) {
				if (!is_null($parentGroup = $this->getGroup($parent))) {
					$parentGroup = $parentGroup->getId();
				}
				else {
					throw new \Exception("Parent group '$parent' not exist");
				}
			}

			$this->validateType($type);

			$row = $this->database->table($this->config['groups']['table'])
								  ->insert([
									  $this->config['groups']['parentId']     => $parentGroup,
									  $this->config['groups']['name']         => $name,
									  $this->config['groups']['type']['name'] => $type
								  ]);

			$this->groups[$row->{$this->getTableInfo()["id"]}] = $group = new Group($row, $this->getTableInfo());
			$group->connectToParent($this->groups);

			if (!$group->hasParent()) {
				$this->rootGroups[$group->getId()] = $group;
			}

			$this->invalidateCache();

			return $this->getGroup($name);
		}
		else {
			throw new \Exception("Group '$name' already exist");
		}
	}
	/**
	 * @param int $id
	 * @return null|Group
	 */
	public function getGroupById($id) {
		$groups = $this->getGroups();

		return isset($groups[$id]) ? $groups[$id] : NULL;
	}
	/**
	 * @param $name
	 * @return null|Group
	 */
	public function getGroup($name) {
		$groups = $this->getGroups();

		foreach ($groups as $v) {
			if ($v->getName() == $name) {
				return $v;
			}
		}

		return NULL;
	}
	/**
	 * @param $email
	 * @return Group[]
	 */
	public function findGroupContains($email) {
		$out = [];

		foreach ($this->getGroups() as $v) {
			if ($v->containEmail($email)) {
				$out[$v->getId()] = $v;
			}
		}

		return $out;
	}
	/**
	 * @param string $name
	 * @param string $parent
	 * @return null|Group
	 * @throws \Exception
	 */
	public function moveGroup($name, $parent = NULL) {
		$checkGroup = $this->getGroup($name);

		if (!is_null($checkGroup)) {
			$parentGroup = NULL;
			if (!is_null($parent)) {
				if (!is_null($parentGroup = $this->getGroup($parent))) {
					$parentGroup = $parentGroup->getId();
				}
				else {
					throw new \Exception("Parent group '$parent' not exist");
				}
			}

			$this->database->table($this->config['groups']['table'])
						   ->where([$this->config['groups']['id'] => $checkGroup->getId()])
						   ->update([$this->config['groups']['parentId'] => $parentGroup]);

			$this->invalidateCache(TRUE);

			return $this->getGroup($name);
		}
		else {
			throw new \Exception("Group '$name' not exist");
		}
	}
	/**
	 * @param string $name
	 * @param string $type
	 * @return null|Group
	 * @throws \Exception
	 */
	public function editGroup($name, $type = 'public') {
		$checkGroup = $this->getGroup($name);

		if (!is_null($checkGroup)) {
			$this->validateType($type);

			$this->database->table($this->config['groups']['table'])
						   ->where([$this->config['groups']['id'] => $checkGroup->getId()])
						   ->update([$this->config['groups']['type']['name'] => $type]);

			$this->invalidateCache(TRUE);

			return $this->getGroup($name);
		}
		else {
			throw new \Exception("Group '$name' not exist");
		}
	}
	/**
	 * @param string $name
	 * @param bool   $recursive
	 * @throws \Exception
	 */
	public function removeGroup($name, $recursive = FALSE) {
		$group = $this->getGroup($name);

		if (is_null($group)) {
			throw new \Exception("Group '$name' not exist");
		}

		if ($recursive) {
			foreach ($group->getChild() as $v) {
				$this->removeGroup($v->getName(), $recursive);
			}
		}

		$this->database->table($this->config['groups']['table'])->get($group->getId())->delete();

		$this->invalidateCache(TRUE);
	}

	/**
	 * @param bool $needReloadNow
	 */
	public function invalidateCache($needReloadNow = FALSE) {
		$this->cacheRemove(self::CACHE_GROUPS);

		if ($needReloadNow) {
			$this->groups = [];
			$this->rootGroups = [];
		}
	}
	/**
	 * @param $key
	 */
	protected function cacheRemove($key) {
		if (!is_null($this->cache)) {
			$this->cache->save($key, NULL);
		}
	}
	/**
	 * @param string   $key
	 * @param callable $fallback
	 * @return mixed|NULL
	 */
	protected function cacheLoad($key, callable $fallback = NULL) {
		if (is_null($this->cache)) {
			if (!is_null($fallback)) {
				return $fallback();
			}
		}
		else {
			if (is_null($fallback) && !is_null($this->cache->load($key))) {
				return $this->cache->load($key);
			}
			else if (!is_null($fallback)) {
				return $this->cache->load($key, function (& $dependencies) use ($fallback) {
					$dependencies = $this->cacheParams;

					return $fallback();
				});
			}
		}

		return NULL;
	}
}

/**
 * Class Group
 * @package Trejjam\Emailing
 */
class Group
{
	protected
		$id,
		$parentId,
		$name,
		$type;

	/**
	 * @var Group
	 */
	protected $parent;
	/**
	 * @var Group[]
	 */
	protected $child = [];
	/**
	 * @var Email[]
	 */
	protected $emails = [];

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
	 * @param $groups
	 */
	public function connectToParent($groups) {
		if (!$this->hasParent()) return;
		$this->parent = $groups[$this->parentId];
		$this->parent->connectToChild($this);
	}
	/**
	 * @return bool
	 */
	public function hasParent() {
		return !is_null($this->parentId);
	}
	/**
	 * @param Group $child
	 */
	protected function connectToChild(Group $child) {
		$this->child[$child->getId()] = $child;
	}
	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}
	/**
	 * @param Group[] $groups
	 */
	public function fillArrays(array &$groups) {
		$groups[$this->getId()] = $this;

		foreach ($this->child as $v) {
			$v->fillArrays($groups);
		}
	}
	/**
	 * @param Email $email
	 */
	public function registerEmail(Email $email) {
		$this->emails[$email->getId()] = $email;
	}
	/**
	 * @return bool
	 */
	public function hasChild() {
		return (bool)count($this->child);
	}
	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}
	/**
	 * @return Group[]
	 */
	public function getChild() {
		return $this->child;
	}
	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}
	/**
	 * @return int
	 */
	public function getDepth() {
		$depth = 0;
		$that = $this;

		while ($that->hasParent()) {
			$that = $that->getParent();
			$depth++;
		}

		return $depth;
	}
	/**
	 * @return Group
	 */
	public function getParent() {
		return $this->parent;
	}
	/**
	 * @param Email $email
	 * @return bool
	 */
	public function containEmail(Email $email) {
		return isset($this->emails[$email->getId()]);
	}
	/**
	 * @return Email[]
	 */
	public function getEmailsRecursive() {
		$out = [];

		$out += $this->getEmails();

		foreach ($this->child as $v) {
			$out += $v->getEmailsRecursive();
		}

		return $out;
	}
	/**
	 * @return Email[]
	 */
	public function getEmails() {
		return $this->emails;
	}
}
