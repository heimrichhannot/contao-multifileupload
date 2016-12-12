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

use HeimrichHannot\Haste\Util\Files;
use HeimrichHannot\Haste\Util\StringUtil;
use HeimrichHannot\Haste\Util\Url;

class MultiFileUpload extends \FileUpload
{
    protected $arrData = array();

    protected $strTemplate       = 'form_multifileupload_dropzone';
    protected $strJQueryTemplate = 'j_multifileupload_dropzone';

    /**
     * @var FormMultiFileUpload
     */
    protected $objWidget;

    const NAME                  = 'multifileupload';
    const ACTION_UPLOAD         = 'upload';
    const ACTION_UPLOAD_BACKEND = 'multifileupload_upload';

    const MIME_THEME_DEFAULT = 'system/modules/multifileupload/assets/img/mimetypes/Numix-uTouch';
    const UPLOAD_TMP         = 'files/tmp';

    const SESSION_ALLOWED_DOWNLOADS = 'multifileupload_allowed_downloads';

    /**
     * Has current page in xhtml type.
     *
     * @var bool
     */

    protected $blnIsXhtml = false;

    public function __construct($arrAttributes, $objWidget = null)
    {
        parent::__construct();
        $this->arrData   = $arrAttributes;
        $this->objWidget = $objWidget;

        $file = \Input::get('file', true);

        // Send the file to the browser
        if ($file != '')
        {
            if (!static::isAllowedDownload($file))
            {
                header('HTTP/1.1 403 Forbidden');
                die('No file access.');
            }

            \Controller::sendFileToBrowser($file);
        }

        global $objPage;

        $this->blnIsXhtml = ($objPage->outputFormat == 'xhtml');

        $this->loadDcaConfig();
    }

    protected function getByteSize($size)
    {
        // Convert the value to bytes
        if (stripos($size, 'K') !== false)
        {
            $size = round($size * 1024);
        }
        elseif (stripos($size, 'M') !== false)
        {
            $size = round($size * 1024 * 1024);
        }
        elseif (stripos($size, 'G') !== false)
        {
            $size = round($size * 1024 * 1024 * 1024);
        }

        return $size;
    }

    /**
     * Get maximum file size in bytes
     *
     * @param null $maxUploadSize
     *
     * @return mixed
     */
    protected function getMaximumUploadSize($maxUploadSize = null)
    {
        // Get the upload_max_filesize from the php.ini
        $upload_max_filesize = $this->getByteSize($maxUploadSize ?: ini_get('upload_max_filesize'));

        return min($upload_max_filesize, $this->getByteSize(\Config::get('maxFileSize')), $this->getByteSize(ini_get('upload_max_filesize')));
    }

    protected function loadDcaConfig()
    {
        // in MiB
        $this->maxFilesize = round(($this->getMaximumUploadSize($this->maxUploadSize) / 1024 / 1024), 2);

        $this->acceptedFiles = implode(
            ',',
            array_map(
                function ($a)
                {
                    return '.' . $a;
                },
                trimsplit(',', strtolower($this->extensions ?: \Config::get('uploadTypes')))
            )
        );

        // labels & messages
        $this->labels   = $this->labels ?: $GLOBALS['TL_LANG']['MSC']['dropzone']['labels'];
        $this->messages = $this->messages ?: $GLOBALS['TL_LANG']['MSC']['dropzone']['messages'];

        foreach ($this->messages as $strKey => $strMessage)
        {
            $this->{$strKey} = $strMessage;
        }

        foreach ($this->labels as $strKey => $strMessage)
        {
            $this->{$strKey} = $strMessage;
        }

        $this->thumbnailWidth  = $this->thumbnailWidth ?: 90;
        $this->thumbnailHeight = $this->thumbnailHeight ?: 90;

        $this->createImageThumbnails = $this->createImageThumbnails ?: true;

        $this->requestToken = \RequestToken::get();

        $this->previewsContainer = '#ctrl_' . $this->id . ' .dropzone-previews';

        $this->uploadMultiple = ($this->fieldType == 'checkbox');
        $this->maxFiles       = ($this->uploadMultiple ? ($this->maxFiles ?: null) : 1);
    }

