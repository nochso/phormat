<?php

namespace nochso\Phormat\CLI;


use Aura\Cli\Stdio;
use Nette\Utils\Finder;
use nochso\Diff;
use nochso\Diff\Format\Template;
use nochso\Omni\Format\Duration;
use nochso\Omni\Format\Quantity;
use nochso\Phormat\Formatter;
use nochso\Phormat\TemplateSkippedException;

class FormatJob
{
	private $output = true;
	private $diff = false;
	private $print = false;
	private $summary = false;
	/**
	 * @var \nochso\Phormat\CLI\FormatJobFile[]
	 */
	private $files = [];
	private $errors = [];
	/**
	 * @var \Aura\Cli\Stdio
	 */
	private $stdio;

	public function __construct(Stdio $stdio)
	{
		$this->stdio = $stdio;
	}


	public function addPath($path)
	{
		if (!is_dir($path)) {
			$this->files[] = new FormatJobFile($path);
			return;
		}
		if (is_dir($path)) {
			/** @var \SplFileInfo $file */
			foreach (Finder::findFiles('*.php')->from($path) as $file) {
				$this->addPath($file);
			}
		}
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
		$startTime = microtime(true);
		$this->stdio->outln(sprintf('Found %d file%s to format.', count($this->files), Quantity::format('(s)', count($this->files))));
		$this->stdio->outln();
		$formatter = new Formatter();
		foreach ($this->files as $key => $file) {
			if ($file->getStatus() === FormatJobFile::STATUS_MISSING) {
				continue;
			}
			try {
				$before = file_get_contents($file->getPath());
				$after = $formatter->format($before);
				$file->setSources($before, $after, $this->print);
				if ($this->diff) {
					$file->setDiff($before, $after);
				}
				if ($this->output && !$this->diff && !$this->print) {
					file_put_contents($file->getPath(), $after);
				}
			} catch (TemplateSkippedException $e) {
				$file->setSkipped();
			} catch (\Exception $e) {
				$file->setError($e->getMessage());
			}
			$this->showProgress($key);
		}
		$duration = microtime(true) - $startTime;
		$this->stdio->out("    \r");
		$this->showDiffs();
		$this->showOutput();
		$this->showFileSummary();
		$this->showSummary($duration);
	}

	public function enablePrint()
	{
		$this->print = true;
	}

	public function disablePrint()
	{
		$this->print=false;
	}

	public function enableOutput()
	{
		$this->output = true;
	}
	public function disableOutput()
	{
		$this->output = false;
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
		foreach ($this->getFilesGroupedByStatus() as $files) {
			$file = $files[0];
			$status = sprintf('<<%s>>%s<<reset>>', $file->getStatusStyle(), $file->getStatusDescription());
			$this->stdio->outln($status);
			foreach ($files as $file) {
				$this->stdio->outln($file->getPath());
			}
			$this->stdio->outln();
		}
	}

	/**
	 * @return int|string
	 */
	private function showDiffs()
	{
		if ($this->stdio->getStdout()->isPosix()) {
			$diffTemplate = new Template\POSIX();
		} else {
			$diffTemplate = new Template\Text();
		}
		foreach ($this->files as $file) {
			if ($file->hasDiff()) {
				$this->stdio->outln('<<ul>>'.$file->getPath() . '<<reset>>:');
				$this->stdio->outln($diffTemplate->format($file->getDiff()));
			}
		}
	}

	private function showOutput()
	{
		foreach ($this->files as $file) {
			if ($file->hasOutput()) {
				$this->stdio->outln('<<ul>>' . $file->getPath() . '<<reset>>:');
				$this->stdio->outln($file->getOutput());
			}
		}
	}

	/**
	 * @return FormatJobFile[][]
	 */
	public function getFilesGroupedByStatus()
	{
		$map = [];
		foreach ($this->files as $file) {
			$map[$file->getStatus()][] = $file;
		}
		ksort($map);
		return $map;
	}

	private function showSummary($microseconds)
	{
		$statusFileMap = $this->getFilesGroupedByStatus();
		$changedCount = 0;
		if (isset($statusFileMap[FormatJobFile::STATUS_CHANGED])) {
			$changedCount = count($statusFileMap[FormatJobFile::STATUS_CHANGED]);
		}
		$duration = Duration::create()->format((int)$microseconds) . ' ';
		if ($microseconds < 10) {
			if ($microseconds < 1) {
				$duration = '';
			}
			$duration .= round(($microseconds - (int)$microseconds) * 1000) .'ms';
		}
		$this->stdio->outln(sprintf('<<greenbg black>>Formatted %d file%s in %s.<<reset>>', $changedCount, Quantity::format('(s)', $changedCount), $duration));
	}
}