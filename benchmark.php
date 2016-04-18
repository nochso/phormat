<?php
use nochso\Benchmark;
use nochso\Benchmark\Parameter;
use nochso\Benchmark\Report;
use nochso\Benchmark\Timer;
use nochso\Benchmark\Unit;
use nochso\Omni;
use nochso\Omni\Exec;
use nochso\Omni\Folder;
use nochso\Omni\Path;

require 'vendor/autoload.php';

$repos = [
	'aura/sql' => 'https://github.com/auraphp/Aura.Sql.git',
	'doctrine/dbal' => 'https://github.com/doctrine/dbal.git',
	'fzaninotto/faker' => 'https://github.com/fzaninotto/Faker.git',
	'paris' => 'https://github.com/j4mie/paris.git',
	'phpunit' => 'https://github.com/sebastianbergmann/phpunit.git',
	'plates' => 'https://github.com/thephpleague/plates.git',
	'slim' => 'https://github.com/slimphp/Slim.git',
	'symfony/yaml' => 'https://github.com/symfony/yaml.git',
	'twig' => 'https://github.com/twigphp/Twig.git',
];

$tmpDir = Path::combine(sys_get_temp_dir(), 'phormat_benchmark');
Folder::ensure($tmpDir);
Timer::$defaultMinDuration = 1000;

$versions = [
	'phormat' => ['php', 'phormat', '-h'],
	'php-cs-fixer' => ['php-cs-fixer', '--version'],
	'phpfmt' => ['fmt.phar', '--version'],
];
foreach ($versions as $name => &$version) {
	$line = Exec::create()->run(...$version)->getOutput()[0];
	if (preg_match('/([0-9][0-9.-]+[0-9a-z-]+)/i', $line, $m)) {
		$version = $m[1];
	}
	$version = $name . ' ' . $version;
}

$report = new Report('Speed comparison of PHP source formatters', '', ['output_dir' => 'benchmark']);
$desc = 'Various open source projects are formatted with common settings.

Each repository is checked out `--hard` once before the benchmark is run.
`.php_cs` and `.php_cs_cache` files are removed to make php-cs-fixer comparable
with other formatters.

You can see the command arguments by clicking on the method.

Versions used:

* `'.implode("`\n* `", $versions) . '`';
$unit = new Unit('Formatting open source projects', $desc);

$phormat = Exec::create('php', 'phormat', '-n', '-s');
$unit->addClosure(function ($n, $parameter) use ($phormat) {
	while ($n--) {
		$phormat->run($parameter);
	}
}, 'phormat', "```bash\n$ ".$phormat->getCommand()."\n```");

$phpCsFixer = Exec::create('php-cs-fixer', 'fix', '--dry-run', '--level=psr1');
$unit->addClosure(function ($n, $parameter) use ($phpCsFixer) {
	chdir($parameter);
    while ($n--) {
        $phpCsFixer->run($parameter);
    }
	chdir(__DIR__);
}, 'php-cs-fixer PSR-1', "```bash\n$ ".$phpCsFixer->getCommand()."\n```");

$phpCsFixer = Exec::create('php-cs-fixer', 'fix', '--dry-run', '--level=psr2');
$unit->addClosure(function ($n, $parameter) use ($phpCsFixer) {
	chdir($parameter);
    while ($n--) {
        $phpCsFixer->run($parameter);
    }
	chdir(__DIR__);
}, 'php-cs-fixer PSR-2', "```bash\n$ ".$phpCsFixer->getCommand()."\n```");

$phpCsFixer = Exec::create('php-cs-fixer', 'fix', '--dry-run', '--level=symfony');
$unit->addClosure(function ($n, $parameter) use ($phpCsFixer) {
	chdir($parameter);
    while ($n--) {
        $phpCsFixer->run($parameter);
    }
	chdir(__DIR__);
}, 'php-cs-fixer Symfony', "```bash\n$ ".$phpCsFixer->getCommand()."\n```");

$phpFmt = Exec::create('fmt.phar', '--dry-run', '--psr');
$unit->addClosure(function ($n, $parameter) use ($phpFmt) {
	chdir($parameter);
    while ($n--) {
        $phpFmt->run($parameter);
    }
	chdir(__DIR__);
}, 'phpfmt PSR-2', "```bash\n$ ".$phpFmt->getCommand()."\n```");

foreach ($repos as $repoName => $repo) {
	$repoDir = Path::combine($tmpDir, 'repo', $repoName);
	if (!is_dir($repoDir)) {
		$clone = Exec::create('git')->run('clone', '--depth=1', '--single-branch', $repo, $repoDir);
	}
	$reset = Exec::create('git')->run('--git-dir='.$repoDir.'/.git', '--work-tree='.$repoDir, 'reset', '--hard', 'HEAD');
	$phpCsCache = Path::combine($repoDir, '.php_cs.cache');
	if (is_file($phpCsCache)) {
		unlink($phpCsCache);
	}
	$phpCsConfig = Path::combine($repoDir, '.php_cs');
	if (is_file($phpCsConfig)) {
		unlink($phpCsConfig);
	}
	$unit->addParam(new Parameter($repoDir, $repoName, "[$repo]($repo)"));
}

$report->unitList->add($unit);
$report->run();
