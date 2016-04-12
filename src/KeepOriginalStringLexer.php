<?php

namespace nochso\Phormat;

use PhpParser\Lexer\Emulative;
use PhpParser\Parser\Tokens;

class KeepOriginalStringLexer extends Emulative
{
	public function getNextToken(&$value = null, &$startAttributes = null, &$endAttributes = null) {
		$tokenId = parent::getNextToken($value, $startAttributes, $endAttributes);
		if ($tokenId == Tokens::T_STRING || $tokenId === Tokens::T_CONSTANT_ENCAPSED_STRING) {
			$endAttributes['originalValue'] = $value;
		}
		return $tokenId;
	}
}