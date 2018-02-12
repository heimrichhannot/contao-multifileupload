<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */


/**
 * Register the namespaces
 */
ClassLoader::addNamespaces(
    [
	'HeimrichHannot',]
);


/**
 * Register the classes
 */
ClassLoader::addClasses(
    [
	// Forms
	'HeimrichHannot\MultiFileUpload\FormMultiFileUpload'           => 'system/modules/multifileupload/forms/FormMultiFileUpload.php',

	// Widgets
	'HeimrichHannot\MultiFileUpload\Widget\BackendMultiFileUpload' => 'system/modules/multifileupload/widgets/BackendMultiFileUpload.php',

	// Classes
	'HeimrichHannot\MultiFileUpload\MultiFileUpload'               => 'system/modules/multifileupload/classes/MultiFileUpload.php',]
);


/**
 * Register the templates
 */
TemplateLoader::addFiles(
    [
	'form_multifileupload_dropzone' => 'system/modules/multifileupload/templates/forms',]
);
