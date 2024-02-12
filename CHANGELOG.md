# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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