<?php
/**
 * Created by PhpStorm.
 * User: Jan
 * Date: 26. 10. 2014
 * Time: 17:38
 */

namespace Trejjam\DI;

use Nette,
	Kdyby\RabbitMq\DI;

class EmailingExtension extends Nette\DI\CompilerExtension implements DI\IConsumersProvider, DI\IProducersProvider
{
	private $defaults = [
		'tables'   => [
			'emails'      => [
				'table' => 'send__emails',
				'id'    => 'id',
				'email' => 'email',
			],
			'groups'      => [
				'table'    => 'send__groups',
				'id'       => 'id',
				'parentId' => 'parent_id',
				'name'     => 'name',
				'type'     => [
					'name'    => 'type',
					'options' => [
						'public',
						'private',
					]
				],
			],
			'group_email' => [
				'table'      => 'send__group_email',
				'id'         => 'id',
				'groupId'    => 'group_id',
				'emailId'    => 'email_id',
				'newsletter' => [
					'name'    => 'newsletter',
					'options' => [
						'enable',
						'disable',
					],
				],
			],
			'senders'     => [
				'table' => 'send__senders',
				'id'         => 'id',
				'email'      => 'email',
				'configName' => 'config_name',
			]
		],
		'connect'  => [
			'default' => [
				'smtp'          => FALSE, //TRUE - use SmtpMailer, NULL - disable connection, FALSE - use SendmailMailer
				'host'          => NULL,
				'smtp_port'     => NULL,
				'username'      => NULL,
				'password'      => NULL,
				'secure'        => 'ssl',

				'imap'          => FALSE,
				'imap_host'     => TRUE, //TRUE -> use host
				'imap_port'     => 993,
				'imap_param'    => '/imap/ssl/validate-cert', //Flags from http://php.net/manual/en/function.imap-open.php
				'imap_username' => TRUE, //TRUE -> use username
				'imap_password' => TRUE, //TRUE -> use password
			],
		],
		'rabbitmq' => [
			'mailer'    => [
				'templateDir'     => '/presenters/templates/',
				'defaultTemplate' => 'emails/default.latte',
			],
			'imap'      => [
				'sendFolder' => 'Sent.From web'
			],
			'producers' => [
				'mailer' => [
					'name'       => NULL,
					'connection' => 'default',
					'exchange'   => NULL,
				],
				'imap'   => [
					'name'       => NULL,
					'connection' => 'default',
					'exchange'   => NULL,
				],
			],
			'consumers' => [
				'mailer' => [
					'connection' => 'default',
					'exchange'   => NULL,
					'queue'      => NULL,
					'method'     => 'sendMailConsumer',
				],
				'imap'   => [
					'connection' => 'default',
					'exchange'   => NULL,
					'queue'      => NULL,
					'method'     => 'saveToImapConsumer',
				],
			]
		],
		'cache'    => [
			'use'     => TRUE,
			'name'    => 'emailing',
			'timeout' => '60 minutes',
		],
	];

	private $producers = [];
	private $consumers = [];

	public function setCompiler(\Nette\DI\Compiler $compiler, $name) {
		$ret = parent::setCompiler($compiler, $name);

		if (!function_exists("imap_open")) {
			throw new \Exception("Check if is installed ext-imap");
		}

		$config = $this->getConfig($this->defaults);

		$this->consumers = $config['rabbitmq']['consumers'];
		$this->producers = $config['rabbitmq']['producers'];

		return $ret;
	}

