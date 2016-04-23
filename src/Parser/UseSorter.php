<?php
namespace nochso\Phormat\Parser;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;

class UseSorter {
	private $types = [Use_::class => 0, GroupUse::class => 0, Class_::class => 2];
	/**
	 * @var \nochso\Phormat\Parser\StableSorter
	 */
	private $stableSort;

	public function __construct() {
		$this->stableSort = new StableSorter();
	}

	/**
	 * @param \PhpParser\Node[] $nodes
	 */
	public function sort(array &$nodes) {
		$this->stableSort->sort($nodes, [$this, 'compareNode']);
	}

	public function compareNode(Node $a, Node $b) {
		// Keep unknown types as they are
		$aClass = get_class($a);
		if (!isset($this->types[$aClass])) {
			return 0;
		}
		$bClass = get_class($b);
		if (!isset($this->types[$bClass])) {
			return 0;
		}
		// Sort by type
		$cmp = strcmp($this->types[$aClass], $this->types[$bClass]);
		if ($cmp === 0 && ($aClass === Use_::class || $aClass === GroupUse::class)) {
			// Sort within type
			$cmp = $this->compareClassNodeOfEqualType($a, $b);
		}
		return $cmp;
	}

	/**
	 * @param \PhpParser\Node\Stmt\GroupUse|\PhpParser\Node\Stmt\Use_ $a
	 * @param \PhpParser\Node\Stmt\GroupUse|\PhpParser\Node\Stmt\Use_ $b
	 *
	 * @return int
	 */
	private function compareClassNodeOfEqualType($a, $b) {
		$useUseSorter = function (UseUse $aUse, UseUse $bUse) {
			return strcasecmp($aUse->name->toString(), $bUse->name->toString());
		};
		usort($a->uses, $useUseSorter);
		usort($b->uses, $useUseSorter);
		$namer = function ($use) {
			if ($use instanceof GroupUse) {
				return $use->prefix->toString();
			}
			return $use->uses[0]->name->toString();
		};
		return strcasecmp($namer($a), $namer($b));
	}
}
