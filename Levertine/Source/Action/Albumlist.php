<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0 / elkarte
 */

namespace Addons\Levertine\Source\Action;

use Addons\Levertine\Source\Helper\Http;
use Addons\Levertine\Source\LevGalBootstrap;
use Addons\Levertine\Source\Model\Group;
use ElkArte\MembersList;
use ElkArte\User;
use function Addons\Levertine\Source\levgal_pageindex;

/**
 * This file provides the lists of albums for the user, site/?media/albumlist/,
 * or site/?media/albumlist/x/member/.
 */
class Albumlist extends LevGalAbstract
{
	/** @var int number of items to show on a page.  Used with member albums */
	public $items_per_page = 30;

	public function __construct()
	{
		parent::__construct();
		$this->addStyleSheets('profile.css');
		$_SESSION['levgal_breadcrumbs'] = [];
	}

	public function actionIndex()
	{
		global $context, $txt, $scripturl;

		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($txt['lgal_albums_list'], '?media/albumlist/');
		$context['canonical_url'] = $scripturl . '?media/albumlist/';

		$this->getSidebar('site');
		$context['page_title'] = $txt['lgal_albums_list'];

		// Nothing to show but an empty gallery
		if (empty($context['album_owners']['members']) && empty($context['album_owners']['groups']) && empty($context['album_owners']['site']))
		{
			$this->setTemplate('LevGal', 'album_list_none');
		}
		else
		{
			$this->setTemplate('LevGal', 'album_list_main');

			// If there's something to load, load it. Groups already have their data loaded,
			// but members didn't to save a query most loads.
			if (!empty($context['album_owners']['members']))
			{
				$perPage = $this->items_per_page;
				$toLoad = array_keys($context['album_owners']['members']);
				$num_pages = ceil(count($toLoad) / $perPage);
				if ($num_pages > 1)
				{
					$context['this_page'] = isset($_GET['page']) ? LevGalBootstrap::clamp((int) $_GET['page'], 1, $num_pages) : 1;
					$context['item_pageindex'] = levgal_pageindex($context['canonical_url'], $context['this_page'], $num_pages, '#members');

					$start = ($context['this_page'] - 1) * $perPage;
					$toLoad = array_slice($toLoad, $start, $perPage);
				}

				MembersList::loadMemberData($toLoad);
			}
		}
	}

	public function actionSite()
	{
		global $context, $txt, $scripturl;

		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($txt['lgal_albums_list'], '?media/albumlist/site/');
		$context['canonical_url'] = $scripturl . '?media/albumlist/';

		$this->getSidebar('site');
		$context['page_title'] = sprintf($txt['lgal_albums_owned_site'], $context['forum_name']);

		// There's only one site item here, it needs to be highlighted.
		if (!empty($context['sidebar']['site']))
		{
			$album_list = LevGalBootstrap::getModel('AlbumList');
			$context['hierarchy'] = $album_list->getAlbumHierarchy('site');

			$this->setTemplate('LevGal', 'album_list_main');

			if (count($context['hierarchy']) >= 2 && allowedTo(['lgal_manage']))
			{
				$context['album_actions']['actions']['movealbum'] = [$txt['lgal_arrange_albums'], $scripturl . '?media/movealbum/site', 'tab' => true];
			}
		}
		else
		{
			$this->setTemplate('LevGal', 'album_list_none');
		}
	}

	public function actionMember()
	{
		global $context, $txt, $scripturl, $user_profile;

		$member_id = $this->getNumericId();
		if ($member_id === 0)
		{
			// All member album listing
			$this->allMembersAlbumList();
			return;
		}

		$loaded = MembersList::load($member_id, false, 'minimal');
		if (!$loaded)
		{
			// We don't have a legal id. Let's get out of here.
			Http::hardRedirect($scripturl . '?media/albumlist/');
		}

		// So, valid member. Let's do this.
		$user_profile[$member_id] = MembersList::get($member_id);
		$this->getSidebar('member', $member_id);
		if (empty($context['does_exist']))
		{
			// There's no albums here, time to leave.
			Http::hardRedirect($scripturl . '?media/albumlist/');
		}

		$context['page_title'] = sprintf($txt['lgal_albums_owned_someone'], $user_profile[$member_id]['real_name']);
		/** @var \Addons\Levertine\Source\Model\AlbumList $album_list */
		$album_list = LevGalBootstrap::getModel('AlbumList');
		$context['hierarchy'] = $album_list->getAlbumHierarchy('member', $member_id);

		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($txt['lgal_albums_list'], '?media/albumlist/');
		$this->addLinkTree($context['page_title'], '?media/albumlist/' . $member_id . '/member/');
		$context['canonical_url'] = $scripturl . '?media/albumlist/' . $member_id . '/member/';

		$this->setTemplate('LevGal', 'album_list_main');

		if (count($context['hierarchy']) >= 2
			&& (allowedTo(['lgal_manage', 'lgal_edit_album_any'])
				|| (allowedTo('lgal_edit_album_own') && $member_id === (int) $context['user']['id'])))
		{
			$context['album_actions']['actions']['movealbum'] = [$txt['lgal_arrange_albums'], $scripturl . '?media/movealbum/' . $member_id . '/member/', 'tab' => true];
		}

		$context['album_actions']['actions']['search'] = [$txt['levgal_search'], $scripturl . '?media/search/', 'tab' => true];
	}