	public function loadConfiguration() {
		parent::loadConfiguration();

		$builder = $this->getContainerBuilder();

		$config = $this->getConfig($this->defaults);
		$config['rabbitmq']["appDir"] = $builder->parameters["appDir"];

		$emails = $builder->addDefinition($this->prefix('emails'))
						  ->setClass('Trejjam\Emailing\Emails')
						  ->addSetup('setConfig', [
							  'config' => $config['tables'],
						  ]);

		$groups = $builder->addDefinition($this->prefix('groups'))
						  ->setClass('Trejjam\Emailing\Groups')
						  ->addSetup('setConfig', [
							  'config' => $config['tables'],
						  ]);

		$senders = $builder->addDefinition($this->prefix('senders'))
						   ->setClass('Trejjam\Emailing\Senders')
						   ->addSetup('setConfig', [
							   'config' => $config['tables'],
						   ]);

		$emailFactory = $builder->addDefinition($this->prefix('emailFactory'))
								->setClass('Trejjam\Emailing\EmailFactory')
								->addSetup('setConfig', [
									'config' => $config['connect'],
								]);

		$imapFactory = $builder->addDefinition($this->prefix('imapFactory'))
							   ->setClass('Trejjam\Emailing\ImapFactory')
							   ->addSetup('setConfig', [
								   'config'     => $config['connect'],
								   'imapConfig' => $config['rabbitmq']['imap'],
							   ]);

		$rabbitmq = $builder->addDefinition($this->prefix('rabbit.mailer'))
			->setClass('Trejjam\Emailing\RabbitMq\Mailer')
							->addSetup('setConfig', [
								'config' => $config['rabbitmq'],
							]);

		if (class_exists('\Symfony\Component\Console\Command\Command')) {
			$command = [
				'cliInstall' => 'CliInstall',
				'cliImap'    => 'CliImap',
				'cliGroups'  => 'CliGroups',
				'cliSenders' => 'CliSenders',
			];

			foreach ($command as $k => $v) {
				$builder->addDefinition($this->prefix($k))
						->setClass('Trejjam\Emailing\\' . $v)
						->addTag('kdyby.console.command');
			}
		}

		if ($config["cache"]["use"]) {
			$builder->addDefinition($this->prefix("cache"))
					->setFactory('Nette\Caching\Cache')
					->setArguments(['@cacheStorage', $config["cache"]["name"]])
					->setAutowired(FALSE);

			$groups->setArguments([$this->prefix("@cache")])
				   ->addSetup("setCacheParams", ["cacheParams" => [
					   Nette\Caching\Cache::EXPIRE => $config["cache"]["timeout"]
				   ]]);

			$senders->setArguments([$this->prefix("@cache")])
					->addSetup("setCacheParams", ["cacheParams" => [
						Nette\Caching\Cache::EXPIRE => $config["cache"]["timeout"]
					]]);
		}
	}

	/**
	 * Returns array of name => array config.
	 *
	 * @return array
	 */
	function getRabbitConsumers() {
		$out = [];

		$consumers = $this->consumers;
		foreach ($consumers as $k => $v) {
			foreach ($v as $k2 => $v2) {
				if (is_null($v2) && in_array($k2, ['exchange', 'queue'])) {
					$consumers[$k][$k2] = $k;
				}
			}

			$out[$k] = [
				'connection' => $consumers[$k]['connection'],
				'exchange'   => [
					'name' => $consumers[$k]['exchange'], 'type' => 'direct'
				],
				'queue'      => ['name' => $consumers[$k]['queue']],
				'callback' => ['@Trejjam\Emailing\RabbitMq\Mailer', $consumers[$k]['method']],
			];
		}


		return $out;
	}
	/**
	 * Returns array of name => array config.
	 *
	 * @return array
	 */
	function getRabbitProducers() {
		$out = [];

		$producers = $this->producers;
		foreach ($producers as $k => $v) {
			foreach ($v as $k2 => $v2) {
				if (is_null($v2) && in_array($k2, ['exchange'])) {
					$producers[$k][$k2] = $k;
				}
			}

			$out[$k] = [
				'connection'  => $producers[$k]['connection'],
				'exchange'    => [
					'name' => $producers[$k]['exchange'], 'type' => 'direct'
				],
				'contentType' => 'application/json',
			];
		}

		return $out;
	}
}
