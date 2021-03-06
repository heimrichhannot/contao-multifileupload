# Changelog
All notable changes to this project will be documented in this file.

## [1.5.4] - 2018-05-15

### Fixed
* `timeout` configuration parameter support provided to exceed default 30 second timeout

## [1.5.3] - 2018-02-12

### Fixed
* image/thumbnail preview for initial images
* image/thumbnail preview layout / alignment (center icons and limit max-width)
* removed `console.log()` messages

### Added
* `.public` file for contao 4.x inside `/files/tmp` and run `contao::symlinks` command to provide proper thumbnail support   

## [1.5.2] - 2018-01-03

#### Fixed
* files not uploading bug introduced with 1.5.0 when using multifileupload not within multicolumneditor

## [1.5.1] - 2017-12-20

#### Fixed
* error from 1.5.0

## [1.5.0] - 2017-12-20

#### Added
* support for [Multi Column Editor](https://github.com/heimrichhannot/contao-multi_column_editor)


## [1.4.5] - 2017-12-15

#### Added 
* added `application/x-shockwave-flash` to accepted mime-types

## [1.4.4] - 2017-11-07

#### Fixed 
* more fixed due changed introduced in 1.4.2

## [1.4.3] - 2017-11-07

#### Fixed 
* fixed config changes from 1.4.2

## [1.4.2] - 2017-11-07

#### Changed 
* renamed shim repository

## [1.4.1] - 2017-11-07

#### Fixed 
* used wrong version in version compare

## [1.4.0] - 2017-11-06

#### Changed
* updated to dropzone 5

## [1.3.4] - 2017-11-06

#### Changed
* generating test files with special names when running tests for the first time

## [1.3.3] - 2017-11-06

#### Changed
* updated config.php for contao-components 2.0

## [1.3.2] - 2017-10-04

#### Fixed
* popup route not working in contao 4

## [1.3.1] - 2017-09-28

#### Fixed
* error file class in contao 4

## [1.3.0] - 2017-09-27

#### Changed
- frontend css and js is now invoked with `heimrichhannot/contao-components` (can be disabled within page layouts), removed `enableMultiFileUploadFrontendStyles` from tl_settings

## [1.2.4] - 2017-09-22

#### Fixed 
* dropzone display error in contao 4

## [1.2.3] - 2017-06-30

#### Fixed 
* front end: dont show dropzone label wihtin `form_multifileupload_dropzone.html5` if `heimrichhannot/contao-bootstrapper` module is active

## [1.2.2] - 2017-06-29

#### Fixed 
* label and error messages within front end

## [1.2.1] - 2017-06-26

#### Fixed
- fixed deps

## [1.2.0] - 2017-06-14

#### Fixed
- javascript paths
- dropzone dep

## [1.1.26] - 2017-06-08

#### Fixed
- 'text/css' was detected as 'text/x-asm'

## [1.1.25] - 2017-05-04

#### Fixed
- 'text/csv' was detected as 'text/plain' by finfo() -> allow safe mimetypes

## [1.1.24] - 2017-04-12

#### Fixed
- `tl_settings` multifileupload palette invokation
- `$GLOBALS['TL_CONFIG']['enableMultiFileUploadFrontendStyles']` default value handling

## [1.1.23] - 2017-04-11

#### Fixed
- frontend info url/download link (removal of all query parameters from url was not correct)
- `form_multifileupload_dropzone.html5` renders label, explanation and error only if `heimrichhannot/contao-bootstrapper` is not active

## [1.1.22] - 2017-04-10

#### Fixed
- restored missing test file

## [1.1.21] - 2017-04-10

#### Added
- file upload unit tests added

#### Fixed
- security issues fixed

#### Changed
- due to ajax token handling now only 1 `parallelUploads` is possible within one upload request 

## [1.1.20] - 2017-03-09

#### Added
- submitOnChange support added on adding files and deleting files (delete for multiple files only) 

## [1.1.19] - 2017-03-02

#### Fixed
- bug in upload size exception handling

## [1.1.18] - 2017-03-02

#### Fixed
- bug in upload size exception handling

## [1.1.17] - 2017-02-28

#### Fixed
- Exception handling for exceed given dca field uploadSize changed, only for admin in backend mode, otherwise System:log entry 

## [1.1.16] - 2017-02-28

#### Fixed
- attach `multifileupload_moveFiles` onsubmit_callback always at first position of all onsubmit_callbacks

## [1.1.15] - 2017-02-24

#### Fixed
- upload size issues
- fixed localization bugs

## [1.1.14] - 2017-02-23

#### Fixed
- frontend CSS Styles

## [1.1.13] - 2017-02-22

#### Fixed
- js issue

## [1.1.12] - 2017-02-22

#### Fixed
- js issue, css styles in fe

## [1.1.11] - 2017-02-22

#### Fixed
- js issue

## [1.1.10] - 2017-02-21

#### Added
- eval flag `skipDeleteAfterSubmit`

## [1.1.9] - 2016-12-22

#### Fixed
- support multiple fields properly, iterator issue

## [1.1.8] - 2016-12-16

#### Fixed
- consider existing `?` in `config.uploadActionParams`

## [1.1.7] - 2016-12-14

#### Fixed
- support for IE10 (no dataset supported..)

## [1.1.6] - 2016-12-12

#### Fixed
- support file preview within popup

## [1.1.5] - 2016-12-12

#### Fixed
- support binary(16) fields

## [1.1.4] - 2016-12-06

#### Fixed
- render widget error in template

## [1.1.3] - 2016-12-06

#### Fixed
- invalid json in "assets/img/mimetypes/Numix-uTouch/mimetypes.json"

## [1.1.1] - 2016-12-02

#### Changed
- improved styling and added 'dz-has-files' class to dropzone container if files are within the box

## [1.1.0] - 2016-12-02

#### Changed
- complete multifileupload refactoring (now working in fron and back end) with dropzone 4.x, see README.md for full feature overview

## [1.0.16] - 2016-11-22

#### Fixed
- added hideLabel eval dca support
- reformat to new psr (spaces)
