<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.0.4 / elkarte
 */

/**
 * This file deals with the integration into the user profile area.
 */
class LevGalProfile_Controller extends Action_Controller
{
	/** @var int */
	private $memID;

	/**
	 * Pre Dispatch, called before other methods.  Loads integration hooks.
	 */
	public function pre_dispatch()
	{
		loadCSSFile(['main.css', 'profile.css'], ['subdir' => 'levgal_res']);
		loadTemplate('levgal_tpl/LevGal-Profile');
		$this->memID = currentMemberID();
	}

	/**
	 * Default entry point, in case action methods are not directly
	 * called.
	 *
	 * @see Action_Controller::action_index()
	 */
	public function action_index()
	{
		// Add the profile areas
	}

	/**
	 * Static method for the hook, loads media profile menu's
	 * @param $profile_areas
	 */
	public static function LevGal_profile(&$profile_areas)
	{
		global $txt, $settings, $context;

		// Need to be able to see the gallery to do any of this stuff.
		if (!allowedTo('lgal_view'))
		{
			return;
		}

		loadLanguage('levgal_lng/LevGal-Profile');
		if (!empty($_GET['area']) && $_GET['area'] === 'permissions')
		{
			loadLanguage('levgal_lng/ManageLevGal');
		}

		$profile_areas['media'] = array(
			'title' => $txt['levgal_profile'],
			'areas' => array(
				'mediasummary' => array(
					'label' => $txt['levgal_profile_summary'],
					'function' => 'levgal_profile_summary',
					'controller' => 'LevGalProfile_Controller',
					'permission' => array(
						'own' => array('profile_view_any', 'profile_view_own'),
						'any' => array('profile_view_any'),
					),
					'icon' => 'media',
				),
				'mediaitems' => array(
					'label' => $txt['levgal_profile_items'],
					'function' => 'levgal_profile_items',
					'controller' => 'LevGalProfile_Controller',
					'permission' => array(
						'own' => array('profile_view_any', 'profile_view_own'),
						'any' => array('profile_view_any'),
					),
					'icon' => 'media',
					'subsections' => array(
						'items' => array($txt['levgal_profile_items'], array('profile_view_any', 'profile_view_own')),
						'likesgiven' => array($txt['levgal_profile_likes_issued'], array('profile_view_any', 'profile_view_own')),
						'likesreceived' => array($txt['levgal_profile_likes_received'], array('profile_view_any', 'profile_view_own')),
					),
				),
				'mediabookmarks' => array(
					'label' => $txt['levgal_profile_bookmarks'],
					'function' => 'levgal_profile_bookmarks',
					'controller' => 'LevGalProfile_Controller',
					'permission' => array(
						'own' => array('profile_view_any', 'profile_view_own'),
						'any' => array('profile_view_any'),
					),
					'icon' => 'mediabookmarks',
				),
				'medianotify' => array(
					'label' => $txt['levgal_profile_notify'],
					'function' => 'levgal_profile_notify',
					'controller' => 'LevGalProfile_Controller',
					'permission' => array(
						'own' => array('profile_extra_any', 'profile_view_own'),
						'any' => array('profile_extra_any'),
					),
					'icon' => 'mail',
				),
				'mediaprefs' => array(
					'label' => $txt['levgal_profile_prefs'],
					'function' => 'levgal_profile_prefs',
					'controller' => 'LevGalProfile_Controller',
					'permission' => array(
						'own' => array('profile_view_own'),
						'any' => array(),
					),
					'icon' => 'features',
				),
			),
		);

		// Bookmarks are kind of complicated if you're not the current user.
		$allowed = false;
		if ($context['id_member'] != $context['user']['id'])
		{
			// Is it actually set in their profile via theme options?
			if (isset($context['member']['options']['lgal_show_bookmarks']))
			{
				$allowed = !empty($context['member']['options']['lgal_show_bookmarks']);
			}
			// Otherwise look at the default setting instead.
			elseif (!empty($settings['lgal_show_bookmarks']))
			{
				$allowed = true;
			}
		}
		if (!$allowed)
		{
			$profile_areas['media']['areas']['mediabookmarks']['permission']['any'] = array();
		}
	}

