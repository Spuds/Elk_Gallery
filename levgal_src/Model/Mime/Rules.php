<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.0 / elkarte
 */

/**
 * This file deals with specific exceptions on top of getID3.
 */
class LevGal_Model_Mime_Rules
{
	/** @var array */
	private $id3;
	/** @var mixed|string */
	private $mime;
	/** @var string */
	private $extension;

	public function __construct($id3, $ext)
	{
		$this->id3 = $id3;
		$this->mime = !empty($id3['mime_type']) ? $id3['mime_type'] : '';
		$this->extension = $ext;
	}

	public function applyExceptions()
	{
		$file_format = $this->id3['fileformat'] ?? '';

		if ($file_format === 'msoffice' || $file_format === 'zip.msoffice')
		{
			$mime_type = $this->getFromExtension();
		}

		switch ($this->mime)
		{
			case 'application/octet-stream':
				// getID3 might just have no idea, so fall through to getting it from extension.
			case 'application/zip':
				// OOo files can be determined by unpacking and looking for a file called mimetype in the zip.
				// But that may be something we leave to another day.
				$mime_type = $this->getFromExtension();
				if ($mime_type !== 'application/octet-stream')
				{
					$mime_type = $mime_type;
				}
				break;
			case 'video/quicktime':
				if (in_array($this->extension, array('m4v', 'mp4')))
				{
					$mime_type = 'video/mp4';
				}
				break;
		}

		if (!empty($mime_type))
		{
			return $mime_type;
		}
		elseif (!empty($this->mime))
		{
			return $this->mime;
		}

		return false;
	}

	public function getFromExtension()
	{
		static $extensions = null;

		if ($extensions === null)
		{
			$extensions = LevGal_Model_Mime_Extension::getExtensionList();
		}

		return $extensions[$this->extension] ?? 'application/octet-stream';
	}
}
