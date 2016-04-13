<?php

namespace nochso\Phormat;

use PhpParser\ParserFactory;

class Formatter
{
	public function format($input)
	{
		$parser = $this->getParser();
		$statements = $parser->parse($input);
		$printer = new NodePrinter();
		$pretty = $printer->prettyPrintFile($statements);
		return $pretty;
	}

	private function getParser()
	{
		$factory = new ParserFactory();
		$lexer = new KeepOriginalStringLexer();
		return $factory->create(ParserFactory::PREFER_PHP7, $lexer);
	}
}