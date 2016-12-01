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

namespace HeimrichHannot\MultiFileUpload;

use Contao\FilesModel;
use Contao\RequestToken;
use Contao\Validator;
use HeimrichHannot\Ajax\Ajax;
use HeimrichHannot\Ajax\AjaxAction;
use HeimrichHannot\Ajax\Response\ResponseData;
use HeimrichHannot\Ajax\Response\ResponseError;
use HeimrichHannot\Ajax\Response\ResponseSuccess;
use HeimrichHannot\Haste\Util\Files;
use HeimrichHannot\Haste\Util\StringUtil;
use HeimrichHannot\Request\Request;

class FormMultiFileUpload extends \Upload
{
    /**
     * Submit user input
     *
     * @var boolean
     */
    protected $blnSubmitInput = true;

    protected static $uploadAction = 'upload';

    public function __construct($arrAttributes = null)
    {
        // check against arrAttributes, as 'onsubmit_callback' => 'multifileupload_moveFiles' does not provide valid attributes
        if ($arrAttributes !== null && !$arrAttributes['uploadFolder'])
        {
            throw new \Exception(
                sprintf($GLOBALS['TL_LANG']['ERR']['noUploadFolderDeclared'], $this->name)
            );
        }

        $arrAttributes['uploadAction'] = static::$uploadAction;

        if (TL_MODE == 'FE')
        {
            $arrAttributes['uploadActionParams'] = http_build_query(AjaxAction::getParams(MultiFileUpload::NAME, static::$uploadAction));
        }

        $arrAttributes['addRemoveLinks'] = isset($arrAttributes['addRemoveLinks']) ? $arrAttributes['addRemoveLinks'] : true;


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
                    \Validator::isBinaryUuid($arrAttributes['value']) ? \StringUtil::binToUuid(
                        $arrAttributes['value']
                    ) : $arrAttributes['value'],
                );
            }
        }

        parent::__construct($arrAttributes);

        $this->objUploader = new MultiFileUpload($arrAttributes);

        // add onsubmit_callback: move files after form submission
        $GLOBALS['TL_DCA'][$this->strTable]['config']['onsubmit_callback']['multifileupload_moveFiles'] =
            array('HeimrichHannot\MultiFileUpload\FormMultiFileUpload', 'moveFiles');

        Ajax::runActiveAction(MultiFileUpload::NAME, MultiFileUpload::ACTION_UPLOAD, $this);
    }

    public function moveFiles(\DataContainer $dc)
    {
        $arrPost = Request::getPost();

        foreach ($arrPost as $key => $value)
        {
            $arrData = $GLOBALS['TL_DCA'][$dc->table]['fields'][$key];

            if ($arrData['inputType'] != MultiFileUpload::NAME)
            {
                continue;
            }

            $arrFiles = deserialize($dc->activeRecord->{$key});

            $strUploadFolder = Files::getFolderFromDca($arrData['eval']['uploadFolder'], $dc);

            if ($strUploadFolder === null)
            {
                throw new \Exception(
                    sprintf($GLOBALS['TL_LANG']['ERR']['uploadNoUploadFolderDeclared'], $key, MultiFileUpload::UPLOAD_TMP)
                );
            }

            if (!is_array($arrFiles))
            {
                $arrFiles = array($arrFiles);
            }

            $objFileModels = FilesModel::findMultipleByUuids($arrFiles);

            if ($objFileModels === null)
            {
                continue;
            }

            $arrPaths   = $objFileModels->fetchEach('path');
            $arrTargets = array();

            // do not loop over $objFileModels as $objFile->close() will pull models away
            foreach ($arrPaths as $strPath)
            {
                $objFile   = new \File($strPath);
                $strName   = $objFile->name;
                $strTarget = $strUploadFolder . '/' . $strName;

                // upload_path_callback
                if (is_array($arrData['upload_path_callback']))
                {
                    foreach ($arrData['upload_path_callback'] as $callback)
                    {
                        $strTarget = \System::importStatic($callback[0])->{$callback[1]}($strTarget, $objFile, $dc) ?: $strTarget;
                    }
                }

                if (StringUtil::startsWith($objFile->path, ltrim($strTarget, '/')))
                {
                    continue;
                }


                if ($objFile->renameTo($strTarget))
                {
                    $arrTargets[] = $strTarget;
                    $objFile->close();
                    continue;
                }

                $arrTargets[] = $strPath;
            }

            // HOOK: post upload callback
            if (isset($GLOBALS['TL_HOOKS']['postUpload']) && is_array($GLOBALS['TL_HOOKS']['postUpload']))
            {
                foreach ($GLOBALS['TL_HOOKS']['postUpload'] as $callback)
                {
                    if (is_array($callback))
                    {
                        \System::importStatic($callback[0])->{$callback[1]}($arrTargets);
                    }
                    elseif (is_callable($callback))
                    {
                        $callback($arrTargets);
                    }
                }
            }
        }
    }

    public function upload()
    {
        // check for the request token
        if (!\Input::post('requestToken') || !RequestToken::validate(\Input::post('requestToken')))
        {
            $objResponse = new ResponseError();
            $objResponse->setMessage('Invalid Request Token!');
            $objResponse->output();
        }

        $objTmpFolder = new \Folder(MultiFileUpload::UPLOAD_TMP);

        $arrUuids  = null;
        $varReturn = null;

        // Dropzone Upload
        if (!empty($_FILES))
        {
            if (!isset($_FILES[$this->name]))
            {
                return;
            }

            $strField = $this->name;
            $varFile  = $_FILES[$strField];

            // Multi-files upload at once
            if (is_array($varFile['name']))
            {
                for ($i = 0; $i < count($varFile['name']); $i++)
                {
                    $arrFiles = array();

                    foreach (array_keys($varFile) as $strKey)
                    {
                        $arrFiles[$strKey] = $varFile[$strKey][$i];
                    }

                    $arrFile     = $this->uploadFile($arrFiles, $objTmpFolder->path, $strField);
                    $varReturn[] = $arrFile;
                    $arrUuids[]  = $arrFile['uuid'];
                }
            }
            // Single-file upload
            else
            {
                $varReturn  = $this->uploadFile($varFile, $objTmpFolder->path, $strField);
                $arrUuids[] = $varReturn['uuid'];
            }

            if ($varReturn !== null)
            {
                $this->varValue = $arrUuids;
                $objResponse    = new ResponseSuccess();
                $objResult      = new ResponseData();
                $objResult->setData($varReturn);
                $objResponse->setResult($objResult);

                return $objResponse;
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
        if ($varInput == '' || $varInput == '[]')
        {
            $varInput = '[]';
        }

        $arrFiles   = json_decode($varInput);
        $arrDeleted = json_decode(($this->getPost('deleted_' . $this->strName)));
        $blnEmpty   = false;

        if (is_array($arrFiles) && is_array($arrDeleted))
        {
            $blnEmpty = empty(array_diff($arrFiles, $arrDeleted));
        }

        if ($this->mandatory && $blnEmpty)
        {
            if ($this->strLabel == '')
            {
                $this->addError($GLOBALS['TL_LANG']['ERR']['mdtryNoLabel']);
            }
            else
            {
                $this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['mandatory'], $this->strLabel));
            }

            // do no delete last file if mandatory
            return;
        }

        $this->deleteScheduledFiles($arrDeleted);

        if (is_array($arrFiles))
        {
            foreach ($arrFiles as $k => $v)
            {
                if (!\Validator::isUuid($v))
                {
                    $this->addError($GLOBALS['TL_LANG']['ERR']['invalidUuid']);

                    return;
                }

                // cleanup non existing files on save
                if (($objFile = Files::getFileFromUuid($v)) === null || !$objFile->exists())
                {
                    unset($arrFiles[$k]);
                    continue;
                }

                $arrFiles[$k] = \StringUtil::uuidToBin($v);
            }
        }
        else
        {
            if (!\Validator::isUuid($arrFiles))
            {
                $this->addError($GLOBALS['TL_LANG']['ERR']['invalidUuid']);

                return;
            }

            // cleanup non existing files on save
            if (($objFile = Files::getFileFromUuid($arrFiles)) === null || !$objFile->exists())
            {
                return;
            }

            $arrFiles = \StringUtil::uuidToBin($arrFiles);
        }

        return $arrFiles;
    }

    /**
     * Validate the uploaded file
     *
     * @param \File $objFile
     *
     * @return string|boolean The error message or false for no error
     */
    protected function validateUpload(\File $objFile)
    {
        $error = false;

        if($objFile->isImage)
        {
            $minWidth = \Image::getPixelValue($this->minImageWidth);
            $minHeight = \Image::getPixelValue($this->minImageHeight);

            $maxWidth = \Image::getPixelValue($this->maxImageWidth);
            $maxHeight = \Image::getPixelValue($this->maxImageHeight);

            if($minWidth > 0 && $objFile->width < $minWidth)
            {
                return sprintf($GLOBALS['TL_LANG']['ERR']['minWidth'], $minWidth, $objFile->width);
            }

            if($minHeight > 0 && $objFile->height < $minHeight)
            {
                return sprintf($GLOBALS['TL_LANG']['ERR']['minHeight'], $minHeight, $objFile->height);
            }

            if($maxWidth > 0 && $objFile->width > $maxWidth)
            {
                return sprintf($GLOBALS['TL_LANG']['ERR']['maxWidth'], $maxWidth, $objFile->width);
            }

            if($maxHeight > 0 && $objFile->height > $maxHeight)
            {
                return sprintf($GLOBALS['TL_LANG']['ERR']['maxHeight'], $maxHeight, $objFile->height);
            }
        }

        return $error;
    }

    /**
     * Upload a file, store to $strUploadFolder and create database entry
     *
     * @param $arrFile         The $_FILES array
     * @param $strUploadFolder The upload target folder within contao files folder
     * @param $strField
     *
     * @return array|bool Returns array with file information on success. Returns false if no valid file, file cannot be moved or destination lies outside the
     *                    contao upload directory.
     */
    protected function uploadFile($arrFile, $strUploadFolder, $strField)
    {
        if (!$arrFile['error'])
        {
            $error       = false;
            $strTempFile = $arrFile['tmp_name'];

            $arrPath = pathinfo($arrFile['name']);

            $strTargetFileName = standardize($arrPath['filename']) . '_' . uniqid() . '.' . strtolower($arrPath['extension']);
            $strTargetFile     = $strUploadFolder . '/' . $strTargetFileName;

            if (!move_uploaded_file($strTempFile, TL_ROOT . '/' . $strTargetFile))
            {
                $error = &$GLOBALS['TL_LANG']['ERR']['moveUploadFile'];
            }

            $arrData = array(
                'filename'     => $strTargetFileName,
                'filenameOrig' => $arrFile['name'],
            );

            if (!$error)
            {
                try
                {
                    // add db record
                    $objFile = new \File($strTargetFile);
                    $objFile->close();
                    $strUuid = $objFile->getModel()->uuid;
                } catch (\InvalidArgumentException $e)
                {
                    $error = &$GLOBALS['TL_LANG']['ERR']['outsideUploadDirectory'];
                }
            }

            if (!$error && $objFile instanceof \File)
            {
                $error = $this->validateUpload($objFile);

                // upload_path_callback
                if (is_array($this->validate_upload_callback))
                {
                    foreach ($this->validate_upload_callback as $callback)
                    {
                        if (!class_exists($callback[0]))
                        {
                            continue;
                        }

                        $objCallback = \System::importStatic($callback[0]);

                        if (!method_exists($objCallback, $callback[1]))
                        {
                            continue;
                        }

                        if ($errorCallback = $objCallback->{$callback[1]}($objFile, $this))
                        {
                            $error = $errorCallback;
                            break; // stop validation on first error
                        }
                    }
                }
            }


            if ($error === false && ($arrInfo = $this->objUploader->prepareFile($strUuid)) !== false)
            {
                $arrData = array_merge($arrData, $arrInfo);

                return $arrData;
            }

            $arrData['error'] = $error;

            // remove invalid files from tmp folder
            if($objFile instanceof \File)
            {
                $objFile->delete();
            }

            return $arrData;
        }

        return array(
            'error' => $arrFile['error'],
            'filenameOrig' => $arrFile['name']
        );
    }

    public function getUploader()
    {
        return $this->objUploader;
    }

    public function deleteScheduledFiles($arrScheduledFiles)
    {
        $arrFiles = array();

        if (empty($arrScheduledFiles))
        {
            return $arrFiles;
        }

        foreach ($arrScheduledFiles as $strUuid)
        {
            if (($objFile = Files::getFileFromUuid($strUuid, true)) !== null && $objFile->exists())
            {
                if ($objFile->delete() === true)
                {
                    $arrFiles[] = $strUuid;
                }
            }
        }
    }

}