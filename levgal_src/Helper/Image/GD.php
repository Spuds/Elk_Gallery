<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.1.1 / elkarte
 */

/**
 * This file deals with images when using the GD library.
 */
class LevGal_Helper_Image_GD
{
	/** @var  */
	private $image;
	/** @var int  */
	private $width = 0;
	/** @var int  */
	private $height = 0;
	/** @var string */
	private $source_file;
	/** @var string */
	private $source_type;
	/** @var int */
	private $compression;

	public function __destruct()
	{
		if ($this->image)
		{
			imagedestroy($this->image);
		}
	}

	public function getVersion()
	{
		$gd_info = gd_info();

		return $gd_info['GD Version'];
	}

	public function setCompression($values)
	{
		$this->compression = $values;
	}

	protected function allocateMemory($width, $height, $format)
	{
		$bpp = $format == IMAGETYPE_JPEG ? 3 : 4; // JPEGs are usually 24bpp = 3 bytes per pixel for memory purposes
		$fudge = 1.8; // Allow generous extra usage.
		$running_use = 5; // Take existing memory use and factor that into the reqs.

		$estimated = ceil(($width * $height * $bpp * $fudge) / 1048576) + $running_use;

		return detectServer()->setMemoryLimit($estimated, true);
	}

	public function getImageSize()
	{
		return array(imagesx($this->image), imagesy($this->image));
	}

	public function loadImageFromFile($file)
	{
		if ($this->image)
		{
			imagedestroy($this->image);
		}

		require_once(SUBSDIR . '/Attachments.subs.php');
		$imageinfo = elk_getimagesize($file, false);
		if (empty($imageinfo))
		{
			return false;
		}
		list ($this->width, $this->height, $type) = $imageinfo;

		if (!$this->allocateMemory($this->width, $this->height, $type))
		{
			return false;
		}

		$funcs = array(
			IMAGETYPE_GIF => array('func' => 'imagecreatefromgif', 'ext' => 'png'),
			IMAGETYPE_PNG => array('func' => 'imagecreatefrompng', 'ext' => 'png'),
			IMAGETYPE_JPEG => array('func' => 'imagecreatefromjpeg', 'ext' => 'jpg'),
		);
		if (isset($funcs[$type]['func']) && function_exists($funcs[$type]['func']) && !empty($this->width) && !empty($this->height))
		{
			$this->image = $funcs[$type]['func']($file);
			if (!$this->image)
			{
				imagedestroy($this->image);

				return false;
			}
			$this->source_file = $file;
			$this->source_type = $funcs[$type]['ext'];

			return $funcs[$type]['ext'];
		}

		return false;
	}

	public function loadImageFromString($string)
	{
		if ($this->image)
		{
			imagedestroy($this->image);
		}

		$this->image = @imagecreatefromstring($string);
		if (!$this->image)
		{
			return false;
		}
		list ($this->width, $this->height) = $this->getImageSize();

		return true;
	}

	public function saveImageToFile($file, $format = 'jpg')
	{
		$imgfunc = $format === 'jpg' ? 'imagejpeg' : 'imagepng';
		$quality = $format === 'jpg' ? $this->compression['jpg'] : $this->compression['png'];
		$imgfunc($this->image, $file, $quality);
	}

	public function resizeImageToMax($max_dimension, $dest_file, $format = 'jpg')
	{
		// This is kind of expensive so let's try to buy ourselves some more time.
		detectServer()->setTimeLimit(30);

		$imgfunc = $format === 'jpg' ? 'imagejpeg' : 'imagepng';
		// Nothing to do, can we save ourselves some hassle?
		if ($this->width <= $max_dimension && $this->height <= $max_dimension)
		{
			// If this is already in size... just get it out the way.
			// Well... did we come from a file? If so, make sure we're not trying to mash a JPG into a PNG or vice versa.
			if (!empty($this->source_file) && !empty($this->source_type) && $format == $this->source_type)
			{
				$this->saveImageToFile($dest_file, $format);
			}
		}
		else
		{
			$largest = max($this->width, $this->height);
			$new_x = round($this->width / $largest * $max_dimension);
			$new_y = round($this->height / $largest * $max_dimension);
			$new_image = imagecreatetruecolor($new_x, $new_y);
			imagealphablending($new_image, false);
			imagesavealpha($new_image, true);
			imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $new_x, $new_y, $this->width, $this->height);
			$imgfunc($new_image, $dest_file);
			imagedestroy($new_image);
		}
	}

	public function rotate($deg)
	{
		imagealphablending($this->image, false);
		imagesavealpha($this->image, true);
		$this->image = imagerotate($this->image, 360 - $deg, 0);
		list ($this->width, $this->height) = $this->getImageSize();
	}

	public function flip($direction)
	{
		imageflip($this->image, $direction === 'y' ? IMG_FLIP_VERTICAL : IMG_FLIP_HORIZONTAL);
		list ($this->width, $this->height) = $this->getImageSize();
	}
}
