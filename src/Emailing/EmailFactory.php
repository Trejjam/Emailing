<?php
/**
 * Created by PhpStorm.
 * User: jam
 * Date: 26.12.14
 * Time: 11:14
 */

namespace Trejjam\Emailing;

use Nette;

class EmailFactory
{
	/**
	 * @var array
	 */
	protected $config;

	protected $emails = [];

	public function setConfig(array $config) {
		foreach ($config as $k => $v) {
			if (is_null($v['smtp'])) {
				unset($config[$k]);
			}
		}

		$this->config = $config;
	}
	/**
	 * @param $name
	 * @return Nette\Mail\IMailer
	 */
	protected function getEmail($name) {
		if (isset($this->emails[$name])) {
			return $this->emails[$name];
		}
		else {
			$config = $this->config[$name];

			return $this->emails[$name] = $config['smtp'] ? new Nette\Mail\SmtpMailer($config) : new Nette\Mail\SendmailMailer;
		}
	}
	/**
	 * @return array
	 */
	public function getConnections() {
		return array_keys($this->config);
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
	 * @return Nette\Mail\IMailer
	 * @throws \Exception
	 */
	public function getConnection($name) {
		if ($this->isConnection($name)) {
			return $this->getEmail($name);
		}
		else {
			throw new \Exception('Connection not found. Did you register it in configuration?');
		}
	}
}
