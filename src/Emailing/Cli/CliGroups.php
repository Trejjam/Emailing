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

class CliGroups extends CliHelper
{
	protected function configure() {
		$this->setName('Emailing:groups')
			 ->setDescription('Edit groups')
			 ->addArgument(
				 'groupName',
				 InputArgument::OPTIONAL,
				 'Group name',
				 NULL
			 )->addArgument(
				'parentName',
				InputArgument::OPTIONAL,
				'Parent group name',
				NULL
			)->addOption(
				'type',
				't',
				InputOption::VALUE_REQUIRED,
				'Group type'
			)->addOption(
				'create',
				'c',
				InputOption::VALUE_NONE,
				'Create group'
			)->addOption(
				'move',
				'm',
				InputOption::VALUE_NONE,
				'Move group'
			)->addOption(
				'remove',
				'r',
				InputOption::VALUE_NONE,
				'Remove group'
			)->addOption(
				'list',
				'l',
				InputOption::VALUE_NONE,
				'List all groups'
			);
	}
	protected function execute(InputInterface $input, OutputInterface $output) {
		$type = $input->getOption('type');
		$create = $input->getOption('create');
		$move = $input->getOption('move');
		$remove = $input->getOption('remove');
		$list = $input->getOption('list');

		$groupName = $input->getArgument('groupName');
		$parentName = $input->getArgument('parentName');

		if ($create) {
			$this->groups->addGroup($groupName, $parentName, is_null($type) ? "public" : $type);
		}
		else if ($move) {
			$this->groups->moveGroup($groupName, $parentName);
		}

		if (!$create && !is_null($type)) {
			$this->groups->editGroup($groupName, $type);
		}
		if (!$create && $remove) {
			$this->groups->removeGroup($groupName);
		}

		if ($list) {
			foreach ($this->groups->getRootGroups() as $v) {
				$this->drawGroups($output, $v);
			}
		}
	}

	protected function drawGroups(OutputInterface $output, Group $group) {
		$output->writeln(Nette\Utils\Strings::padLeft('', $group->getDepth(), ' ') . '\_ ' . $group->getName() . ":" . $group->getType());

		foreach ($group->getChild() as $v) {
			$this->drawGroups($output, $v);
		}
	}
}
