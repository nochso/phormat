<?php

namespace nochso\Phormat\CLI;


use Aura\Cli\Stdio;
use Nette\Utils\Finder;
use nochso\Diff;
use nochso\Diff\Format\Template;
use nochso\Omni\Format\Quantity;
use nochso\Phormat\Formatter;
use nochso\Phormat\TemplateSkippedException;

class FormatJob
{
	const FILE_SAME = 0;
	const FILE_CHANGED = 1;
	const FILE_ERROR = 2;
	const FILE_TEMPLATE_SKIPPED = 3;

	const FILE_DESCRIPTIONS = [
		self::FILE_SAME => 'Already conforms to phormat',
		self::FILE_CHANGED => 'Different to phormat',
		self::FILE_ERROR => 'Parse error',
		self::FILE_TEMPLATE_SKIPPED => 'Skipped native templates',
	];
	const FILE_STYLES = [
		self::FILE_SAME => 'green',
		self::FILE_CHANGED => 'yellow',
		self::FILE_ERROR => 'red',
		self::FILE_TEMPLATE_SKIPPED => 'dim',
	];

	private $diff = false;
	private $print = false;
	private $summary = false;
	private $files = [];
	private $errors = [];
	/**
	 * @var \Aura\Cli\Stdio
	 */
	private $stdio;
	/**
	 * @var \nochso\Diff\Diff[]
	 */
	private $diffs = [];
	private $outputs = [];
	private $statuses = [];

	public function __construct(Stdio $stdio)
	{
		$this->stdio = $stdio;
	}


	public function addPath($path)
	{
		if (is_file($path)) {
			$this->files[] = $path;
			return;
		}
		if (is_dir($path)) {
			/** @var \SplFileInfo $file */
			foreach (Finder::findFiles('*.php')->from($path) as $file) {
				$this->files[] = $file->getPathname();
			}
			return;
		}
		$this->errors[] = new \InvalidArgumentException(sprintf("File or directory '%s' does not exist.", $path)); 
	}

	public function getErrors()
	{
		if (!count($this->files)) {
			$this->errors[] = new \RuntimeException('No files specified or found.');
		}
		return $this->errors;
	}
	
	public function addPaths($paths)
	{
		foreach ($paths as $path) {
			$this->addPath($path);
		}
	}

	public function enableDiff()
	{
		$this->diff=true;
	}

	public function disableDiff()
	{
		$this->diff=false;
	}

	public function enableSummary()
	{
		$this->summary =true;
	}

	public function disableSummary()
	{
		$this->summary =false;
	}

	public function run()
	{
		$this->stdio->outln(sprintf('Found %d file%s to format.', count($this->files), Quantity::format('(s)', count($this->files))));
		$this->stdio->outln();
		$formatter = new Formatter();
		foreach ($this->files as $key => $file) {
			try {
				$before = file_get_contents($file);
				$after = $formatter->format($before);
				$status = self::FILE_CHANGED;
				if ($before === $after) {
					$status = self::FILE_SAME;
				}
				if ($this->diff) {
					$this->diffs[$file] = Diff\Diff::create($before, $after);
				}
				if (!$this->diff && !$this->print) {
					file_put_contents($file, $after);
				}
				if ($this->print) {
					$this->outputs[$file] = $after;
				}
			} catch (TemplateSkippedException $e) {
				$status = self::FILE_TEMPLATE_SKIPPED;
			} catch (\Exception $e) {
				$status = self::FILE_ERROR;
			}
			if ($this->summary) {
				$this->statuses[$status][] = $file;
			}
			$this->showProgress($key);
		}
		$this->stdio->out("    \r");

		$this->showDiffs();
		$this->showOutput();
		$this->showFileSummary();
	}

	public function enablePrint()
	{
		$this->print = true;
	}

	public function disablePrint()
	{
		$this->print=false;
	}

	private function showProgress($key)
	{
		$percentage = round(($key + 1) / count($this->files) * 100);
		$percentage = str_pad($percentage, 3, ' ', STR_PAD_LEFT) ;
		$this->stdio->out($percentage . "%\r");
	}

	private function showFileSummary()
	{
		if (!$this->summary) {
			return;
		}
		foreach ($this->statuses as $status => $files) {
			$message = sprintf(
				'<<%s>>%s<<reset>>',
				self::FILE_STYLES[$status],
				self::FILE_DESCRIPTIONS[$status]
			);
			$this->stdio->outln($message);
			foreach ($files as $file) {
				$this->stdio->outln($file);
			}
		}
	}

	/**
	 * @return int|string
	 */
	private function showDiffs()
	{
		if (!$this->diff) {
			return;
		}
		if ($this->stdio->getStdout()->isPosix()) {
			$diffTemplate = new Template\POSIX();
		} else {
			$diffTemplate = new Template\Text();
		}
		foreach ($this->diffs as $file => $diff) {
			$this->stdio->outln('<<ul>>'.$file . '<<reset>>:');
			$this->stdio->outln($diffTemplate->format($diff));
		}
	}

	private function showOutput()
	{
		if (!$this->print) {
			return;
		}
		foreach ($this->outputs as $file => $output) {
			$this->stdio->outln('<<ul>>'.$file . '<<reset>>:');
			$this->stdio->outln($output);
		}
	}
}