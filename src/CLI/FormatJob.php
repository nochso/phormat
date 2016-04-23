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

class FormatJob {
	private $output = true;
	private $diff = false;
	private $print = false;
	private $summary = false;
	private $order = false;
	/**
	 * @var \nochso\Phormat\CLI\FormatJobFile[]
	 */
	private $files = [];
	private $errors = [];
	private $progressStatusMap = [];
	/**
	 * @var \Aura\Cli\Stdio
	 */
	private $stdio;

	public function __construct(Stdio $stdio) {
		$this->stdio = $stdio;
	}

	public function addPath($path) {
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

	public function getErrors() {
		if (!count($this->files)) {
			$this->errors[] = new \RuntimeException('No files specified or found.');
		}
		return $this->errors;
	}

	public function addPaths($paths) {
		foreach ($paths as $path) {
			$this->addPath($path);
		}
	}

	public function enableDiff() {
		$this->diff = true;
	}

	public function disableDiff() {
		$this->diff = false;
	}

	public function enableSummary() {
		$this->summary = true;
	}

	public function disableSummary() {
		$this->summary = false;
	}

	public function run() {
		$this->progressStatusMap = array_fill_keys(array_keys(FormatJobFile::STATUS_STYLES), 0);
		$startTime = microtime(true);
		$this->stdio->outln();
		$formatter = new Formatter();
		$formatter->setOrderClassElements($this->order);
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
			$this->showProgress($key, $file);
		}
		$duration = microtime(true) - $startTime;
		$this->stdio->out("\r" . str_repeat(' ', 80) . "\r");
		$this->showDiffs();
		$this->showOutput();
		$this->showFileSummary();
		$this->showSummary($duration);
	}

	public function enablePrint() {
		$this->print = true;
	}

	public function disablePrint() {
		$this->print = false;
	}

	public function enableOutput() {
		$this->output = true;
	}

	public function disableOutput() {
		$this->output = false;
	}

	public function enableOrder() {
		$this->order = true;
	}

	public function disableOrder() {
		$this->order = false;
	}

	private function showProgress($key, FormatJobFile $file) {
		$this->progressStatusMap[$file->getStatus()]++;
		if (($key + 1) % 5) {
			return;
		}
		$count = count($this->files);
		$formatCount = str_pad(number_format($key + 1), strlen(number_format($count)), ' ', STR_PAD_LEFT)
			. '/' . number_format($count);
		$bar = '';
		$sumChars = 0;
		foreach ($this->progressStatusMap as $status => $statusCount) {
			$chars = floor($statusCount / $count * 100 / 2);
			$sumChars += $chars;
			$bar .= sprintf(
				'<<%s>>%s<<reset>>',
				FormatJobFile::STATUS_STYLES[$status],
				str_repeat('|', $chars),
				str_repeat(' ', 50 - $chars)
			);
		}
		$bar .= str_repeat(' ', 50 - $sumChars);
		$bar = '[' . $bar . ']';
		$percentage = ($key + 1) / $count * 100;
		$paddedPercentage = str_pad(round($percentage), 3, ' ', STR_PAD_LEFT);
		$this->stdio->out("\r" . $paddedPercentage . "% {$bar} {$formatCount}");
	}

	private function showFileSummary() {
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
	private function showDiffs() {
		if ($this->stdio->getStdout()->isPosix()) {
			$diffTemplate = new Template\POSIX();
		} else {
			$diffTemplate = new Template\Text();
		}
		foreach ($this->files as $file) {
			if ($file->hasDiff() && count($file->getDiff()->getDiffLines())) {
				$this->stdio->outln('<<ul>>' . $file->getPath() . '<<reset>>:');
				$this->stdio->outln($diffTemplate->format($file->getDiff()));
			}
		}
	}

	private function showOutput() {
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
	public function getFilesGroupedByStatus() {
		$map = [];
		foreach ($this->files as $file) {
			$map[$file->getStatus()][] = $file;
		}
		ksort($map);
		return $map;
	}

	private function showSummary($microseconds) {
		$statusFileMap = $this->getFilesGroupedByStatus();
		$changedCount = 0;
		if (isset($statusFileMap[FormatJobFile::STATUS_CHANGED])) {
			$changedCount = count($statusFileMap[FormatJobFile::STATUS_CHANGED]);
		}
		$duration = Duration::create()->format((int) $microseconds) . ' ';
		if ($microseconds < 10) {
			if ($microseconds < 1) {
				$duration = '';
			}
			$duration .= round(($microseconds - (int) $microseconds) * 1000) . 'ms';
		}
		$this->stdio->outln(
			sprintf(
				'<<green>>Formatted %d file%s in %s.<<reset>>',
				$changedCount,
				Quantity::format('(s)', $changedCount),
				$duration
			)
		);
	}
}