	public function levgal_profile_summary()
	{
		global $context, $txt, $scripturl;

		$memID = $this->memID;

		$context['page_title'] = $txt['levgal_profile_summary'];
		loadLanguage('levgal_lng/LevGal');
		Templates::instance()->load('levgal_tpl/LevGal-Profile');
		Templates::instance()->load('levgal_tpl/LevGal');

		// Let's get the last 4 items they uploaded.
		$item_list = LevGal_Bootstrap::getModel('LevGal_Model_ItemList');
		$context['latest_items'] = $item_list->getLatestItemsForUser($memID, 4);

		// Let's get the albums they own.
		$album_list = LevGal_Bootstrap::getModel('LevGal_Model_AlbumList');
		$context['hierarchy'] = $album_list->getAlbumHierarchy('member', $memID);

		$context['total_albums'] = count($context['hierarchy']);
		$context['total_items'] = 0;
		$context['total_unapproved_items'] = 0;

		foreach ($context['hierarchy'] as $album)
		{
			$context['total_items'] += $album['num_items'];
			$context['total_unapproved_items'] += $album['num_unapproved_items'];
		}

		$context['summary_items'] = array();
		$context['summary_items'][] = '<span class="lgalicon album"></span> <a href="' . $scripturl . '?media/albumlist/' . $memID . '/">' . LevGal_Helper_Format::numstring('lgal_albums', $context['total_albums']) . '</a>';
		$context['summary_items'][] = '<span class="lgalicon album"></span> <a href="' . $scripturl . '?action=profile;area=mediaitems;u=' . $memID . '">' . LevGal_Helper_Format::numstring('lgal_items', $context['total_items']) . '</a>';

		if (allowedTo(array('lgal_manage', 'lgal_approve_item')) && !empty($context['total_unapproved_items']))
		{
			$context['summary_items'][] = '<span class="lgalicon unapproved"></span> ' . $txt['lgal_unapproved'] . LevGal_Helper_Format::numstring('lgal_items', $context['total_unapproved_items']);
		}
	}

	public function levgal_profile_items()
	{
		global $context, $txt, $modSettings, $scripturl;

		$memID = $this->memID;

		$context['page_title'] = $txt['levgal_profile_items'];
		loadTemplate('levgal_tpl/LevGal');

		$context[$context['profile_menu_name']]['tab_data'] = array(
			'title' => '<span class="lgalicon album"></span> ' . $txt['levgal_profile_items'],
			'description' => '',
			'tabs' => array(
				'items' => array(
					'description' => $context['id_member'] == $context['user']['id'] ? $txt['levgal_profile_items_desc'] : $txt['levgal_profile_items_other_desc'],
				),
				'likesgiven' => array(
					'description' => $context['id_member'] == $context['user']['id'] ? $txt['levgal_profile_likes_issued_desc'] : $txt['levgal_profile_likes_issued_other_desc'],
				),
				'likesreceived' => array(
					'description' => $context['id_member'] == $context['user']['id'] ? $txt['levgal_profile_likes_received_desc'] : $txt['levgal_profile_likes_received_other_desc'],
				),
			),
		);

		$sa = array(
			'items' => array('getItemCount', 'getItemList', 'area=mediaitems', 'items_'),
			'likesgiven' => array('getLikeIssuedCount', 'getItemsLikeIssued', 'area=mediaitems;sa=likesgiven', 'likes_issued_'),
			'likesreceived' => array('getLikeReceivedCount', 'getItemsReceivedIssued', 'area=mediaitems;sa=likesreceived', 'likes_received_'),
		);

		$_GET['sa'] = isset($_GET['sa'], $sa[$_GET['sa']]) ? $_GET['sa'] : 'items';

		$memberModel = LevGal_Bootstrap::getModel('LevGal_Model_Member');

		list ($countMethod, $itemMethod, $url, $none_found_fragment) = $sa[$_GET['sa']];

		$context['num_items'] = $memberModel->$countMethod($memID);

		if (!empty($context['num_items']))
		{
			$context['page_index'] = constructPageIndex($scripturl . '?action=profile;' . $url . ';u=' . $memID, $_REQUEST['start'], $context['num_items'], $modSettings['lgal_items_per_page']);
			$context['profile_items'] = $memberModel->$itemMethod($memID, $modSettings['lgal_items_per_page'], $_REQUEST['start']);
		}
		else
		{
			$context['no_items_text'] = $txt['levgal_profile_' . $none_found_fragment . ($context['id_member'] != $context['user']['id'] ? 'other_none' : 'none')];
		}
	}

