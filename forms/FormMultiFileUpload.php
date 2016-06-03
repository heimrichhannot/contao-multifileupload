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

	public function __construct($arrAttributes=null)
	{
		// bin to string
		if (!is_array($arrAttributes['value']) && !Validator::isBinaryUuid($arrAttributes['value']))
			$arrAttributes['value'] = json_decode($arrAttributes['value']);

		if ($arrAttributes['value'])
		{
			if (is_array($arrAttributes['value']))
			{
				$arrAttributes['value'] = array_map(
						function($val) {
							return \StringUtil::binToUuid($val);
						}, $arrAttributes['value']
				);
			}
			else
			{
				$arrAttributes['value'] = array(
					\StringUtil::binToUuid($arrAttributes['value'])
				);
			}
		}

		//parent::__construct($arrAttributes);
		$this->objUploader = new MultiFileUpload($arrAttributes);

		// TODO atm ajax-only
		if (!\Input::get('isAjaxUpload'))
			return;

		$strAction = \Input::get('action');

		// check for the request token
		if (!\Input::get('requestToken') || !RequestToken::validate(\Input::get('requestToken')))
			die('Invalid Request Token!');

		// create if not existing
		new \Folder($this->objUploader->uploadFolder);

		$strUploadFolder = rtrim($this->objUploader->uploadFolder, '/');

		if ($strAction && \Input::get('field') == $arrAttributes['name'])
		{
			if (\Input::get('uuid'))
			{
				switch ($strAction)
				{
					case 'remove':
						if (!is_array($_SESSION['uploadFilesToRemove'][$arrAttributes['name']]))
							$_SESSION['uploadFilesToRemove'][$arrAttributes['name']] = array();

						$_SESSION['uploadFilesToRemove'][$arrAttributes['name']][] = \Input::get('uuid');
						break;
				}
			}

			return;
		}

		// Dropzone Upload
		if(!empty($_FILES))
		{
			$arrFiles = array();
			foreach($_FILES as $strField => $arrFile)
			{
				if ($strField != $arrAttributes['name'])
					continue;

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
				die(json_encode($arrFiles));
		}
	}

	protected function uploadFile($strFieldUpload, $strField, $arrFile, $strUploadFolder)
	{
		if (!$arrFile['error'] && $strFieldUpload == $strField)
		{
			$strTempFile = $arrFile['tmp_name'];

			$arrPath = pathinfo($arrFile['name']);

			// example testfile.png -> testfile_2015_12_29_16_08.png
			$strTargetFileName = Files::sanitizeFileName($arrPath['filename']) . '_' .
					date('Y_m_d-H_i_s', time()) . '.' . $arrPath['extension'];
			$strTargetFile =  $strUploadFolder . '/' . $strTargetFileName;

			move_uploaded_file($strTempFile, TL_ROOT . '/' . $strTargetFile);

			// add db record
			$objFile = new \File($strTargetFile);
			// create the file model
			$objFile->close();

			return array(
				'filename' => $strTargetFileName,
				'filenameOrig' => $arrFile['name'],
				'uuid' => \StringUtil::binToUuid($objFile->getModel()->uuid),
				'size' => $objFile->filesize,
			);
		}
	}

	public function getUploader() {
		return $this->objUploader;
	}

	public function deleteScheduledFiles($arrScheduledFiles)
	{
		if(!empty($arrScheduledFiles))
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