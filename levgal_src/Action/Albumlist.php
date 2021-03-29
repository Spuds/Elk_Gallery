<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.0.4 / elkarte
 */

/**
 * This file provides the lists of albums for the user, site/?media/albumlist/,
 * or site/?media/albumlist/x/member/.
 */
class LevGal_Action_Albumlist extends LevGal_Action_Abstract
{
	public function __construct()
	{
		parent::__construct();
		$this->addStyleSheets('profile.css');
	}

	public function actionIndex()
	{
		global $context, $txt, $scripturl;

		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($txt['lgal_albums_list'], '?media/albumlist/');
		$context['canonical_url'] = $scripturl . '?media/albumlist/';
		$this->getSidebar('site');

		// There's only one site item here, it needs to be highlighted.
		if (!empty($context['sidebar']['site']))
		{
			$context['page_title'] = sprintf($txt['lgal_albums_owned_site'], $context['forum_name']);
			$album_list = LevGal_Bootstrap::getModel('LevGal_Model_AlbumList');
			$context['hierarchy'] = $album_list->getAlbumHierarchy('site');

			$context['sidebar']['site']['items'][0]['active'] = true;
			$this->setTemplate('LevGal', 'album_list_main');
			$context['album_actions'] = array();
			if (allowedTo('lgal_manage') && count($context['hierarchy']) >= 2)
			{
				$context['album_actions']['actions']['editalbum'] = array($txt['lgal_arrange_albums'], $scripturl . '?media/movealbum/site/');
			}
		}
		else
		{
			$context['page_title'] = $txt['lgal_albums_list'];

			// So, let's see: are there any items we can display for users or groups? If not, just
			// throw them to an error page.
			if (empty($context['album_owners']['members']) && empty($context['album_owners']['groups']))
			{
				$this->setTemplate('LevGal', 'album_list_none');
			}
			else
			{
				// If there's something to load, load it. Groups already have their data loaded,
				// but members didn't to save a query most loads.
				if (!empty($context['album_owners']['members']))
				{
					$loaded = loadMemberData(array_keys($context['album_owners']['members']));
					foreach ($loaded as $loaded_user)
					{
						loadMemberContext($loaded_user);
					}
				}

				$this->setTemplate('LevGal', 'album_list_main');
			}
		}
	}

	public function actionMember()
	{
		global $context, $txt, $scripturl, $user_profile;

		$member_id = $this->getNumericId();
		$loaded = loadMemberData($member_id, false, 'minimal');
		if (!$loaded)
		{
			// We don't have a legal id. Let's get out of here.
			LevGal_Helper_Http::hardRedirect($scripturl . '?media/albumlist/');
		}

		// So, valid member. Let's do this.
		$this->getSidebar('member', $member_id);
		if (empty($context['does_exist']))
		{
			// There's no albums here, time to leave.
			LevGal_Helper_Http::hardRedirect($scripturl . '?media/albumlist/');
		}

		$context['page_title'] = sprintf($txt['lgal_albums_owned_someone'], $user_profile[$member_id]['real_name']);
		$album_list = LevGal_Bootstrap::getModel('LevGal_Model_AlbumList');
		$context['hierarchy'] = $album_list->getAlbumHierarchy('member', $member_id);

		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($txt['lgal_albums_list'], '?media/albumlist/');
		$this->addLinkTree($context['page_title'], '?media/albumlist/' . $member_id . '/member/');
		$context['canonical_url'] = $scripturl . '?media/albumlist/' . $member_id . '/member/';

		$this->setTemplate('LevGal', 'album_list_main');

		$context['album_actions'] = array();
		if (count($context['hierarchy']) >= 2 && (allowedTo(array('lgal_manage', 'lgal_edit_album_any')) || (allowedTo('lgal_edit_album_own') && $member_id == $context['user']['id'])))
		{
			$context['album_actions']['actions']['editalbum'] = array($txt['lgal_arrange_albums'], $scripturl . '?media/movealbum/' . $member_id . '/member/');
		}
	}

