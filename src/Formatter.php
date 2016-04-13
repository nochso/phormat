<?php

namespace nochso\Phormat;

use nochso\Phormat\Parser\Lexer;
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
		return $this->printer->prettyPrintFile($statements);
	}
}