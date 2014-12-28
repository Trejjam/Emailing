<?php
/**
 * Created by PhpStorm.
 * User: Jan
 * Date: 25. 10. 2014
 * Time: 23:24
 */

namespace Trejjam\Emailing\Rabbitmq;

use Nette,
	Nette\Application\UI;

class RabbitMailer
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
	protected $mailerFactory;
	/**
	 * @var \Trejjam\Emailing\ImapFactory
	 */
	protected $imapFactory;


	/**
	 * @var array
	 */
	protected $config;

	public function __construct(\Kdyby\RabbitMq\Connection $bunny, \Trejjam\Emailing\EmailFactory $mailerFactory, \Trejjam\Emailing\ImapFactory $imapFactory) {
		$this->bunny = $bunny;
		$this->rabbitMailer = $this->bunny->getProducer('mailer');
		$this->rabbitImap = $this->bunny->getProducer('imap');
		$this->mailerFactory = $mailerFactory;
		$this->imapFactory = $imapFactory;
	}
	public function setConfig(array $config) {
		$this->config = $config;
	}
	/**
	 * @param string      $template
	 * @param array       $templateArr
	 * @param string      $from
	 * @param string      $to
	 * @param string      $subject
	 * @param string      $connection
	 * @param bool|string $imap
	 */
	public function sendMail($template, array $templateArr, $from, $to, $subject, $connection = 'default', $imap = FALSE) {
		$result = [
			'template'    => $template,
			'templateArr' => $templateArr,
			'from'        => $from,
			'to'          => $to,
			'subject'     => $subject,
			'connection'  => $connection,
			'imap'        => $imap
		];

		$this->rabbitMailer->publish(json_encode((object)$result));
	}
	public function sendMailConsumer(\PhpAmqpLib\Message\AMQPMessage $message) {
		$sendMail = json_decode($message->body);

		$latte = new \Latte\Engine;

		if (!isset($sendMail->templateArr->email)) {
			$sendMail->templateArr->email = $sendMail->to;
		}
		if (!isset($sendMail->templateArr->subject)) {
			$sendMail->templateArr->subject = $sendMail->subject;
		}

		$mail = new \Nette\Mail\Message;
		$mail->setFrom($sendMail->from)
			 ->addTo($sendMail->to)
			 ->setHtmlBody($latte->renderToString($this->config["appDir"] . $this->config['mailer']['templateDir'] . $sendMail->template,
				 (array)$sendMail->templateArr
			 ));

		if (isset($sendMail->templateArr->unsubscribe)) {
			$mail->setHeader('List-Unsubscribe', '<' . $sendMail->templateArr->unsubscribe . '>', TRUE);
		}
		if (isset($sendMail->templateArr->subscribe)) {
			$mail->setHeader('List-Subscribe', '<' . $sendMail->templateArr->subscribe . '>', TRUE);
		}
		$mail->setSubject($sendMail->subject);

		try {
			$mailer = $this->mailerFactory->getConnection($sendMail->connection);

			$mailer->send($mail);

			dump($sendMail->to);

			if ($sendMail->imap !== FALSE) {
				$this->saveToImap($mail->generateMessage(), $this->config['imap']['sendFolder'], $sendMail->imap === TRUE ? $sendMail->connection : $sendMail->imap);
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
