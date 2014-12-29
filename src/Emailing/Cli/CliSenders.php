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

class CliSenders extends CliHelper
{
	protected function configure() {
		$this->setName('Emailing:senders')
			 ->setDescription('Edit senders')
			 ->addArgument(
				 'sender',
				 InputArgument::OPTIONAL,
				 'Sender email',
				 NULL
			 )->addArgument(
				'connection',
				InputArgument::OPTIONAL,
				'Sender connection',
				'default'
			)->addOption(
				'create',
				'c',
				InputOption::VALUE_NONE,
				'Create sender'
			)->addOption(
				'remove',
				'r',
				InputOption::VALUE_NONE,
				'Remove sender'
			)->addOption(
				'list',
				'l',
				InputOption::VALUE_NONE,
				'List all senders'
			);
	}
	protected function execute(InputInterface $input, OutputInterface $output) {
		$create = $input->getOption('create');
		$remove = $input->getOption('remove');
		$list = $input->getOption('list');

		$sender = $input->getArgument('sender');
		$connection = $input->getArgument('connection');

		if ($create) {
			$this->senders->addSender($sender, $connection);
		}
		else if (!is_null($sender)) {
			$this->senders->editSender($sender, $connection);
		}

		if (!$create && $remove) {
			$this->senders->removeSender($sender);
		}

		if ($list) {
			foreach ($this->senders->getSenders() as $v) {
				$output->writeln(" - " . $v->getEmail() . ":" . $v->getConfigName());
			}
		}
	}
}
