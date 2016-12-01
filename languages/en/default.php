<?php

$GLOBALS['TL_LANG']['MSC']['dropzone']['labels'] = array(
    'head' => '',
    'body' => 'To upload a file, drop it here or click the field.',
    'foot' => '',
);

// dots in order to prevent contao from doing a replaceInsertTags() and remove them
$GLOBALS['TL_LANG']['MSC']['dropzone']['messages'] = array(
    'dictDefaultMessage'           => 'Drop files here to upload',
    'dictFallbackMessage'          => 'Your browser does not support drag\'n\'drop file uploads.',
    'dictFallbackText'             => 'Please use the fallback form below to upload your files like in the olden days.',
    'dictFileTooBig'               => 'File is too big ({.{filesize}.}MiB). Max filesize: {.{maxFilesize}.}MiB.',
    'dictInvalidFileType'          => 'Falscher Typ.',
    'dictResponseError'            => 'Server responded with {.{statusCode}.} code.',
    'dictCancelUpload'             => 'Cancel upload',
    'dictCancelUploadConfirmation' => 'Are you sure you want to cancel this upload?',
    'dictRemoveFile'               => 'Remove file',
    'dictRemoveFileConfirmation'   => null,
    'dictMaxFilesExceeded'         => 'You can not upload any more files.',
);

$GLOBALS['TL_LANG']['ERR']['invalidUuid'] = 'No unique identifier (uuid) has been assigned to the file, please try to upload the file again.';
$GLOBALS['TL_LANG']['ERR']['moveUploadFile'] = 'Invalid file or error on moving files, check permissions or upload destination.';
$GLOBALS['TL_LANG']['ERR']['outsideUploadDirectory'] = 'Upload destination lies outside the contao upload directory.';
$GLOBALS['TL_LANG']['ERR']['uploadNoUploadFolderDeclared'] = 'Kein gültiger "uploadFolder" für das Feld "%s" in eval angegeben, die Dateien wurden aus dem Verzeichnis "%s" nicht verschoben.';
$GLOBALS['TL_LANG']['ERR']['noUploadFolderDeclared'] = 'Kein "uploadFolder" für das Feld "%s" in eval angegeben.';
$GLOBALS['TL_LANG']['ERR']['minWidth'] = 'The width of the image must not be less than %s pixel (image width: %s pixel).';
$GLOBALS['TL_LANG']['ERR']['minHeight'] = 'The height of the image must not be less than %s pixel (image width: %s pixel).';
$GLOBALS['TL_LANG']['ERR']['maxWidth'] = 'The width of the image must not exceed %s pixel (image width: %s pixel).';
$GLOBALS['TL_LANG']['ERR']['maxHeight'] = 'The height of the image must not exceed %s pixel (image width: %s pixel).';