<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.1.0 / elkarte
 */

/**
 * This file deals with images, abstracting away their real complexity.
 */
class LevGal_Helper_Image
{
	private $image_handler;

	public function __construct($fatal = true)
	{
		$handlers = $this->availableHandlers();
		if ($handlers['Imagick'] === true)
		{
			$this->image_handler = new LevGal_Helper_Image_Imagick();
		}
		elseif ($handlers['GD'] === true)
		{
			$this->image_handler = new LevGal_Helper_Image_GD();
		}

		if (empty($this->image_handler))
		{
			if ($fatal)
			{
				LevGal_Helper_Http::fatalError('levgal_no_image_support');
			}
			else
			{
				return false;
			}
		}

		$this->image_handler->setCompression(array(
			'png' => 9,
			'jpg' => 85,
		));
	}

	public function availableHandlers()
	{
		$handlers = array();
		if (function_exists('imagecreatetruecolor'))
		{
			$handlers['GD'] = true;
		}

		if (class_exists('Imagick'))
		{
			$imagick = new Imagick();
			$formats = $imagick->queryFormats();
			$handlers['Imagick'] = !empty($formats) ? true : 'error';
		}
		else
		{
			$handlers['Imagick'] = false;
		}

		return $handlers;
	}

	public function getHandlerVersions()
	{
		$handlers = $this->availableHandlers();
		$versions = array();
		foreach ($handlers as $handler => $state)
		{
			if ($state !== false)
			{
				$class_handler = 'LevGal_Helper_Image_' . $handler;
				$this_handler = new $class_handler();
				$versions[$handler] = $this_handler->getVersion();
			}
		}

		return $versions;
	}

	public function loadImageFromFile($file)
	{
		return $this->image_handler->loadImageFromFile($file);
	}

	public function loadImageFromString($string)
	{
		return $this->image_handler->loadImageFromString($string);
	}

	public function getImageSize()
	{
		return $this->image_handler->getImageSize();
	}

	public function saveImageToFile($file, $format = 'jpg')
	{
		$this->image_handler->saveImageToFile($file, $format);
	}

	public function resizeToNewFile($max_dimension, $file, $format = 'jpg')
	{
		$this->image_handler->resizeImageToMax($max_dimension, $file, $format);
	}

	public function fixOrientation($current_orientation)
	{
		$changed = false;
		$transforms = array(
			2 => array('flip' => 'x'),
			3 => array('rotate' => 180),
			4 => array('flip' => 'y'),
			5 => array('rotate' => 90, 'flip' => 'x'),
			6 => array('rotate' => 90),
			7 => array('rotate' => 270, 'flip' => 'y'),
			8 => array('rotate' => 270),
		);
		if (isset($transforms[$current_orientation]))
		{
			if (!empty($transforms[$current_orientation]['rotate']))
			{
				$this->image_handler->rotate($transforms[$current_orientation]['rotate']);
				$changed = true;
			}
			if (!empty($transforms[$current_orientation]['flip']))
			{
				$this->image_handler->flip($transforms[$current_orientation]['flip']);
				$changed = true;
			}
		}

		return $changed;
	}
}

