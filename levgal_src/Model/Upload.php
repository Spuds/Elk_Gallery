<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.0 / elkarte
 */

/**
 * This file deals with the dynamics of actually processing uploads.
 */
class LevGal_Model_Upload
{
	/** @var LevGal_Model_File */
	private $file_model;

	protected function getFileModel()
	{
		if ($this->file_model === null)
		{
			$this->file_model = new LevGal_Model_File();
		}
	}

	public function isTypeEnabled($type)
	{
		global $modSettings;

		return !empty($modSettings['lgal_enable_' . $type]);
	}

	public function isResizingEnabled()
	{
		global $modSettings;

		return !empty($modSettings['lgal_enable_resize']);
	}

	public function typesEnabled()
	{
		global $modSettings;
		$enabled = array();
		foreach (array('image', 'audio', 'video', 'document', 'archive', 'generic', 'external') as $type)
		{
			if (!empty($modSettings['lgal_enable_' . $type]))
			{
				$enabled[] = $type;
			}
		}

		return $enabled;
	}

	public function enabledFormats($type)
	{
		global $modSettings;

		return !empty($modSettings['lgal_' . $type . '_formats']) ? explode(',', $modSettings['lgal_' . $type . '_formats']) : array();
	}

	public function getDisplayFileFormats()
	{
		global $txt;
		loadLanguage('levgal_lng/ManageLevGal-Quotas');

		$format_list = array();
		$types = $this->typesEnabled();
		foreach ($types as $type)
		{
			$formats = $this->enabledFormats($type);
			if (!empty($formats))
			{
				if ($type !== 'external')
				{
					// External things don't follow the quota system.
					$quota = $this->getSpecificQuota($type);
					if ($quota === false)
					{
						continue;
					}
				}
				foreach ($formats as $format)
				{
					$format_list[$type][$format] = $txt['lgal_' . $type . '_' . $format] ?? $format;
				}
			}
		}

		return $format_list;
	}

	public function getAllFileFormats()
	{
		static $format_list = null;

		if ($format_list !== null)
		{
			return $format_list;
		}

		$format_list = array();
		$types = $this->typesEnabled();
		// Don't want external here.
		$types = array_diff($types, array('external'));
		foreach ($types as $type)
		{
			$format_list = array_merge($format_list, $this->getFileFormatsByType($type));
		}

		return array_unique($format_list);
	}

	public function getFileFormatsByType($type)
	{
		static $formats = null;

		if (isset($formats[$type]))
		{
			return $formats[$type];
		}

		$formats[$type] = $this->enabledFormats($type);
		// There are certain... mutant... formats.
		$alts = $this->getAlternateExtensions();
		foreach ($alts as $master => $extras)
		{
			if (in_array($master, $formats[$type], true))
			{
				$formats[$type] = array_merge($formats[$type], $extras);
			}
		}

		return $formats[$type];
	}

	protected function getAlternateExtensions()
	{
		// See also LevGal_Model_Item::getIconUrl if we update this.
		return array(
			'doc' => array(
				'dot', 'docm', 'docx', 'dotm', 'dotx', // MS Word
				'fodt', 'odt', // OpenDocument/LibreOffice Writer
				'stw', 'sxg', 'sxw', // StarOffice/OpenOffice Writer
			),
			'exe' => array('bin', 'dll'),
			'html' => array('htm', 'mhtm', 'mhtml'),
			'iff' => array('lbm'),
			'jpg' => array('jpeg'),
			'lz' => array('lzma'),
			'm4a' => array('mp4'),
			'm4v' => array('mp4'),
			'mov' => array('mqv', 'qt'),
			'oga' => array('ogg'),
			'ogv' => array('ogg'),
			'ppt' => array(
				'pot', 'potm', 'potx', 'ppam', 'pps', 'ppsm', 'ppsx', 'pptm', 'pptx', 'sldm', 'sldx', // MS Powerpoint
				'fodp', 'odp', // OpenDocument/LibreOffice Impress
				'sti', 'sxi', // StarOffice/OpenOffice Impress
			),
			'targz' => array('tar', 'gz', 'tgz', 'bz2', 'tbz2', 'z'),
			'ttf' => array('otf'),
			'xls' => array(
				'xla', 'xlam', 'xll', 'xlm', 'xlw', 'xlsb', 'xlsm', 'xlsx', 'xlt', 'xltm', 'xltx', // MS Excel
				'fods', 'ods', // OpenDocument/LibreOffice Calc
				'stc', 'sxc', // StarOffice/OpenOffice Calc
			),
		);
	}

