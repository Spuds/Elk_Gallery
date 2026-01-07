<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.1.1 / elkarte
 */

/**
 * This file deals with getting information items imported from externally.
 */
class LevGal_Model_External
{
	/** @var array|mixed  */
	private $meta;

	public function __construct($meta = array())
	{
		$this->meta = $meta;
	}

	public function getDisplayProperties()
	{
		if ($model = $this->getProvider('getDetails'))
		{
			return $model->getDetails();
		}

		return array(
			'display_template' => 'generic',
		);
	}

	public function getURLData($url, $bypass_check = false)
	{
		global $modSettings;

		// 1. Validate schema.
		$url_parts = parse_url($url);
		// We will allow non-scheme ones, e.g. //youtube.com/ but filter_var doesn't filter FTP or Gopher or suchlike.
		if (empty($url_parts['scheme']))
		{
			$url = 'https://' . $url;
			$url_parts = parse_url($url);
		}

		$url_parts['scheme'] = strtolower($url_parts['scheme']);
		if ($url_parts['scheme'] !== 'http' && $url_parts['scheme'] !== 'https')
		{
			return array();
		}

		// 2. Do something with the domain name.
		$domain = strtolower($url_parts['host']);
		$allowed_providers = explode(',', $modSettings['lgal_external_formats']);

		$array = array(
			'youtube' => array(
				'youtube.com' => 'YouTube',
				'youtu.be' => 'YouTube',
			),
			'vimeo' => array(
				'vimeo.com' => 'Vimeo',
			),
			'dailymotion' => array(
				'dailymotion.com' => 'DailyMotion',
				'dai.ly' => 'DailyMotion',
			),
			'metacafe' => array(
				'metacafe.com' => 'MetaCafe',
			),
		);

		foreach ($array as $provider => $details)
		{
			if (!$bypass_check && !in_array($provider, $allowed_providers))
			{
				continue;
			}

			foreach ($details as $known_domain => $class)
			{
				if (strpos($domain, $known_domain) !== false)
				{
					try
					{
						$modelName = 'LevGal_Model_External_' . $class;
						$model = new $modelName();
						if (method_exists($model, 'matchURL'))
						{
							$provider = $model->matchURL($url);
							if (!empty($provider['provider']))
							{
								$this->meta = $provider;
							}

							return $provider;
						}
					}
					catch (RuntimeException $e)
					{
						// We don't really care if it's not found at this point.
					}
				}
			}
		}

		return array();
	}

	public function getThumbnail()
	{
		if (!empty($this->meta['provider']) && !empty($this->meta['id']) && $model = $this->getProvider('getThumbnail'))
		{
			return $model->getThumbnail();
		}

		return false;
	}

	protected function getProvider($available_method = '')
	{
		if (empty($this->meta['provider']) || empty($this->meta['id']))
		{
			return false;
		}

		$class = 'LevGal_Model_External_' . $this->meta['provider'];
		try
		{
			$model = new $class($this->meta);
			if ($available_method === '' || method_exists($model, $available_method))
			{
				return $model;
			}
		}
		catch (RuntimeException $e)
		{
			// If this fails, that's fine. This is primarily to stop the autoloader complaining.
		}

		return false;
	}
}
