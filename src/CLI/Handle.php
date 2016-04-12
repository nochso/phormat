<?php

namespace nochso\Phormat\CLI;


class Handle extends \Aura\Cli\Stdio\Handle
{
	protected function setPosix($posix)
	{
		parent::setPosix($posix);
		if (strtolower(substr($this->php_os, 0, 3)) == 'win') {
			$this->posix = getenv('ANSICON') !== false || getenv('ConEmuANSI') === 'ON';
		}
	}
}