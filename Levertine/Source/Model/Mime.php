<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0 / elkarte
 */

namespace Addons\Levertine\Source\Model;

use Addons\Levertine\Source\Model\Mime\Extension;
use ElkArte\Helper\FileFunctions;

/**
 * This file deals with discovering the MIME type of a file.
 */
class Mime
{
	/** @var string */
	private $filepath;

	/** @var string */
	private $filename;

	public function __construct($filepath, $filename)
	{
		$this->filepath = $filepath;
		$this->filename = $filename;
	}

	/**
	 * Determines the MIME type of the file associated with the current object.
	 * This method attempts multiple approaches to identify the MIME type,
	 * including checking the file extension, using PHP's finfo and mime_content_type functions,
	 * executing external commands, and inspecting image metadata.
	 *
	 * @return string|false The resolved MIME type as a string if successful, or false if the file does not
	 * exist or the type cannot be determined.
	 */
	public function getMimeType()
	{
		// FILE_NOT_FOUND?
		if (FileFunctions::instance()->fileExists($this->filepath))
		{
			return false;
		}

		// FInfo if it's available.
		if (function_exists('finfo_file'))
		{
			$finfo = @finfo_open(FILEINFO_MIME);
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

		// Lets begin to panic and try an exec call
		if (function_exists('exec'))
		{
			$type = @exec("/usr/bin/file -i -b $this->filepath");
			if ($type !== false && trim($type) !== '')
			{
				return $this->handleResponse($type);
			}
		}

		// Should be able to get images correct, right?
		if (function_exists('getimagesize'))
		{
			$type = @getimagesize($this->filepath);

			// Can't get it, what shall we return
			if (!empty($type['mime']))
			{
				return $this->handleResponse($type['mime']);
			}
		}

		// Lastly, extension.
		return $this->getMimeTypeFromExtension();
	}

	protected function handleResponse($type)
	{
		// Strip parameters if any, we probably don't want them.
		return str_contains($type, ';') ? trim(substr($type, 0, strpos($type, ';'))) : trim($type);
	}

	protected function getMimeTypeFromExtension()
	{
		$mime_types = Extension::getExtensionList();

		$ext = strtolower(substr(strrchr($this->filename, '.'), 1));

		return $mime_types[$ext] ?? 'application/octet-stream';
	}
}
