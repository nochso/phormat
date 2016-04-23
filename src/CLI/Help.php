<?php
namespace nochso\Phormat\CLI;

use nochso\Omni\Multiline;

/**
 * @todo Extract this into its own package together with Stdio from nochso/writeme.
 */
class Help extends \Aura\Cli\Help {
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
		$text = '    '
			. $this->getHelpOptionParam($option->name, $option->param, $option->multi);
		if ($option->alias) {
			$text .= ', '
				. $this->getHelpOptionParam($option->alias, $option->param, $option->multi);
		}
		$text .= PHP_EOL;
		if (!$option->descr) {
			$option->descr = 'No description.';
		}
		$indent = 8;
		$remaining = 80 - $indent;
		$lines = Multiline::create(wordwrap($option->descr, $remaining, "\n"));
		$lines->prefix(str_repeat(' ', $indent));
		$text .= (string) $lines . PHP_EOL . PHP_EOL;
		return $text;
	}
}
