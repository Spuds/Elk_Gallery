<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.1.1 / elkarte
 */

/**
 * This file deals with externally-linked MetaCafe videos.
 */
class LevGal_Model_External_MetaCafe
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
			'~metacafe\.com/watch/(\d+)/~i',
		);
		$provider = array();
		foreach ($patternlist as $pattern)
		{
			if (preg_match($pattern, $url, $matches))
			{
				$provider = array(
					'provider' => 'MetaCafe',
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
			'external_url' => 'http://www.metacafe.com/watch/' . $this->meta['id'] . '/',
			'video_id' => $this->meta['id'],
			'markup' => '
	<iframe src="https://www.metacafe.com/embed/' . $this->meta['id'] . '/" width="540" height="304" allowFullScreen frameborder="0" style="margin: 0 auto; display:block"></iframe>
	<div class="centertext ext_link"><a href="https://www.metacafe.com/watch/' . $this->meta['id'] . '/">' . $txt['lgal_view_metacafe'] . '</a></div>',
		);
	}

	public function getThumbnail()
	{
		require_once(SUBSDIR . '/Package.subs.php');

		// This is a bit complicated, but essentially we can look up the thumbnail from the OpenGraph tags.
		if ($page = fetch_web_data('https://www.metacafe.com/watch/' . $this->meta['id'] . '/'))
		{
			if (preg_match('~<meta property="og:image" content="([^"]+)"( /)?>~i', $page, $match) && !empty($match[1]))
			{
				if ($thumbnail_data = fetch_web_data($match[1]))
				{
					return array('data' => $thumbnail_data, 'image_mime' => 'image/jpeg');
				}
			}
		}

		return false;
	}
}
