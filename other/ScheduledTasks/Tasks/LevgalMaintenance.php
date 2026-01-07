<?php

/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0 / elkarte
 */

namespace ElkArte\ScheduledTasks\Tasks;

use Addons\Levertine\Source\LevGalBootstrap;
use Addons\Levertine\Source\Model\Comment;
use Addons\Levertine\Source\Model\Maintenance;
use Addons\Levertine\Source\Model\Report;
use Addons\Levertine\Source\Model\Search;
use Addons\Levertine\Source\Model\Unseen;

/**
 * Run various Levertine maintenance functions.
 *
 * @package ScheduledTasks
 */
class LevgalMaintenance implements ScheduledTaskInterface
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
		$gal_dir = LevGalBootstrap::getGalleryDir();

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
		$search = new Search();
		$search->deleteSearchesBeforeTimestamp($most_recent);
	}

	private function recountGallery()
	{
		// First, flush the unseen count for everyone.
		$unseenModel = new Unseen();
		$unseenModel->markForRecount();

		// Second, fix total items, comments etc.
		$maintModel = new Maintenance();
		$maintModel->recalculateTotalItems();
		$maintModel->recalculateTotalComments();

		// Third, fix unapproved counts
		$commentModel = new Comment();
		$commentModel->updateUnapprovedCount();

		// Fourth, fix report counts
		$reportModel = new Report();
		$reportModel->resetReportCount();

		// Fix master counts for things
		$maintModel->fixItemStats();
		$maintModel->fixAlbumStats();
	}
}
