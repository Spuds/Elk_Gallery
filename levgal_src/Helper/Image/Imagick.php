<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license proprietary
 *
 * @version 1.0 / elkarte
 */

/**
 * This file deals with images when using the GD library.
 */
class LevGal_Helper_Image_Imagick
{
	/** @var  */
	private $image;
	/** @var int */
	private $width;
	/** @var int */
	private $height;
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
			@$this->image->clear();
		}
	}

	public function getVersion()
	{
		$version = Imagick::getVersion();
		preg_match('~ImageMagick (\d+\.\d+\.\d+(\-\d+)?)~', $version['versionString'], $match);

		return !empty($match[1]) ? $match[1] : false;
	}

	public function setCompression($values)
	{
		$this->compression = $values;
	}

	public function getImageSize()
	{
		return array($this->image->getImageWidth(), $this->image->getImageHeight());
	}

	public function loadImageFromFile($file)
	{
		if ($this->image)
		{
			@$this->image->clear();
		}

		try
		{
			$this->image = new Imagick($file);
		}
		catch (Exception $e)
		{
			return false;
		}

		list ($this->width, $this->height) = $this->getImageSize();
		$actual_type = strtolower($this->image->getImageFormat());
		$type = $actual_type === 'gif' || $actual_type === 'png' ? 'png' : 'jpg';

		$this->source_file = $file;
		$this->source_type = $type;

		return $type;
	}

	public function loadImageFromString($string)
	{
		if ($this->image)
		{
			@$this->image->clear();
		}

		$this->image = new Imagick();
		try
		{
			// since apparently we do generally need a filename parameter.
			$this->image->readImageBlob($string, '');
		}
		catch (Exception $e)
		{
			return false;
		}

		list ($this->width, $this->height) = $this->getImageSize();

		return true;
	}

	public function saveImageToFile($file, $format = 'jpg')
	{
		if ($format === 'jpg')
		{
			$this->image->setCompression(Imagick::COMPRESSION_JPEG);
			$this->image->setCompressionQuality($this->compression['jpg']);
			$this->image->writeImage('jpg:' . $file);
		}
		else
		{
			// We accept it as a 0-9 value but Imagick is weird like this.
			$this->image->setCompressionQuality($this->compression['png'] * 10);
			$this->image->writeImage('png:' . $file);
		}
	}

	public function resizeImageToMax($max_dimension, $dest_file, $format = 'jpg')
	{
		// Nothing to do, can we save ourselves some hassle?
		if ($this->width <= $max_dimension & $this->height <= $max_dimension)
		{
			if (!empty($this->source_file) && !empty($this->source_type) && $format == $this->source_type)
			{
				$this->saveImageToFile($dest_file, $format);
			}
		}
		else
		{
			$new_image = clone $this->image;
			if ($this->width > $this->height)
			{
				$new_image->resizeImage($max_dimension, 0, Imagick::FILTER_LANCZOS, 1);
			}
			else
			{
				$new_image->resizeImage(0, $max_dimension, Imagick::FILTER_LANCZOS, 1);
			}

			if ($format === 'jpg')
			{
				$new_image->borderImage('white', 0, 0);
				$new_image->setCompression(Imagick::COMPRESSION_JPEG);
				$new_image->setCompressionQuality($this->compression['jpg']);
				$new_image->writeImage('jpg:' . $dest_file);
			}
			else
			{
				// We accept it as a 0-9 value but Imagick is weird like this.
				$new_image->setCompressionQuality($this->compression['png'] * 10);
				$new_image->writeImage('png:' . $dest_file);
			}

			$new_image->clear();
		}
	}

	public function rotate($deg)
	{
		$this->image->rotateImage(new ImagickPixel('#00000000'), $deg);
		$this->image->setImageOrientation(imagick::ORIENTATION_TOPLEFT);
		list ($this->width, $this->height) = $this->getImageSize();
	}

	public function flip($direction)
	{
		$directions = str_split($direction);
		if (in_array('x', $directions))
		{
			$this->image->flopImage();
		}
		if (in_array('y', $directions))
		{
			$this->image->flipImage();
		}

		$this->image->setImageOrientation(imagick::ORIENTATION_TOPLEFT);
		list ($this->width, $this->height) = $this->getImageSize();
	}
}