	private function allMembersAlbumList()
	{
		global $context, $txt, $scripturl;

		$context['page_title'] = $txt['lgal_albums_member'];

		$this->getSidebar('members');

		// Are there any items we can display for users?
		if (empty($context['album_owners']['members']))
		{
			$this->setTemplate('LevGal', 'album_list_none');
		}
		else
		{
			$this->addLinkTree($txt['levgal'], '?media/');
			$this->addLinkTree($txt['lgal_albums_list'], '?media/albumlist/');
			$this->addLinkTree($context['page_title'], '?media/albumlist/member/');
			$context['canonical_url'] = $scripturl . '?media/albumlist/member/';

			$perPage = $this->items_per_page;
			$toLoad = array_keys($context['album_owners']['members']);
			$num_pages = ceil(count($toLoad) / $perPage);
			if ($num_pages > 1)
			{
				$context['this_page'] = isset($_GET['page']) ? LevGalBootstrap::clamp((int) $_GET['page'], 1, $num_pages) : 1;
				$context['item_pageindex'] = levgal_pageindex($context['canonical_url'], $context['this_page'], $num_pages, '#album_sidebar');

				$start = ($context['this_page'] - 1) * $perPage;
				$toLoad = array_slice($toLoad, $start, $perPage);
			}

			MembersList::loadMemberData($toLoad);

			$context['nested_hierarchy'] = [];
			/** @var \Addons\Levertine\Source\Model\AlbumList $album_list */
			$album_list = LevGalBootstrap::getModel('AlbumList');
			foreach ($toLoad as $loaded_user)
			{
				// We cannot loop on $GLOBALS['memberContext'], as it is not in the same order as $toLoad that we want to display
				// @todo still true ??
				if (empty($GLOBALS['memberContext'][$loaded_user]))
				{
					continue;
				}
				$context['nested_hierarchy'][$GLOBALS['memberContext'][$loaded_user]['real_name']] = $album_list->getAlbumHierarchy('member', $loaded_user);
			}

			$this->setTemplate('LevGal', 'album_list_main');
		}
	}

	public function actionGroup()
	{
		global $context, $txt, $scripturl;

		$sub = $this->_req->getQuery('sub', 'trim', '');
		$group_id = $this->_req->getQuery('item', 'intval');

		if ($group_id === null && $sub === 'group')
		{
			$this->allGroupsAlbumList();
			return;
		}

		/** @var $groupModel Group */
		$groupModel = LevGalBootstrap::getModel('Group');
		$groups = $groupModel->getGroupsById($group_id);
		if (empty($groups))
		{
			// We don't have a legal id. Let's get out of here.
			Http::hardRedirect($scripturl . '?media/albumlist/');
		}

		// So, valid group. Let's do this.
		$this->getSidebar('group', $group_id);
		if (empty($context['does_exist']))
		{
			// There's no albums here, time to leave.
			Http::hardRedirect($scripturl . '?media/albumlist/');
		}

		$context['page_title'] = sprintf($txt['lgal_albums_owned_someone'], $groups[$group_id]['group_name']);
		$album_list = LevGalBootstrap::getModel('AlbumList');
		$context['hierarchy'] = $album_list->getAlbumHierarchy('group', $group_id);

		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($txt['lgal_albums_list'], '?media/albumlist/');
		$this->addLinkTree($context['page_title'], '?media/albumlist/' . $group_id . '/group/');
		$context['canonical_url'] = $scripturl . '?media/albumlist/' . $group_id . '/group/';

		$this->setTemplate('LevGal', 'album_list_main');

		if (count($context['hierarchy']) >= 2 && (allowedTo(['lgal_manage', 'lgal_edit_album_any']) || (allowedTo('lgal_edit_album_own') && in_array($group_id, User::$info['groups'], true))))
		{
			$context['album_actions']['actions']['movealbum'] = [$txt['lgal_arrange_albums'], $scripturl . '?media/movealbum/' . $group_id . '/group/', 'tab' => true];
		}

		$context['album_actions']['actions']['search'] = [$txt['levgal_search'], $scripturl . '?media/search/', 'tab' => true];
	}

	private function allGroupsAlbumList()
	{
		global $context, $txt, $scripturl;

		$context['page_title'] = $txt['lgal_albums_group'];

		$this->getSidebar('group');

		// Are there any items we can display for users?
		if (empty($context['album_owners']['groups']))
		{
			$this->setTemplate('LevGal', 'album_list_none');
		}
		else
		{
			$context['nested_hierarchy'] = [];
			/** @var \Addons\Levertine\Source\Model\AlbumList $album_list */
			$album_list = LevGalBootstrap::getModel('AlbumList');
			foreach ($context['album_owners']['groups'] as $group_id => $group_data)
			{
				$context['nested_hierarchy'][$group_data['name']] = $album_list->getAlbumHierarchy('group', $group_id);
			}

			$this->addLinkTree($txt['levgal'], '?media/');
			$this->addLinkTree($txt['lgal_albums_list'], '?media/albumlist/');
			$this->addLinkTree($context['page_title'], '?media/albumlist/group/');
			$context['canonical_url'] = $scripturl . '?media/albumlist/group/';

			$this->setTemplate('LevGal', 'album_list_main');
		}
	}