	public function getFormatMap()
	{
		static $map = null;

		if ($map !== null)
		{
			return $map;
		}

		$types = $this->typesEnabled();
		$alts = $this->getAlternateExtensions();
		// Don't want external here.
		$types = array_diff($types, array('external'));

		$map = array();

		foreach ($types as $type)
		{
			$formats = $this->getFileFormatsByType($type);
			foreach ($alts as $master => $extras)
			{
				if (in_array($master, $formats, true))
				{
					$formats = array_merge($formats, $extras);
				}
			}
			foreach ($formats as $format)
			{
				$map[$format] = $type;
			}
		}

		return $map;
	}

	public function getByteSize($human_size)
	{
		$human_size = strtolower($human_size);
		if (preg_match('~^(\d+)([kmgt])$~', $human_size, $match))
		{
			$multiplier = array(
				'k' => 1024,
				'm' => 1048576,
				'g' => 1073741824,
				't' => 1099511627776,
			);

			return $match[1] * $multiplier[$match[2]];
		}

		return 0;
	}

	public function getGalleryQuota()
	{
		global $modSettings;

		return $this->getByteSize($modSettings['lgal_max_space']);
	}

	public function isGalleryUnderQuota()
	{
		$size = $this->getGalleryQuota();
		$statsModel = LevGal_Bootstrap::getModel('LevGal_Model_Stats');
		$gal_size = $statsModel->getTotalGallerySize();

		// If we don't get an actual size, play it safe and disallow it.
		return $gal_size !== false && $gal_size < $size;
	}

	public function getAllQuotas()
	{
		$quota_list = $this->typesEnabled();
		if (empty($quota_list))
		{
			return array();
		}

		$quota_list = array_diff($quota_list, array('external'));
		foreach ($quota_list as $quota)
		{
			$this_quota = $this->getSpecificQuota($quota);
			if (!empty($this_quota))
			{
				$quotas[$quota] = $this_quota;
			}
		}

		return empty($quotas) ? array() : $quotas;
	}

	public function getSpecificQuota($type)
	{
		global $user_info, $modSettings;

		if (allowedTo('lgal_manage'))
		{
			return true; // Always allowed.
		}

		$quotas = Util::unserialize($modSettings['lgal_' . $type . '_quotas']);
		if (empty($quotas))
		{
			return false;
		}

		$unlimited_size = false;
		$unlimited_file = false;
		$temp_quotas = array();

		foreach ($quotas as $quota)
		{
			if ($type === 'image')
			{
				list ($groups, $imagesize, $filesize) = $quota;
			}
			else
			{
				list ($groups, $filesize) = $quota;
			}

			if (count(array_intersect($groups, $user_info['groups'])) > 0)
			{
				if (isset($imagesize))
				{
					if ($imagesize === '0x0')
					{
						$unlimited_size = true;
					}
					elseif (!$unlimited_size)
					{
						$temp_quotas['image'][$imagesize] = $imagesize;
					}
				}

				if ($filesize === '0')
				{
					$unlimited_file = true;
				}
				elseif (!$unlimited_file)
				{
					$temp_quotas['file'][$filesize] = $this->getByteSize($filesize);
				}
			}
		}

		// Either way we should have a file size.
		$final_quota = array();
		if (!$unlimited_file && empty($temp_quotas['file']))
		{
			return false; // No quota for you!
		}

		if ($unlimited_file)
		{
			$final_quota['file'] = true;
		}
		else
		{
			arsort($temp_quotas['file']);
			$keys = $temp_quotas['file'];
			$final_quota['file'] = array_shift($keys);
		}

		// We may or may not have an image size.
		if (isset($imagesize))
		{
			if (!$unlimited_size && empty($temp_quotas['image']))
			{
				return false; // No quota for you!
			}

			if ($unlimited_size)
			{
				$final_quota['image'] = true;
			}
			else
			{
				arsort($temp_quotas['image']);
				$keys = $temp_quotas['image'];
				$final_quota['image'] = array_shift($keys);
			}
		}

		return $final_quota;
	}

