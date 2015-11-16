<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @package Multifileupload
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */


/**
 * Register the namespaces
 */
ClassLoader::addNamespaces(array
(
	'HeimrichHannot',
));


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	// Forms
	'HeimrichHannot\MultiFileUpload\FormMultiFileUpload' => 'system/modules/multifileupload/forms/FormMultiFileUpload.php',

	// Classes
	'HeimrichHannot\MultiFileUpload\MultiFileUpload'     => 'system/modules/multifileupload/classes/MultiFileUpload.php',
));


/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	'form_multifileupload_dropzone' => 'system/modules/multifileupload/templates/forms',
	'j_multifileupload_dropzone'    => 'system/modules/multifileupload/templates/jquery',
));
