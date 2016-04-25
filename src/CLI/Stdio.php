<?php
namespace nochso\Phormat\CLI;

use nochso\Omni\Exec;
use nochso\Omni\OS;

class Stdio extends \Aura\Cli\Stdio {
	private $terminalWidth;

	public function error($error) {
		$this->outln($this->paint(' Error ', 'redbg white') . ' ' . $this->paint($error, 'red'));
	}

	public function warn($warning) {
		$this->outln(
			sprintf(
				'%s %s',
				$this->paint(' Warning ', 'yellowbg black'),
				$this->paint($warning, 'yellow')
			)
		);
	}

	public function neutral($message) {
		$this->outln($this->paint($message, 'yellow'));
	}

	public function success($message) {
		$this->outln($this->paint($message, 'green'));
	}

	public function paint($input, $format) {
		$input = str_replace('<<reset>>', '<<reset>><<' . $format . '>>', $input);
		return sprintf('<<%s>>%s<<reset>>', $format, $input);
	}

	public function getTerminalWidth() {
		if ($this->terminalWidth !== null) {
			return $this->terminalWidth;
		}
		$this->terminalWidth = 80;
		if (OS::isWindows()) {
			if (preg_match('/^\s*\d+x\d+ (\d+)x\d+\s*$/', getenv('ANSICON'), $matches)) {
				$this->terminalWidth = $matches[1];
			} else {
				$mode = Exec::create('mode')->run()->getOutput()[0];
				if (preg_match('/CON.*:(\n[^|]+?){3}(?<cols>\d+)/', $mode, $matches)) {
					$this->terminalWidth = $matches['cols'];
				}
			}
		} else {
			$stty = Exec::create('stty', '-a')->run()->getOutput();
			foreach ($stty as $line) {
				if (preg_match('/columns (\d+)/', $line, $matches)) {
					$this->terminalWidth = $matches[1];
					break;
				}
			}
		}
		return $this->terminalWidth;
	}
}
