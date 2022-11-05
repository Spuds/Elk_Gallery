<?php

/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.0 / elkarte
 */

namespace ElkArte\sources\subs\ScheduledTask;

/**
 * Run various Levertine maintenance functions.
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
		$this->recountGallery();

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

	private function recountGallery()
	{
		// First, flush the unseen count for everyone.
		$unseenModel = new \LevGal_Model_Unseen();
		$unseenModel->markForRecount();

		// Second, fix total items, comments etc.
		$maintModel = new \LevGal_Model_Maintenance();
		$maintModel->recalculateTotalItems();
		$maintModel->recalculateTotalComments();

		// Third, fix unapproved counts
		$commentModel = new \LevGal_Model_Comment();
		$commentModel->updateUnapprovedCount();

		// Fourth, fix report counts
		$reportModel = new \LevGal_Model_Report();
		$reportModel->resetReportCount();

		// Fix master counts for things
		$maintModel->fixItemStats();
		$maintModel->fixAlbumStats();
	}
}