<?php

namespace nochso\Phormat;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;

class NodePrinter extends \PhpParser\PrettyPrinter\Standard
{
	const SEPARATE_TYPES = [
		Node\Const_::class,
		Stmt\ClassConst::class,
		Stmt\Property::class,
		Stmt\ClassMethod::class,
		Stmt\Function_::class,
		Stmt\Use_::class,
	];
	const SEPARATE_IDENTICAL_TYPES = [
		Stmt\ClassMethod::class,
		Stmt\Function_::class,
	];

	public function __construct(array $options = [])
	{
		$options['shortArraySyntax'] = true;
		parent::__construct($options);
	}

	/**
	 * Pretty prints a file of statements (includes the opening <?php tag if it is required).
	 *
	 * @param \PhpParser\Node[] $stmts Array of statements
	 *
	 * @return string Pretty printed statements
	 */
	public function prettyPrintFile(array $stmts) {
		if (!$stmts) {
			return "<?php\n\n";
		}
		$p = "<?php\n" . $this->prettyPrint($stmts);
		if ($stmts[0] instanceof Stmt\InlineHTML) {
			$p = preg_replace('/^<\?php\s+\?>\n?/', '', $p);
		}
		if ($stmts[count($stmts) - 1] instanceof Stmt\InlineHTML) {
			$p = preg_replace('/<\?php$/', '', rtrim($p));
		}

		return $p . "\n";
	}


	public function pStmt_Function(Stmt\Function_ $node) {
		return 'function ' . ($node->byRef ? '&' : '') . $node->name
		. $this->pCommaSeparatedLines($node->params, '(', ')')
		. (null !== $node->returnType ? ' : ' . $this->pType($node->returnType) : '')
		. ' {' . $this->pStmts($node->stmts) . "\n" . '}';
	}

	public function pStmt_Namespace(Stmt\Namespace_ $node) {
		if ($this->canUseSemicolonNamespaces) {
			return 'namespace ' . $this->p($node->name) . ';' . "\n" . $this->pStmts($node->stmts, false);
		} else {
			return 'namespace' . (null !== $node->name ? ' ' . $this->p($node->name) : '')
			. ' {' . $this->pStmts($node->stmts) . "\n" . '}';
		}
	}

	public function pStmt_ClassMethod(Stmt\ClassMethod $node) {
		return $this->pModifiers($node->type)
		. 'function ' . ($node->byRef ? '&' : '') . $node->name
		. $this->pCommaSeparatedLines($node->params, '(', ')')
		. (null !== $node->returnType ? ' : ' . $this->pType($node->returnType) : '')
		. (null !== $node->stmts
			? ' {' . $this->pStmts($node->stmts) . "\n" . '}'
			: ';');
	}

	public function pExpr_FuncCall(Expr\FuncCall $node) {
		return $this->pCallLhs($node->name)
			. $this->pCommaSeparatedLines($node->args, '(', ')');
	}

	public function pExpr_MethodCall(Expr\MethodCall $node) {
		return $this->pDereferenceLhs($node->var) . '->' . $this->pObjectProperty($node->name)
			. $this->pCommaSeparatedLines($node->args, '(', ')');
	}

	public function pExpr_StaticCall(Expr\StaticCall $node) {
		return $this->pDereferenceLhs($node->class) . '::'
		. ($node->name instanceof Expr
			? ($node->name instanceof Expr\Variable
				? $this->p($node->name)
				: '{' . $this->p($node->name) . '}')
			: $node->name)
		. $this->pCommaSeparatedLines($node->args, '(', ')');
	}

	public function pExpr_Closure(Expr\Closure $node) {
		return ($node->static ? 'static ' : '')
		. 'function ' . ($node->byRef ? '&' : '')
		. $this->pCommaSeparatedLines($node->params, '(', ')')
		. (!empty($node->uses) ? ' use ' . $this->pCommaSeparatedLines($node->uses, '(', ')') : '')
		. (null !== $node->returnType ? ' : ' . $this->pType($node->returnType) : '')
		. ' {' . $this->pStmts($node->stmts) . "\n" . '}';
	}

