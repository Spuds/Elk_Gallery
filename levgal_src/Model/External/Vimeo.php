<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.0 / elkarte
 */

/**
 * This file deals with externally-linked Vimeo videos.
 */
class LevGal_Model_External_Vimeo
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
			'~vimeo\.com/(\d+)~i',
			'~vimeo\.com/groups/[^/]+/videos/(\d+)~i',
		);
		$provider = array();
		foreach ($patternlist as $pattern)
		{
			if (preg_match($pattern, $url, $matches))
			{
				$provider = array(
					'provider' => 'Vimeo',
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
			'external_url' => 'http://vimeo.com/' . $this->meta['id'],
			'video_id' => $this->meta['id'],
			'markup' => '
	<iframe width="500" height="281" src="//player.vimeo.com/video/' . $this->meta['id'] . '?title=0" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen style="margin: 0 auto; display:block"></iframe>
	<div class="centertext ext_link"><a href="http://vimeo.com/' . $this->meta['id'] . '">' . $txt['lgal_view_vimeo'] . '</a></div>',
		);
	}

	public function getThumbnail()
	{
		require_once(SUBSDIR . '/Pacakge.subs.php');

		if ($url_data = fetch_web_data('http://vimeo.com/api/v2/video/' . $this->meta['id'] . '.php'))
		{
			$array = @unserialize($url_data);
			if (!empty($array) && !empty($array[0]) && !empty($array[0]['thumbnail_medium']))
			{
				$thumb_url = filter_var($array[0]['thumbnail_medium'], FILTER_VALIDATE_URL);
				if (!empty($thumb_url))
				{
					if ($thumbnail_data = fetch_web_data($thumb_url))
					{
						return array('data' => $thumbnail_data, 'image_mime' => 'image/jpeg');
					}
				}
			}
		}

		return false;
	}
}