	public function assertGalleryWritable()
	{
		$gal_dir = LevGal_Bootstrap::getGalleryDir();
		if (!is_writable($gal_dir . '/files/'))
		{
			LevGal_Helper_Http::fatalError('lgal_dir_not_writable');
		}
	}

	public function getFileHash($filename)
	{
		return hash('sha1', hash('md5', $filename . time()) . mt_rand());
	}

	public function baseFile($id, $filename, $hash)
	{
		return $id . '_' . $hash . '_' . $this->getExtension($filename) . '.dat';
	}

	public function getExtension($filename)
	{
		return strtolower(substr(strrchr($filename, '.'), 1));
	}

	public function sanitizeFilename($filename)
	{
		return preg_replace('~["<>:/\\\\]+~', '', $filename);
	}

	protected function getUserIdentifier()
	{
		global $user_info, $context;

		return !empty($user_info['id']) ? $user_info['id'] : preg_replace('~[^0-9a-z]~i', '', $context['session_id']);
	}

	public function errorAsyncFile($code, $fileID)
	{
		global $txt;

		loadLanguage('levgal_lng/LevGal');
		loadLanguage('levgal_lng/LevGal-Errors');

		if ($code === 'over_quota')
		{
			$uploadModel = new LevGal_Model_Upload();
			$txt['lgal_async_over_quota'] = sprintf($txt['levgal_gallery_over_quota'], LevGal_Helper_Format::filesize($uploadModel->getGalleryQuota()));
		}
		$error = $txt['lgal_async_' . $code] ?? $code;

		// Clean up
		$path = LevGal_Bootstrap::getGalleryDir();
		$user_ident = $this->getUserIdentifier();
		$in = $path . '/async_' . $user_ident . '_' . $fileID . '*.dat';
		$iterator = new GlobIterator($in, FilesystemIterator::SKIP_DOTS | FilesystemIterator::KEY_AS_FILENAME);
		foreach ($iterator as $file)
		{
			@unlink($file->getPathname());
		}

		return ['error' => $error, 'code' => $code, 'id' => $fileID];
	}

	public function validateAsyncFile($filename, $fileID)
	{
		// For the first (or only) one, check we're under quota.
		if (!allowedTo('lgal_manage') && !$this->isGalleryUnderQuota())
		{
			return $this->errorAsyncFile( 'over_quota', $fileID);
		}

		// And whether we match the file type. We don't need to do these every chunk
		// since we match chunks after this one.
		$ext = $this->getExtension($filename);
		$formats = $this->getAllFileFormats();
		if (!in_array($ext, $formats, true))
		{
			return $this->errorAsyncFile( 'not_allowed', $fileID);
		}

		// Now, is it within quota for this user? Sadly things like file size and image size
		// can't be done here because we don't get the right data, but we do get stuff that is useful.
		$map = $this->getFormatMap();
		$all_quotas = $this->getAllQuotas();
		$this_quota = $map[$ext] ?? '';
		if (empty($all_quotas[$this_quota]))
		{
			// Theoretically allowed as an upload format but not for this particular user.
			return $this->errorAsyncFile( 'not_allowed', $fileID);
		}

		return true;
	}

