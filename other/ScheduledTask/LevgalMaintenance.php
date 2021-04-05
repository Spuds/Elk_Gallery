<?php

/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.0.2 / elkarte
 */

namespace ElkArte\sources\subs\ScheduledTask;

/**
 * Run various Levertine maintance functions.
 *
 * @package ScheduledTasks
 */
class Levgal_Maintenance implements Scheduled_Task_Interface
{
	/**
	 * Auto optimize the gallery.
	 */
	public function run()
	{
		$this->pruneTempUploads();
		$this->pruneSearchResults();

		return true;
	}

	private function pruneTempUploads()
	{
		$gal_dir = \LevGal_Bootstrap::getGalleryDir();

		// Kick anything more than 2 hours old.
		$most_recent = time() - (2 * 60 * 60);

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

	private function pruneSearchResults()
	{
		// Anything older than 4 hrs
		$most_recent = time() - (4 * 60 * 60);
		$search = new \LevGal_Model_Search();
		$search->deleteSearchesBeforeTimestamp($most_recent);
	}
}