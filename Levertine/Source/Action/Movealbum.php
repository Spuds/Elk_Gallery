<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.0 / elkarte
 */

/**
 * This file provides the functionality for moving albums around inside user/group
 * hierarchies, site/?media/movealbum/site/ or site/?media/movealbum/x/member/.
 */
class LevGal_Action_Movealbum extends LevGal_Action_Abstract
{
	public function __construct()
	{
		global $modSettings;

		parent::__construct();

		$this->addStyleSheets('profile.css');
		$this->setTemplate('LevGal-MoveAlbum', 'movealbum');

		$modSettings['jquery_include_ui'] = true;
	}

	public function actionIndex()
	{
		LevGal_Helper_Http::fatalError('levgal_invalid_action');
	}

	/**
	 * Performs actions related to rearranging the site albums in the gallery.
	 *
	 * @return void
	 */
	public function actionSite()
	{
		global $context, $txt, $scripturl;

		// Only gallery managers can rearrange site albums.
		isAllowedTo('lgal_manage');

		$album_list = LevGal_Bootstrap::getModel('LevGal_Model_AlbumList');

		/** @var $album_list \LevGal_Model_Albumlist */
		$context['hierarchy'] = $album_list->getAlbumHierarchy('site');

		// No albums? Only one album (nothing to arrange)? Bye.
		if (empty($context['hierarchy']) || count($context['hierarchy']) === 1)
		{
			LevGal_Helper_Http::fatalError('levgal_invalid_action');
		}

		if (isset($_POST['saveorder']))
		{
			$this->saveHierarchy($context['hierarchy'], 'site');
		}

		// OK, so we have the album list. Let's set up for display.
		$context['page_title'] = $txt['lgal_arrange_albums'];
		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($txt['lgal_arrange_albums'], '?media/movealbum/site/');
		$context['canonical_url'] = $scripturl . '?media/movealbum/site/';

		$context['return_url'] = 'media/albumlist/site/';
		$context['form_url'] = 'media/movealbum/site/';
	}

	public function actionMember()
	{
		global $context, $txt, $scripturl;

		$member_id = $this->getNumericId();
		// Not a valid member?
		$loaded = loadMemberData($member_id, false, 'minimal');
		if (!$loaded)
		{
			LevGal_Helper_Http::fatalError('levgal_invalid_action');
		}

		// Next up: let's figure out whether we can actually be doing this.
		// Gallery managers, people who can edit any album, or people who can edit their own albums (and this is us)
		if (!allowedTo(array('lgal_manage', 'lgal_edit_album_any'))
			&& !(allowedTo('lgal_edit_album_own') && (int) $member_id === (int) $context['user']['id']))
		{
			LevGal_Helper_Http::fatalError('levgal_invalid_action');
		}

		// OK, folks, showtime.
		$album_list = LevGal_Bootstrap::getModel('LevGal_Model_AlbumList');

		$context['hierarchy'] = $album_list->getAlbumHierarchy('member', $member_id);

		// No albums? Only one album (nothing to arrange)? Bye.
		if (empty($context['hierarchy']) || count($context['hierarchy']) == 1)
		{
			LevGal_Helper_Http::fatalError('levgal_invalid_action');
		}

		if (isset($_POST['saveorder']))
		{
			$this->saveHierarchy($context['hierarchy'], 'member', $member_id);
		}

		$context['page_title'] = $txt['lgal_arrange_albums'];
		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($txt['lgal_arrange_albums'], '?media/movealbum/' . $member_id . '/member/');
		$context['canonical_url'] = $scripturl . '?media/movealbum/' . $member_id . '/member/';

		$context['return_url'] = 'media/albumlist/' . $member_id . '/member/';
		$context['form_url'] = 'media/movealbum/' . $member_id . '/member/';
	}

