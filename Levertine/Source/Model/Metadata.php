<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0 / elkarte
 */

namespace Addons\Levertine\Source\Model;

use Addons\Levertine\Source\Model\Metadata\Exif;
use ElkArte\Helper\FileFunctions;

/**
 * This file deals with preparing metadata about files wherever possible.
 */
class Metadata
{
	/** @var string */
	private $file;

	/** @var string */
	private $filename;

	public function __construct($filepath, $filename)
	{
		$this->file = $filepath;
		$this->filename = $filename;
	}

	public function getMetadata()
	{
		// First, we should really use getID3 if we have it.
		$meta_id3 = [];
		if (FileFunctions::instance()->fileExists(ADDONSDIR . '/Levertine/Source/library/getid3/getid3.php'))
		{
			require_once(ADDONSDIR . '/Levertine/Source/library/getid3/getid3.php');
			$getID3 = new \getID3;
			$id3 = $getID3->analyze($this->file);
			$getID3->CopyTagsToComments($id3);

			// Some of these we can get ready HTML formatted.
			$meta_id3['raw_id3'] = $id3;
			if (!empty($id3['mime_type']))
			{
				$meta_id3['mime_type'] = $id3['mime_type'];
			}

			if (isset($id3['comments_html']))
			{
				$tags = ['title', 'artist', 'album_artist', 'album', 'track_number', 'genre'];
				foreach ($tags as $tag)
				{
					if (!empty($id3['comments_html'][$tag]))
					{
						$meta_id3[$tag] = $id3['comments_html'][$tag];
					}
				}
			}

			// We don't need to duplicate this.
			if (isset($meta_id3['artist'], $meta_id3['album_artist']) && $meta_id3['artist'] === $meta_id3['album_artist'])
			{
				unset ($meta_id3['album_artist']);
			}

			// Other exciting things.
			if (isset($id3['playtime_seconds']))
			{
				$meta_id3['playtime'] = sprintf('%01.2f', $id3['playtime_seconds']);
			}

			if (isset($id3['bitrate']))
			{
				$meta_id3['bitrate'] = (int) $id3['bitrate'];
			}

			if (isset($id3['comments']['picture'][0]['data']))
			{
				$meta_id3['thumbnail'] = $id3['comments']['picture'][0];
			}

			if (!empty($id3['video']['resolution_x']) && !empty($id3['video']['resolution_y']))
			{
				$meta_id3['width'] = $id3['video']['resolution_x'];
				$meta_id3['height'] = $id3['video']['resolution_y'];

				if (!empty($id3['mime_type']) && in_array($id3['mime_type'], ['image/jpg', 'image/jpeg']))
				{
					$exifModel = new Exif($this->file);
					$exif = $exifModel->getExif();
					if (empty($exifModel->getErrors()))
					{
						$meta_id3['exif'] = $exif;
					}
				}
			}
		}

		// If no getID3, we should at least attempt to find a mime type with our own fallback.
		if (empty($meta_id3['mime_type']))
		{
			$mimeModel = new Mime($this->file, $this->filename);
			$mime_type = $mimeModel->getMimeType();
			$meta_id3['mime_type'] = !empty($mime_type) ? $mime_type : 'application/octet-stream';
		}

		return $meta_id3;
	}
}
