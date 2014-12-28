<?php
/**
 * Created by PhpStorm.
 * User: jam
 * Date: 6.12.14
 * Time: 2:25
 */

namespace Trejjam\Emailing;

use Symfony\Component\Console\Input\InputArgument,
	Symfony\Component\Console\Input\InputOption,
	Symfony\Component\Console\Input\InputInterface,
	Symfony\Component\Console\Output\OutputInterface,
	Nette;

class CliImap extends CliHelper
{
	protected function configure() {
		$this->setName('Emailing:imap')
			 ->setDescription('Basic IMAP task')
			 ->addArgument(
				 'connection',
				 InputArgument::OPTIONAL,
				 'Connection name',
				 'default'
			 )->addOption(
				'create-folder',
				'c',
				InputOption::VALUE_REQUIRED,
				"Create folder in IMAP (delimiter is '.')"
			)->addOption(
				'list-folders',
				'l',
				InputOption::VALUE_NONE,
				'List all IMAP folders'
			);
	}
	protected function execute(InputInterface $input, OutputInterface $output) {
		$createFolder = $input->getOption('create-folder');
		$listFolders = $input->getOption('list-folders');

		$connection = $input->getArgument('connection');

		$imap = $this->imapFactory->getConnection($connection);

		if (!is_null($createFolder)) {
			$imap->createFolder($createFolder);
		}
		if ($listFolders) {
			foreach ($imap->getFoldersList() as $v) {
				$output->writeln($v);
			}
		}
	}
}
