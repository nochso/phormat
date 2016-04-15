<?php
namespace nochso\Phormat\CLI;

use Aura\Cli\CliFactory;
use Aura\Cli\Context\OptionFactory;
use Aura\Cli\Help;
use Aura\Cli\Status;
use Aura\Cli\Stdio;
use Aura\Cli\Stdio\Formatter;
use nochso\Omni\VersionInfo;

class Application {
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

	public function __construct($version) {
		$this->version = $version;
		$cliFactory = new CliFactory();
		$this->context = $cliFactory->newContext($GLOBALS);
		$this->stdio = new Stdio(
			new Handle('php://stdin', 'r'),
			new Handle('php://stdout', 'w+'),
			new Handle('php://stderr', 'w+'),
			new Formatter()
		);
		$this->opt = $this->context->getopt($this->getOptions());
	}

	public function run() {
		$this->showVersion();
		if ($this->opt->get('--help')) {
			$this->showHelp();
			return;
		}
		$errors = $this->opt->getErrors();
		$paths = array_filter(
			$this->opt->get(),
			function ($key) {
				return is_int($key) && $key > 0;
			},
			ARRAY_FILTER_USE_KEY
		);
		$job = new FormatJob($this->stdio);
		$job->addPaths($paths);
		$errors = array_merge($errors, $job->getErrors());
		if (count($errors)) {
			$this->showHelp();
			/** @var \Exception $error */
			foreach ($errors as $error) {
				$this->stdio->errln('<<red>>' . $error->getMessage() . '<<reset>>');
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
		if ($this->opt->get('--order')) {
			$job->enableOrder();
		}
		$job->run();
		exit(Status::SUCCESS);
	}

	private function showHelp() {
		$help = new Help(new OptionFactory());
		$help->setOptions($this->getOptions());
		$help->setSummary('Format PHP source code by a single convention.');
		$help->setUsage(['[options] <path>', '[options] <path1> <path2> ...']);
		$help->setDescr(<<<TAG
By default PHP files from the specified paths will be overwritten.
TAG
);
		$this->stdio->outln($help->getHelp('phormat'));
	}

	private function getOptions() {
		return [
			'd,diff' => 'Preview diff of formatted code. Implies --no-output.',
			's,summary' => "Show a status summary for each file.",
			'o,order' => 'Change order of class elements.',
			'p,print' => 'Print full output of formatted code. Implies --no-output.',
			'n,no-output' => 'Do not overwrite source files.',
			'h,help' => 'Show this help.',
			'#paths' => 'One or many paths to files or directories.',
		];
	}

	private function showVersion() {
		$out = sprintf(
			'<<green>>%s<<reset>> <<yellow>>%s<<reset>>',
			$this->version->getName(),
			$this->version->getVersion()
		);
		$this->stdio->outln($out);
	}
}
