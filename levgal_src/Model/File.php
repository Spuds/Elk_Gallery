<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.0 / elkarte
 */

/**
 * This file deals with actually fetching details about a file.
 */
class LevGal_Model_File
{
	/** @var bool */
	protected $current_item = false;
	/** @var bool */
	protected $current_album = false;

	public function getFileInfoById($itemId)
	{
		$db = database();

		// It's a uint, anything like this can disappear.
		if ($itemId < 0)
		{
			return false;
		}

		// This can be called multiple times, potentially, for the same album.
		if (!empty($this->current_item['id_item']) && $itemId = $this->current_item['id_item'])
		{
			return $this->current_item;
		}

		$request = $db->query('', '
			SELECT 
				id_item, id_album, item_slug, filename, filehash, filesize, extension, mime_type, 
				time_added, time_updated, approved, filesize
			FROM {db_prefix}lgal_items
			WHERE id_item = {int:itemId}',
			array(
				'itemId' => $itemId,
			)
		);

		if ($db->num_rows($request) > 0)
		{
			$this->current_item = $db->fetch_assoc($request);
		}
		$db->free_result($request);

		return $this->current_item;
	}

	public function getFilePaths()
	{
		$files = array();

		if (empty($this->current_item) || empty($this->current_item['filehash']))
		{
			return $files;
		}

		// This is mildly complicated. A .jpg file with filehash abcd and id 1 will have the following
		// path: (default) BOARDDIR/lgal_items/files/a/ab/1_abcd_jpg.dat
		$file_path = $this->current_item['filehash'][0] . '/' . $this->current_item['filehash'][0] . $this->current_item['filehash'][1] . '/' . $this->current_item['id_item'] . '_' . $this->current_item['filehash'] . (!empty($this->current_item['extension']) ? '_' . $this->current_item['extension'] : '');
		$gal_dir = LevGal_Bootstrap::getGalleryDir();

		$files['filehash'] = $this->current_item['filehash'];
		$files['fake_raw'] = $gal_dir . '/files/' . $file_path . '.dat';
		if (file_exists($files['fake_raw']))
		{
			$files['raw'] = $files['fake_raw'];
		}
		// External files have no physical local path but they may have thumbnails.
		elseif (strpos($this->current_item['mime_type'], 'external') !== 0)
		{
			trigger_error('File with id ' . $this->current_item['id_item'] . ', files/' . $file_path . '.dat is missing');

			return $files;
		}

		// If you ever add 4 character filetype specs instead of _jpg and _png, update the file sender, please.
		$possibles = array(
			'preview' => array('_preview.dat', '_preview_jpg.dat', '_preview_png.dat'),
			'thumb' => array('_thumb.dat', '_thumb_jpg.dat', '_thumb_png.dat'),
		);
		foreach ($possibles as $possible_type => $possible_suffix)
		{
			foreach ($possible_suffix as $suffix)
			{
				if (file_exists($gal_dir . '/files/' . $file_path . $suffix))
				{
					$files[$possible_type] = $gal_dir . '/files/' . $file_path . $suffix;
					break;
				}
			}
		}

		return $files;
	}

	public function makePath($hash)
	{
		$path = LevGal_Bootstrap::getGalleryDir();
		if (!file_exists($path . '/files/' . $hash[0]))
		{
			@mkdir($path . '/files/' . $hash[0]);
		}
		if (!file_exists($path . '/files/' . $hash[0] . '/' . $hash[0] . $hash[1]))
		{
			@mkdir($path . '/files/' . $hash[0] . '/' . $hash[0] . $hash[1]);
		}

		return $path . '/files/' . $hash[0] . '/' . $hash[0] . $hash[1];
	}

	public function getFileUrl()
	{
		global $scripturl;

		if (empty($this->current_item))
		{
			return false;
		}

		return $scripturl . '?media/file/' . (!empty($this->current_item['item_slug']) ? $this->current_item['item_slug'] . '.' : '') . $this->current_item['id_item'] . '/';
	}

	public function isApproved()
	{
		return !empty($this->current_item) && !empty($this->current_item['approved']);
	}

	public function isVisible()
	{
		global $user_info;

		// File invalid?
		if (empty($this->current_item))
		{
			return false;
		}

		// Is the user an admin or gallery manager?
		if (allowedTo('lgal_manage'))
		{
			return true;
		}

		// OK, so we need to check if the user can see the album it's in. For that we need the album model.
		if (empty($this->current_album))
		{
			$this->current_album = new LevGal_Model_Album();
			$this->current_album->getAlbumById($this->current_item['id_album']);
		}

		if (!$this->current_album->isVisible())
		{
			return false;
		}

		// So the user can see the album. If the item is approved or they're the album owner, they can see it.
		if ($this->isApproved() || $this->isOwnedByUser() || $this->current_album->isOwnedByUser() || ($user_info['is_guest'] && !empty($_SESSION['lgal_items']) && in_array($this->current_item['id_item'], $_SESSION['lgal_items'])))
		{
			return true;
		}

		// Otherwise no.
		return false;
	}

	public function isOwnedByUser()
	{
		global $user_info;

		return !empty($this->current_item['id_member']) && $this->current_item['id_member'] == $user_info['id'];
	}

	public function getETag()
	{
		return !empty($this->current_item) ? md5($this->current_item['id_item'] . '_' . $this->current_item['filename'] . '_' . $this->current_item['time_updated']) : '';
	}

	public function modifiedSince($timestamp)
	{
		return !empty($this->current_item) && $this->current_item['time_updated'] > $timestamp;
	}

	public function deleteFiles($file_list = array())
	{
		$files = $this->getFilePaths();
		foreach ($files as $key => $file)
		{
			if ((empty($file_list) || in_array($key, $file_list)) && $key !== 'filehash')
			{
				@unlink($file);
			}
		}

		// Now, is the folder empty? If so, delete it.
		$parentfolder = LevGal_Bootstrap::getGalleryDir() . '/files/' . $this->current_item['filehash'][0];
		$subfolder = $parentfolder . '/' . $this->current_item['filehash'][0] . $this->current_item['filehash'][1];

		// Subfolder first.
		if ($this->isEmptyFolder($subfolder))
		{
			@rmdir($subfolder);
			if ($this->isEmptyFolder($parentfolder))
			{
				@rmdir($parentfolder);
			}
		}

		return true;
	}

	protected function isEmptyFolder($path)
	{
		if (!class_exists('DirectoryIterator'))
		{
			return true; // We don't know, pretend there's something there in case.
		}

		if (!file_exists($path))
		{
			return false;
		}

		foreach (new DirectoryIterator($path) as $fileInfo)
		{
			if (!$fileInfo->isDot())
			{
				return true;
			}
		}

		return false;
	}
}
