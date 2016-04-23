<?php
namespace nochso\Phormat\Parser;

class StableSorter {
	public function sort(array &$array, $compareFunction) {
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
}
