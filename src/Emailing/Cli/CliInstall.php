<?php
/**
 * Created by PhpStorm.
 * User: jam
 * Date: 6.12.14
 * Time: 2:25
 */

namespace Trejjam\Emailing;

use Symfony\Component\Console\Command\Command,
	Symfony\Component\Console\Input\InputInterface,
	Symfony\Component\Console\Output\OutputInterface,
	Nette;

class CliInstall extends Command
{
	const
		FILE_EMAILS_TABLE = 'send__emails',
		FILE_GROUPS_TABLE = 'send__groups',
		FILE_SENDER_LIST_TABLE = 'send__sender_list',
		FILE_GROUP_EMAIL_TABLE = 'send__group_email';

	/**
	 * @var \Nette\Database\Context @inject
	 */
	public $database;

	protected function configure() {
		$this->setName('Emailing:install')
			 ->setDescription('Install default tables');
	}
	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->database->query($this->getFile(self::FILE_EMAILS_TABLE));
		$this->database->query($this->getFile(self::FILE_GROUPS_TABLE));
		$this->database->query($this->getFile(self::FILE_SENDER_LIST_TABLE));
		$this->database->query($this->getFile(self::FILE_GROUP_EMAIL_TABLE));
	}
	protected function getFile($file) {
		return file_get_contents(__DIR__ . '/../../../sql/' . $file . '.sql');
	}
}
