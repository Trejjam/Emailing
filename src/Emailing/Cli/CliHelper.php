<?php
/**
 * Created by PhpStorm.
 * User: jam
 * Date: 6.12.14
 * Time: 1:45
 */

namespace Trejjam\Emailing;

use Symfony\Component\Console\Command\Command;

abstract class CliHelper extends Command
{
	/**
	 * @var EmailFactory
	 */
	protected $emailFactory;
	/**
	 * @var ImapFactory
	 */
	protected $imapFactory;
	/**
	 * @var Emails
	 */
	protected $emails;
	/**
	 * @var Groups
	 */
	protected $groups;
	/**
	 * @var Senders
	 */
	protected $senders;

	public function __construct(EmailFactory $emailFactory, ImapFactory $imapFactory, Emails $emails, Groups $groups, Senders $senders) {
		parent::__construct();

		$this->emailFactory = $emailFactory;
		$this->imapFactory = $imapFactory;
		$this->emails = $emails;
		$this->groups = $groups;
		$this->senders = $senders;
	}
}
