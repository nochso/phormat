<?php
namespace nochso\Phormat\CLI;

class Stdio extends \Aura\Cli\Stdio {
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
}
