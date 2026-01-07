<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.0 / elkarte
 */

/**
 * This file deals with externally-linked Vimeo videos.
 */
class LevGal_Model_External_DailyMotion
{
	/** @var array|mixed  */
	private $meta;

	public function __construct($meta = array())
	{
		$this->meta = $meta;
	}

	public function matchURL($url)
	{
		$patternlist = array(
			'~dailymotion\.com/video/([a-z0-9]+)$~i',
			'~dailymotion\.com/video/([a-z0-9]+)_~i',
			'~dai.ly/([a-z0-9]+)~i',
		);
		$provider = array();
		foreach ($patternlist as $pattern)
		{
			if (preg_match($pattern, $url, $matches))
			{
				$provider = array(
					'provider' => 'DailyMotion',
					'id' => $matches[1],
					'mime_type' => 'external/_video',
				);
				break;
			}
		}

		return $provider;
	}

	public function getDetails()
	{
		global $txt;

		return array(
			'display_template' => 'external',
			'external_url' => 'https://www.dailymotion.com/video/' . $this->meta['id'],
			'video_id' => $this->meta['id'],
			'provider' => $this->meta['provider'],
			'markup' => '
	<div class="lg_item">		
		<iframe class="base_iframe" src="//www.dailymotion.com/embed/video/' . $this->meta['id'] . '?title=0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>
		<div class="centertext ext_link"><a href="https://www.dailymotion.com/video/' . $this->meta['id'] . '">' . $txt['lgal_view_dailymotion'] . '</a></div>
	</div>',
		);
	}

	public function getThumbnail()
	{
		require_once(SUBSDIR . '/Package.subs.php');
		if ($thumbnail_data = fetch_web_data('https://www.dailymotion.com/thumbnail/video/' . $this->meta['id']))
		{
			return array('data' => $thumbnail_data, 'image_mime' => 'image/jpeg');
		}

		return false;
	}
}
