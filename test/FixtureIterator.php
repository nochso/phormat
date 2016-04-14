<?php
namespace nochso\Phormat\Test;

/**
 * FixtureIterator provides test data from files.
 *
 * For files named `*.in.php` will try to use a matching `*.out.php` file for expected output.
 * If not output file is found, the input is re-used for expected output.
 */
class FixtureIterator implements \Iterator{
	/**
	 * @var string[]
	 */
	private $files;

	/**
	 * @param string $pattern glob() style pattern of input files.
	 */
	public function __construct($pattern) {
		$this->files = glob($pattern);
	}

	public function rewind() {
		return reset($this->files);
	}

	public function current() {
		$inputFile = current($this->files);
		return $this->readFixture($inputFile);
	}

	public function key() {
		return key($this->files);
	}

	public function next() {
		return next($this->files);
	}

	public function valid() {
		return key($this->files) !== null;
	}

	private function readFixture($inputFile) {
		$outputFile = str_replace('.in.php', '.out.php', $inputFile);
		$input = file_get_contents($inputFile);
		if (!is_file($outputFile)) {
			$output = $input;
		} else {
			$output = file_get_contents($outputFile);
		}
		return [$output, $input];
	}
}
