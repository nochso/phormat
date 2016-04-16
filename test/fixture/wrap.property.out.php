<?php
class Bytes {
	private static $suffixes = [
		self::SUFFIX_SIMPLE => ['B', 'K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y'],
		self::SUFFIX_IEC => ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'],
		self::SUFFIX_IEC_LONG => [
			'byte(s)',
			'kibibyte(s)',
			'mebibyte(s)',
			'gibibyte(s)',
			'tebibyte(s)',
			'pebibyte(s)',
			'exbibyte(s)',
			'zebibyte(s)',
			'yobibyte(s)',
		],
		self::SUFFIX_SI => ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'],
		self::SUFFIX_SI_LONG => [
			'byte(s)',
			'kilobyte(s)',
			'megabyte(s)',
			'gigabyte(s)',
			'terabyte(s)',
			'petabyte(s)',
			'exabyte(s)',
			'zettabyte(s)',
			'yottabyte(s)',
		],
	];
	private $precision = 2;
}