    /**
     * Generate the markup for the default uploader
     *
     * @return string
     */
    public function generateMarkup()
    {
        $arrValues = array_values($this->value ?: array());

        $objT = new \FrontendTemplate($this->strTemplate);
        $objT->setData($this->arrData);
        $objT->id                    = $this->strField;
        $objT->uploadMultiple        = $this->uploadMultiple;
        $objT->initialFiles          = json_encode($arrValues);
        $objT->initialFilesFormatted = $this->prepareValue();
        $objT->uploadedFiles         = '[]';
        $objT->deletedFiles          = '[]';
        $objT->attributes            = $this->getAttributes($this->getDropZoneOptions());
        $objT->widget                = $this->objWidget;
        return $objT->parse();
    }

    /**
     * Return data attributes in correct syntax, considering doc type
     *
     * @param array $arrAttributes
     *
     * @return string
     */
    protected function getAttributes(array $arrAttributes = array())
    {
        $arrOptions = array();

        foreach ($arrAttributes as $strKey => $varValue)
        {
            $arrOptions[] = $this->getAttribute($strKey, $varValue);
        }

        return implode(' ', $arrOptions);
    }

    /**
     * Return html attribute in correct syntax, considering doc type
     *
     * @param string $strKey
     * @param        $varValue
     *
     * @return string
     */
    protected function getAttribute($strKey, $varValue)
    {
        if ($strKey == 'disabled' || $strKey == 'readonly' || $strKey == 'required' || $strKey == 'autofocus' || $strKey == 'multiple')
        {
            $varValue = $strKey;

            return $this->blnIsXhtml ? ' ' . $strKey . '="' . $varValue . '"' : ' ' . $strKey;
        }
        else
        {
            return ' ' . $strKey . '="' . $varValue . '"';
        }

        return '';
    }

    /**
     * Get all dropzone related options
     *
     * @return string
     */
    protected function getDropZoneOptions()
    {
        $arrOptions = array();

        foreach (array_keys($this->arrData) as $strKey)
        {
            if (($varValue = $this->getDropZoneOption($strKey)) === null)
            {
                continue;
            }

            // convert camelCase to hyphen, jquery.data() will make camelCase from hyphen again
            $strKey = 'data-' . strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $strKey));

