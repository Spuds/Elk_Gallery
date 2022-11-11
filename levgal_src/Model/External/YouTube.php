<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.0 / elkarte
 */

/**
 * This file deals with externally-linked YouTube videos.
 */
class LevGal_Model_External_YouTube
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
			'~youtube\.com/\?v=([^&]+)~i',
			'~youtube\.com/watch/?\?v=([^&]+)~i',
			'~youtube\.com/[ev]/([^/]+)~i',
			'~youtube\.com/embed/([^/]+)~i',
			'~youtu\.be/([^/\?#]+)~i',
			'~youtube\.com/watch/?\?feature=player_embedded&v=([^&]+)~i',
			'~youtube\.com/\?feature=player_embedded&v=([^&]+)~i',
		);
		$provider = array();
		foreach ($patternlist as $pattern)
		{
			if (preg_match($pattern, $url, $matches))
			{
				$provider = array(
					'provider' => 'YouTube',
					'id' => $matches[1],
					'start' => $this->getTimestamp($url),
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

		$this->meta['start'] = $this->meta['start'] ?? '';
		return array(
			'display_template' => 'external',
			'external_url' => 'https://www.youtube.com/watch?v=' . $this->meta['id'],
			'video_id' => $this->meta['id'],
			'markup' => '
	<iframe class="base_iframe" style="width: 560px; height: 315px" src="//www.youtube-nocookie.com/embed/' . $this->meta['id'] . $this->meta['start'] . '" allowfullscreen></iframe>
	<div class="centertext ext_link"><a href="https://www.youtube.com/watch?v=' . $this->meta['id'] . '">' . $txt['lgal_view_youtube'] . '</a></div>',
		);
	}

	public function getTimestamp($link)
	{
		$pattern = '~\?t=(?:([1-9]{1,2})h)?(?:([1-9]{1,2})m)?(?:([1-9]+)s?)~';

		if (preg_match($pattern, $link, $match))
		{
			$startAtSeconds = 0;

			if (!empty($match[1]))
			{
				$startAtSeconds += $match[1] * 3600;
			}

			if (!empty($match[2]))
			{
				$startAtSeconds += $match[2] * 60;
			}

			if (!empty($match[3]))
			{
				$startAtSeconds += $match[3];
			}

			return '?start=' . $startAtSeconds;
		}

		return '';
	}

	public function getThumbnail()
	{
		require_once(SUBSDIR . '/Package.subs.php');
		if ($thumbnail_data = fetch_web_data('https://img.youtube.com/vi/' . $this->meta['id'] . '/0.jpg'))
		{
			return array('data' => $thumbnail_data, 'image_mime' => 'image/jpeg');
		}

		return false;
	}
}
