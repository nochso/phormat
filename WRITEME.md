---
composer:
    name: nochso/phormat
toc:
    max-depth: 2
changelog:
    max-changes: 3
---
# @composer.name@

@badge.writeme@
@badge.license('nochso/phormat')@
@badge.tag('nochso/phormat')@
@badge.travis('nochso/phormat')@
@badge.coveralls('nochso/phormat')@

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

@toc@

# Requirements
This project is written for and tested with PHP 5.6, 7.0 and HHVM.

# Installation
For end-users the PHAR version is preferred. To install it **globally**:

1. Download the PHAR file from the
   [latest release](https://github.com/@composer.name@/releases).
2. Make it executable: `chmod +x phormat.phar`
3. Move it somewhere within your `PATH`: `sudo cp phormat.phar /usr/local/bin/phormat`

As **local Composer development** dependency per project:
```
composer require --dev @composer.name@
```

As **global** Composer dependency:
```
composer global require @composer.name@
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
USAGE
    phormat [options] <path>
    phormat [options] <path1> <path2> ...

DESCRIPTION
    By default PHP files from the specified paths will be overwritten.

ARGUMENTS
    <paths>
        One or many paths to files or directories.

OPTIONS
    -d
    --diff
        Preview diff of formatted code. Implies --no-output.

    -s
    --summary
        Show a status summary for each file.

    -o
    --order
        Change order of class elements.

    -p
    --print
        Print full output of formatted code. Implies --no-output.

    -n
    --no-output
        Do not overwrite source files.

    -h
    --help
        Show this help.
```

## Limitations
Long lines with concatenation and ternary expressions are not wrapped at all.
Any suggestions on how to do this would be welcome.

# Change log
See [CHANGELOG.md](CHANGELOG.md) for the full history of changes between
releases.

@changelog@

# License
This project is released under the MIT license. See [LICENSE.md](LICENSE.md)
for the full text.
