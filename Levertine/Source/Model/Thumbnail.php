<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.0 / elkarte
 */

/**
 * This file deals with preparing thumbnails/previews for files.
 */
class LevGal_Model_Thumbnail
{
	/** @var string */
	private $file;
	/** @var string */
	private $ext;
	/** @var LevGal_Helper_Image */
	private $image;

	public function __construct($filepath)
	{
		$this->file = $filepath;
	}

	public function createFromString($string, $mime_type)
	{
		$this->image = new LevGal_Helper_Image();

		switch ($mime_type) {
			case 'image/png':
				$this->ext = 'png';
				break;
			case 'image/webp':
				$this->ext = 'webp';
				break;
			case 'image/jpeg':
			case 'image/jpg':
			default:
				$this->ext = 'jpg';
		}

		return $this->image->loadImageFromString($string);
	}

	public function createFromFile()
	{
		$this->image = new LevGal_Helper_Image();
		if ($ext = $this->image->loadImageFromFile($this->file))
		{
			$this->ext = $ext;

			return true;
		}

		return false;
	}

	public function generateThumbnails()
	{
		global $modSettings;

		$thumbMax = $modSettings['attachmentThumbWidth'] ?: 125;
		$preview_path = str_replace('.dat', '_preview_' . $this->ext . '.dat', $this->file);
		$thumb_path = str_replace('.dat', '_thumb_' . $this->ext . '.dat', $this->file);

		$this->image->resizeToNewFile(500, $preview_path, $this->ext);
		$this->image->resizeToNewFile($thumbMax, $thumb_path, $this->ext);

		return true;
	}
}
