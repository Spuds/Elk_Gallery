<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0 / elkarte
 */

namespace Addons\Levertine\Source\Action;

use Addons\Levertine\Source\LevGalBootstrap;
use Addons\Levertine\Source\Model\AlbumList;
use Addons\Levertine\Source\Model\ItemList;
use Addons\Levertine\Source\Model\Stats;
use ElkArte\Helper\Util;
use ElkArte\Languages\Txt;
use ElkArte\User;

/**
 * This file provides the home index for the gallery, site/?media/.
 */
class Home extends LevGalAbstract
{
	public function actionIndex()
	{
		global $context, $txt, $scripturl, $modSettings;

		// First we need the language and templates.
		Txt::load('Levertine/LevGal');
		Txt::load('Levertine/LevGal-Stats');

		// Recent / Random Dependencies
		loadCSSFile('glightbox.min.css', ['subdir' => 'Levertine/lightbox']);
		loadJavascriptFile('glightbox.min.js', ['subdir' => 'Levertine/lightbox', 'defer => true']);

		$this->addLinkTree($txt['levgal'], '?media/');
		$context['canonical_url'] = $scripturl . '?media/';
		$this->setTemplate('LevGal', 'main');

		$context['page_title'] = $txt['levgal'];
		$_SESSION['levgal_breadcrumbs'] = [];

		// Featured items are very simple. And we even get to do some caching magic.
		/** @var AlbumList $albumList */
		$albumList = LevGalBootstrap::getModel('AlbumList');
		$context['featured_albums'] = $albumList->getFeaturedAlbums();

		// The main area is fairly dull.
		$itemList = LevGalBootstrap::getModel('ItemList');
		/** @var ItemList $itemList */
		$context['latest_items'] = $itemList->getLatestItems(20);
		$context['random_items'] = $itemList->getRandomItems(10);

		// Sidebar not much better.
		/** @var Stats $statsModel */
		$statsModel = LevGalBootstrap::getModel('Stats');
		$context['stats'] = [
			'levgal_stats_total_items' => comma_format($statsModel->getTotalItems()),
			'levgal_stats_total_comments' => comma_format($statsModel->getTotalComments()),
			'levgal_stats_total_albums' => comma_format($statsModel->getTotalAlbums()),
		];

		$context['gallery_actions'] = [];

		if (!$context['user']['is_guest'] && allowedTo(['lgal_adduseralbum']))
		{
			$context['gallery_actions']['actions']['myalbums'] = [$txt['levgal_myalbums'], $scripturl . '?media/albumlist/' . $context['user']['id'] . '/member/', 'tab' => true, 'sidebar' => false];
		}

		if (!empty($context['stats']['levgal_stats_total_albums']) || allowedTo(['lgal_manage', 'lgal_adduseralbum', 'lgal_addgroupalbum']))
		{
			$context['gallery_actions']['actions']['album'] = [$txt['lgal_see_albums'], $scripturl . '?media/albumlist/', 'tab' => true];
		}

		if (allowedTo(['lgal_manage', 'lgal_adduseralbum', 'lgal_addgroupalbum']))
		{
			$context['gallery_actions']['actions']['addalbum'] = [$txt['levgal_newalbum'], $scripturl . '?media/newalbum/', 'tab' => true];
		}

		if (!empty(User::$settings['lgal_new']))
		{
			$unseenModel = LevGalBootstrap::getModel('Unseen');
			$unseenModel->updateUnseenItems();
		}
		if (!$context['user']['is_guest'] && !empty(User::$settings['lgal_unseen']))
		{
			$context['gallery_actions']['actions']['new'] = [$txt['levgal_unseen'] . ' [<strong>' . User::$settings['lgal_unseen'] . '</strong>]', $scripturl . '?media/unseen/', 'tab' => true];
		}

		$context['gallery_actions']['actions']['search'] = [$txt['levgal_search'], $scripturl . '?media/search/', 'tab' => true];
		$context['gallery_actions']['actions']['stats'] = [$txt['lgal_gallery_stats'], $scripturl . '?media/stats/'];
		$context['gallery_actions']['actions']['tag'] = [$txt['levgal_tagcloud'], $scripturl . '?media/tag/cloud/'];

		if (allowedTo(['lgal_manage', 'lgal_approve_comment', 'lgal_approve_item']))
		{
			$moderation_count = 0;
			$moderation_count += LevGalBootstrap::getUnapprovedCommentsCount();
			$moderation_count += LevGalBootstrap::getUnapprovedItemsCount();
			$moderation_count += LevGalBootstrap::getUnapprovedAlbumsCount();
			if (allowedTo('lgal_manage'))
			{
				$reported = Util::unserialize($modSettings['lgal_reports']);
				foreach (['comments', 'items'] as $type)
				{
					if (!empty($reported[$type]))
					{
						$moderation_count += $reported[$type];
					}
				}
			}
			$context['gallery_actions']['actions']['moderate'] = [$txt['levgal_moderate'] . (empty($moderation_count) ? '' : ' [<strong>' . $moderation_count . '</strong>]'), $scripturl . '?media/moderate/', 'tab' => true];
		}
	}
}
