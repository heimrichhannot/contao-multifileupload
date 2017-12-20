<?php
/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2016 Heimrich & Hannot GmbH
 *
 * @author  Rico Kaltofen <r.kaltofen@heimrich-hannot.de>
 * @license http://www.gnu.org/licences/lgpl-3.0.html LGPL
 */

namespace HeimrichHannot\MultiFileUpload\Widget;

use HeimrichHannot\Ajax\Response\Response;
use HeimrichHannot\Ajax\Response\ResponseError;
use HeimrichHannot\Haste\Dca\General;
use HeimrichHannot\MultiFileUpload\FormMultiFileUpload;
use HeimrichHannot\MultiFileUpload\MultiFileUpload;
use HeimrichHannot\Request\Request;

class BackendMultiFileUpload extends FormMultiFileUpload
{
    protected static $uploadAction = 'multifileupload_upload';

    public function __construct($arrAttributes = null)
    {
        parent::__construct($arrAttributes);
    }

    public function executePostActionsHook($strAction, \DataContainer $dc)
    {
        if ($strAction !== static::$uploadAction)
        {
            return false;
        }

        $fields = \Session::getInstance()->get(MultiFileUpload::SESSION_FIELD_KEY);

        // Check whether the field is allowed for regular users
        if (!isset($fields[$dc->table][Request::getPost('field')]) || ($fields[$dc->table]['fields'][Request::getPost('field')]['exclude'] && !\BackendUser::getInstance()->hasAccess($dc->table . '::' . \Input::post('field'), 'alexf')))
        {
            \System::log('Field "' . \Input::post('field') . '" is not an allowed selector field (possible SQL injection attempt)', __METHOD__, TL_ERROR);

            $objResponse = new ResponseError();
            $objResponse->setMessage('Bad Request');
            $objResponse->output();
        }

        if ($dc->activeRecord === null)
        {
            $dc->activeRecord = General::getModelInstance($dc->table, $dc->id);
        }

        // add dca attributes and instantiate current object to set widget attributes
        $arrAttributes = \Widget::getAttributesFromDca($fields[$dc->table][Request::getPost('field')], Request::getPost('field'));
        $objUploader   = new static($arrAttributes);
        $objResponse   = $objUploader->upload();

        /** @var Response */
        if ($objResponse instanceof Response)
        {
            $objResponse->output();
        }
    }

}