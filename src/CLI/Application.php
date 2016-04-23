<?php
namespace nochso\Phormat\CLI;

use Aura\Cli\CliFactory;
use Aura\Cli\Context\OptionFactory;
use Aura\Cli\Status;
use Aura\Cli\Stdio;
use Aura\Cli\Stdio\Formatter;
use Humbug\SelfUpdate\Strategy\GithubStrategy;
use Humbug\SelfUpdate\Updater;
use nochso\Omni\VersionInfo;
use nochso\Phormat\Parser\NodeSorter;

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
		if ($this->opt->get('--version')) {
			exit(Status::SUCCESS);
		}
		if ($this->opt->get('--help')) {
			$this->showHelp();
			return;
		}
		if ($this->opt->get('--self-update')) {
			$this->selfUpdate();
			exit(Status::SUCCESS);
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
		$help->setDescr(
			<<<TAG
By default PHP files from the specified paths will be overwritten.
TAG

		);
		$this->stdio->outln($help->getHelp('phormat'));
	}

	private function getOptions() {
		$accessorPrefixes = implode('* > ', (new NodeSorter())->getAccessorPrefixes());
		return [
			'd,diff' => 'Preview diff of formatted code. Implies --no-output.',
			's,summary' => "Show a status summary for each file.",
			'o,order' => 'Change order of class elements:
constants > properties > methods
static > abstract > *
public > protected > private
__* > ' . $accessorPrefixes . '* > *',
			'p,print' => 'Print full output of formatted code. Implies --no-output.',
			'n,no-output' => 'Do not overwrite source files.',
			'h,help' => 'Show this help.',
			'version' => 'Show version information.',
			'self-update' => 'Update phormat to the lateste version.',
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
		$this->stdio->outln();
	}

	private function selfUpdate() {
		$phar = \Phar::running(false);
		if ($phar === '') {
			$this->stdio->errln(
				'<<red>>Self-updating only works when running the PHAR version of phormat.<<reset>>'
			);
			exit(Status::UNAVAILABLE);
		}
		$updater = new Updater($phar);
		$strategy = new GithubStrategy();
		$strategy->setPackageName('nochso/phormat');
		$strategy->setPharName('phormat.phar');
		$strategy->setCurrentLocalVersion($this->version->getVersion());
		$updater->setStrategyObject($strategy);
		try {
			if ($updater->update()) {
				$this->stdio->outln(
					sprintf(
						'<<green>>Successfully updated phormat from %s to %s.<<reset>>',
						$updater->getOldVersion(),
						$updater->getNewVersion()
					)
				);
				exit(Status::SUCCESS);
			}
			$this->stdio->outln('<<yellow>>There is no update available.<<reset>>');
			exit(Status::SUCCESS);
		} catch (\Exception $e) {
			$this->stdio->outln(sprintf("<<red>>Self-update failed:\n%s<<reset>>", $e->getMessage()));
		}
	}
}
