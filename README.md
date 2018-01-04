# Contao Multi File Upload

![](https://img.shields.io/packagist/v/heimrichhannot/contao-multifileupload.svg)
![](https://img.shields.io/packagist/l/heimrichhannot/contao-multifileupload.svg)
![](https://img.shields.io/packagist/dt/heimrichhannot/contao-multifileupload.svg)
[![](https://img.shields.io/travis/heimrichhannot/contao-multifileupload/master.svg)](https://travis-ci.org/heimrichhannot/contao-multifileupload/)
[![](https://img.shields.io/coveralls/heimrichhannot/contao-multifileupload/master.svg)](https://coveralls.io/github/heimrichhannot/contao-multifileupload)

Contao front end widget that provides [dropzonejs.com](http://www.dropzonejs.com/) functionality to both back and front end.
The javascript is written in native javascript and invoked for both jquery and mootools on "document ready" and "ajax complete".

![alt text](./doc/multifileupload-demo.jpg "Multifileupload demo within contao backend")


## Features

### Technical instructions

Use the inputType "multifileupload" for your field. In the backend, the widget is replaced by a "fileTree".

```
'client_logo' => array(
    'label'     => &$GLOBALS['TL_LANG']['tl_jobmarket_job']['client_logo'],
    'exclude'   => true,
    'inputType' => TL_MODE == 'BE' ? 'fileTree' : 'multifileupload',
    'eval'      => array(
        'tl_class'      => 'clr',
        'extensions'    => \Config::get('validImageTypes'),
        'filesOnly'     => true,
        'fieldType'     => 'radio',
        'addRemoveLinks' => true,
        'minImageWidth'       => '600px',
        'minImageHeight'      => '300px',
        'maxImageWidth'       => '1600px',
        'maxImageHeight'      => '1200px',
        'multipleFiles' => false,
        'labels'        => array(
            'head' => &$GLOBALS['TL_LANG']['tl_jobmarket_job']['client_logo']['messageText'][0],
            'body' => &$GLOBALS['TL_LANG']['tl_jobmarket_job']['client_logo']['messageText'][1],
        ),
        'skipDeleteAfterSubmit' => true
    ),
    'upload_path_callback' => array(array('MyClass', 'getJobUploadPath')),
    'validate_upload_callback' => array(array('MyClass', 'validateUpload')),
    'sql'       => "blob NULL",
),
```

### Flow chart

A flowchart with description of the full upload procedure with callback injection can be found here: [Flowchart](./doc/upload-flow-chart.html).

### Eval-Properties

Defined at your field's dca.

Name | Default | Description
---- | ------- | -----------
fieldType | 'checkbox' | If set to "checkbox", multiple files can be uploaded, for single upload set to 'radio'
extensions | \Config::get('uploadTypes') | A comma separated list of allowed file types (e.g. "jpg,png")
maxUploadSize | minimum of $GLOBALS['TL_CONFIG']['maxFileSize'] and php.ini 'upload_max_filesize' | The desired maximum upload size measured in Bytes (e.g. "100"), KiB, MiB or GiB (e.g. "10M"). Can not exceed $GLOBALS['TL_CONFIG']['maxFileSize'] or php upload_max_filesize value.
maxFiles | 10 | The maximum file count per field
uploadFolder | null | The upload folder as String, e.g. "files/uploads", function or array. **(must be declared !!!)**, required to move files to correct destination after submission.
addRemoveLinks | true | Remove links are added to each of the file avatars in the jquery (caption can be overwritten within language files)
minImageWidth | 0 | The minimum image width. Set to 0 for no min width image validation. All units from \Image::getPixelValue() are supported.
minImageHeight | 0 | The minimum image height. Set to 0 for no min height image validation. All units from \Image::getPixelValue() are supported.
maxImageWidth | 0 | The maximum image width. Set to 0 for no max width image validation. All units from \Image::getPixelValue() are supported.
maxImageHeight | 0 | The maximum image height. Set to 0 for no max image height validation. All units from \Image::getPixelValue() are supported.
minImageWidthErrorText | $GLOBALS['TL_LANG']['ERR']['minWidth'] | Custom error message for minimum image width. (arguments provided: 1 - minimum width from config, 2 - current image width)
minImageHeightErrorText | $GLOBALS['TL_LANG']['ERR']['minHeight'] | Custom error message for minimum image height. (arguments provided: 1 - minimum height from config, 2 - current image height)
maxImageWidthErrorText | $GLOBALS['TL_LANG']['ERR']['maxWidth'] | Custom error message for maximum image width. (arguments provided: 1 - maximum width from config, 2 - current image width)
maxImageHeightErrorText | $GLOBALS['TL_LANG']['ERR']['maxHeight'] | Custom error message for maximum image height. (arguments provided: 1 - maximum height from config, 2 - current image height)
createImageThumbnails | boolean(true) | Set to false if you dont want to preview thumbnails.
mimeFolder | system/modules/multifileupload/assets/img/mimetypes/Numix-uTouch | The relative path from contao root to custom mimetype folder, mimetypes.json and images must lie inside. (example: system/modules/multifileupload/assets/img/mimetypes/Numix-uTouch)
mimeThumbnailsOnly | boolean(false) | Set to true if you want to show mime image thumbnails only, and no image preview at all. (performance improvement)
thumbnailWidth | 90 | The thumbnail width (in px) of the uploaded file preview within the dropzone preview container.
thumbnailHeight | 90 | The thumbnail height (in px) of the uploaded file preview within the dropzone preview container.
labels | array() | Overwrite the head and body labels within the upload field.
skipDeleteAfterSubmit | false | Prevent file removal from filesystem.


### Field Callbacks

Type | Arguments | Expected return value | Description
---- | ---- | ---- | -----------
upload_path_callback | $strTarget, \File $objFile, \DataContainer $dc | $strTarget | Manipulate the upload path after form submission (run within onsubmit_callback).
validate_upload_callback | \File $objFile, \Widget $objWidget | boolean(false) or string with frontend error message | Validate the uploaded file and add an error message if file does not pass validation, otherwise boolean(false) is expected.

