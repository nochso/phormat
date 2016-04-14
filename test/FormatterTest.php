<?php

namespace nochso\Phormat\Test;


use nochso\Phormat\Formatter;
use nochso\Phormat\TemplateSkippedException;

class FormatterTest extends \PHPUnit_Framework_TestCase
{
	public function formatProvider()
	{
		return new FixtureIterator(__DIR__ . '/fixture/*.in.php');
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

	public function skipProvider()
	{
		return new FixtureIterator(__DIR__ . '/fixture/skip/*.php');
	}

	/**
	 * @dataProvider skipProvider
	 */
	public function testNativeTemplatesMustBeSkipped($expectedSource, $inputSource)
	{
		$formatter = new Formatter();
		$this->expectException(TemplateSkippedException::class);
		$formatter->format($inputSource);
	}
}
