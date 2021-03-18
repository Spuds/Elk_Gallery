<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license proprietary
 *
 * @version 1.0 / elkarte
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
		$this->ext = $mime_type === 'image/jpeg' || $mime_type === 'image/jpg' ? 'jpg' : 'png';

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
		$preview_path = str_replace('.dat', '_preview_' . $this->ext . '.dat', $this->file);
		$thumb_path = str_replace('.dat', '_thumb_' . $this->ext . '.dat', $this->file);

		$this->image->resizeToNewFile(500, $preview_path, $this->ext);
		$this->image->resizeToNewFile(125, $thumb_path, $this->ext);

		return true;
	}
}
