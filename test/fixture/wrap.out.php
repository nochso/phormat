<?php
function foo(
	$longParameterName1,
	$longParameterName2,
	$longParameterName3,
	$longParameterName4 = '',
	$longParameterName1 = ''
) {
}

$closure = function (
	$longParameterName1,
	$longParameterName2,
	$longParameterName3,
	$longParameterName4 = '',
	$longParameterName1 = ''
) {
	$innerClosure = function ($param) use (
		$longUseVariable1,
		$longUseVariable2,
		$longUseVariable3,
		$longUseVariable4,
		$longUseVariable5
	) {
		foo(
			'longParameterName1',
			'longParameterName2',
			'longParameterName3',
			'longParameterName4',
			'longParameterName1'
		);
		$obj->foo(
			'longParameterName1',
			'longParameterName2',
			'longParameterName3',
			'longParameterName4',
			'longParameterName1'
		);
		FooClass::foo(
			'longParameterName1',
			'longParameterName2',
			'longParameterName3',
			'longParameterName4',
			'longParameterName1'
		);
	};
};
class X extends Y implements 
	LongInterfaceName1,
	LongInterfaceName2,
	LongInterfaceName3,
	LongInterfaceName4,
	LongInterfaceName5,
	LongInterfaceName5
{
	use
		SomeLongTraitName1,
		SomeLongTraitName2,
		SomeLongTraitName3,
		SomeLongTraitName4,
		SomeLongTraitName5;

	public function foo(
		$longParameterName1,
		$longParameterName2,
		$longParameterName3,
		$longParameterName4 = '',
		$longParameterName1 = ''
	) {
	}
}
class Y {
	use
		SomeLongTraitName1,
		SomeLongTraitName2,
		SomeLongTraitName3,
		SomeLongTraitName4,
		SomeLongTraitName5 {
			B::smallTalk insteadof SomeLongTraitName1;
			A::bigTalk insteadof SomeLongTraitName2;
			B::bigTalk as talk;
		}

	/**
	 * @var int
	 */
	private
		$somePrivatePropertiesOnTheSameLine1,
		$somePrivatePropertiesOnTheSameLine2,
		$somePrivatePropertiesOnTheSameLine3;
}
interface IX extends 
	LongInterfaceName1,
	LongInterfaceName2,
	LongInterfaceName3,
	LongInterfaceName4,
	LongInterfaceName5,
	LongInterfaceName5
{
}
