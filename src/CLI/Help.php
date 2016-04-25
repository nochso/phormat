<?php
namespace nochso\Phormat\CLI;

use nochso\Omni\Multiline;

/**
 * @todo Extract this into its own package together with Stdio from nochso/writeme.
 */
class Help extends \Aura\Cli\Help {
	/**
	 * @var int|null
	 */
	private $maxOptionLength;
	private $terminalWidth = 80;

	public function setOptions(array $options) {
		parent::setOptions($options);
		$this->maxOptionLength = null;
	}

	public function getHelp($name) {
		$help = parent::getHelp($name);
		// Make subjects green
		$subjectpattern = '/(?<! )<<bold>>(.*)<<reset>>/';
		$subjectPainter = function ($matches) {
			return '<<green>>' . ucfirst(strtolower($matches[1])) . '<<reset>>';
		};
		$help = preg_replace_callback($subjectpattern, $subjectPainter, $help);
		// Make options yellow
		$help = preg_replace("/ (-[a-z],?(?! )|--[a-z][a-z-]+)/", ' <<yellow>>\1<<reset>>', $help);
		$help = preg_replace("/(?<!<)<([^<> ]+)>(?!>)/", '<<yellow>><\1><<reset>>', $help);
		$help = preg_replace("/\\[([^\\[\\] ]+)\\]/", '<<yellow>>[\1]<<reset>>', $help);
		return $help;
	}

	/**
	 * @param int $terminalWidth
	 */
	public function setTerminalWidth($terminalWidth) {
		$this->terminalWidth = $terminalWidth;
	}

	/**
	 * Gets the formatted output for one option.
	 *
	 * @param \stdClass $option An option structure.
	 *
	 * @return string
	 */
	protected function getHelpOption($option) {
		if (!$option->name) {
			// it's an argument
			return '';
		}
		$text = '  ' . $this->getOptionString($option);
		$text .= '  ';
		$leftWidth = $this->getMaxOptionLength() + 4;
		$text = str_pad($text, $leftWidth);
		if (!$option->descr) {
			$option->descr = 'No description.';
		}
		$remaining = $this->terminalWidth - $leftWidth;
		$descr = wordwrap($option->descr, $remaining);
		$lines = Multiline::create($descr)->prefix(str_repeat(' ', $leftWidth));
		$text .= (string) ltrim($lines) . PHP_EOL;
		return $text;
	}

	/**
	 * @param $option
	 * @return string
	 */
	protected function getOptionString($option) {
		$text = $this->getHelpOptionParam($option->name, $option->param, $option->multi);
		if ($option->alias) {
			$text .= ', ' . $this->getHelpOptionParam($option->alias, $option->param, $option->multi);
			return $text;
		}
		return $text;
	}

	private function getMaxOptionLength() {
		if ($this->maxOptionLength !== null) {
			return $this->maxOptionLength;
		}
		$this->maxOptionLength = 0;
		foreach ($this->options as $string => $descr) {
			$option = $this->option_factory->newInstance($string, $descr);
			$this->maxOptionLength = max($this->maxOptionLength, strlen($this->getOptionString($option)));
		}
		return $this->maxOptionLength;
	}
}
