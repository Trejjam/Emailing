<?php
/**
 * Created by PhpStorm.
 * User: jam
 * Date: 29.12.14
 * Time: 15:28
 */

namespace Trejjam\Emailing\RabbitMq;

use Nette;
use Nette\Utils\Validators;

class Email
{
	protected $template    = NULL;
	protected $templateArr = [];

	protected $connection = "default";
	/**
	 * @var \Trejjam\Emailing\Sender
	 */
	protected $sender;
	protected $imapSave       = FALSE;
	protected $imapConnection = NULL;
	protected $imapFolder     = NULL;

	protected $from;
	protected $to;
	protected $subject = "";

	protected $unsubscribeEmail = NULL;
	protected $unsubscribeLink  = NULL;

	function __construct(\Trejjam\Emailing\EmailFactory $emailFactory, \Trejjam\Emailing\ImapFactory $imapFactory, \Trejjam\Emailing\Senders $senders) {
		$this->emailFactory = $emailFactory;
		$this->imapFactory = $imapFactory;
		$this->senders = $senders;
	}

	/**
	 * @param string $template
	 */
	public function setTemplate($template) {
		$this->template = $template;
	}
	/**
	 * @param string $connection
	 * @throws \Exception
	 */
	public function setConnection($connection) {
		$this->emailFactory->getConnection($connection);

		$this->connection = $connection;
	}
	/**
	 * @param $email
	 * @throws \Exception
	 */
	public function setSender($email) {
		$this->sender = $this->senders->getSender($email);

		if (is_null($this->sender)) {
			throw new \Exception("Sender '$email' not found.");
		}

		$this->setConnection($this->sender->getConfigName());
		$this->setFrom($this->sender->getEmail());
	}
	/**
	 * @param boolean $imapSave
	 */
	public function setImapSave($imapSave) {
		$this->imapSave = $imapSave;
	}
	/**
	 * @param string $imapConnection
	 */
	public function setImapConnection($imapConnection) {
		$this->imapFactory->getConnection($imapConnection);

		$this->imapConnection = $imapConnection;
	}
	/**
	 * @param string $folder
	 */
	public function setImapFolder($folder) {
		$this->imapFolder = $folder;
	}
	/**
	 * @param string $from
	 * @throws \Exception
	 */
	public function setFrom($from) {
		if (!Validators::isEmail($from)) {
			throw new \Exception("Email has bad format");
		}
		$this->from = $from;
	}
	/**
	 * @param string $to
	 * @throws \Exception
	 */
	public function setTo($to) {
		if (!Validators::isEmail($to)) {
			throw new \Exception("Email has bad format");
		}
		$this->to = $to;
	}
	/**
	 * @param string $subject
	 */
	public function setSubject($subject) {
		$this->subject = $subject;
	}
	/**
	 * @param array $templateArr
	 */
	public function setTemplateArr(array $templateArr) {
		$this->templateArr = $templateArr;
	}
	/**
	 * @param array $templateArr
	 */
	public function addTemplateArr(array $templateArr) {
		$this->templateArr += $templateArr;
	}
	/**
	 * @param $unsubscribeEmail
	 * @throws \Exception
	 */
	public function setUnsubscribeEmail($unsubscribeEmail) {
		if (!Validators::isEmail($unsubscribeEmail)) {
			throw new \Exception("Email has bad format");
		}

		$this->unsubscribeEmail = $unsubscribeEmail;
	}
	/**
	 * @param $unsubscribeLink
	 * @throws \Exception
	 */
	public function setUnsubscribeLink($unsubscribeLink) {
		if (!Validators::isUrl($unsubscribeLink)) {
			throw new \Exception("Link has bad format");
		}

		$this->unsubscribeLink = $unsubscribeLink;
	}

	/**
	 * @return string
	 */
	public function getJson() {
		if (is_null($this->imapConnection)) {
			$this->imapConnection = $this->connection;
		}

		$result = [
			'template'         => $this->template,
			'templateArr'      => $this->templateArr,
			'from'             => $this->from,
			'to'               => $this->to,
			'subject'          => $this->subject,
			'connection'       => $this->connection,
			'imapSave'         => $this->imapSave,
			'imapConnection'   => $this->imapConnection,
			'imapFolder'       => $this->imapFolder,
			'unsubscribeEmail' => $this->unsubscribeEmail,
			'unsubscribeLink'  => $this->unsubscribeLink,
		];

		return json_encode((object)$result);
	}
}