	protected function getSidebar($sidebar_type, $sidebar_id = 0)
	{
		global $context, $txt, $scripturl;

		/** @var $album_list \Addons\Levertine\Source\Model\AlbumList */
		$album_list = LevGalBootstrap::getModel('AlbumList');
		$context['album_owners'] = $album_list->getAlbumHierarchyByOwners();

		$context['sidebar'] = [];
		$context['album_actions'] = [];
		$context['album_actions']['actions'] = [];

		$sub = $this->_req->getQuery('sub', 'trim', '');

		if (!empty($context['album_owners']['site']))
		{
			$context['sidebar']['site'] = [
				'title' => $txt['lgal_albums_site'],
				'items' => [
					[
						'url' => $scripturl . '?media/albumlist/site/',
						'title' => $txt['lgal_albums_site'],
						'count' => $context['album_owners']['site'],
						'active' => $sidebar_type === 'site' && $sub === 'site',
					],
				],
			];
			if ($sidebar_type === 'site')
			{
				$context['does_exist'] = true;
			}
			$context['album_actions']['actions']['sitealbums'] = [$txt['lgal_albums_site'], $scripturl . '?media/albumlist/site/', 'tab' => true, 'sidebar' => false, 'active' => $sidebar_type === 'site' && $sub === 'site'];
		}

		if (!empty($context['album_owners']['groups']))
		{
			// We need to rearrange this into name order.
			$groups = [];
			foreach ($context['album_owners']['groups'] as $id => $group)
			{
				$groups[$group['name']] = [
					'url' => $scripturl . '?media/albumlist/' . $id . '/group/',
					'id' => $id,
					'title' => $group['color_name'],
					'count' => $group['count'],
					'active' => $sidebar_type === 'group' && $sidebar_id === $id,
				];
				if ($sidebar_type === 'group' && $sidebar_id === $id)
				{
					$context['does_exist'] = true;
				}
				ksort($groups);
				$context['sidebar']['groups'] = [
					'title' => $txt['lgal_albums_group'],
					'items' => $groups,
				];
			}
			$context['album_actions']['actions']['groupalbums'] = [$txt['lgal_albums_group'], $scripturl . '?media/albumlist/group/', 'tab' => true, 'sidebar' => false, 'active' => $sidebar_type === 'group'];
		}

		if (!empty($context['album_owners']['members']))
		{
			// We need to rearrange this into name order.
			$members = [];
			foreach ($context['album_owners']['members'] as $id => $member)
			{
				$members[$member['name']] = [
					'url' => $scripturl . '?media/albumlist/' . $id . '/member/',
					'id' => $id,
					'title' => $member['name'],
					'count' => $member['count'],
					'active' => $sidebar_type === 'member' && $sidebar_id === $id,
				];
				if ($sidebar_type === 'member' && $sidebar_id === $id)
				{
					$context['does_exist'] = true;
				}
				if ($id === User::$info['id'])
				{
					// Add My Albums as first
					$context['album_actions']['actions'] = ['myalbums' => [$txt['levgal_myalbums'], $scripturl . '?media/albumlist/' . $id . '/member/', 'tab' => true, 'sidebar' => false, 'active' => $sidebar_type === 'member' && $sidebar_id === $id]] + $context['album_actions']['actions'];
				}
			}
			$context['album_actions']['actions']['memberalbums'] = [$txt['lgal_albums_member'], $scripturl . '?media/albumlist/member/', 'tab' => true, 'sidebar' => false, 'active' => (($sidebar_type === 'members' && $sidebar_id === 0) || ($sidebar_type === 'member' && $sidebar_id === $id))];

			// For the sidebar
			ksort($members, SORT_FLAG_CASE|SORT_STRING);

			// For placard listings, a bit more convoluted
			$keys = array_keys($context['album_owners']['members']);
			$names = array_column($context['album_owners']['members'], 'name');
			array_multisort($names, SORT_ASC, SORT_FLAG_CASE|SORT_STRING, $context['album_owners']['members'], $keys);
			$context['album_owners']['members'] = array_combine($keys, $context['album_owners']['members']);

			$context['sidebar']['members'] = [
				'title' => $txt['lgal_albums_member'],
				'items' => $members,
			];
		}

		if (allowedTo(['lgal_manage', 'lgal_adduseralbum', 'lgal_addgroupalbum']))
		{
			$context['album_actions']['actions']['addalbum'] = [$txt['levgal_newalbum'], $scripturl . '?media/newalbum/', 'tab' => true];
		}

		$context['album_actions']['actions']['search'] = [$txt['levgal_search'], $scripturl . '?media/search/', 'tab' => true];
	}
}