	public function actionGroup()
	{
		global $context, $txt, $user_info, $scripturl;

		$group_id = $this->getNumericId();
		// Valid group?
		$groupModel = LevGal_Bootstrap::getModel('LevGal_Model_Group');
		$groups = $groupModel->getGroupsById($group_id);
		if (empty($groups))
		{
			// We don't have a legal id. Let's get out of here.
			LevGal_Helper_Http::hardRedirect($scripturl . '?media/albumlist/');
		}

		// Next up: let's figure out whether we can actually be doing this.
		// Gallery managers, people who can edit any album, or people who can edit their own albums (and this is us)
		if (!allowedTo(array('lgal_manage', 'lgal_edit_album_any')) && !(allowedTo('lgal_edit_album_own') && in_array($group_id, $user_info['groups'], true)))
		{
			LevGal_Helper_Http::fatalError('levgal_invalid_action');
		}

		// OK, folks, showtime.
		$album_list = LevGal_Bootstrap::getModel('LevGal_Model_AlbumList');

		$context['hierarchy'] = $album_list->getAlbumHierarchy('group', $group_id);

		// No albums? Only one album (nothing to arrange)? Bye.
		if (empty($context['hierarchy']) || count($context['hierarchy']) == 1)
		{
			LevGal_Helper_Http::fatalError('levgal_invalid_action');
		}

		if (isset($_POST['saveorder']))
		{
			$this->saveHierarchy($context['hierarchy'], 'group', $group_id);
		}

		$context['page_title'] = $txt['lgal_arrange_albums'];
		$this->addLinkTree($txt['levgal'], '?media/');
		$this->addLinkTree($txt['lgal_arrange_albums'], '?media/movealbum/' . $group_id . '/group/');
		$context['canonical_url'] = $scripturl . '?media/movealbum/' . $group_id . '/group/';

		$context['return_url'] = 'media/albumlist/' . $group_id . '/group/';
		$context['form_url'] = 'media/movealbum/' . $group_id . '/group/';
	}

	protected function saveHierarchy($hierarchy, $hierarchy_type, $hierarchy_id = 0)
	{
		$db = database();

		// So we're saving, we already know which hierarchy we're dealing with
		checkSession();

		$newHierarchy = array();

		// Very quick sanity check.
		if (empty($_POST['album']) || !is_array($_POST['album']) || count(array_intersect(array_keys($_POST['album']), array_keys($hierarchy))) !== count($hierarchy))
		{
			LevGal_Helper_Http::jsonResponse(array('error' => 1));
		}

		$current_pos = 1;
		foreach ($_POST['album'] as $id_album => $parent)
		{
			$parent = (int) $parent;

			// It comes in with the form album[1]=null&album[2]=1 to indicate 1 is top level and 2 is a child of 1.
			// We know the keys are all legit, but we haven't sanitised the values yet.
			if ($parent === 0)
			{
				// Parent of 0 means top level album. Easy one.
				$newHierarchy[$id_album] = array('album_pos' => $current_pos++, 'album_level' => 0);
			}
			elseif ($parent < 0 || !isset($newHierarchy[$parent]))
			{
				// Parent negative or we didn't already hit this album we're trying to find a child of means invalid.
				LevGal_Helper_Http::jsonResponse(array('error' => 1));
			}
			else
			{
				// Otherwise add it to the list, and get the parent's indent level for this one.
				$newHierarchy[$id_album] = array('album_pos' => $current_pos++, 'album_level' => $newHierarchy[$parent]['album_level'] + 1);
			}
		}

		// OK, so now we have the new hierarchy. Let's see about updating it.
		// This is an operation we pretty much only ever do here. Hence no model.
		$changes = array();
		foreach ($newHierarchy as $id_album => $new_details)
		{
			if ($new_details['album_pos'] != $hierarchy[$id_album]['album_pos'] || $new_details['album_level'] != $hierarchy[$id_album]['album_level'])
			{
				$changes[$id_album] = $new_details;
			}
		}

		if (!empty($changes))
		{
			// Site albums are really member_id=0 albums. Let's address that real quick.
			if ($hierarchy_type === 'site')
			{
				$hierarchy_type = 'member';
				$hierarchy_id = 0;
			}

			$table = $hierarchy_type === 'member' ? 'lgal_owner_member' : 'lgal_owner_group';
			$column = $hierarchy_type === 'member' ? 'id_member' : 'id_group';

			foreach ($changes as $id_album => $new_details)
			{
				$db->query('', '
					UPDATE {db_prefix}{raw:table}
					SET album_pos = {int:album_pos},
						album_level = {int:album_level}
					WHERE id_album = {int:id_album}
						AND {raw:column} = {int:hierarchy_id}',
					array(
						'table' => $table,
						'column' => $column,
						'album_pos' => $new_details['album_pos'],
						'album_level' => $new_details['album_level'],
						'id_album' => $id_album,
						'hierarchy_id' => $hierarchy_id,
					)
				);
			}
		}

		LevGal_Helper_Http::jsonResponse(array('OK' => 1), 200);
	}
}
