<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.0.2 / elkarte
 */

/**
 * This file deals with the scheduled maintenance that needs to be run.
 */
class LevGal_Helper_Scheduled
{
	public static function execute()
	{
		self::pruneTempUploads();
		self::pruneSearchResults();
		self::checkPortals();

		return true;
	}

	protected static function pruneTempUploads()
	{
		$gal_dir = LevGal_Bootstrap::getGalleryDir();

		// Kick anything more than 6 hours old.
		$most_recent = time() - (6 * 60 * 60);

		foreach (scandir($gal_dir) as $file)
		{
			$filepath = $gal_dir . '/' . $file;
			if (is_dir($filepath))
			{
				continue;
			}

			if (strpos($file, 'async_') === 0 && substr($file, -4) === '.dat' && @filemtime($filepath) < $most_recent)
			{
				@unlink($filepath);
			}

			if (strpos($file, 'album_') === 0 && @filemtime($filepath) < $most_recent)
			{
				@unlink($filepath);
			}
		}
	}

	protected static function pruneSearchResults()
	{
		$most_recent = time() - (12 * 60 * 60);
		$search = new LevGal_Model_Search();
		$search->deleteSearchesBeforeTimestamp($most_recent);
	}

	public static function checkPortals()
	{
		// TinyPortal doesn't require a check for this, since it uses separate blockcode.
		foreach (array('SimplePortal') as $portal)
		{
			$class = 'LevGal_Portal_' . $portal;
			$instance = new $class();
			if ($instance->isPortalInstalled())
			{
				$instance->ensureInstalled();
			}
		}
	}
}
