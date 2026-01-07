<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.0 / elkarte
 */

/**
 * This file provides the stats page, site/?media/stats/.
 */
class LevGal_Action_Stats extends LevGal_Action_Abstract
{
	public function actionIndex()
	{
		global $context, $txt, $scripturl;

		// Stuff we will need
		$this->setTemplate('LevGal-Stats', 'stats', 'admin_lg.css');
		loadLanguage('levgal_lng/LevGal-Stats');
		loadJavascriptFile('chart.min.js', ['subdir' => 'levgal_res', 'defer' => false]);

		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($txt['levgal_stats_linktree'], '?media/stats/');
		$context['canonical_url'] = $scripturl . '?media/stats/';

		$context['page_title'] = sprintf($txt['levgal_stats'], $context['forum_name']);

		$this->getGeneralStats();

		call_integration_hook('integrate_lgal_stats');
	}

	protected function getGeneralStats()
	{
		global $context;

		$statsModel = new LevGal_Model_Stats();

		$context['general_stats'] = array(
			'left' => array(),
			'right' => array(),
		);

		// Easy ones.
		$total_items = $statsModel->getTotalItems();
		$total_comments = $statsModel->getTotalComments();
		$total_albums = $statsModel->getTotalAlbums();

		$context['general_stats']['left']['total_items'] = comma_format($total_items);
		$context['general_stats']['left']['total_comments'] = comma_format($total_comments);
		$context['general_stats']['left']['total_albums'] = comma_format($total_albums);

		// Averages are fun, don't you think?
		$time_since_installation = $statsModel->timeSinceInstall();
		$context['general_stats']['right']['average_items_day'] = comma_format($total_items / ceil($time_since_installation / 86400));
		$context['general_stats']['right']['average_comments_day'] = comma_format($total_comments / ceil($time_since_installation / 86400));

		// Total file size.
		$size = $statsModel->getTotalGallerySize();
		if ($size !== false)
		{
			$context['general_stats']['right']['total_filesize'] = LevGal_Helper_Format::filesize($size);
		}

		// Top posters and top albums.
		$context['top_posters'] = $statsModel->getTopPosters();
		$context['top_albums'] = $statsModel->getTopAlbums();
		$context['top_items_by_comments'] = $statsModel->getTopItemsByComments();
		$context['top_items_by_views'] = $statsModel->getTopItemsByViews();
	}
}
