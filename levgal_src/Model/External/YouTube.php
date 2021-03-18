<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license proprietary
 *
 * @version 1.1.1 / elkarte
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
			'external_url' => 'https://www.youtube.com/watch?v=' . $this->meta['id'],
			'video_id' => $this->meta['id'],
			'markup' => '
	<iframe width="560" height="315" src="//www.youtube-nocookie.com/embed/' . $this->meta['id'] . '?wmode=transparent" frameborder="0" allowfullscreen style="margin: 0 auto; display:block"></iframe>
	<div class="centertext ext_link"><a href="https://www.youtube.com/watch?v=' . $this->meta['id'] . '">' . $txt['lgal_view_youtube'] . '</a></div>',
		);
	}

	public function getThumbnail()
	{
		require_once(SUBSDIR . '/Package.subs.php');
		if ($thumbnail_data = fetch_web_data('http://img.youtube.com/vi/' . $this->meta['id'] . '/0.jpg'))
		{
			return array('data' => $thumbnail_data, 'image_mime' => 'image/jpeg');
		}

		return false;
	}
}
