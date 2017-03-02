# Change Log
All notable changes to this project will be documented in this file.

## [1.1.18] - 2017-03-02

### Fixed
- bug in upload size exception handling

## [1.1.17] - 2017-02-28

### Fixed
- Exception handling for exceed given dca field uploadSize changed, only for admin in backend mode, otherwise System:log entry 

## [1.1.16] - 2017-02-28

### Fixed
- attach `multifileupload_moveFiles` onsubmit_callback always at first position of all onsubmit_callbacks

## [1.1.15] - 2017-02-24

### Fixed
- upload size issues
- fixed localization bugs

## [1.1.14] - 2017-02-23

### Fixed
- frontend CSS Styles

## [1.1.13] - 2017-02-22

### Fixed
- js issue

## [1.1.12] - 2017-02-22

### Fixed
- js issue, css styles in fe

## [1.1.11] - 2017-02-22

### Fixed
- js issue

## [1.1.10] - 2017-02-21

### Added
- eval flag `skipDeleteAfterSubmit`

## [1.1.9] - 2016-12-22

### Fixed
- support multiple fields properly, iterator issue

## [1.1.8] - 2016-12-16

### Fixed
- consider existing `?` in `config.uploadActionParams`

## [1.1.7] - 2016-12-14

### Fixed
- support for IE10 (no dataset supported..)

## [1.1.6] - 2016-12-12

### Fixed
- support file preview within popup

## [1.1.5] - 2016-12-12

### Fixed
- support binary(16) fields

## [1.1.4] - 2016-12-06

### Fixed
- render widget error in template

## [1.1.3] - 2016-12-06

### Fixed
- invalid json in "assets/img/mimetypes/Numix-uTouch/mimetypes.json"

## [1.1.1] - 2016-12-02

### Changed
- improved styling and added 'dz-has-files' class to dropzone container if files are within the box

## [1.1.0] - 2016-12-02

### Changed
- complete multifileupload refactoring (now working in fron and back end) with dropzone 4.x, see README.md for full feature overview

## [1.0.16] - 2016-11-22

### Fixed
- added hideLabel eval dca support
- reformat to new psr (spaces)
