<?php
$a = 'function ' . ($node->byRef ? '&' : '') . $node->name
	. $this->pCommaSeparatedLines($node->params, '(', ')')
	. (null !== $node->returnType ? ' : ' . $this->pType($node->returnType) : '') . ' {'
	. $this->pStmts($node->stmts) . "\n" . '}';
$b = 'outside-left' . 'outside-left2'
	. ('1111111111' . '2222222222' . '3333333333' . '4444444444' . '5555555555' . '6666666666')
	. 'outside-right' . 'outside-right2' . 'xxxxxxxxxxxxxxxxxxxx'
	. 'dsrfiosjf sdoifj sdofijsd fiojsdf';
$c = $use . $traits
	. (empty($node->adaptations) ? ';' : ' {'
		. preg_replace(
			'~\n(?!$|\n|' . $this->noIndentToken . ')~',
			"\n" . $this->indentation,
			$this->pStmts($node->adaptations) . "\n" . '}'
		)
	);
$text = '    ' . $this->getHelpOptionParam($option->name, $option->param, $option->multi);
doSomething(
	doSomethingElse(
		'01234567890123456789012345678901234567890123456789012345678901234567890123456789',
		doSomethingElseEntirely(
			'01234567890123456789012345678901234567890123456789012345678901234567890123456789'
		)
	)
);
return $use . $traits
	. (empty($node->adaptations) ? ';' : ' {'
		. preg_replace(
			'~\n(?!$|\n|' . $this->noIndentToken . ')~',
			"\n" . $this->indentation,
			$this->pStmts($node->adaptations) . "\n" . '}'
		)
	);
if ($variable == '394i 309483094'
	|| $variable === '394i30948349083498 n' && $variable !== 'okfkddfoksd fopdsk' && true && 1 && 2) {
}
if ($variable == '394i 309483094' && $variable === '394i30948349083498 n'
	|| $variable !== 'okfkddfoksd fopdsk' || true || 1 || 2) {
}