	public function levgal_profile_bookmarks()
	{
		global $context, $txt;

		$memID = $this->memID;

		$context['page_title'] = $txt['levgal_profile_bookmarks'];
		$context['bookmarks_desc'] = $context['id_member'] == $context['user']['id'] ? $txt['levgal_profile_bookmarks_desc'] : $txt['levgal_profile_bookmarks_other_desc'];
		$context['no_bookmarks_text'] = $context['id_member'] == $context['user']['id'] ? $txt['levgal_profile_bookmarks_none'] : $txt['levgal_profile_bookmarks_none_other'];

		$bookmarkModel = new LevGal_Model_Bookmark();
		$context['bookmarks'] = $bookmarkModel->getBookmarkList($memID);
	}

	public function levgal_profile_notify()
	{
		global $context, $txt;

		$memID = $this->memID;

		$context['page_title'] = $txt['levgal_profile_notify'];
		$context['notify_desc'] = $context['id_member'] == $context['user']['id'] ? $txt['levgal_profile_notify_desc'] : $txt['levgal_profile_notify_other_desc'];

		// First we need some extra details, like whether their profile setting is actually set to notify.
		$notifyModel = LevGal_Bootstrap::getModel('LevGal_Model_Notify');
		$context['notify_options'] = $notifyModel->getUserNotifyPref($memID);

		// Now we need to get the lists of things they are notified of.
		$context['album_notifications'] = $notifyModel->getNotifyAlbumsForUser($memID);
		$context['item_notifications'] = $notifyModel->getNotifyItemsForUser($memID);

		// Lastly has the admin enabled notifications in these areas
		$context['enabled_media_notifications'] = $notifyModel->getSiteEnableNotifications();

		if (isset($_GET['save']))
		{
			checkSession();

			$things = array();
			foreach (array('item', 'album') as $type)
			{
				if (isset($_POST['edit_notify_' . $type]) && !empty($_POST['notify_' . $type . 's']) && is_array($_POST['notify_' . $type . 's']))
				{
					$things[$type] = array();
					foreach ($_POST['notify_' . $type . 's'] as $item)
					{
						$item = (int) $item;
						if ($item > 0)
						{
							$things[$type][] = $item;
							// And remove it from the list already loaded for users.
							unset ($context[$type . '_notifications'][$item]);
						}
					}
				}
			}
			if (!empty($things['item']))
			{
				$notifyModel->unsetNotifyItem($things['item'], $memID);
			}
			if (!empty($things['album']))
			{
				$notifyModel->unsetNotifyAlbum($things['album'], $memID);
			}
		}
	}

	public function levgal_profile_prefs()
	{
		global $context, $txt, $options, $settings, $modSettings;

		$memID = $this->memID;

		$db = database();

		$context['page_title'] = $txt['levgal_profile_prefs'];

		loadCSSFile(['admin-lg.css'], ['subdir' => 'levgal_res']);

		$context['preferences'] = array();
		if (!empty($modSettings['lgal_enable_mature']))
		{
			$context['preferences'][] = array('check', 'lgal_show_mature');
		}
		$context['preferences'][] = array('check', 'lgal_show_bookmarks');

		// And before we go any further we have to pull the value from bookmarks from $settings to $options.
		if (!isset($options['lgal_show_bookmarks']) && !empty($settings['lgal_show_bookmarks']))
		{
			$options['lgal_show_bookmarks'] = $settings['lgal_show_bookmarks'];
		}

		if (isset($_POST['save']))
		{
			checkSession();
			$changes = array();
			foreach ($context['preferences'] as $pref)
			{
				switch ($pref[0])
				{
					case 'check':
						$new_value = isset($_POST[$pref[1]]) ? 1 : 0;
						if (!isset($options[$pref[1]]) || $new_value != $options[$pref[1]])
						{
							$changes[] = array($memID, 1, $pref[1], $new_value);
							$options[$pref[1]] = $new_value;
						}
						break;
				}
			}

			if (!empty($changes))
			{
				$db->insert('replace',
					'{db_prefix}themes',
					array('id_member' => 'int', 'id_theme' => 'int', 'variable' => 'string', 'value' => 'string'),
					$changes,
					array('id_member', 'id_theme', 'variable')
				);
			}
		}
	}
}
