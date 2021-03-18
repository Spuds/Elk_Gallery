<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license proprietary
 *
 * @version 1.1.1 / elkarte
 */

/**
 * This file deals with display metadata stored in the item table.
 */
class LevGal_Model_Metadata_Display
{
	/** @var array */
	private $meta;
	/** @var mixed */
	private $settings;

	public function __construct($meta)
	{
		global $modSettings;

		$this->meta = $meta;
		loadLanguage('levgal_lng/LevGal-Exif');

		$this->settings = unserialize($modSettings['lgal_metadata']);
	}

	protected function isDisplaying($class, $value)
	{
		return isset($this->settings[$class]) && in_array($value, $this->settings[$class]);
	}

	public function getExifInfo()
	{
		// There needs to be some Exif data stored for us?
		if (empty($this->meta['exif']))
		{
			return array();
		}

		$exifModel = new LevGal_Model_Metadata_ExifTag();

		$meta = array();
		$exif = $exifModel->formatData($this->meta['exif']);

		// This one is a mutant, because we're doing some prettier printing for it.
		if (isset($exif['IFD0']['Make'], $exif['IFD0']['Model']))
		{
			$exif['IFD0']['CameraMakeModel'] = trim($exif['IFD0']['Make'] . ' ' . $exif['IFD0']['Model']);
		}

		$display_items = array(
			'title' => array('IFD0', 'XPTitle'),
			'subject' => array('IFD0', 'XPSubject'),
			'keywords' => array('IFD0', 'XPKeywords'),
			'author' => array('IFD0', 'XPAuthor'),
			'comment' => array('IFD0', 'XPComment'),
			'datetime' => array('IFD0', 'DateTime'),
			'make' => array('IFD0', 'CameraMakeModel'),
			'flash' => array('SubIFD', 'Flash'),
			'exposure_time' => array('SubIFD', 'ExposureTime'),
			'fnumber' => array('SubIFD', 'FNumber'),
			'shutter_speed' => array('SubIFD', 'ShutterSpeedValue'),
			'focal_length' => array('SubIFD', 'FocalLength'),
			'digitalzoom' => array('SubIFD', 'DigitalZoomRatio'),
			'brightness' => array('SubIFD', 'BrightnessValue'),
			'contrast' => array('SubIFD', 'Contrast'),
			'sharpness' => array('SubIFD', 'Sharpness'),
			'isospeed' => array('SubIFD', 'ISOSpeedRatings'),
			'lightsource' => array('SubIFD', 'LightSource'),
			'exposure_prog' => array('SubIFD', 'ExposureProgram'),
			'metering_mode' => array('SubIFD', 'MeteringMode'),
			'sensitivity' => array('SubIFD', 'SensitivityType'),
		);
		foreach ($display_items as $id => $item)
		{
			if ($this->isDisplaying('images', $id) && isset($exif[$item[0]][$item[1]]))
			{
				$meta[$id] = $exif[$item[0]][$item[1]];
			}
		}

		return $meta;
	}

	public function getAudioInfo()
	{
		global $txt;
		$meta = array();
		foreach ($this->meta as $key => $value)
		{
			if ($key === 'playtime' && $this->isDisplaying('audio', 'playtime'))
			{
				$meta['playtime'] = $value;
				$meta['playtime_display'] = LevGal_Helper_Format::humantime($value);
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
		$meta = array();
		foreach ($this->meta as $key => $value)
		{
			if ($key === 'playtime' && $this->isDisplaying('video', 'playtime'))
			{
				$meta['playtime'] = $value;
				$meta['playtime_display'] = LevGal_Helper_Format::humantime($value);
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
