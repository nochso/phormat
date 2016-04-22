# nochso/phormat

[![write me to read me](https://img.shields.io/badge/writeme-readme-blue.svg)](https://github.com/nochso/writeme)
[![License](https://img.shields.io/github/license/nochso/phormat.svg)](https://packagist.org/packages/nochso/phormat)
[![Latest tag on Github](https://img.shields.io/github/tag/nochso/phormat.svg)](https://github.com/nochso/phormat/tags)
[![Travis CI build status](https://api.travis-ci.org/nochso/phormat.svg)](https://travis-ci.org/nochso/phormat)
[![Coverage status](https://coveralls.io/repos/github/nochso/phormat/badge.svg)](https://coveralls.io/github/nochso/phormat)

Phormat formats PHP source code.

Differences to other [fixers](https://github.com/FriendsOfPHP/PHP-CS-Fixer) or
[PSR2](http://www.php-fig.org/psr/psr-2/):

- You can not influence the style, [similar](https://blog.golang.org/go-fmt-your-code)
  to [gofmt](https://golang.org/cmd/gofmt/).
- Tabs for indentation.
- Opening braces `{` on the same line: `) {`
- No extra whitespacy lines in code or comments.
- Fast. Check out the [benchmarks](http://nochso.github.io/phormat/benchmark/).

* * *

- [nochso/phormat](#nochsophormat)
- [Introduction, goals and scope](#introduction-goals-and-scope)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
    - [Command line options](#command-line-options)
- [Change log](#change-log)
    - [Unreleased](#unreleased)
    - [0.1.1 - 2016-04-18](#011---2016-04-18)
    - [0.1.0 - 2016-04-16](#010---2016-04-16)
- [License](#license)

# Introduction, goals and scope

Phormat is a pretty printer based on [nikic/php-parser](https://github.com/nikic/PHP-Parser).
It **discards** any formatting and prints source code in a **uniform** style. Custom
formatting options are out of scope.

The chosen style is personal preference and attempts to keep the line count low
while keeping the code readable.

> You're anti-PSR! Why do you hate FIG?

I'm not. I don't. This is **not a replacement, improvement or critique of PSR2**
but merely an alternative you're free to ignore.

PSR2 is quite widespread for a reason and has helped lots of projects decide on a common
style. However it does not mean everybody loves it personally or that it is a
de-facto standard you must adhere to. The questions of *tabs vs. spaces* or *placement of braces*
will always be part of a holy war as it comes down to personal preference.

In the end, it's **best for collaboration** if a project has a **well defined**
style that is easy for contributors to adhere to. Which is easily possible for
both PSR2 and phormat.

See [php-cs-fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer) for a great
way to adhere to PSR2.

# Requirements
This project is written for and tested with PHP 5.6, 7.0 and HHVM.

# Installation
For end-users the PHAR version is preferred. To install it **globally**:

1. Download the PHAR file from the
   [latest release](https://github.com/nochso/phormat/releases).
2. Make it executable: `chmod +x phormat.phar`
3. Move it somewhere within your `PATH`: `sudo cp phormat.phar /usr/local/bin/phormat`

As **local Composer development** dependency per project:
```
composer require --dev nochso/phormat
```

As **global** Composer dependency:
```
composer global require nochso/phormat
```

# Usage

As a local dependency `php vendor/bin/phormat` or if installed globally just `phormat`.

    phormat [options] <path>
    phormat [options] <path1> <path2> ...

By default PHP files from the specified paths will be overwritten. See the
options below to override this behaviour.

If path is a folder it will be searched recursively for files ending with
`*.php`.

Native PHP templates will be skipped to avoid messing up their formatting.
Templates are detected by looking for [alternative syntax for control structures](http://php.net/manual/en/control-structures.alternative-syntax.php)
like `if (true): .. endif;`

## Command line options
```
Usage
    phormat [options] <path>
    phormat [options] <path1> <path2> ...

Description
    By default PHP files from the specified paths will be overwritten.

Arguments
    <paths>
        One or many paths to files or directories.

Options
    -d, --diff
        Preview diff of formatted code. Implies --no-output.

    -s, --summary
        Show a status summary for each file.

    -o, --order
        Change order of class elements.

    -p, --print
        Print full output of formatted code. Implies --no-output.

    -n, --no-output
        Do not overwrite source files.

    -h, --help
        Show this help.

    --version
        Show version information.
```

# Change log
See [CHANGELOG.md](CHANGELOG.md) for the full history of changes between
releases.

## [Unreleased]

### Changed
- Made `--help` output less `man`ish and more colorful.


## [0.1.1] - 2016-04-18

### Added
- Command line option `--version` to display version information and quit.
- Benchmark script comparing to php-cs-fixer and phpfmt.
- Wrapping of long lines by `.` concatenation.


### Fixed
- Removed const arrays for HHVM compatibility.
- Do not indent single/double quoted strings.
- Stop putting property name on new line if it's a single property with wrappable assignment.
- Put braces on first line in traits.


## [0.1.0] - 2016-04-16

### Added
- First public release.

[Unreleased]: https://github.com/nochso/phormat/compare/0.1.1...HEAD
[0.1.1]: https://github.com/nochso/phormat/compare/0.1.0...0.1.1
[0.1.0]: https://github.com/nochso/phormat/compare/049e1ebafb5fb8de18ac9532bc20191cc7df79c3...0.1.0



# License
This project is released under the MIT license. See [LICENSE.md](LICENSE.md)
for the full text.
