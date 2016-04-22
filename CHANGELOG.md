# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

<!--
Added      for new features.
Changed    for changes in existing functionality.
Deprecated for once-stable features removed in upcoming releases.
Removed    for deprecated features removed in this release.
Fixed      for any bug fixes.
Security   to invite users to upgrade in case of vulnerabilities.
-->

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