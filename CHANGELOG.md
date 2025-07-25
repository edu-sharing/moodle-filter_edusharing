# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [10.0.0] - 2025-07-14

## Added

- Compatibility with ES-Repository versions >=10 (new Rendering Service 2)

## Changed

- Bumped the PHP version in the CI pipeline to 8.4
- Bumped the MYSQL version in the CI pipeline to 9.3.0
- Bumped the Moodle version in the CI pipeline to 5.0
- Ensured compatibility with Moodle 5.0

## [9.0.0] - 2025-01-07

### Added

- French translation file

## [8.1.4] - 2024-11-07

### Changed

- Refactored code for compatibility with Moodle 4.5

## [8.1.3] - 2024-07-17

### Fixed

- Potential parsing error in filter logic caused by imprecise regular expressions

## [8.1.2] - 2024-05-17

### Fixed

- If fetching the usage id fails the filter now defaults to the old legacy logic to fetch the snippet

## [8.1.1] - 2024-05-15

### Fixed

- Objects embedded without usage id (before 2022) are now displayed correctly

## [8.1.0] - 2024-05-02

### Changed

- Major refactoring to update plugin to current Moodle CI requirements

### Added

- GitLab CI pipeline including Moodle CI checks

### Fixed

- Missing query parameters do no longer cause type error on object conversion

## [8.0.4] - 2024-04-02

### Removed

- Event listener for metadata info button removed (logic is now in a script in rendering service)

## [8.0.3] - 2024-01-14

### Changed

- Refactored code and doc blocks to conform with moodle guidelines
- Refactored code to use new ES API node methods

### Fixed

- Different PHP versions handle URLs which in turn have another URL as query parameter in different manners. 
While sometimes the query params of the latter are treated as singular entries in $GET, at other times the whole URL 
is one entry in $GET. This led to a bug where the resource id of an ES-object could not be retrieved.

## [8.0.2] - 2023-11-17

### Added

- Compatibility with new Edu-Sharing TinyMCE plugin
- Compatibility with version 8.0.3 of the edu-sharing activity module

### Changed

- Refactored code and doc blocks to conform with moodle guidelines

## [8.0.1] - 2023-11-17

### Removed

- Compatibility with legacy ES Tiny-Editor-Plugin

##  [8.0.0] - 2023-10-01

### Added

- Unit test folder and unit test classes
- Compatibility with version 8.0.0 of the edu-sharing activity module

### Removed

- Unnecessary classes which were added to various DOM nodes related to embedded edu-sharing objects

### Fixed

- Duplicated captions of embedded edu-sharing objects

### Changed

- Major refactoring in order to match updated moodle criteria as well as to facilitate unit testing
