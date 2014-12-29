<?php
/**
 * Created by PhpStorm.
 * User: Jan
 * Date: 25. 10. 2014
 * Time: 1:03
 */

namespace Trejjam\Emailing;


class Imap
{
	/**
	 * @var array
	 */
	protected $config;
	protected $imapStream;
	private
			  $username, $password, $host, $param, $port;
	function __construct($host, $username, $password, $port = 993, $param = '/imap/ssl/validate-cert') {
		$this->host = $host;
		$this->username = $username;
		$this->password = $password;
		$this->port = $port;
		$this->param = $param;

		$this->connect();
	}
	protected function connect() {
		$this->imapStream = imap_open($this->getMailbox(), $this->username, $this->password);
	}
	protected function getMailbox() {
		return '{' . $this->host . ':' . $this->port . $this->param . '}';
	}
	public function setConfig(array $config) {
		$this->config = $config;

		return $this;
	}
	function __destruct() {
		$this->disconnect();
	}
	protected function disconnect() {
		imap_close($this->imapStream);
	}

	public function createFolder($name = NULL) {
		$nameArr = explode(".", $name);

		$folders = $this->getFoldersList();

		$createName = NULL;
		foreach ($nameArr as $v) {
			$createName .= !is_null($createName) ? "." : $this->getMailbox();
			$createName .= $v;

			if (!in_array($createName, $folders)) {
				imap_createmailbox($this->imapStream, imap_utf7_encode($createName));
			}
		}
	}
	public function getFoldersList() {
		$out = [];

		$list = imap_list($this->imapStream, $this->getMailbox(), '*');
		if (is_array($list)) {
			foreach ($list as $val) {
				$out[] = imap_utf7_decode($val);
			}
		}
		else {
			throw new \Exception('Imap list failed: ' . imap_last_error());
		}

		return $out;
	}
	public function saveMailToImap($content, $folder = 'INBOX') {
		imap_append($this->imapStream, $this->getMailbox() . $folder, $content);
	}
}
