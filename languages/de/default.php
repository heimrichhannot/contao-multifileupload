<?php

$GLOBALS['TL_LANG']['MSC']['dropzone']['labels'] = [
    'head' => '',
    'body' => 'Um eine Datei hochzuladen, ziehen Sie diese hierhin oder klicken Sie das Feld an.',
    'foot' => '',
];

// dots in order to prevent contao from doing a replaceInsertTags() and remove them
$GLOBALS['TL_LANG']['MSC']['dropzone']['messages'] = [
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
    'dictMaxFilesExceeded'         => 'Sie können nicht noch mehr Dateien hochladen.'
];

$GLOBALS['TL_LANG']['ERR']['illegalMimeType'] = 'Unerlaubter Dateityp: %s';
$GLOBALS['TL_LANG']['ERR']['illegalFileExtension'] = 'Unerlaubte Dateiendung: %s';
$GLOBALS['TL_LANG']['ERR']['invalidUuid'] = 'Der Datei wurde kein eindeutiger Kennzeichner (uuid) zugewiesen, bitte versuchen Sie die Datei erneut hochzuladen.';
$GLOBALS['TL_LANG']['ERR']['moveUploadFile'] = 'Ungültige Datei oder Fehler beim Verschieben der Datei (Berechtigungen des Speicherziels prüfen, Fehlermeldung: %s).';
$GLOBALS['TL_LANG']['ERR']['outsideUploadDirectory'] = 'Speicherziel liegt außerhalb des Contao-Upload-Verzeichnisses.';
$GLOBALS['TL_LANG']['ERR']['uploadNoUploadFolderDeclared'] = 'Kein gültiger "uploadFolder" für das Feld "%s" in eval angegeben, die Dateien wurden aus dem Verzeichnis "%s" nicht verschoben.';
$GLOBALS['TL_LANG']['ERR']['noUploadFolderDeclared'] = 'Kein "uploadFolder" für das Feld "%s" in eval angegeben.';
$GLOBALS['TL_LANG']['ERR']['minWidth'] = 'Die Breite des Bildes darf %s Pixel nicht unterschreiten (aktuelle Bildbreite: %s Pixel).';
$GLOBALS['TL_LANG']['ERR']['minHeight'] = 'Die Höhe des Bildes darf %s Pixel nicht unterschreiten (aktuelle Bildhöhe: %s Pixel).';
$GLOBALS['TL_LANG']['ERR']['maxWidth'] = 'Die Breite des Bildes darf %s Pixel nicht überschreiten (aktuelle Bildbreite: %s Pixel).';
$GLOBALS['TL_LANG']['ERR']['maxHeight'] = 'Die Höhe des Bildes darf %s Pixel nicht überschreiten (aktuelle Bildhöhe: %s Pixel).';