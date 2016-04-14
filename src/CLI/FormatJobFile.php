<?php
namespace nochso\Phormat\CLI;

use nochso\Diff\Diff;
use nochso\Omni\Multiline;

class FormatJobFile {
	const STATUS_SAME = 0;
	const STATUS_CHANGED = 1;
	const STATUS_ERROR = 2;
	const STATUS_SKIPPED = 3;
	const STATUS_MISSING = 4;
	const STATUS_DESCRIPTIONS = [
		self::STATUS_SAME => 'No formatting needed',
		self::STATUS_CHANGED => 'Formatted',
		self::STATUS_ERROR => 'Error while formatting',
		self::STATUS_SKIPPED => 'Skipped templates',
		self::STATUS_MISSING => 'Not found',
	];
	const STATUS_STYLES = [
		self::STATUS_SAME => 'green',
		self::STATUS_CHANGED => 'yellow',
		self::STATUS_ERROR => 'red',
		self::STATUS_SKIPPED => 'dim',
		self::STATUS_MISSING => 'red',
	];

	/**
	 * @var string
	 */
	private $path, $message;
	/**
	 * @var int
	 */
	private $fileSizeBefore, $fileSizeAfter;
	/**
	 * @var int
	 */
	private $lineCountBefore, $lineCountAfter;
	/**
	 * @var int nochso\Phormat\CLI\FileResult::FORMAT_* constant
	 */
	private $status;
	/**
	 * @var \nochso\Diff\Diff
	 */
	private $diff;
	/**
	 * @var string
	 */
	private $output;

	public function __construct($path) {
		$this->path = $path;
		if (!is_file($path)) {
			$this->status = self::STATUS_MISSING;
		}
	}

	public function setSources($sourceBefore, $sourceAfter, $keepOutput = false) {
		$this->fileSizeBefore = strlen($sourceBefore);
		$this->lineCountBefore = Multiline::create($sourceBefore)->count();
		$this->fileSizeAfter = strlen($sourceAfter);
		$this->lineCountAfter = Multiline::create($sourceAfter)->count();
		if ($keepOutput) {
			$this->output = $sourceAfter;
		}
		if ($sourceBefore === $sourceAfter) {
			$this->status = self::STATUS_SAME;
		} else {
			$this->status = self::STATUS_CHANGED;
		}
	}

	public function setError($message) {
		$this->message = $message;
		$this->status = self::STATUS_ERROR;
	}

	public function setSkipped() {
		$this->status = self::STATUS_SKIPPED;
	}

	public function getDiff() {
		return $this->diff;
	}

	public function setDiff($sourceBefore, $sourceAfter) {
		$this->diff = Diff::create($sourceBefore, $sourceAfter);
	}

	public function hasDiff() {
		return $this->diff !== null;
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	public function getStatus() {
		return $this->status;
	}

	public function hasOutput() {
		return $this->output !== null;
	}

	public function getOutput() {
		return $this->output;
	}

	public function getStatusDescription() {
		return self::STATUS_DESCRIPTIONS[$this->status];
	}

	public function getStatusStyle() {
		return self::STATUS_STYLES[$this->status];
	}
}
