<?php
/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2015 Heimrich & Hannot GmbH
 *
 * @package multifileupload
 * @author  Rico Kaltofen <r.kaltofen@heimrich-hannot.de>
 * @license http://www.gnu.org/licences/lgpl-3.0.html LGPL
 */


/**
 *  Default config
 */
if(!isset($GLOBALS['TL_CONFIG']['enableMultiFileUploadFrontendStyles']))
{
    $GLOBALS['TL_CONFIG']['enableMultiFileUploadFrontendStyles'] = true;
}

/**
 * Front end form fields
 */
$GLOBALS['TL_FFL']['multifileupload'] = 'HeimrichHannot\MultiFileUpload\FormMultiFileUpload';
$GLOBALS['BE_FFL']['multifileupload'] = 'HeimrichHannot\MultiFileUpload\Widget\BackendMultiFileUpload';

/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['executePostActions']['multifileupload'] = ['HeimrichHannot\MultiFileUpload\Widget\BackendMultiFileUpload', 'executePostActionsHook'];

/**
 * Ajax action
 */
$GLOBALS['AJAX'][\HeimrichHannot\MultiFileUpload\MultiFileUpload::NAME] = [
    'actions' => [
        \HeimrichHannot\MultiFileUpload\MultiFileUpload::ACTION_UPLOAD => [
            'arguments'       => [],
            'optional'        => [],
            'csrf_protection' => true, // cross-site request forgery (ajax token check)
        ],
    ],
];

/**
 * Assets (add dropzone not within contao files manager)
 */
if (TL_MODE == 'FE')
{
    $strBasePath = version_compare(VERSION, '4.0', '<') ? 'composer/vendor/enyo/dropzone' : 'assets/dropzone4';

    if (\Config::get('enableMultiFileUploadFrontendStyles'))
    {
        $GLOBALS['TL_CSS']['dropzone'] = 'system/modules/multifileupload/assets/css/dropzone.css';
    }

    $GLOBALS['TL_JAVASCRIPT']['dropzone']        = $strBasePath . '/dist/min/dropzone.min.js';
    $GLOBALS['TL_JAVASCRIPT']['multifileupload'] = 'system/modules/multifileupload/assets/js/multifileupload.min.js';
}

if (TL_MODE == 'BE' && \Input::get('do') != 'files')
{
    $strBasePath = version_compare(VERSION, '4.0', '<') ? 'composer/vendor/enyo/dropzone' : 'assets/dropzone4';

    $GLOBALS['TL_CSS']['dropzone'] = 'system/modules/multifileupload/assets/css/dropzone.css';

    $GLOBALS['TL_JAVASCRIPT']['dropzone']        = $strBasePath . '/dist/min/dropzone.min.js';
    $GLOBALS['TL_JAVASCRIPT']['multifileupload'] = 'system/modules/multifileupload/assets/js/multifileupload.min.js';
}
