<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.0 / elkarte
 */

/**
 * This file deals with discovering the MIME type of a file.
 */
class LevGal_Model_Mime
{
	/** @var string */
	private $filepath;
	/** @var string */
	private $filename;
	/** @var  MAGIC */
	private $magic_file;

	public function __construct($filepath, $filename)
	{
		$this->filepath = $filepath;
		$this->filename = $filename;
	}

	public function getMimeType()
	{
		// FILE_NOT_FOUND? (TDWTF would be proud.)
		if (!file_exists($this->filepath))
		{
			return false;
		}

		// FInfo if it's available.
		if (function_exists('finfo_file'))
		{
			$finfo = @finfo_open(FILEINFO_MIME, $this->magic_file);
			if ($finfo)
			{
				$type = finfo_file($finfo, $this->filepath);
				finfo_close($finfo);
				if ($type !== false && trim($type) !== '')
				{
					return $this->handleResponse($type);
				}
			}
		}

		// File magic
		if (function_exists('mime_content_type'))
		{
			$type = mime_content_type($this->filepath);
			if ($type !== false && trim($type) !== '')
			{
				return $this->handleResponse($type);
			}
		}

		// Lastly, extension.
		return $this->getMimeTypeFromExtension();
	}

	protected function handleResponse($type)
	{
		// Strip parameters if any, we probably don't want them.
		return strpos($type, ';') !== false ? trim(substr($type, 0, strpos($type, ';'))) : trim($type);
	}

	protected function getMimeTypeFromExtension()
	{
		$mime_types = LevGal_Model_Mime_Extension::getExtensionList();

		$ext = strtolower(substr(strrchr($this->filename, '.'), 1));

		return $mime_types[$ext] ?? 'application/octet-stream';
	}
}
