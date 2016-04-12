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
			yield [file_get_contents($outputFile), file_get_contents($inputFile)];
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