	public function saveAsyncFile($filename)
	{
		$chunk = 0;
		$chunks = 1;

		// Check if this is chunked
		if (isset($_POST['dzchunkindex'], $_POST['dztotalchunkcount']))
		{
			$chunk = (int) $_POST['dzchunkindex'];
			$chunks = (int) $_POST['dztotalchunkcount'];
			if ($chunk < 0 || $chunks < 1 || $chunk >= $chunks)
			{
				return $this->errorAsyncFile('invalid', 0);
			}
		}

		// We must be given the uuid
		if (!isset($_POST['dzuuid']) && !isset($_POST['async']))
		{
			return $this->errorAsyncFile('invalid', 0);
		}

		$filename = $this->sanitizeFilename($filename);
		$fileID = $_POST['dzuuid'] ?? $_POST['async'];

		// If we're not chunking, or we're on the first one, validate
		if ($chunks === 1 || ($chunks > 1 && $chunk === 0))
		{
			// Check file types, quotas, permissions
			$result = $this->validateAsyncFile($filename, $fileID);
			if ($result !== true)
			{
				return $result;
			}
		}

		$path = LevGal_Bootstrap::getGalleryDir();
		if (!is_writable($path))
		{
			return $this->errorAsyncFile( 'not_writable', $fileID);
		}

		$user_ident = $this->getUserIdentifier();
		$local_file = 'async_' . $user_ident . '_' . $fileID . ($chunks > 1 ? '_part_' . $chunk : '') . '.dat';
		$out = $path . '/' . $local_file;
		$in = $_FILES['file']['tmp_name'];
		@move_uploaded_file($in, $out);
		$success = file_exists($out) ;
		if (!$success)
		{
			return $this->errorAsyncFile( 'not_found', $fileID);
		}

		return ['id' => $fileID, 'code' => ''];
	}

	public function combineChunks($fileID, $chunks, $filename)
	{
		$path = LevGal_Bootstrap::getGalleryDir();
		$user_ident = $this->getUserIdentifier();
		$in = $path . '/async_' . $user_ident . '_' . $fileID . '_part_*.dat';

		// Check that all chunks do exist
		$iterator = new GlobIterator($in, FilesystemIterator::SKIP_DOTS | FilesystemIterator::KEY_AS_FILENAME);
		if (!$iterator->count() || $iterator->count() !== $chunks)
		{
			return $this->errorAsyncFile( 'not_found', $fileID);
		}

		// Combine the chunks in the correct order
		$success = true;
		$out = $path . '/async_' . $user_ident . '_' . $fileID . '.dat';
		for ($i = 0; $i < $chunks; $i++)
		{
			$in = $path . '/async_' . $user_ident . '_' . $fileID . '_part_' . $i . '.dat';
			$success &= file_put_contents($out, file_get_contents($in), LOCK_EX | FILE_APPEND) !== false;
			@unlink($in);
		}

		// Often success feels empty
		if (empty($success))
		{
			return $this->errorAsyncFile( 'not_found', $fileID);
		}

		return ['id' => $fileID, 'code' => ''];
	}

