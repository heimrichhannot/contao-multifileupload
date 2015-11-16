<?php

$GLOBALS['TL_LANG']['MSC']['dropzone']['labels'] = array(
	'head' => '',
	'body' => 'To upload a file, drop it here or click the field.',
	'foot' => ''
);

// dots in order to prevent contao from doing a replaceInsertTags() and remove them
$GLOBALS['TL_LANG']['MSC']['dropzone']['messages'] = array(
	'dictDefaultMessage' => 'Drop files here to upload',
	'dictFallbackMessage' => 'Your browser does not support drag\'n\'drop file uploads.',
	'dictFallbackText' => 'Please use the fallback form below to upload your files like in the olden days.',
	'dictFileTooBig' => 'File is too big ({.{filesize}.}MiB). Max filesize: {.{maxFilesize}.}MiB.',
	'dictInvalidFileType' => 'Falscher Typ.',
	'dictResponseError' => 'Server responded with {.{statusCode}.} code.',
	'dictCancelUpload' => 'Cancel upload',
	'dictCancelUploadConfirmation' => 'Are you sure you want to cancel this upload?',
	'dictRemoveFile' => 'Remove file',
	'dictRemoveFileConfirmation' => null,
	'dictMaxFilesExceeded' => 'You can not upload any more files.'
);