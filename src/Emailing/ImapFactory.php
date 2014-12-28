<?php
/**
 * Created by PhpStorm.
 * User: jam
 * Date: 26.12.14
 * Time: 11:14
 */

namespace Trejjam\Emailing;


class ImapFactory
{
	/**
	 * @var array
	 */
	protected $config;
	/**
	 * @var array
	 */
	protected $imapConfig;
	/**
	 * @var Imap[]
	 */
	protected $imaps = [];

	public function setConfig(array $config, array $imapConfig) {
		foreach ($config as $k => $v) {
			if ($v['imap'] !== TRUE) {
				unset($config[$k]);
			}
		}

		$this->config = $config;
		$this->imapConfig = $imapConfig;
	}
	/**
	 * @return array
	 */
	public function getConnections() {
		return array_keys($this->config);
	}
	/**
	 * @param $name
	 * @return Imap
	 * @throws \Exception
	 */
	public function getConnection($name) {
		if ($this->isConnection($name)) {
			return $this->getImap($name);
		}
		else {
			throw new \Exception('Connection not found. Did you register it in configuration?');
		}
	}
	/**
	 * @param $name
	 * @return bool
	 */
	public function isConnection($name) {
		return isset($this->config[$name]);
	}
	/**
	 * @param $name
	 * @return Imap
	 */
	protected function getImap($name) {
		if (isset($this->imaps[$name])) {
			return $this->imaps[$name];
		}
		else {
			$config = $this->config[$name];
			if ($config['imap_host'] === TRUE) {
				$config['imap_host'] = is_null($config['host']) ? ini_get('SMTP') : $config['host'];
			}
			if ($config['imap_username'] === TRUE) {
				$config['imap_username'] = is_null($config['username']) ? '' : $config['username'];
			}
			if ($config['imap_password'] === TRUE) {
				$config['imap_password'] = is_null($config['password']) ? '' : $config['password'];
			}

			return $this->imaps[$name] = (new Imap($config['imap_host'], $config['imap_username'], $config['imap_password'], $config['imap_port'], $config['imap_param']))->setConfig($this->imapConfig);
		}
	}
}
