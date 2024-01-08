<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.0 / elkarte
 */

/**
 * This file provides the home index for the gallery, site/?media/.
 */
class LevGal_Action_Home extends LevGal_Action_Abstract
{
	public function actionIndex()
	{
		global $context, $txt, $scripturl, $user_settings, $modSettings;

		// First we need the language and templates.
		loadLanguage('levgal_lng/LevGal');
		loadLanguage('levgal_lng/LevGal-Stats');

		// Recent / Random Dependencies
		loadCSSFile('glightbox.min.css', ['subdir' => 'levgal_res/lightbox']);
		loadJavascriptFile('glightbox.min.js', ['subdir' => 'levgal_res/lightbox', 'defer => true']);

		$this->addLinkTree($txt['levgal'], '?media/');
		$context['canonical_url'] = $scripturl . '?media/';
		$this->setTemplate('LevGal', 'main');

		$context['page_title'] = $txt['levgal'];
		$_SESSION['levgal_breadcrumbs'] = [];

		// Featured items are very simple. And we even get to do some caching magic.
		/** @var \LevGal_Model_AlbumList $albumList */
		$albumList = LevGal_Bootstrap::getModel('LevGal_Model_AlbumList');
		$context['featured_albums'] = $albumList->getFeaturedAlbums();

		// The main area is fairly dull.
		$itemList = LevGal_Bootstrap::getModel('LevGal_Model_ItemList');
		/** @var \LevGal_Model_ItemList $itemList */
		$context['latest_items'] = $itemList->getLatestItems(20);
		$context['random_items'] = $itemList->getRandomItems(10);

		// Sidebar not much better.
		/** @var \LevGal_Model_Stats $statsModel */
		$statsModel = LevGal_Bootstrap::getModel('LevGal_Model_Stats');
		$context['stats'] = array(
			'levgal_stats_total_items' => comma_format($statsModel->getTotalItems()),
			'levgal_stats_total_comments' => comma_format($statsModel->getTotalComments()),
			'levgal_stats_total_albums' => comma_format($statsModel->getTotalAlbums()),
		);

		$context['gallery_actions'] = array();

		if (!$context['user']['is_guest'] && allowedTo(array('lgal_adduseralbum')))
		{
			$context['gallery_actions']['actions']['myalbums'] = array($txt['levgal_myalbums'], $scripturl . '?media/albumlist/' . $context['user']['id'] . '/member/', 'tab' => true, 'sidebar' => false);
		}

		if (!empty($context['stats']['levgal_stats_total_albums']) || allowedTo(array('lgal_manage', 'lgal_adduseralbum', 'lgal_addgroupalbum')))
		{
			$context['gallery_actions']['actions']['album'] = array($txt['lgal_see_albums'], $scripturl . '?media/albumlist/', 'tab' => true);
		}

		if (allowedTo(array('lgal_manage', 'lgal_adduseralbum', 'lgal_addgroupalbum')))
		{
			$context['gallery_actions']['actions']['addalbum'] = array($txt['levgal_newalbum'], $scripturl . '?media/newalbum/', 'tab' => true);
		}

		if (!empty($user_settings['lgal_new']))
		{
			$unseenModel = LevGal_Bootstrap::getModel('LevGal_Model_Unseen');
			$unseenModel->updateUnseenItems();
		}
		if (!$context['user']['is_guest'] && !empty($user_settings['lgal_unseen']))
		{
			$context['gallery_actions']['actions']['new'] = array($txt['levgal_unseen'] . ' [<strong>' . $user_settings['lgal_unseen'] . '</strong>]', $scripturl . '?media/unseen/', 'tab' => true);
		}

		$context['gallery_actions']['actions']['search'] = array($txt['levgal_search'], $scripturl . '?media/search/', 'tab' => true);
		$context['gallery_actions']['actions']['stats'] = array($txt['lgal_gallery_stats'], $scripturl . '?media/stats/');
		$context['gallery_actions']['actions']['tag'] = array($txt['levgal_tagcloud'], $scripturl . '?media/tag/cloud/');

		if (allowedTo(array('lgal_manage', 'lgal_approve_comment', 'lgal_approve_item')))
		{
			$moderation_count = 0;
			$moderation_count += LevGal_Bootstrap::getUnapprovedCommentsCount();
			$moderation_count += LevGal_Bootstrap::getUnapprovedItemsCount();
			$moderation_count += LevGal_Bootstrap::getUnapprovedAlbumsCount();
			if (allowedTo('lgal_manage'))
			{
				$reported = Util::unserialize($modSettings['lgal_reports']);
				foreach (array('comments', 'items') as $type)
				{
					if (!empty($reported[$type]))
					{
						$moderation_count += $reported[$type];
					}
				}
			}
			$context['gallery_actions']['actions']['moderate'] = array($txt['levgal_moderate'] . (empty($moderation_count) ? '' : ' [<strong>' . $moderation_count . '</strong>]'), $scripturl . '?media/moderate/', 'tab' => true);
		}
	}
}
