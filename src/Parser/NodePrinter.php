<?php
namespace nochso\Phormat\Parser;

use nochso\Omni\Multiline;
use nochso\Omni\Strings;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;

class NodePrinter extends \PhpParser\PrettyPrinter\Standard {
	private $separateTypes = [
		Node\Const_::class,
		Stmt\ClassConst::class,
		Stmt\Property::class,
		Stmt\ClassMethod::class,
		Stmt\Function_::class,
		Stmt\Class_::class,
		Stmt\Trait_::class,
		Stmt\Interface_::class,
	];
	private $separateIdenticalTypes = [Stmt\ClassMethod::class, Stmt\Function_::class, Stmt\Class_::class];
	private $orderElements = false;
	/**
	 * @var \nochso\Phormat\Parser\NodeSorter
	 */
	private $comparer;
	/**
	 * @var \nochso\Phormat\Parser\UseSorter
	 */
	private $useSorter;
	private $indentation = "\t";
	private $softLimit = 80;
	private $softLimitInfix = 100;

	public function __construct(array $options = []) {
		$options['shortArraySyntax'] = true;
		$this->comparer = new NodeSorter();
		$this->useSorter = new UseSorter();
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
			. (null !== $node->returnType ? ' : ' . $this->pType($node->returnType) : '') . ' {'
			. $this->pStmts($node->stmts) . "\n" . '}';
	}

	public function pStmt_Namespace(Stmt\Namespace_ $node) {
		if ($this->canUseSemicolonNamespaces) {
			return 'namespace ' . $this->p($node->name) . ';' . "\n" . $this->pStmts($node->stmts, false);
		} else {
			return 'namespace' . (null !== $node->name ? ' ' . $this->p($node->name) : '') . ' {'
				. $this->pStmts($node->stmts) . "\n" . '}';
		}
	}

	public function pStmt_ClassMethod(Stmt\ClassMethod $node) {
		return $this->pModifiers($node->type) . 'function ' . ($node->byRef ? '&' : '') . $node->name
			. $this->pCommaSeparatedLines($node->params, '(', ')')
			. (null !== $node->returnType ? ' : ' . $this->pType($node->returnType) : '')
			. (null !== $node->stmts ? ' {' . $this->pStmts($node->stmts) . "\n" . '}' : ';');
	}

	public function pExpr_FuncCall(Expr\FuncCall $node) {
		return $this->pCallLhs($node->name) . $this->pCommaSeparatedLines($node->args, '(', ')');
	}

	public function pExpr_MethodCall(Expr\MethodCall $node) {
		return $this->pDereferenceLhs($node->var) . '->' . $this->pObjectProperty($node->name)
			. $this->pCommaSeparatedLines($node->args, '(', ')');
	}

	public function pExpr_StaticCall(Expr\StaticCall $node) {
		return $this->pDereferenceLhs($node->class) . '::'
			. ($node->name instanceof Expr ? $node->name instanceof Expr\Variable ? $this->p($node->name) : '{' . $this->p($node->name) . '}' : $node->name)
			. $this->pCommaSeparatedLines($node->args, '(', ')');
	}

	public function pExpr_Closure(Expr\Closure $node) {
		return ($node->static ? 'static ' : '') . 'function ' . ($node->byRef ? '&' : '')
			. $this->pCommaSeparatedLines($node->params, '(', ')')
			. (!empty($node->uses) ? ' use ' . $this->pCommaSeparatedLines($node->uses, '(', ')') : '')
			. (null !== $node->returnType ? ' : ' . $this->pType($node->returnType) : '') . ' {'
			. $this->pStmts($node->stmts) . "\n" . '}';
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
		if (count($node->props) > 1) {
			$properties = $this->pCommaSeparatedLines($node->props);
			if (strpos($properties, "\n") !== false) {
				$modifier = rtrim($modifier);
				$properties = rtrim($properties);
			}
		} else {
			$properties = $this->pCommaSeparated($node->props);
		}
		return $modifier . $properties . ';';
	}

	/**
	 * @param bool $orderElements
	 */
	public function setOrderElements($orderElements) {
		$this->orderElements = $orderElements;
	}

	public function pExpr_BinaryOp_Concat(Expr\BinaryOp\Concat $node) {
		return $this->pInfixOp('Expr_BinaryOp_Concat', $node->left, ' . ', $node->right, true);
	}

	public function pExpr_BinaryOp_BooleanAnd(Expr\BinaryOp\BooleanAnd $node) {
		return $this->pInfixOp('Expr_BinaryOp_BooleanAnd', $node->left, ' && ', $node->right, true);
	}

	public function pExpr_BinaryOp_BooleanOr(Expr\BinaryOp\BooleanOr $node) {
		return $this->pInfixOp('Expr_BinaryOp_BooleanOr', $node->left, ' || ', $node->right, true);
	}

	protected function pInfixOp($type, Node $leftNode, $operatorString, Node $rightNode, $wrap = false) {
		list($precedence, $associativity) = $this->precedenceMap[$type];
		$left = $this->pPrec($leftNode, $precedence, $associativity, -1);
		$right = $this->pPrec($rightNode, $precedence, $associativity, 1);
		if ($wrap) {
			// Find position of last new line
			$lastNewLine = strrpos(trim($left), "\n");
			// If no new line, consider start of string as one
			if ($lastNewLine === false) {
				$lastNewLine = 0;
			}
			// Is a newline needed after the last one?
			if (strlen($left . $operatorString . $right) - $lastNewLine >= $this->softLimitInfix) {
				$out = "\n" . ltrim($operatorString) . $right;
				$out = preg_replace('~\n(?!$|\n|' . $this->noIndentToken . ')~', "\n" . $this->indentation, $out);
				return $left . $out;
			}
		}
		return $left . $operatorString . $right;
	}

