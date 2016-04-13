<?php

namespace nochso\Phormat\CLI;

use Aura\Cli\CliFactory;
use Aura\Cli\Context\OptionFactory;
use Aura\Cli\Help;
use Aura\Cli\Status;
use Aura\Cli\Stdio;
use Aura\Cli\Stdio\Formatter;
use nochso\Omni\VersionInfo;

class Application
{
	/**
	 * @var \Aura\Cli\Context
	 */
	private $context;
	/**
	 * @var \Aura\Cli\Stdio
	 */
	private $stdio;
	/**
	 * @var VersionInfo
	 */
	private $version;

	public function __construct($version)
	{
		$this->version = $version;
		$cliFactory = new CliFactory();
		$this->context = $cliFactory->newContext($GLOBALS);
		$this->stdio = new Stdio(
			new Handle('php://stdin', 'r'),
			new Handle('php://stdout', 'w+'),
			new Handle('php://stderr', 'w+'),
			new Formatter
		);
		$this->opt = $this->context->getopt($this->getOptions());
	}

	public function run()
	{
		$this->showVersion();
		$errors = $this->opt->getErrors();
		$paths = array_filter($this->opt->get(), function ($key) {
			return is_int($key) && $key > 0;
		}, ARRAY_FILTER_USE_KEY);
		$job = new FormatJob($this->stdio);
		$job->addPaths($paths);
		$errors = array_merge($errors, $job->getErrors());
		if (count($errors)) {
			$this->showHelp();
			/** @var \Exception $error */
			foreach ($errors as $error) {
				$this->stdio->errln('<<red>>'.$error->getMessage().'<<reset>>');
			}
			exit(Status::USAGE);
		}
		
		if ($this->opt->get('--diff')) {
			$job->enableDiff();
		}
		if ($this->opt->get('--summary')) {
			$job->enableSummary();
		}
		if ($this->opt->get('--print')) {
			$job->enablePrint();
		}
		if ($this->opt->get('--no-output')) {
			$job->disableOutput();
		}
		$job->run();
		exit(Status::SUCCESS);
	}

	private function showHelp()
	{
		$help = new Help(new OptionFactory());
		$help->setOptions($this->getOptions());
		$help->setSummary('Format PHP source code by a single convention.');
		$help->setUsage('[options] -- <paths ...>');
		$this->stdio->outln($help->getHelp('phormat'));
	}

	private function getOptions()
	{
		return [
			'd,diff' => 'Display differences instead of rewriting files.',
			's,summary' => "Show summary of file status.",
			'p,print' => 'Display full output instead of rewriting files.',
			'n,no-output' => 'Do not overwrite files.',
			'#paths' => 'One or many paths to files or directories.',
		];
	}

	private function showVersion()
	{
		$out = sprintf('<<green>>%s<<reset>> <<yellow>>%s<<reset>>', $this->version->getName(), $this->version->getVersion());
		$this->stdio->outln($out);
	}
}