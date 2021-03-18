<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license proprietary
 *
 * @version 1.0 / elkarte
 */

/**
 * This file deals with preparing metadata about files wherever possible.
 */
class LevGal_Model_Metadata
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
		$meta_id3 = array();
		if (file_exists(SOURCEDIR . '/levgal_src/library/getid3/getid3.php'))
		{
			require_once(SOURCEDIR . '/levgal_src/library/getid3/getid3.php');
			$getID3 = new getID3;
			$id3 = $getID3->analyze($this->file);
			getid3_lib::CopyTagsToComments($id3);

			// Some of these we can get ready HTML formatted.
			$meta_id3['raw_id3'] = $id3;
			if (!empty($id3['mime_type']))
			{
				$meta_id3['mime_type'] = $id3['mime_type'];
			}

			if (isset($id3['comments_html']))
			{
				$tags = array('title', 'artist', 'album_artist', 'album', 'track_number', 'genre');
				foreach ($tags as $tag)
				{
					if (!empty($id3['comments_html'][$tag]))
					{
						$meta_id3[$tag] = $id3['comments_html'][$tag];
					}
				}
			}

			// We don't need to duplicate this.
			if (isset($meta_id3['artist'], $meta_id3['album_artist']) && $meta_id3['artist'] == $meta_id3['album_artist'])
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

				if (!empty($id3['mime_type']) && in_array($id3['mime_type'], array('image/jpg', 'image/jpeg')))
				{
					$exifModel = new LevGal_Model_Metadata_Exif($this->file);
					$exif = $exifModel->getExif();
					if (empty($exif['errors']))
					{
						$meta_id3['exif'] = $exif;
					}
				}
			}
		}

		// If no getID3, we should at least attempt to find a mime type with our own fallback.
		if (empty($meta_id3['mime_type']))
		{
			$mimeModel = new LevGal_Model_Mime($this->file, $this->filename);
			$mime_type = $mimeModel->getMimeType();
			$meta_id3['mime_type'] = !empty($mime_type) ? $mime_type : 'application/octet-stream';
		}

		return $meta_id3;
	}
}
