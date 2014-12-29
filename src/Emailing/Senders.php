<?php
/**
 * Created by PhpStorm.
 * User: jam
 * Date: 29.12.14
 * Time: 16:51
 */

namespace Trejjam\Emailing;

use Nette,
	Nette\Caching;

class Senders
{
	const
		CACHE_SENDERS = "sender_list";

	/**
	 * @var Caching\Cache
	 */
	protected $cache;
	/**
	 * @var Nette\Database\Context
	 */
	protected $database;
	/**
	 * @var EmailFactory
	 */
	protected $emailFactory;

	/**
	 * @var array
	 */
	protected $config;
	/**
	 * @var array
	 */
	protected $cacheParams = [];
	/**
	 * @var Sender[]
	 */
	protected $senders = [];

	public function __construct(Caching\Cache $cache = NULL, Nette\Database\Context $database, \Trejjam\Emailing\EmailFactory $emailFactory) {
		$this->cache = $cache;
		$this->database = $database;
		$this->emailFactory = $emailFactory;
	}
	public function setConfig(array $config) {
		$this->config = $config;
	}
	public function setCacheParams($cacheParams) {
		$this->cacheParams = $cacheParams;
	}

	protected function getTableInfo() {
		$tableInfo = $this->config['senders'];
		unset($tableInfo["table"]);

		return $tableInfo;
	}

	protected function loadSenders() {
		$senders = [];
		foreach ($this->database->table($this->config['senders']['table']) as $v) {
			$senders[$v->{$this->getTableInfo()["id"]}] = new Sender($v, $this->getTableInfo());
		}

		return $senders;
	}
	protected function validateSender(Sender $sender) {
		if ($this->emailFactory->isConnection($sender->getConfigName())) {
			return $sender;
		}

		return NULL;
	}

	public function getSenders() {
		if (!count($this->senders)) {
			$this->senders = $this->cacheLoad(self::CACHE_SENDERS, [$this, 'loadSenders']);
		}

		foreach ($this->senders as $k => $v) {
			$sender = $this->validateSender($v);
			if (is_null($sender)) {
				unset($this->senders[$k]);
			}
		}

		return $this->senders;
	}
	public function getSender($email) {
		foreach ($this->getSenders() as $v) {
			if ($v->getEmail() == $email) {
				return $v;
			}
		}

		return NULL;
	}
	public function getSenderById($id) {
		return isset($this->getSenders()[$id]) ? $this->getSenders()[$id] : NULL;
	}
	public function addSender($email, $config = "default") {
		$sender = $this->getSender($email);

		if (is_null($sender)) {
			$senderDb = $this->database->table($this->config['senders']['table'])->insert([
				$this->config['senders']['email']      => $email,
				$this->config['senders']['configName'] => $config,
			]);

			$this->senders[$senderDb->{$this->getTableInfo()["id"]}] = $sender = new Sender($senderDb, $this->getTableInfo());

			$this->invalidateCache();

			return $sender;
		}
		else {
			throw new \Exception("Sender '$email' already exist");
		}
	}
	public function editSender($email, $config = "default") {
		$sender = $this->getSender($email);

		if (!is_null($sender)) {
			$this->database->table($this->config['senders']['table'])->where([
				$this->config['senders']['id'] => $sender->getId(),
			])->update([
				$this->config['senders']['configName'] => $config,
			]);

			$this->invalidateCache(TRUE);
		}
		else {
			throw new \Exception("Sender '$email' not exist");
		}
	}
	public function removeSender($email) {
		$sender = $this->getSender($email);

		if (!is_null($sender)) {
			$this->database->table($this->config['senders']['table'])->where([
				$this->config['senders']['id'] => $sender->getId(),
			])->delete();

			unset($this->senders[$sender->getId()]);
			$this->invalidateCache();
		}
		else {
			throw new \Exception("Sender '$email' not exist");
		}
	}


	/**
	 * @param bool $needReloadNow
	 */
	public function invalidateCache($needReloadNow = FALSE) {
		$this->cacheRemove(self::CACHE_SENDERS);

		if ($needReloadNow) {
			$this->senders = [];
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

class Sender
{
	protected
		$id,
		$email,
		$configName;

	/**
	 * @param Nette\Database\Table\IRow $row
	 * @param array                     $tableInfo
	 */
	function __construct(Nette\Database\Table\IRow $row, array $tableInfo) {
		foreach ($tableInfo as $k => $v) {
			$this->{is_numeric($k) ? $v : $k} = $row->$v;
		}
	}

	public function getId() {
		return $this->id;
	}
	public function getEmail() {
		return $this->email;
	}
	public function getConfigName() {
		return $this->configName;
	}
}
