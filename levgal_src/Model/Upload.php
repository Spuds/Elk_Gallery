<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.0 / elkarte
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
			if (in_array($master, $formats[$type]))
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
				if (in_array($master, $formats))
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
		else
		{
			return 0;
		}
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
		return $gal_size !== false ? $gal_size < $size : false;
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

		return $quotas;
	}

	public function getSpecificQuota($type)
	{
		global $user_info, $modSettings;

		if (allowedTo('lgal_manage'))
		{
			return true; // Always allowed.
		}

		$quotas = @unserialize($modSettings['lgal_' . $type . '_quotas']);
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
		return sha1(md5($filename . time()) . mt_rand());
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

	public function saveAsyncFile($filename)
	{
		// Check chunks.
		if (isset($_POST['chunk'], $_POST['chunks']))
		{
			$chunk = (int) $_POST['chunk'];
			$chunks = (int) $_POST['chunks'];
			if ($chunk < 0 || $chunks < 1 || $chunk >= $chunks)
			{
				return 'invalid';
			}
		}
		else
		{
			$chunk = 0;
			$chunks = 1;
		}

		// Check file types.
		$filename = $this->sanitizeFilename($filename);

		// If we're not chunking, or we're on the first one, we're assigning it by way of new id.
		if ($chunks == 1 || $chunk == 0)
		{
			// For the first (or only) one, check we're under quota.
			if (!allowedTo('lgal_manage') && !$this->isGalleryUnderQuota())
			{
				return 'over_quota';
			}
			$fileID = !empty($_SESSION['lgal_async']) ? max(array_keys($_SESSION['lgal_async'])) + 1 : 1;

			// And whether we match the file type. We don't need to do these every chunk since we match chunks after this one.
			$ext = $this->getExtension($filename);
			$formats = $this->getAllFileFormats();

			if (!in_array($ext, $formats))
			{
				return 'not_allowed';
			}

			// Now, is it within quota for this user? Sadly things like file size and image size can't be done here
			// because we don't get the right data, but we do get stuff that is useful.
			$map = $this->getFormatMap();
			$all_quotas = $this->getAllQuotas();
			$this_quota = $map[$ext] ?? '';
			if (empty($all_quotas[$this_quota]))
			{
				return 'not_allowed'; // Theoretically allowed as an upload format but not for this particular user.
			}
		}
		else
		{
			$fileID = false;

			$basename = basename($filename);
			// So we're hunting for it in session. Match by name, total chunks and last chunk number.
			foreach ($_SESSION['lgal_async'] as $this_fileID => $file)
			{
				if (is_array($file) && $file[2] == $basename && $file[1] == $chunks && $file[0] == $chunk - 1)
				{
					$fileID = $this_fileID;
					break;
				}
			}
			if ($fileID === false)
			{
				return 'invalid';
			}
		}

		$path = LevGal_Bootstrap::getGalleryDir();
		$user_ident = $this->getUserIdentifier();
		$local_file = 'async_' . $user_ident . '_' . $fileID . ($chunks > 1 ? '_part' : '') . '.dat';
		$local_full_file = 'async_' . $user_ident . '_' . $fileID . '.dat';

		if (!is_writable($path))
		{
			return 'not_writable';
		}

		$success = false;
		$out = @fopen($path . '/' . $local_file, $chunk == 0 ? 'wb' : 'ab');
		if ($out && is_uploaded_file($_FILES['file']['tmp_name']))
		{
			$in = @fopen($_FILES['file']['tmp_name'], 'rb');
			if ($in)
			{
				while ($buffer = fread($in, 4096))
				{
					fwrite($out, $buffer);
				}
				$success = true;
			}
		}

		if (isset($in))
		{
			@fclose($in);
		}
		@fclose($out);

		if (!$success)
		{
			return 'not_found';
		}

		// This the last chunk?
		if ($chunks > 1 && $chunk == $chunks - 1)
		{
			@rename($path . '/' . $local_file, $path . '/' . $local_full_file);
		}

		// Successful? Store details in session and return id.
		@unlink($_FILES['file']['tmp_name']);
		if ($chunks > 1 && $chunk < $chunks - 1)
		{
			$_SESSION['lgal_async'][$fileID] = array($chunk, $chunks, basename($filename));
		}
		else
		{
			$_SESSION['lgal_async'][$fileID] = basename($filename);
		}

		return $fileID;
	}

	public function validateUpload($fileID, $size, $filename)
	{
		// This is hardly bullet proof but we'll see.
		$fileID = (int) $fileID;
		$size = (int) $size;

		$path = LevGal_Bootstrap::getGalleryDir();
		$user_ident = $this->getUserIdentifier();
		$local_file = 'async_' . $user_ident . '_' . $fileID . '.dat';
		if (isset($_SESSION['lgal_async'][$fileID]) && is_array($_SESSION['lgal_async'][$fileID]))
		{
			// The upload wasn't actually finished but they tried submitting anyway.
			@unlink($path . '/' . $local_file);

			return 'async_invalid';
		}

		$filename = $this->sanitizeFilename($filename);

		// Are we under quota?
		if (!allowedTo('lgal_manage'))
		{
			$map = $this->getFormatMap();
			$all_quotas = $this->getAllQuotas();
			$ext = $this->getExtension($filename);
			$this_type = $map[$ext] ?? '';
			$this_quota = $all_quotas[$this_type] ?? false;
			if (empty($this_quota) || empty($this_quota['file']) || ($this_quota['file'] !== true && $this_quota['file'] < $size))
			{
				@unlink($path . '/' . $local_file);

				return 'upload_too_large';
			}

			// Of course, if it's an image we also need to check its size.
			if ($this_type === 'image')
			{
				$valid = false;
				if (!empty($this_quota['image']))
				{
					if ($this_quota['image'] === true)
					{
						$valid = true;
					}
					else
					{
						list ($width, $height) = getimagesize($path . '/' . $local_file);
						list ($quota_width, $quota_height) = explode('x', $this_quota['image']);
						if ($width <= $quota_width && $height <= $quota_height)
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
		}

		if (isset($_SESSION['lgal_async'][$fileID]) && (basename($filename) == $_SESSION['lgal_async'][$fileID]) && @file_exists($path . '/' . $local_file) && @filesize($path . '/' . $local_file) == $size)
		{
			return true;
		}

		@unlink($path . '/' . $local_file);

		return 'upload_no_validate';
	}

	public function moveUpload($fileID, $itemID, $filename)
	{
		// This is hardly bullet proof but we'll see.
		$this->getFileModel();
		$path = LevGal_Bootstrap::getGalleryDir();
		$user_ident = $this->getUserIdentifier();
		$local_file = 'async_' . $user_ident . '_' . $fileID . '.dat';

		$hash = $this->getFileHash($filename);
		$destFile = $this->baseFile($itemID, $filename, $hash);
		// Make the folders if they don't exist.
		$this->file_model->makePath($hash);

		$result = @rename($path . '/' . $local_file, $path . '/files/' . $hash[0] . '/' . $hash[0] . $hash[1] . '/' . $destFile);

		// And clean up in session.
		if ($result)
		{
			unset ($_SESSION['lgal_async'][$fileID]);
		}

		return $result ? $hash : false;
	}
}