            $arrOptions[$strKey] = $varValue;
        }

        return $arrOptions;
    }

    /**
     * Get a single dropzone option
     *
     * @param        $strKey
     *
     * @return string
     */
    public function getDropZoneOption(&$strKey)
    {
        $varValue = null;

        switch ($strKey)
        {
            case 'url':
            case 'uploadAction':
            case 'uploadActionParams':
            case 'method':
            case 'withCredentials':
            case 'parallelUploads':
            case 'maxFiles':
            case 'uploadMultiple':
            case 'maxFilesize':
            case 'requestToken':
            case 'acceptedFiles':
            case 'addRemoveLinks':
            case 'thumbnailWidth':
            case 'thumbnailHeight':
            case 'previewsContainer':
                $varValue = $this->arrData[$strKey];
                break;
            case 'createImageThumbnails':
                $varValue = ($this->thumbnailWidth || $this->thumbnailHeight && $this->arrData[$strKey]) ? 'true' : 'false';
                break;
            case 'name':
                $varValue = $this->arrData[$strKey];
                $strKey   = 'paramName';
                break;
            case 'dictDefaultMessage':
            case 'dictFallbackMessage':
            case 'dictFallbackText':
            case 'dictInvalidFileType':
            case 'dictFileTooBig':
            case 'dictResponseError':
            case 'dictCancelUpload':
            case 'dictCancelUploadConfirmation':
            case 'dictRemoveFile':
            case 'dictMaxFilesExceeded':
                $varValue = is_array($this->arrData[$strKey]) ? reset($this->arrData[$strKey]) : $this->arrData[$strKey];
                break;
        }

        return $varValue;
    }

    protected function prepareValue()
    {
        if (!empty($this->value))
        {
            $arrResult = array();

            foreach ($this->value as $strUuid)
            {
                if ($arrFile = $this->prepareFile($strUuid))
                {
                    $arrResult[] = $arrFile;
                }
            }

            return json_encode($arrResult);
        }
    }

    public function prepareFile($varUuid)
    {
        if (($objFile = Files::getFileFromUuid($varUuid, true)) !== null && $objFile->exists())
        {
            static::addAllowedDownload($objFile->value);

            $arrReturn = array(
                // remove timestamp from filename
                'name' => StringUtil::preg_replace_last('@_[a-f0-9]{13}@', $objFile->name),
                'uuid' => \StringUtil::binToUuid($objFile->getModel()->uuid),
                'size' => $objFile->filesize,
            );

            if (($strImage = $this->getPreviewImage($objFile)) !== null)
            {
                $arrReturn['url'] = $strImage;
            }

            if (($strInfoUrl = $this->getInfoAction($objFile)) !== null)
            {
                $arrReturn['info'] = $strInfoUrl;
            }

            return $arrReturn;
        }

        return false;
    }

    protected function getInfoAction(\File $objFile)
    {
        $strUrl             = null;
        $strFileNameEncoded = utf8_convert_encoding($objFile->name, \Config::get('characterSet'));

        switch (TL_MODE)
        {
            case 'FE':

                $strHref = Url::getCurrentUrlWithoutParameters();
                $strHref .= ((\Config::get('disableAlias') || strpos($strHref, '?') !== false) ? '&amp;' : '?') . 'file=' . \System::urlEncode($objFile->value);

                return 'window.open("' . $strHref . '", "_blank");';

                break;
            case 'BE':
                    $popupWidth  = 664;
                    $popupHeight = 299;
                    $href        = 'contao/popup.php?src=' . base64_encode($objFile->value);

                    return 'Backend.openModalIframe({"width":"' . $popupWidth . '","title":"' . str_replace(
                            "'",
                            "\\'",
                            specialchars($strFileNameEncoded, false, true)
                        ) . '","url":"' . $href . '","height":"' . $popupHeight . '"});return false';
                break;
        }

        return $strUrl;
    }

    public static function addAllowedDownload($strFile)
    {
        $arrDownloads = \Session::getInstance()->get(static::SESSION_ALLOWED_DOWNLOADS);

        if (!is_array($arrDownloads))
        {
            $arrDownloads = array();
        }

        $arrDownloads[] = $strFile;

        $arrDownloads = array_filter($arrDownloads);

        \Session::getInstance()->set(static::SESSION_ALLOWED_DOWNLOADS, $arrDownloads);
    }

    public static function isAllowedDownload($strFile)
    {
        $arrDownloads = \Session::getInstance()->get(static::SESSION_ALLOWED_DOWNLOADS);

        if (!is_array($arrDownloads))
        {
            return false;
        }

        if (array_search($strFile, $arrDownloads) !== false)
        {
            return true;
        }

        return false;
    }

    protected function getPreviewImage(\File $objFile)
    {
        if ($objFile->isImage && !$this->mimeThumbnailsOnly)
        {
            return $objFile->path;
        }

        $themeFolder = rtrim($this->mimeFolder ?: static::MIME_THEME_DEFAULT, '/');

        if (!file_exists(TL_ROOT . '/' . $themeFolder . '/mimetypes.json'))
        {
            return null;
        }

        $objMimeFile = new \File($themeFolder . '/mimetypes.json');

        $objMines = json_decode($objMimeFile->getContent());

        if (!$objMines->{$objFile->extension})
        {
            return null;
        }

        if (!file_exists(TL_ROOT . '/' . $themeFolder . '/' . $objMines->{$objFile->extension}))
        {
            return null;
        }

        return $themeFolder . '/' . $objMines->{$objFile->extension};
    }

    /**
     * Set an object property
     *
     * @param string
     * @param mixed
     */
    public function __set($strKey, $varValue)
    {
        $this->arrData[$strKey] = $varValue;
    }


    /**
     * Return an object property
     *
     * @param string
     *
     * @return mixed
     */
    public function __get($strKey)
    {
        switch ($strKey)
        {
            case 'name':
                return $this->strName;
                break;
        }


        if (isset($this->arrData[$strKey]))
        {
            return $this->arrData[$strKey];
        }

        return parent::__get($strKey);
    }


    /**
     * Check whether a property is set
     *
     * @param string
     *
     * @return boolean
     */
    public function __isset($strKey)
    {
        return isset($this->arrData[$strKey]);
    }
}
