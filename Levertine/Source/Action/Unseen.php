<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0 / elkarte
 */

namespace Addons\Levertine\Source\Action;

use Addons\Levertine\Source\Helper\Http;
use Addons\Levertine\Source\LevGalBootstrap;
use Addons\Levertine\Source\Model\Unseen as UnseenModel;
use ElkArte\User;
use function Addons\Levertine\Source\levgal_pageindex;

/**
 * This file provides the unseen page, site/?media/unseen/.
 */
class Unseen extends LevGalAbstract
{
	public function __construct()
	{
		parent::__construct();

		// This cannot work for guests, fairly obviously.
		is_not_guest();
	}

	public function actionIndex()
	{
		global $context, $txt, $modSettings, $scripturl;

		// Stuff we will need
		$this->setTemplate('LevGal-Unseen', 'unseen');

		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($txt['levgal_unseen'], '?media/unseen/');

		$context['page_title'] = $txt['levgal_unseen'];
		$context['canonical_url'] = $scripturl . '?media/unseen/';

		// Only go looking for unseen things if we think there is something to see.
		if (!empty(User::$settings['lgal_unseen']))
		{
			$unseenModel = new UnseenModel();
			$context['unseen_albums'] = $unseenModel->getUnseenCountByAlbum();

			$num_pages = ceil(User::$settings['lgal_unseen'] / $modSettings['lgal_items_per_page']);
			$this_page = isset($_GET['page']) ? LevGalBootstrap::clamp((int) $_GET['page'], 1, $num_pages) : 1;

			// Are we filtering by album?
			$base_url = $scripturl . '?media/unseen/';
			[$album_slug, $album_id] = $this->getSlugAndId();
			$context['album_filter'] = 0;
			if (!empty($album_id) && isset($context['unseen_albums'][$album_id])) {
				$base_url = $context['unseen_albums'][$album_id]['filter_url'];
				$this->addLinkTree($context['unseen_albums'][$album_id]['album_name'], $context['unseen_albums'][$album_id]['filter_url']);
				$context['canonical_url'] = $context['unseen_albums'][$album_id]['filter_url'];

				// If we *are* filtering, we need to recalculate the page count.
				$num_pages = ceil($context['unseen_albums'][$album_id]['unseen'] / $modSettings['lgal_items_per_page']);
				$this_page = isset($_GET['page']) ? LevGalBootstrap::clamp((int) $_GET['page'], 1, $num_pages) : 1;
				if ($this_page > 1)
				{
					$context['canonical_url'] .= 'page-' . $this_page . '/';
				}
				// Check the slug is right otherwise redirect.
				if ($album_slug !== $context['unseen_albums'][$album_id]['album_slug'])
				{
					Http::hardRedirect($base_url . ($this_page > 1 ? 'page-' . $this_page . '/' : ''));
				}
				$context['album_filter'] = $album_id;
				$context['unseen_actions']['actions']['album'] = [$txt['lgal_go_to_album'], $context['unseen_albums'][$album_id]['album_url']];
				$context['unseen_actions']['actions']['markseen'] = [$txt['lgal_mark_album_seen'], $context['unseen_albums'][$album_id]['filter_url'] . 'markseen/' . $context['session_var'] . '=' . $context['session_id'] . '/', 'tab' => true];
			}

			if ($num_pages > 1)
			{
				$context['unseen_pageindex'] = levgal_pageindex($base_url, $this_page, $num_pages);
			}

			$context['unseen_items'] = $unseenModel->getUnseenItems(($this_page - 1) * $modSettings['lgal_items_per_page'], $modSettings['lgal_items_per_page'], $context['album_filter']);

			// Marking All seen
			if (empty($album_id))
			{
				$context['unseen_actions']['actions']['markseen'] = [$txt['lgal_mark_all_seen'], '?media/unseen/all.1/markall/' . $context['session_var'] . '=' . $context['session_id'] . '/', 'tab' => true, 'sidebar' => false];
			}
		}
	}

	public function actionMarkseen()
	{
		global $scripturl, $context;

		[, $album_id] = $this->getSlugAndId();
		if (!empty($album_id))
		{
			checkSession('get');
			$unseenModel = new UnseenModel();
			$context['unseen_albums'] = $unseenModel->getUnseenCountByAlbum();
			if (isset($context['unseen_albums'][$album_id]))
			{
				$unseenModel->markAlbumSeen($album_id);
			}
		}

		redirectexit($scripturl . '?media/unseen/');
	}

	public function actionMarkall()
	{
		global $scripturl;

		if (!User::$info['is_guest'] && !empty(User::$settings['lgal_unseen']))
		{
			checkSession('get');
			$unseenModel = new UnseenModel();
			$unseen_albums = $unseenModel->getUnseenCountByAlbum();
			foreach ($unseen_albums as $album_id => $album)
			{
				$unseenModel->markAlbumSeen($album_id);
			}
		}
		redirectexit($scripturl . '?media/unseen/');
	}
}
