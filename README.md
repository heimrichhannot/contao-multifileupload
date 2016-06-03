# Contao Multi File Upload

Contao front end widget that provides [dropzonejs.com](http://www.dropzonejs.com/) functionality.

Currently the widget only works in combination with [heimrichhannot/contao-formhybrid](https://github.com/heimrichhannot/contao-formhybrid).

## Features

### Technical instructions

Use the inputType "multifileupload" for your field. In the backend, the widget is replaced by a "fileTree".

### Eval-Properties

Defined at your field's dca.

Name | Default | Description
---- | ------- | -----------
fieldType | false | If set to "checkbox", multiple files can be uploaded
extensions | \Config::get('uploadTypes') | A comma separated list of allowed file types (e.g. "jpg,png")
maxUploadSize | php.ini value "upload_max_filesize" in MB | The desired maximum upload size measured in MB
maxFiles | infinite | The maximum file count per field
uploadFolder | \Config::get('uploadPath') | The upload folder as String, e.g. "files/uploads"
addRemoveLinks | flase | Remove links are added to each of the file avatars in the jquery
minImageWidth | 0 | The minimum image width
minImageHeight | 0 | The minimum image height