	protected function pComments(array $comments) {
		$lines = Multiline::create(parent::pComments($comments));
		// Trim trailing non-Markdown whitespace
		$lines->apply(
			function ($line) {
				if (preg_match('/(?<! |\\*)  $/', $line)) {
					return $line;
				}
				return rtrim($line);
			}
		);
		$lastStartPos = -1;
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
				$lastStartPos = $pos - $consecutive - 1;
			}
			// Remove lines if possible
			$consecutive = $this->removeConsecutiveEmptyDocs($lines, $consecutive, $lastStartPos, $pos);
		}
		return (string) $lines;
	}

	protected function pCommaSeparatedLines(array $nodes, $prefix = '', $suffix = '', $trailingComma = false) {
		$arr = parent::pCommaSeparated($nodes);
		if ($this->needsWrapping($arr)) {
			$arr = "\n" . $this->pImplode($nodes, ",\n") . ($trailingComma ? ',' : '') . "\n";
		}
		return $prefix
			. preg_replace('~\n(?!$|\n|' . $this->noIndentToken . ')~', "\n" . $this->indentation, $arr)
			. $suffix;
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
		if ($this->orderElements) {
			$this->comparer->sort($nodes);
		}
		$this->useSorter->sort($nodes);
		$result = '';
		$prevContext = null;
		/** @var Node $prevNode */
		$prevNode = null;
		foreach ($nodes as $node) {
			$newContext = get_class($node);
			if ($prevContext !== $newContext) {
				if ($prevContext !== null
					&& (in_array($prevContext, $this->separateTypes) || in_array($newContext, $this->separateTypes))) {
					$result .= "\n";
				}
				$prevContext = $newContext;
			} elseif (in_array($newContext, $this->separateIdenticalTypes)) {
				$result .= "\n";
			}
			/** @var \PhpParser\Comment[] $comments */
			$comments = $node->getAttribute('comments', []);
			if ($comments) {
				// Keep comments on the same line.
				if ($prevNode !== null && $comments[0]->getLine() === $prevNode->getAttribute('endLine')) {
					$result .= ' ';
				} else {
					$result .= "\n";
				}
				$result .= $this->pComments($comments);
				if ($node instanceof Stmt\Nop) {
					$prevNode = $node;
					continue;
				}
			}
			$prevNode = $node;
			$nodeCode = $this->p($node);
			if (strpos($nodeCode, "\n") !== false) {
				if (Strings::endsWith($nodeCode, '))')) {
					$nodeCode = substr($nodeCode, 0, -2) . ")\n\t)";
				} elseif (Strings::endsWith($nodeCode, '));')) {
					$nodeCode = substr($nodeCode, 0, -3) . ")\n\t);";
				}
			}
			$result .= "\n" . $nodeCode . ($node instanceof Node\Expr ? ';' : '');
		}
		if ($indent) {
			return preg_replace('~\n(?!$|\n|' . $this->noIndentToken . ')~', "\n" . $this->indentation, $result);
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

	public function pStmt_Trait(Stmt\Trait_ $node) {
		return 'trait ' . $node->name . " {" . $this->pStmts($node->stmts) . "\n" . '}';
	}

	public function pStmt_TraitUse(Stmt\TraitUse $node) {
		$traits = rtrim($this->pCommaSeparatedLines($node->traits));
		$use = 'use';
		if (strpos($traits, "\n") === false) {
			$use .= ' ';
		}
		return $use . $traits
			. (empty($node->adaptations) ? ';' : ' {'
				. preg_replace(
					'~\n(?!$|\n|' . $this->noIndentToken . ')~',
					"\n" . $this->indentation,
					$this->pStmts($node->adaptations) . "\n" . '}'
				)
			);
	}

	protected function pClassCommon(Node\Stmt\Class_ $node, $afterClassToken) {
		return $this->pModifiers($node->type) . 'class' . $afterClassToken
			. (null !== $node->extends ? ' extends ' . $this->p($node->extends) : '')
			. (!empty($node->implements) ? ' implements ' . $this->pCommaSeparatedLines($node->implements) : ' ')
			. '{' . $this->pStmts($node->stmts) . "\n" . '}';
	}

	public function pScalar_String(Node\Scalar\String_ $node) {
		$kind = $node->getAttribute('kind', Node\Scalar\String_::KIND_SINGLE_QUOTED);
		if ($kind === Node\Scalar\String_::KIND_HEREDOC || $kind === Node\Scalar\String_::KIND_NOWDOC) {
			return parent::pScalar_String($node);
		}
		return $this->pNoIndent($node->getAttribute('originalValue'));
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

	protected function removeConsecutiveEmptyDocs(Multiline $lines, $consecutive, $lastStartPos, $pos) {
		if ($consecutive > 0 && $lastStartPos === $pos - $consecutive - 1 || $consecutive > 1) {
			foreach (range($pos - $consecutive, $pos - 1) as $needle) {
				$lines->remove($needle);
			}
		}
		return 0;
	}

	/**
	 * @param string$input
	 *
	 * @return bool
	 */
	protected function needsWrapping($input) {
		$trimInput = trim($input);
		return mb_strlen($trimInput) >= $this->softLimit;
	}
}
