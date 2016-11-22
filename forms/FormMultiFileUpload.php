<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2015 Heimrich & Hannot GmbH
 *
 * @package dropzone
 * @author  Rico Kaltofen <r.kaltofen@heimrich-hannot.de>
 * @license http://www.gnu.org/licences/lgpl-3.0.html LGPL
 */

namespace HeimrichHannot\MultiFileUpload;

use Contao\RequestToken;
use Contao\Validator;
use HeimrichHannot\Haste\Util\Files;

class FormMultiFileUpload extends \Upload
{

    // ajax-only atm, uploaded files are written to the session in order to save them to model data
    protected $blnSubmitInput = false;

    public function __construct($arrAttributes = null)
    {
        $this->strName     = $this->strId = $arrAttributes['name'];
        $this->explanation = $arrAttributes['explanation'];
        $this->mandatory   = $arrAttributes['mandatory'];
        $this->invisible   = $arrAttributes['invisible'];

        if (!is_array($arrAttributes['value']) && !Validator::isBinaryUuid($arrAttributes['value']))
        {
            $arrAttributes['value'] = json_decode($arrAttributes['value']);
        }

        // bin to string -> never pass binary to the widget!!
        if ($arrAttributes['value'])
        {
            if (is_array($arrAttributes['value']))
            {
                $arrAttributes['value'] = array_map(
                    function ($val)
                    {
                        return \Validator::isBinaryUuid($val) ? \StringUtil::binToUuid($val) : $val;
                    },
                    $arrAttributes['value']
                );
            }
            else
            {
                $arrAttributes['value'] = array(
                    \Validator::isBinaryUuid($arrAttributes['value']) ? \StringUtil::binToUuid($arrAttributes['value']) : $arrAttributes['value'],
                );
            }
        }

        //parent::__construct($arrAttributes);
        $this->objUploader = new MultiFileUpload($arrAttributes);

        // TODO atm ajax-only
        if (!\Input::get('isAjaxUpload'))
        {
            return;
        }

        $strAction = \Input::get('action');

        // check for the request token
        if (!\Input::get('requestToken') || !RequestToken::validate(\Input::get('requestToken')))
        {
            die('Invalid Request Token!');
        }

        $strUploadFolder = $this->objUploader->uploadFolder;

        if (\Validator::isUuid($strUploadFolder))
        {
            if (($strUploadFolder = Files::getPathFromUuid($strUploadFolder)) === null)
            {
                die('Invalid upload folder!');
            }
        }

        // create if not existing
        new \Folder($strUploadFolder);

        $strUploadFolder = rtrim($strUploadFolder, '/');

        if ($strAction && \Input::get('field') == $arrAttributes['name'])
        {
            if (\Input::get('uuid'))
            {
                switch ($strAction)
                {
                    case 'remove':
                        if (!is_array($_SESSION['uploadFilesToRemove'][$arrAttributes['name']]))
                        {
                            $_SESSION['uploadFilesToRemove'][$arrAttributes['name']] = array();
                        }

                        $_SESSION['uploadFilesToRemove'][$arrAttributes['name']][] = \Input::get('uuid');
                        break;
                }
            }

            return;
        }

        // Dropzone Upload
        if (!empty($_FILES))
        {
            $arrFiles = array();
            foreach ($_FILES as $strField => $arrFile)
            {
                if ($strField != $arrAttributes['name'])
                {
                    continue;
                }

                if (is_array($arrFile['name']))
                {
                    for ($i = 0; $i < count($arrFile['name']); $i++)
                    {
                        $arrResult = array();

                        foreach (array_keys($arrFile) as $strKey)
                        {
                            $arrResult[$strKey] = $arrFile[$strKey][$i];
                        }

                        $arrFiles[] = $this->uploadFile($arrAttributes['name'], $strField, $arrResult, $strUploadFolder);
                    }
                }
                else
                {
                    die(json_encode($this->uploadFile($arrAttributes['name'], $strField, $arrFile, $strUploadFolder)));
                }
            }

            if (!empty($arrFiles))
            {
                die(json_encode($arrFiles));
            }
        }
    }

    public function generateLabel()
    {
        if ($this->strLabel == '')
        {
            return '';
        }

        return sprintf(
            '<label%s%s>%s%s%s</label>',
            ($this->blnForAttribute ? ' for="ctrl_' . $this->strId . '"' : ''),
            (($this->strClass != '') ? ' class="' . $this->strClass . '"' : ''),
            ($this->mandatory ? '<span class="invisible">' . $GLOBALS['TL_LANG']['MSC']['mandatory'] . ' </span>' : ''),
            $this->strLabel,
            ($this->mandatory ? '<span class="mandatory">*</span>' : '')
        );
    }

    public function validator($varInput)
    {
        if ($varInput == '[]' || !$varInput)
        {
            if ($this->mandatory)
            {
                if ($this->strLabel == '')
                {
                    $this->addError($GLOBALS['TL_LANG']['ERR']['mdtryNoLabel']);
                }
                else
                {
                    $this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['mandatory'], $this->strLabel));
                }
            }
        }
    }


    protected function uploadFile($strFieldUpload, $strField, $arrFile, $strUploadFolder)
    {
        if (!$arrFile['error'] && $strFieldUpload == $strField)
        {
            $strTempFile = $arrFile['tmp_name'];

            $arrPath = pathinfo($arrFile['name']);

            // example testfile.png -> testfile_2015_12_29_16_08.png
            $strTargetFileName = Files::sanitizeFileName($arrPath['filename']) . '_' . uniqid() . '.' . $arrPath['extension'];
            $strTargetFile     = $strUploadFolder . '/' . $strTargetFileName;

            move_uploaded_file($strTempFile, TL_ROOT . '/' . $strTargetFile);

            // add db record
            $objFile = new \File($strTargetFile);
            $strUuid = \StringUtil::binToUuid(\Dbafs::addResource($objFile->value)->uuid);

            $arrData = array(
                'filename'     => $strTargetFileName,
                'filenameOrig' => $arrFile['name'],
                'uuid'         => $strUuid,
                'size'         => $objFile->filesize,
            );

            return $arrData;
        }
    }

    public function getUploader()
    {
        return $this->objUploader;
    }

    public function deleteScheduledFiles($arrScheduledFiles)
    {
        if (!empty($arrScheduledFiles))
        {
            foreach ($arrScheduledFiles as $strUuid)
            {
                if (($objFile = Files::getFileFromUuid($strUuid, true)) !== null && $objFile->exists())
                {
                    $objFile->delete();
                }
            }
        }
    }

}