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

$GLOBALS['TL_LANG']['ERR']['invalidUuid'] = 'Der Datei wurde kein eindeutiger Kennzeichner (uuid) zugewiesen, bitte versuchen Sie die Datei erneut hochzuladen.';
$GLOBALS['TL_LANG']['ERR']['moveUploadFile'] = 'Ungültige Datei oder Fehler beim Verschieben der Datei (Berechtigungen des Speicherziels prüfen).';
$GLOBALS['TL_LANG']['ERR']['outsideUploadDirectory'] = 'Speicherziel liegt außerhalb des Contao-Upload-Verzeichnisses.';
$GLOBALS['TL_LANG']['ERR']['uploadNoUploadFolderDeclared'] = 'No valid "uploadFolder" in eval for field "%s" declared, files were not moved from "%s".';
$GLOBALS['TL_LANG']['ERR']['noUploadFolderDeclared'] = 'No "uploadFolder" in eval for field "%s" declared.';
$GLOBALS['TL_LANG']['ERR']['minWidth'] = 'Die Breite des Bildes darf %s Pixel nicht unterschreiten (Bildbreite: %s Pixel).';
$GLOBALS['TL_LANG']['ERR']['minHeight'] = 'Die Höhe des Bildes darf %s Pixel nicht unterschreiten (Bildhöhe: %s Pixel).';
$GLOBALS['TL_LANG']['ERR']['maxWidth'] = 'Die Breite des Bildes darf %s Pixel nicht überschreiten (Bildbreite: %s Pixel).';
$GLOBALS['TL_LANG']['ERR']['maxHeight'] = 'Die Höhe des Bildes darf %s Pixel nicht überschreiten (Bildhöhe: %s Pixel).';