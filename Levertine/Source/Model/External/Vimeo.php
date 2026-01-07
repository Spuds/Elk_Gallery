<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0 / elkarte
 */

use ElkArte\Helper\Util;

/**
 * This file deals with externally-linked Vimeo videos.
 */
class Vimeo
{
	/** @var array|mixed  */
	private $meta;

	public function __construct($meta = [])
	{
		$this->meta = $meta;
	}

	public function matchURL($url)
	{
		$patternlist = [
			'~vimeo\.com/(\d+)~i',
			'~vimeo\.com/groups/[^/]+/videos/(\d+)~i',
		];
		$provider = [];
		foreach ($patternlist as $pattern)
		{
			if (preg_match($pattern, $url, $matches))
			{
				$provider = [
					'provider' => 'Vimeo',
					'id' => $matches[1],
					'mime_type' => 'external/_video',
				];
				break;
			}
		}

		return $provider;
	}

	public function getDetails()
	{
		global $txt;

		return [
			'display_template' => 'external',
			'external_url' => 'https://vimeo.com/' . $this->meta['id'],
			'video_id' => $this->meta['id'],
			'provider' => $this->meta['provider'],
			'markup' => '
	<div class="lg_item">		
		<iframe class="base_iframe" style="width: 500px; height: 281px" src="//player.vimeo.com/video/' . $this->meta['id'] . '?title=0" allowfullscreen></iframe>
		<div class="centertext ext_link"><a href="https://vimeo.com/' . $this->meta['id'] . '">' . $txt['lgal_view_vimeo'] . '</a></div>
	</div>',
		];
	}

	public function getThumbnail()
	{
		require_once(SUBSDIR . '/Pacakge.subs.php');

		if ($url_data = fetch_web_data('https://vimeo.com/api/v2/video/' . $this->meta['id'] . '.php'))
		{
			$array = Util::unserialize($url_data);
			if (!empty($array) && !empty($array[0]) && !empty($array[0]['thumbnail_medium']))
			{
				$thumb_url = filter_var($array[0]['thumbnail_medium'], FILTER_VALIDATE_URL);
				if (!empty($thumb_url) && $thumbnail_data = fetch_web_data($thumb_url))
				{
					return ['data' => $thumbnail_data, 'image_mime' => 'image/jpeg'];
				}
			}
		}

		return false;
	}
}
