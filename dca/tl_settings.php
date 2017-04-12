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

$arrDca = &$GLOBALS['TL_DCA']['tl_settings'];

/**
 * Palettes
 */
$arrDca['palettes']['default'] = str_replace('imageHeight', 'imageHeight;{multifileupload_legend},enableMultiFileUploadFrontendStyles;', $arrDca['palettes']['default']);

/**
 * Fields
 */
$arrFields = [
    'enableMultiFileUploadFrontendStyles' => [
        'label'     => &$GLOBALS['TL_LANG']['tl_settings']['enableMultiFileUploadFrontendStyles'],
        'exclude'   => true,
        'inputType' => 'checkbox',
        'default'   => true,
        'eval'      => ['tl_class' => 'w50'],
    ],
];

$arrDca['fields'] = array_merge($arrFields, $arrDca['fields']);