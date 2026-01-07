<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0 / elkarte
 */

namespace Addons\Levertine\Source\Model\Metadata;

use Addons\Levertine\Source\Helper\Format;
use ElkArte\Helper\Util;
use ElkArte\Languages\Txt;

/**
 * This file deals with display metadata stored in the item table.
 */
class Display
{
	/** @var array */
	private $meta;

	/** @var mixed */
	private $settings;

	public function __construct($meta)
	{
		global $modSettings;

		$this->meta = $meta;
		Txt::load('Levertine/LevGal-Exif');

		$this->settings = Util::unserialize($modSettings['lgal_metadata']);
	}

	protected function isDisplaying($class, $value)
	{
		return isset($this->settings[$class]) && in_array($value, $this->settings[$class], true);
	}

	public function getExifInfo()
	{
		// There needs to be some Exif data stored for us?
		if (empty($this->meta['exif']))
		{
			return [];
		}

		$exifModel = new ExifTag();

		$meta = [];
		$exif = $exifModel->formatData($this->meta['exif']);

		// This one is a mutant, because we're doing some prettier printing for it.
		if (isset($exif['IFD0']['Make'], $exif['IFD0']['Model']))
		{
			$exif['IFD0']['CameraMakeModel'] = trim($exif['IFD0']['Make'] . ' ' . $exif['IFD0']['Model']);
		}

		$display_items = [
			'title' => ['IFD0', 'XPTitle'],
			'subject' => ['IFD0', 'XPSubject'],
			'keywords' => ['IFD0', 'XPKeywords'],
			'author' => ['IFD0', 'XPAuthor'],
			'comment' => ['IFD0', 'XPComment'],
			'datetime' => ['IFD0', 'DateTime'],
			'make' => ['IFD0', 'CameraMakeModel'],
			'flash' => ['SubIFD', 'Flash'],
			'exposure_time' => ['SubIFD', 'ExposureTime'],
			'fnumber' => ['SubIFD', 'FNumber'],
			'shutter_speed' => ['SubIFD', 'ShutterSpeedValue'],
			'focal_length' => ['SubIFD', 'FocalLength'],
			'digitalzoom' => ['SubIFD', 'DigitalZoomRatio'],
			'brightness' => ['SubIFD', 'BrightnessValue'],
			'contrast' => ['SubIFD', 'Contrast'],
			'sharpness' => ['SubIFD', 'Sharpness'],
			'isospeed' => ['SubIFD', 'ISOSpeedRatings'],
			'lightsource' => ['SubIFD', 'LightSource'],
			'exposure_prog' => ['SubIFD', 'ExposureProgram'],
			'metering_mode' => ['SubIFD', 'MeteringMode'],
			'sensitivity' => ['SubIFD', 'SensitivityType'],
		];
		foreach ($display_items as $id => $item)
		{
			if (isset($exif[$item[0]][$item[1]]) && $this->isDisplaying('images', $id))
			{
				$meta[$id] = $exif[$item[0]][$item[1]];
			}
		}

		return $meta;
	}

	public function getAudioInfo()
	{
		global $txt;
		$meta = [];
		foreach ($this->meta as $key => $value)
		{
			if ($key === 'playtime' && $this->isDisplaying('audio', 'playtime'))
			{
				$meta['playtime'] = $value;
				$meta['playtime_display'] = Format::humantime($value);
				continue;
			}
			if ($key === 'bitrate' && $this->isDisplaying('audio', 'bitrate'))
			{
				$meta['bitrate'] = $value;
				$meta['bitrate_display'] = sprintf($txt['lgal_metadata_bitrate_kbps'], sprintf('%01.1f', $meta['bitrate'] / 1024));
				continue;
			}

			if (isset($txt['lgal_metadata_' . $key]) && $this->isDisplaying('audio', $key))
			{
				$meta[$key] = is_array($value) ? implode(', ', $value) : $value;
			}
		}

		return $meta;
	}

	public function getVideoInfo()
	{
		global $txt;
		$meta = [];
		foreach ($this->meta as $key => $value)
		{
			if ($key === 'playtime' && $this->isDisplaying('video', 'playtime'))
			{
				$meta['playtime'] = $value;
				$meta['playtime_display'] = Format::humantime($value);
				continue;
			}
			if ($key === 'bitrate' && $this->isDisplaying('video', 'bitrate'))
			{
				$meta['bitrate'] = $value;
				$meta['bitrate_display'] = sprintf($txt['lgal_metadata_bitrate_kbps'], sprintf('%01.1f', $meta['bitrate'] / 1024));
				continue;
			}

			if (isset($txt['lgal_metadata_' . $key]) && $this->isDisplaying('video', $key))
			{
				$meta[$key] = is_array($value) ? implode(', ', $value) : $value;
			}
		}

		return $meta;
	}
}