	public function actionGroup()
	{
		global $context, $txt, $scripturl, $user_info;

		$group_id = $this->getNumericId();
		$groupModel = LevGal_Bootstrap::getModel('LevGal_Model_Group');
		$groups = $groupModel->getGroupsById($group_id);
		if (empty($groups))
		{
			// We don't have a legal id. Let's get out of here.
			LevGal_Helper_Http::hardRedirect($scripturl . '?media/albumlist/');
		}

		// So, valid group. Let's do this.
		$this->getSidebar('group', $group_id);
		if (empty($context['does_exist']))
		{
			// There's no albums here, time to leave.
			LevGal_Helper_Http::hardRedirect($scripturl . '?media/albumlist/');
		}

		$context['page_title'] = sprintf($txt['lgal_albums_owned_someone'], $groups[$group_id]['group_name']);
		$album_list = LevGal_Bootstrap::getModel('LevGal_Model_AlbumList');
		$context['hierarchy'] = $album_list->getAlbumHierarchy('group', $group_id);

		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($txt['lgal_albums_list'], '?media/albumlist/');
		$this->addLinkTree($context['page_title'], '?media/albumlist/' . $group_id . '/group/');
		$context['canonical_url'] = $scripturl . '?media/albumlist/' . $group_id . '/group/';

		$this->setTemplate('LevGal', 'album_list_main');

		$context['album_actions'] = array();
		if (count($context['hierarchy']) >= 2 && (allowedTo(array('lgal_manage', 'lgal_edit_album_any')) || (allowedTo('lgal_edit_album_own') && in_array($group_id, $user_info['groups']))))
		{
			$context['album_actions']['actions']['editalbum'] = array($txt['lgal_arrange_albums'], $scripturl . '?media/movealbum/' . $group_id . '/group/');
		}
	}

	protected function getSidebar($sidebar_type, $sidebar_id = 0)
	{
		global $context, $txt, $scripturl;

		$album_list = LevGal_Bootstrap::getModel('LevGal_Model_AlbumList');
		$context['album_owners'] = $album_list->getAlbumHierarchyByOwners();

		$context['sidebar'] = array();

		if (!empty($context['album_owners']['site']))
		{
			$context['sidebar']['site'] = array(
				'title' => $txt['lgal_albums_site'],
				'items' => array(
					array(
						'url' => $scripturl . '?media/albumlist/',
						'title' => $txt['lgal_albums_site'],
						'count' => $context['album_owners']['site'],
						'active' => $sidebar_type === 'site',
					),
				),
			);
			if ($sidebar_type === 'site')
			{
				$context['does_exist'] = true;
			}
		}

		if (!empty($context['album_owners']['members']))
		{
			// We need to rearrange this into name order.
			$members = array();
			foreach ($context['album_owners']['members'] as $id => $member)
			{
				$members[$member['name']] = array(
					'url' => $scripturl . '?media/albumlist/' . $id . '/member/',
					'id' => $id,
					'title' => $member['name'],
					'count' => $member['count'],
					'active' => $sidebar_type === 'member' && $sidebar_id == $id,
				);
				if ($sidebar_type === 'member' && $sidebar_id == $id)
				{
					$context['does_exist'] = true;
				}
			}
			ksort($members);
			$context['sidebar']['members'] = array(
				'title' => $txt['lgal_albums_member'],
				'items' => $members,
			);
		}

		if (!empty($context['album_owners']['groups']))
		{
			// We need to rearrange this into name order.
			$groups = array();
			foreach ($context['album_owners']['groups'] as $id => $group)
			{
				$groups[$group['name']] = array(
					'url' => $scripturl . '?media/albumlist/' . $id . '/group/',
					'id' => $id,
					'title' => $group['color_name'],
					'count' => $group['count'],
					'active' => $sidebar_type === 'group' && $sidebar_id == $id,
				);
				if ($sidebar_type === 'group' && $sidebar_id == $id)
				{
					$context['does_exist'] = true;
				}
				ksort($groups);
				$context['sidebar']['groups'] = array(
					'title' => $txt['lgal_albums_group'],
					'items' => $groups,
				);
			}
		}
	}
}
