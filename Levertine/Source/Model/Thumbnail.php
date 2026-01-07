<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0 / elkarte
 */

namespace Addons\Levertine\Source\Model;

use Addons\Levertine\Source\Helper\Image;

/**
 * This file deals with preparing thumbnails/previews for files.
 */
class Thumbnail
{
	/** @var string */
	private $file;

	/** @var string */
	private $ext;

	/** @var Image */
	private $image;

	public function __construct($filepath)
	{
		$this->file = $filepath;
	}

	public function createFromString($string, $mime_type)
	{
		$this->image = new Image();

		$this->ext = match ($mime_type)
		{
			'image/png' => 'png',
			'image/webp' => 'webp',
			default => 'jpg',
		};

		return $this->image->loadImageFromString($string);
	}

	public function createFromFile()
	{
		$this->image = new Image();
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