	public function validateUpload($fileID, $size, $filename)
	{
		// This is hardly bulletproof but we'll see.
		$size = (int) $size;

		$path = LevGal_Bootstrap::getGalleryDir();
		$user_ident = $this->getUserIdentifier();
		$local_file = 'async_' . $user_ident . '_' . $fileID . '.dat';
		$filename = $this->sanitizeFilename($filename);

		// Are we under quota?
		if (!allowedTo('lgal_manage'))
		{
			$map = $this->getFormatMap();
			$all_quotas = $this->getAllQuotas();
			$ext = $this->getExtension($filename);
			$this_type = $map[$ext] ?? '';
			$this_quota = $all_quotas[$this_type] ?? false;

			// Of course, if it's an image we also need to check its size.
			if ($this_type === 'image')
			{
				$valid = false;
				if (!empty($this_quota['image']))
				{
					if ($this_quota['image'] === true && $this_quota['file'] === true)
					{
						$valid = true;
					}
					else
					{
						require_once(SUBSDIR . '/Attachments.subs.php');
						// If we have dimension clamping enabled, now is the time to enforce it so that we
						// check final size and dimensions on that image
						if ($this->isResizingEnabled() && in_array($ext, ['png', 'jpg', 'jpeg', 'webp']))
						{
							$this->resizeUpload($local_file, $this_quota, $size);
						}
						list ($width, $height) = elk_getimagesize($path . '/' . $local_file);
						list ($quota_width, $quota_height) = explode('x', $this_quota['image'] === true ? $width . 'x' . $height : $this_quota['image']);
						if ($width <= $quota_width && $height <= $quota_height && ($this_quota['file'] === true || $size <= $this_quota['file']))
						{
							$valid = true;
						}
					}
				}

				if (!$valid)
				{
					@unlink($path . '/' . $local_file);

					return 'upload_image_too_big';
				}
			}
			if (empty($this_quota) || empty($this_quota['file']) || ($this_quota['file'] !== true && $this_quota['file'] < $size))
			{
				@unlink($path . '/' . $local_file);

				return 'upload_too_large';
			}
		}

		if (@file_exists($path . '/' . $local_file)
			&& @filesize($path . '/' . $local_file) === $size)
		{
			return true;
		}

		@unlink($path . '/' . $local_file);

		return 'upload_no_validate';
	}

	public function resizeUpload($local_file, $this_quota, &$size)
	{
		global $context;

		$image = new LevGal_Helper_Image();
		$path = LevGal_Bootstrap::getGalleryDir();

		// Normally checked at the end, but we may change size, so we do it here and now as well
		if (@filesize($path . '/' . $local_file) !== $size)
		{
			return;
		}

		list ($width, $height) = elk_getimagesize($path . '/' . $local_file);
		list ($quota_width, $quota_height) = explode('x', $this_quota['image']);

		// Allowed to change the WxH ?
		if ($this_quota['image'] !== true && ($width > $quota_width || $height > $quota_height))
		{
			$ext = $image->loadImageFromFile($path . '/' . $local_file);
			if ($ext !== false)
			{
				$image->fixDimensions(min($quota_width, $quota_height), $path . '/' . $local_file, $ext);
				clearstatcache(true);
				$size = @filesize($path . '/' . $local_file);
				$context['async_size'] = $size;
			}

			return;
		}

		// Just trying to enforce filesize, maybe our default compression will helpful
		if ($this_quota['file'] !== true && $size > $this_quota['file'])
		{
			$ext = $image->loadImageFromFile($path . '/' . $local_file);
			if ($ext !== false)
			{
				$image->fixDimensions(max($width, $height), $path . '/' . $local_file, $ext);
				clearstatcache(true);
				$size = @filesize($path . '/' . $local_file);
				$context['async_size'] = $size;
			}
		}
	}

	public function moveUpload($fileID, $itemID, $filename)
	{
		// This is hardly bulletproof but we'll see.
		$this->getFileModel();
		$path = LevGal_Bootstrap::getGalleryDir();
		$user_ident = $this->getUserIdentifier();
		$local_file = 'async_' . $user_ident . '_' . $fileID . '.dat';

		$hash = $this->getFileHash($filename);
		$destFile = $this->baseFile($itemID, $filename, $hash);
		// Make the folders if they don't exist.
		$this->file_model->makePath($hash);

		$result = @rename($path . '/' . $local_file, $path . '/files/' . $hash[0] . '/' . $hash[0] . $hash[1] . '/' . $destFile);

		return $result ? $hash : false;
	}
}
