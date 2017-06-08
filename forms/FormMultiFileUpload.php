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
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FormMultiFileUpload extends \Upload
{
    /**
     * Submit user input
     *
     * @var boolean
     */
    protected $blnSubmitInput = true;

    protected static $uploadAction = 'upload';

    /**
     * For binary(16) fields the values must be provided as single field
     *
     * @var bool
     */
    protected $blnSingleFile = false;

    const UNIQID_PREFIX = 'mfuid';

    public function __construct($arrAttributes = null)
    {
        // this is the case for 'onsubmit_callback' => 'multifileupload_moveFiles'
        if ($arrAttributes === null)
        {
            $arrAttributes                     = [];
            $arrAttributes['isSubmitCallback'] = true;
        }

        // check against arrAttributes, as 'onsubmit_callback' => 'multifileupload_moveFiles' does not provide valid attributes
        if (!$arrAttributes['isSubmitCallback'] && !$arrAttributes['uploadFolder'])
        {
            throw new \Exception(
                sprintf($GLOBALS['TL_LANG']['ERR']['noUploadFolderDeclared'], $this->name)
            );
        }

        if ($arrAttributes !== null && $arrAttributes['strTable'])
        {
            $arrTableFields = \Database::getInstance()->listFields($arrAttributes['strTable']);

            foreach ($arrTableFields as $arrField)
            {
                if ($arrField['name'] == $arrAttributes['name'] && $arrField['type'] != 'index' && $arrField['type'] == 'binary')
                {
                    $this->blnSingleFile = true;
                    break;
                }
            }
        }

        $arrAttributes['uploadAction'] = static::$uploadAction;

        if (TL_MODE == 'FE')
        {
            $arrAttributes['uploadActionParams'] = http_build_query(AjaxAction::getParams(MultiFileUpload::NAME, static::$uploadAction));
        }

        $arrAttributes['parallelUploads'] = 1; // in order to provide new token for each ajax request, upload one by one

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
                $arrAttributes['value'] = [
                    \Validator::isBinaryUuid($arrAttributes['value']) ? \StringUtil::binToUuid(
                        $arrAttributes['value']
                    ) : $arrAttributes['value'],
                ];
            }
        }

        parent::__construct($arrAttributes);

        $this->objUploader = new MultiFileUpload($arrAttributes, $this);

        $arrAttributes = array_merge($arrAttributes, $this->objUploader->getData());

        foreach ($arrAttributes as $strKey => $varValue)
        {
            $this->{$strKey} = $varValue;
        }

        // add onsubmit_callback at first onsubmit_callback position: move files after form submission
        if (is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['onsubmit_callback']))
        {
            $GLOBALS['TL_DCA'][$this->strTable]['config']['onsubmit_callback'] =
                ['multifileupload_moveFiles' => ['HeimrichHannot\MultiFileUpload\FormMultiFileUpload', 'moveFiles']]
                + $GLOBALS['TL_DCA'][$this->strTable]['config']['onsubmit_callback'];
        }
        else
        {
            $GLOBALS['TL_DCA'][$this->strTable]['config']['onsubmit_callback']['multifileupload_moveFiles'] = ['HeimrichHannot\MultiFileUpload\FormMultiFileUpload', 'moveFiles'];
        }

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
                $arrFiles = [$arrFiles];
            }

            $objFileModels = FilesModel::findMultipleByUuids($arrFiles);

            if ($objFileModels === null)
            {
                continue;
            }

            $arrPaths   = $objFileModels->fetchEach('path');
            $arrTargets = [];

            // do not loop over $objFileModels as $objFile->close() will pull models away
            foreach ($arrPaths as $strPath)
            {
                $objFile   = new \File($strPath);
                $strTarget = $strTarget = $strUploadFolder . '/' . $objFile->name;

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

                $strTarget = Files::getUniqueFileNameWithinTarget($strTarget, static::UNIQID_PREFIX);

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
        if (!Request::hasPost('requestToken') || !RequestToken::validate(Request::getPost('requestToken')))
        {
            $objResponse = new ResponseError();
            $objResponse->setMessage('Invalid Request Token!');
            $objResponse->output();
        }

        if (!Request::getInstance()->files->has($this->name))
        {
            return;
        }

        $objTmpFolder = new \Folder(MultiFileUpload::UPLOAD_TMP);

        $strField = $this->name;
        $varFile  = Request::getInstance()->files->get($strField);

        // Multi-files upload at once
        if (is_array($varFile))
        {
            // prevent disk flooding
            if (count($varFile) > $this->maxFiles)
            {
                $objResponse = new ResponseError();
                $objResponse->setMessage('Bulk file upload violation.');
                $objResponse->output();
            }

            /**
             * @var UploadedFile $objFile
             */
            foreach ($varFile as $strKey => $objFile)
            {
                $arrFile     = $this->uploadFile($objFile, $objTmpFolder->path, $strField);
                $varReturn[] = $arrFile;

                if (\Validator::isUuid($arrFile['uuid']))
                {
                    $arrUuids[] = $arrFile['uuid'];
                }
            }
        }
        // Single-file upload
        else
        {
            /**
             * @var UploadedFile $varFile
             */
            $varReturn = $this->uploadFile($varFile, $objTmpFolder->path, $strField);

            if (\Validator::isUuid($varReturn['uuid']))
            {
                $arrUuids[] = $varReturn['uuid'];
            }
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

        if (!$this->skipDeleteAfterSubmit)
        {
            $this->deleteScheduledFiles($arrDeleted);
        }

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

        return $this->blnSingleFile ? reset($arrFiles) : $arrFiles;
    }

    /**
     * Validate a given extension
     *
     * @param UploadedFile $objUploadFile The uploaded file object
     *
     * @return string|boolean The error message or false for no error
     */
    protected function validateExtension(UploadedFile $objUploadFile)
    {
        $error = false;

        $strAllowed = $this->extensions ?: \Config::get('uploadTypes');

        $arrAllowed = trimsplit(',', $strAllowed);

        $strExtension = $objUploadFile->getClientOriginalExtension();

        if (!$strExtension || !is_array($arrAllowed) || !in_array($strExtension, $arrAllowed))
        {
            return sprintf(sprintf($GLOBALS['TL_LANG']['ERR']['illegalFileExtension'], $strExtension));
        }

        // compare client mime type with mime type check result from server (e.g. user uploaded a php file with jpg extension)
        if (!$this->validateMimeType($objUploadFile->getClientMimeType(), $objUploadFile->getMimeType()))
        {
            return sprintf(sprintf($GLOBALS['TL_LANG']['ERR']['illegalMimeType'], $objUploadFile->getMimeType()));
        }

        return $error;
    }

    protected function validateMimeType($mimeClient, $mimeDetected)
    {
        if ($mimeClient !== $mimeDetected)
        {
            // allow safe mime types
            switch ($mimeDetected)
            {
                // css files might be detected as the following instead of 'text/css'
                case 'text/x-asm':
                // csv files might be detected as the following instead of 'text/csv'
                case 'text/plain':
                case 'text/csv':
                case 'text/x-csv':
                case 'text/comma-separated-values':
                case 'text/x-comma-separated-values':
                case 'text/tab-separated-values':
                    return true;
                    break;
            }

            return false;
        }

        return true;
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

        if ($objFile->isImage)
        {
            $minWidth  = \Image::getPixelValue($this->minImageWidth);
            $minHeight = \Image::getPixelValue($this->minImageHeight);

            $maxWidth  = \Image::getPixelValue($this->maxImageWidth);
            $maxHeight = \Image::getPixelValue($this->maxImageHeight);

            if ($minWidth > 0 && $objFile->width < $minWidth)
            {
                return sprintf($this->minImageWidthErrorText ?: $GLOBALS['TL_LANG']['ERR']['minWidth'], $minWidth, $objFile->width);
            }

            if ($minHeight > 0 && $objFile->height < $minHeight)
            {
                return sprintf($this->minImageHeightErrorText ?: $GLOBALS['TL_LANG']['ERR']['minHeight'], $minHeight, $objFile->height);
            }

            if ($maxWidth > 0 && $objFile->width > $maxWidth)
            {
                return sprintf($this->maxImageWidthErrorText ?: $GLOBALS['TL_LANG']['ERR']['maxWidth'], $maxWidth, $objFile->width);
            }

            if ($maxHeight > 0 && $objFile->height > $maxHeight)
            {
                return sprintf($this->maxImageHeightErrorText ?: $GLOBALS['TL_LANG']['ERR']['maxHeight'], $maxHeight, $objFile->height);
            }
        }

        return $error;
    }

    /**
     * Upload a file, store to $strUploadFolder and create database entry
     *
     * @param $objUploadFile         UploadedFile        The uploaded file object
     * @param $strUploadFolder       The upload target folder within contao files folder
     * @param $strField
     *
     * @return array|bool Returns array with file information on success. Returns false if no valid file, file cannot be moved or destination lies outside the
     *                    contao upload directory.
     */
    protected function uploadFile($objUploadFile, $strUploadFolder, $strField)
    {
        $strOriginalFileName        = rawurldecode($objUploadFile->getClientOriginalName()); // e.g. double quotes are escaped with %22 -> decode it
        $strOriginalFileNameEncoded = rawurlencode($strOriginalFileName);
        $strSanitizedFileName       = Files::sanitizeFileName($objUploadFile->getClientOriginalName());

        if ($objUploadFile->getError())
        {
            return [
                'error'               => $objUploadFile->getError(),
                'filenameOrigEncoded' => $strOriginalFileNameEncoded,
                'filenameSanitized'   => $strSanitizedFileName,
            ];
        }

        $error = false;

        $strTargetFileName = Files::addUniqIdToFilename($strSanitizedFileName, static::UNIQID_PREFIX);

        if (($error = $this->validateExtension($objUploadFile)) !== false)
        {
            return [
                'error'               => $error,
                'filenameOrigEncoded' => $strOriginalFileNameEncoded,
                'filenameSanitized'   => $strSanitizedFileName,
            ];
        }

        try
        {
            $objUploadFile = $objUploadFile->move(TL_ROOT . '/' . $strUploadFolder, $strTargetFileName);
        } catch (FileException $e)
        {
            return [
                'error'               => sprintf($GLOBALS['TL_LANG']['ERR']['moveUploadFile'], $e->getMessage()),
                'filenameOrigEncoded' => $strOriginalFileNameEncoded,
                'filenameSanitized'   => $strSanitizedFileName,
            ];
        }

        $arrData = [
            'filename'            => $strTargetFileName,
            'filenameOrigEncoded' => $strOriginalFileNameEncoded,
            'filenameSanitized'   => $strSanitizedFileName,
        ];

        $strRelativePath = ltrim(str_replace(TL_ROOT, '', $objUploadFile->getRealPath()), DIRECTORY_SEPARATOR);

        $objFile  = null;
        $objModel = null;

        try
        {
            // add db record
            $objFile = new \File($strRelativePath);
            $objFile->close();
            $objModel = $objFile->getModel();
            $strUuid  = $objFile->getModel()->uuid;
        } catch (\InvalidArgumentException $e)
        {
            // remove file from file system
            @unlink(TL_ROOT . '/' . $strRelativePath);

            return [
                'error'               => $GLOBALS['TL_LANG']['ERR']['outsideUploadDirectory'],
                'filenameOrigEncoded' => $strOriginalFileNameEncoded,
                'filenameSanitized'   => $strSanitizedFileName,
            ];
        }

        if (!$objFile instanceof \File || $objModel === null)
        {
            // remove file from file system
            @unlink(TL_ROOT . '/' . $strRelativePath);

            return [
                'error'               => $GLOBALS['TL_LANG']['ERR']['outsideUploadDirectory'],
                'filenameOrigEncoded' => $strOriginalFileNameEncoded,
                'filenameSanitized'   => $strSanitizedFileName,
            ];
        }

        if (($error = $this->validateUpload($objFile)) !== false)
        {
            return [
                'error'               => $error,
                'filenameOrigEncoded' => $strOriginalFileNameEncoded,
                'filenameSanitized'   => $strSanitizedFileName,
            ];
        }

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


        if ($error === false && ($arrInfo = $this->objUploader->prepareFile($strUuid)) !== false)
        {
            $arrData = array_merge($arrData, $arrInfo);

            return $arrData;
        }

        $arrData['error'] = $error;

        // remove invalid files from tmp folder
        if ($objFile instanceof \File)
        {
            $objFile->delete();
        }

        return $arrData;
    }

    public function getUploader()
    {
        return $this->objUploader;
    }

    public function deleteScheduledFiles($arrScheduledFiles)
    {
        $arrFiles = [];

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