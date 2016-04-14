<?php

namespace nochso\Phormat\Parser;

use nochso\Omni\Multiline;
use nochso\Omni\Strings;
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

	public function pStmt_Property(Stmt\Property $node) {
		$modifier = 0 === $node->type ? 'var ' : $this->pModifiers($node->type);
		$properties = $this->pCommaSeparatedLines($node->props);
		if (strpos($properties, "\n") !== false) {
			return rtrim($modifier) . rtrim($properties).';';
		}
		return $modifier . $properties . ';';
	}

	protected function pComments(array $comments)
	{
		$lines = Multiline::create(parent::pComments($comments));
		// Trim trailing non-Markdown whitespace
		$lines->apply(function ($line) {
			if (preg_match('/(?<! |\\*)  $/', $line)) {
				return $line;
			}
			return rtrim($line);
		});
		$lastStartPos = PHP_INT_MIN;
		$consecutive = 0;
		$isFenced = false;
		foreach ($lines->toArray() as $pos => $line) {
			// Ignore fenced Markdown
			if (preg_match('/^\s*\\*\s?```\s*$/', $line)) {
				$isFenced = !$isFenced;
			}
			if ($isFenced) {
				$consecutive = $this->removeConsecutiveEmptyDocs($lines, $consecutive, $lastStartPos, $pos);
				continue;
			}
			// Remember last start /* or /**
			if (preg_match("/^(\\s*\\/\\*+\\s*)$/", $line)) {
				$lastStartPos = $pos;
				continue;
			}
			// Keep matching whitespacey lines
			if (preg_match("/^\\s*\\*\\s*$/", $line)) {
				$consecutive++;
				continue;
			}
			if (preg_match('/^\\s*\\*\\/\\s*$/', $line)) {
				$lastStartPos = $pos-$consecutive-1;
			}
			// Remove lines if possible
			$consecutive = $this->removeConsecutiveEmptyDocs($lines, $consecutive, $lastStartPos, $pos);
		}
		return (string)$lines;
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

	protected function handleMagicTokens($str) {
        // Drop no-indent tokens
        $str = str_replace($this->noIndentToken, '', $str);

        // Replace doc-string-end tokens with nothing or a newline
        $str = str_replace($this->docStringEndToken . ";\n", ";\n", $str);
        $str = str_replace($this->docStringEndToken . ";", ";", $str);
        $str = str_replace($this->docStringEndToken, "\n", $str);

        return $str;
    }

	public function pStmt_Interface(Stmt\Interface_ $node) {
		return 'interface ' . $node->name
		. (!empty($node->extends) ? ' extends ' . $this->pCommaSeparatedLines($node->extends) : ' ')
		. '{' . $this->pStmts($node->stmts) . "\n" . '}';
	}

	public function pStmt_TraitUse(Stmt\TraitUse $node) {
		$traits = rtrim($this->pCommaSeparatedLines($node->traits));
		$use = 'use';
		if (strpos($traits, "\n") === false) {
			$use .= ' ';
		}
		return $use . $traits
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
		$kind = $node->getAttribute('kind', Node\Scalar\String_::KIND_SINGLE_QUOTED);
		if ($kind === Node\Scalar\String_::KIND_HEREDOC || $kind === Node\Scalar\String_::KIND_NOWDOC) {
			return parent::pScalar_String($node);
		}
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

	protected function removeConsecutiveEmptyDocs(Multiline $lines, $consecutive, $lastStartPos, $pos)
	{
		if ($consecutive > 0 && $lastStartPos === $pos - $consecutive - 1 || $consecutive > 1) {
			foreach (range($pos - $consecutive, $pos - 1) as $needle) {
				$lines->remove($needle);
			}
		}
		return 0;
	}
}