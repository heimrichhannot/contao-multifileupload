<?php

$GLOBALS['TL_LANG']['MSC']['dropzone']['labels'] = array(
    'head' => '',
    'body' => 'Um eine Datei hochzuladen, ziehen Sie diese hierhin oder klicken Sie das Feld an.',
    'foot' => '',
);

// dots in order to prevent contao from doing a replaceInsertTags() and remove them
$GLOBALS['TL_LANG']['MSC']['dropzone']['messages'] = array(
    'dictDefaultMessage'           => 'Um eine Datei hochzuladen, ziehen Sie diese hierhin oder klicken Sie das Feld an.',
    'dictFallbackMessage'          => 'Ihr Browser unterstützt keine Drag&Drop-Uploads.',
    'dictFallbackText'             => 'Bitte nutzen Sie das untenstehende Formular für den Upload.',
    'dictFileTooBig'               => 'Die Datei ist zu groß ({.{filesize}.}MB). Max. Dateigröße: {.{maxFilesize}.}MB.',
    'dictInvalidFileType'          => 'Sie können keine Dateien dieses Typs hochladen.',
    'dictResponseError'            => 'Serverfehler {.{statusCode}.}.',
    'dictCancelUpload'             => 'Abbrechen',
    'dictCancelUploadConfirmation' => 'Möchten Sie den Upload wirklich abbrechen?',
    'dictRemoveFile'               => 'Entfernen',
    'dictRemoveFileConfirmation'   => null,
    'dictMaxFilesExceeded'         => 'Sie können nicht noch mehr Dateien hochladen.',
    'dictImageDimensionsTooSmall'  => 'Das Bild muss mindestens {.{minImageWidth}.}x{.{minImageHeight}.}px groß sein.',
);