<?php

namespace nochso\Phormat;

use nochso\Omni\Strings;
use nochso\Phormat\Parser\Lexer;
use nochso\Phormat\Parser\NodePrinter;
use nochso\Phormat\Parser\TemplateVisitor;
use PhpParser\ParserFactory;

class Formatter
{
	/**
	 * @var \PhpParser\Lexer
	 */
	private $lexer;
	/**
	 * @var \PhpParser\Parser
	 */
	private $parser;

	public function __construct()
	{
		$this->printer = new NodePrinter();
		$this->lexer = new Lexer();
		$factory = new ParserFactory();
		$this->parser = $factory->create(ParserFactory::PREFER_PHP7, $this->lexer);
	}

	public function format($input)
	{
		$statements = $this->parser->parse($input);
		$this->detectTemplate();
		return $this->printer->prettyPrintFile($statements);
	}

	private function detectTemplate()
	{
		foreach ($this->lexer->getTokens() as $token) {
			if (!is_array($token)) {
				continue;
			}
			if ($token[0] === T_ENDIF || $token[0] === T_ENDFOREACH || $token[0] === T_ENDFOR || $token[0] === T_ENDWHILE) {
				if (Strings::startsWith($token[1], 'end')) {
					throw new TemplateSkippedException();
				}
			}
		}
	}
}