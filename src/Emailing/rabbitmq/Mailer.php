<?php
/**
 * Created by PhpStorm.
 * User: Jan
 * Date: 25. 10. 2014
 * Time: 23:24
 */

namespace Trejjam\Emailing\RabbitMq;

use Nette,
	Latte,
	Nette\Application\UI;

class Mailer
{

	/**
	 * @var \Kdyby\RabbitMq\Connection
	 */
	protected $bunny;
	/**
	 * @var \Kdyby\RabbitMq\Producer
	 */
	protected $rabbitMailer;
	/**
	 * @var \Kdyby\RabbitMq\Producer
	 */
	protected $rabbitImap;

	/**
	 * @var \Trejjam\Emailing\EmailFactory
	 */
	protected $emailFactory;
	/**
	 * @var \Trejjam\Emailing\ImapFactory
	 */
	protected $imapFactory;
	/**
	 * @var \Trejjam\Emailing\Senders
	 */
	protected $senders;

	/**
	 * @var array
	 */
	protected $config;

	public function __construct(\Kdyby\RabbitMq\Connection $bunny, \Trejjam\Emailing\EmailFactory $emailFactory, \Trejjam\Emailing\ImapFactory $imapFactory, \Trejjam\Emailing\Senders $senders) {
		$this->bunny = $bunny;
		$this->rabbitMailer = $this->bunny->getProducer('mailer');
		$this->rabbitImap = $this->bunny->getProducer('imap');
		$this->emailFactory = $emailFactory;
		$this->imapFactory = $imapFactory;
		$this->senders = $senders;
	}
	public function setConfig(array $config) {
		$this->config = $config;
	}
	/**
	 * @return Email
	 */
	public function createEmail() {
		$email = new Email($this->emailFactory, $this->imapFactory, $this->senders);

		return $email;
	}

	public function send(Email $email) {
		$this->rabbitMailer->publish($email->getJson());
	}
	public function sendMailConsumer(\PhpAmqpLib\Message\AMQPMessage $message) {
		$sendMail = json_decode($message->body);

		$latte = new Latte\Engine;

		$sendMail->templateArr->email = $sendMail->to;
		$sendMail->templateArr->subject = $sendMail->subject;
		if (!is_null($sendMail->unsubscribeLink)) {
			$sendMail->templateArr->unsubscribeLink = $sendMail->unsubscribeLink;
		}

		$mail = new Nette\Mail\Message;
		$mail->setFrom($sendMail->from)
			 ->addTo($sendMail->to)
			->setHtmlBody($latte->renderToString($this->config["appDir"] . $this->config['mailer']['templateDir'] . (is_null($sendMail->template) ? $this->config['mailer']['defaultTemplate'] : $sendMail->template),
				 (array)$sendMail->templateArr
			 ));

		if (!is_null($sendMail->unsubscribeEmail) || !is_null($sendMail->unsubscribeLink)) {
			$mail->setHeader('List-Unsubscribe', (!is_null($sendMail->unsubscribeEmail) ? '<mailto:' . $sendMail->unsubscribeEmail . '>' : '') . (!is_null($sendMail->unsubscribeEmail) && !is_null($sendMail->unsubscribeLink) ? ", " : "") . (!is_null($sendMail->unsubscribeLink) ? '<' . $sendMail->unsubscribeLink . '>' : ''), TRUE);
		}
		$mail->setSubject($sendMail->subject);

		try {
			$mailer = $this->emailFactory->getConnection($sendMail->connection);

			$mailer->send($mail);

			dump($sendMail->to);

			if ($sendMail->imapSave) {
				$this->saveToImap($mail->generateMessage(), is_null($sendMail->imapFolder) ? $this->config['imap']['sendFolder'] : $sendMail->imapFolder, $sendMail->imapConnection);
			}

			return TRUE;
		}
		catch (\Exception $e) {
			return FALSE;
		}
	}

	public function saveToImap($message, $folder, $connection = 'default') {
		$result = [
			'message'    => $message,
			'folder'     => $folder,
			'connection' => $connection,
		];

		$this->rabbitImap->publish(json_encode((object)$result));
	}
	public function saveToImapConsumer(\PhpAmqpLib\Message\AMQPMessage $message) {
		$imap = json_decode($message->body);

		try {
			$imapConnection = $this->imapFactory->getConnection($imap->connection);

			$imapConnection->saveMailToImap($imap->message, $imap->folder);

			return TRUE;
		}
		catch (\Exception $e) {
			return FALSE;
		}
	}

}
