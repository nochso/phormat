<?php

namespace nochso\Phormat\Test;


class FixtureProvider
{
	public static function provide($pattern)
	{
		$inputFiles = glob($pattern);
		foreach ($inputFiles as $inputFile) {
			$outputFile = str_replace('.in.php', '.out.php', $inputFile);
			$input = file_get_contents($inputFile);
			if (!is_file($outputFile)) {
				$output = $input;
			} else {
				$output = file_get_contents($outputFile);
			}
			yield $inputFile => [$output, $input];
		}
	}
}