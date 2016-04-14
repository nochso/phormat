<?php

namespace nochso\Phormat\Test;


use nochso\Phormat\Formatter;

class FormatterTest extends \PHPUnit_Framework_TestCase
{
	public function formatProvider()
	{
		foreach (FixtureProvider::provide(__DIR__ . '/fixture/*.in.php') as $key => $value) {
			yield $key => $value;
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

	/**
	 * @dataProvider formatProvider
	 */
	public function testFormat_OutputMustNotChange($expectedSource, $inputSource)
	{
		$formatter = new Formatter();
		$this->assertSame($expectedSource, $formatter->format($expectedSource));
	}
}
