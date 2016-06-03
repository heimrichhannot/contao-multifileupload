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

use HeimrichHannot\Haste\Util\Files;
use HeimrichHannot\Haste\Util\StringUtil;

class MultiFileUpload extends \FileUpload
{
	protected $arrData = array();

	protected $strTemplate = 'form_multifileupload_dropzone';
	protected $strJQueryTemplate = 'j_multifileupload_dropzone';

	public function __construct($arrAttributes)
	{
		parent::__construct();
		$this->arrData = $arrAttributes;

		$this->loadDcaConfig();
	}

	protected function loadDcaConfig()
	{
		// in MB
		$this->maxFilesize = ($this->maxUploadSize ?: $this->getMaximumUploadSize() / 1024 / 1024);

		$this->acceptedFiles = implode(
			',',
			array_map(
				function ($a) {
					return '.' . $a;
				},
				trimsplit(',', strtolower($this->extensions ?: \Config::get('uploadTypes')))
			)
		);

		// upload folder
		if (is_array($this->uploadFolder))
		{
			$arrCallback = $this->uploadFolder;
			$this->uploadFolder = \System::importStatic($arrCallback[0])->$arrCallback[1]($this->arrData['dataContainer']);
		}
		elseif (is_callable($this->uploadFolder))
		{
			$strMethod = $this->uploadFolder;
			$this->uploadFolder = $strMethod($this->arrData['dataContainer']);
		}
		else
		{
			if (strpos($this->uploadFolder, '../') !== false)
			{
				throw new \Exception("Invalid target path $this->uploadFolder");
			} elseif (!$this->uploadFolder)
			{
				$this->uploadFolder = \Config::get('uploadPath');
			}
		}

		// labels & messages
		$this->labels = $this->labels ?: $GLOBALS['TL_LANG']['MSC']['dropzone']['labels'];
		$this->messages = $this->messages ?: $GLOBALS['TL_LANG']['MSC']['dropzone']['messages'];

		// image measurements
		$this->minImageWidth = $this->minImageWidth ?: 0;
		$this->minImageHeight = $this->minImageHeight ?: 0;
	}

	/**
	 * Generate the markup for the default uploader
	 *
	 * @return string
	 */
	public function generateMarkup()
	{
		$objT = new \FrontendTemplate($this->strTemplate);
		$objT->setData($this->arrData);
		$objT->id = $this->strField;
		$objT->uploadMultiple = ($this->fieldType == 'checkbox');
		$objT->initialFiles = json_encode($this->value ?: array());
		$objT->initialFilesFormatted = $this->prepareValue();
		$objT->uploadedFiles = '[]';
		$objT->deletedFiles = '[]';
		$objT->js = $this->generateJs();

		return $objT->parse();
	}

	protected function prepareValue() {
		if (!empty($this->value))
		{
			$arrResult = array();

			foreach ($this->value as $strUuid)
			{
				if ($arrFile = $this->prepareFile($strUuid))
					$arrResult[] = $arrFile;
			}

			return json_encode($arrResult);
		}
	}

	protected function prepareFile($varUuid)
	{
		if (($objFile = Files::getFileFromUuid($varUuid, true)) !== null && $objFile->exists())
		{
			return array(
				// remove timestamp from filename
				'name' => StringUtil::preg_replace_last('@_\d{4}_\d{2}_\d{2}-\d{2}_\d{2}_\d{2}@', $objFile->name),
				'uuid' => \StringUtil::binToUuid($objFile->getModel()->uuid),
				'size' => $objFile->filesize
			);
		}

		return false;
	}

	protected function generateJs()
	{
		$objT = new \FrontendTemplate($this->strJQueryTemplate);
		$objT->setData($this->arrData);
		$objT->id = $this->strField;
		$objT->uploadMultiple = ($this->fieldType == 'checkbox');

		return $objT->parse();
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



		if (isset($this->arrData[$strKey])) {
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
