<?php
namespace nochso\Phormat\Parser;

use nochso\Omni\Strings;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\Property;

class NodeCompare {
	private static $types = [ClassConst::class => 1, Property::class => 2, ClassMethod::class => 3];

	/**
	 * @param \PhpParser\Node[] $nodes
	 */
	public static function sortNodes(array &$nodes) {
		self::stableUsort($nodes, function (Node $a, Node $b) {
				return self::compareNode($a, $b);
			});
	}

	protected static function compareNode(Node $a, Node $b) {
		// Keep unknown types as they are
		$aClass = get_class($a);
		if (!isset(self::$types[$aClass])) {
			return 0;
		}
		$bClass = get_class($b);
		if (!isset(self::$types[$bClass])) {
			return 0;
		}
		// Sort by type
		$cmp = strcmp(self::$types[$aClass], self::$types[$bClass]);
		if ($cmp === 0) {
			// Sort within type
			$cmp = self::compareClassNodeOfEqualType($a, $b);
		}
		return $cmp;
	}

	protected static function compareClassNodeOfEqualType(Node $a, Node $b) {
		$cmp = 0;
		// Do not sort within constants
		if ($a instanceof ClassConst) {
			return $cmp;
		}
		// If possible, compare by visibility
		if ($a instanceof Property || $a instanceof ClassMethod) {
			$cmp = self::compareVisibility($a, $b);
		}
		if ($cmp !== 0) {
			return $cmp;
		}
		$cmp = self::compareModifier($a, $b);
		if ($cmp !== 0) {
			return $cmp;
		}
		if ($a instanceof ClassMethod) {
			$cmp = self::compareClassMethodName($a, $b);
		}
		return $cmp;
	}

	protected static function compareVisibility(Node $a, Node $b) {
		$visibility = function (Node $n) {
			return $n->isPublic() ? 0 : ($n->isProtected() ? 1 : 2);
		};
		return strcmp($visibility($a), $visibility($b));
	}

	protected static function compareModifier(Node $a, Node $b) {
		// Within same visibility, static goes first
		$cmp = strcmp($b->isStatic(), $a->isStatic());
		if ($cmp !== 0) {
			return $cmp;
		}
		// Within same visibility and staticness, abstract goes first
		if ($a instanceof ClassMethod) {
			$cmp = strcmp($b->isAbstract(), $a->isAbstract());
		}
		return $cmp;
	}

	protected static function stableUsort(array &$array, $compareFunction) {
		$index = 0;
		foreach ($array as &$item) {
			$item = [$index++, $item];
		}
		$result = usort(
			$array,
			function ($a, $b) use ($compareFunction) {
				$result = call_user_func($compareFunction, $a[1], $b[1]);
				return $result == 0 ? $a[0] - $b[0] : $result;
			}
		);
		foreach ($array as &$item) {
			$item = $item[1];
		}
		return $result;
	}

	protected static function compareClassMethodName(ClassMethod $a, ClassMethod $b) {
		$aIsMagic = Strings::startsWith($a->name, '__');
		$bIsMagic = Strings::startsWith($b->name, '__');
		// __magic goes first
		if ($aIsMagic || $bIsMagic) {
			return strcmp($bIsMagic, $aIsMagic);
		}
		// Check for accessors
		$accessorPrefixes = ['has', 'is', 'get', 'set', 'add', 'remove', 'enable', 'disable'];
		$regex = '/^(' . implode('|', $accessorPrefixes) . ')(([A-Z].*)?)$/';
		$aAccessor = preg_match($regex, $a->name, $aMatches);
		$bAccessor = preg_match($regex, $b->name, $bMatches);
		$cmp = strcmp($bAccessor, $aAccessor);
		// Only one accessor: move it up. If no accessors, keep as is.
		if ($cmp !== 0 || !$aAccessor && !$bAccessor) {
			return $cmp;
		}
		// Both are accessors. If they have different suffixes, sort by it
		$cmp = strcmp($aMatches[2], $bMatches[2]);
		if ($cmp !== 0) {
			return $cmp;
		}
		// Accessors with identical suffix. Sort by accessor prefix priority
		$aPrio = array_search($aMatches[1], $accessorPrefixes, true);
		$bPrio = array_search($bMatches[1], $accessorPrefixes, true);
		return strcmp($aPrio, $bPrio);
	}
}