	public function pExpr_Array(Node\Expr\Array_ $node) {
		return $this->pCommaSeparatedLines($node->items, '[', ']', true);
	}

	public function pExpr_New(Expr\New_ $node) {
		if ($node->class instanceof Stmt\Class_) {
			$args = $node->args ? '(' . $this->pCommaSeparatedLines($node->args) . ')' : '';
			return 'new ' . $this->pClassCommon($node->class, $args);
		}
		$result = 'new ' . $this->p($node->class) . $this->pCommaSeparatedLines($node->args, '(', ')');
		return $result;
	}

	protected function pCommaSeparatedLines(array $nodes, $prefix = '', $suffix = '', $trailingComma = false)
	{
		$arr = parent::pCommaSeparated($nodes);
		if (strlen($arr) > 80) {
			$arr = "\n".$this->pImplode($nodes, ",\n").($trailingComma ? ',' : '')."\n";
		}
		return $prefix . preg_replace('~\n(?!$|\n|' . $this->noIndentToken . ')~', "\n\t", $arr) . $suffix;
	}

	/**
	 * Pretty prints an array of nodes (statements) and indents them optionally.
	 *
	 * @param \PhpParser\Node[] $nodes  Array of nodes
	 * @param bool   $indent Whether to indent the printed nodes
	 *
	 * @return string Pretty printed statements
	 */
	protected function pStmts(array $nodes, $indent = true) {
		$result = '';
		$prevContext = null;
		foreach ($nodes as $node) {
			$newContext = get_class($node);
			if ($prevContext !== $newContext) {
				if ($prevContext !== null && (in_array($prevContext, self::SEPARATE_TYPES) || in_array($newContext, self::SEPARATE_TYPES))) {
					$result.="\n";
				}
				$prevContext = $newContext;
			} elseif (in_array($newContext, self::SEPARATE_IDENTICAL_TYPES)) {
				$result .= "\n";
			}
			$result .= "\n"
				. $this->pComments($node->getAttribute('comments', array()))
				. $this->p($node)
				. ($node instanceof Node\Expr ? ';' : '');
		}

		if ($indent) {
			return preg_replace('~\n(?!$|\n|' . $this->noIndentToken . ')~', "\n\t", $result);
		} else {
			return $result;
		}
	}

	public function pStmt_Interface(Stmt\Interface_ $node) {
		return 'interface ' . $node->name
		. (!empty($node->extends) ? ' extends ' . $this->pCommaSeparatedLines($node->extends) : ' ')
		. '{' . $this->pStmts($node->stmts) . "\n" . '}';
	}

	public function pStmt_TraitUse(Stmt\TraitUse $node) {
		return 'use ' . rtrim($this->pCommaSeparatedLines($node->traits))
		. (empty($node->adaptations)
			? ';'
			: ' {' . preg_replace('~\n(?!$|\n|' . $this->noIndentToken . ')~', "\n\t", $this->pStmts($node->adaptations) . "\n" . '}'));
	}



	protected function pClassCommon(Node\Stmt\Class_ $node, $afterClassToken) {
		return $this->pModifiers($node->type)
		. 'class' . $afterClassToken
		. (null !== $node->extends ? ' extends ' . $this->p($node->extends) : '')
		. (!empty($node->implements) ? ' implements ' . $this->pCommaSeparatedLines($node->implements) : ' ')
		. '{' . $this->pStmts($node->stmts) . "\n" . '}';
	}

	public function pScalar_String(Node\Scalar\String_ $node)
	{
		return $node->getAttribute('originalValue');
	}

	protected function pEncapsList(array $encapsList, $quote) {
		$return = '';
		foreach ($encapsList as $element) {
			if ($element instanceof Node\Scalar\EncapsedStringPart) {
				if ($element->getAttribute('startLine') === $element->getAttribute('endLine')) {
					$return .= $this->escapeString($element->value, $quote);
				} else {
					$return .= $element->value;
				}
			} else {
				$return .= '{' . $this->p($element) . '}';
			}
		}

		return $return;
	}
}