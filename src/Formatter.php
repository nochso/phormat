<?php
namespace nochso\Phormat;

use nochso\Omni\Strings;
use nochso\Phormat\Parser\Lexer;
use nochso\Phormat\Parser\NodePrinter;
use PhpParser\ParserFactory;

/**
 * Formatter takes PHP code as a string and formats it the phormat way.
 *
 * @see \nochso\Phormat\Parser\NodePrinter
 */
class Formatter {
	/**
	 * @var \PhpParser\Lexer
	 */
	private $lexer;
	/**
	 * @var \PhpParser\Parser
	 */
	private $parser;
	/**
	 * @var \nochso\Phormat\Parser\NodePrinter
	 */
	private $printer;

	public function __construct() {
		$this->printer = new NodePrinter();
		$this->lexer = new Lexer();
		$factory = new ParserFactory();
		$this->parser = $factory->create(ParserFactory::PREFER_PHP7, $this->lexer);
	}

	/**
	 * @param bool $enable
	 */
	public function setOrderClassElements($enable) {
		$this->printer->setOrderElements($enable);
	}

	/**
	 * Format PHP code the phormat way.
	 *
	 * @param string $input PHP source code.
	 * @return string The formatted code.
	 * @throws \nochso\Phormat\TemplateSkippedException
	 */
	public function format($input) {
		$statements = $this->parser->parse($input);
		$this->detectTemplate();
		return $this->printer->prettyPrintFile($statements);
	}

	private function detectTemplate() {
		foreach ($this->lexer->getTokens() as $token) {
			if (!is_array($token)) {
				continue;
			}
			$templateEndTokens = [T_ENDIF => 1, T_ENDFOREACH => 1, T_ENDFOR => 1, T_ENDWHILE => 1];
			if (!isset($templateEndTokens[$token[0]])) {
				continue;
			}
			if (Strings::startsWith($token[1], 'end')) {
				throw new TemplateSkippedException();
			}
		}
	}
}
