<?php
abstract class Foo {
	private function prif() {}
	private static function prisf() {}
	public abstract function pubaf();
	public static abstract function pubasf();
	protected function prof() {}
	protected static function prosf() {}
	abstract protected function proaf();
	abstract static protected function proasf();
	private function __construct() {}
	public function __destruct() {}
	public function __toString() {}
	private function __get() {}
	public function pubf() {}
	public static function pubsf() {}
}
