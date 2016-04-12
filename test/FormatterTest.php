<?php

namespace nochso\Phormat\Test;


use nochso\Phormat\Formatter;

class FormatterTest extends \PHPUnit_Framework_TestCase
{
	public function formatProvider()
	{
		$inputFiles = glob(__DIR__.'/fixture/*.in.php');
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

	/**
	 * @dataProvider formatProvider
	 */
	public function testFormat($expectedSource, $inputSource)
	{
		$formatter = new Formatter();
		$this->assertSame($expectedSource, $formatter->format($inputSource));
	}
}
