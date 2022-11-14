<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.0 / elkarte
 */

/**
 * This file deals with album internals.
 */
class LevGal_Model_Album
{
	/** @var mixed  */
	private $current_album = false;
	/** @var int  */
	public const LOCKED_ITEMS = 1;
	/** @var int  */
	public const LOCKED_COMMENTS = 2;

	public function getAlbumById($albumId)
	{
		$db = database();

		// It's a uint, anything like this can disappear.
		if ($albumId <= 0)
		{
			return false;
		}

		// This can be called multiple times, potentially, for the same album.
		if (!empty($this->current_album['id_album']) && $albumId = $this->current_album['id_album'])
		{
			return $this->current_album;
		}

		$request = $db->query('', '
			SELECT 
				id_album, album_name, album_slug, thumbnail, editable, locked, approved, num_items, num_unapproved_items, num_comments,
				num_unapproved_comments, featured, owner_cache, perms
			FROM {db_prefix}lgal_albums
			WHERE id_album = {int:albumId}',
			array(
				'albumId' => $albumId,
			)
		);

		if ($db->num_rows($request) > 0)
		{
			$this->current_album = $db->fetch_assoc($request);
			foreach (array('owner_cache', 'perms') as $item)
			{
				$this->current_album[$item] = !empty($this->current_album[$item]) ? Util::unserialize($this->current_album[$item]) : array();
			}
			foreach (array('member', 'group') as $type)
			{
				if (isset($this->current_album['owner_cache'][$type]) && !is_array($this->current_album['owner_cache'][$type]))
				{
					$this->current_album['owner_cache'][$type] = (array) $this->current_album['owner_cache'][$type];
				}
			}
			if (empty($this->current_album['perms']))
			{
				$this->current_album['perms'] = array('type' => 'justme');
			}
			$this->current_album['album_url'] = $this->getAlbumUrl();
			$this->current_album['thumbnail_url'] = $this->getThumbnailUrl();
		}
		$db->free_result($request);

		return $this->current_album;
	}

	public function getLinkTreeDetails()
	{
		if (empty($this->current_album))
		{
			return array();
		}

		return array(
			'name' => $this->current_album['album_name'],
			'url' => $this->current_album['album_url'],
		);
	}

	// This isn't pretty, but it means we can reuse all the exciting other methods without having
	// to expressly re-query anything.
	public function buildFromSurrogate($details)
	{
		$this->current_album = $details;
		foreach (array('owner_cache', 'perms') as $item)
		{
			$this->current_album[$item] = !empty($this->current_album[$item]) ? Util::unserialize($this->current_album[$item]) : array();
		}
		$this->current_album['album_url'] = $this->getAlbumUrl();
		$this->current_album['thumbnail_url'] = $this->getThumbnailUrl();
		$this->current_album['is_surrogate'] = true;
	}

	public function getAlbumUrl()
	{
		global $scripturl;

		return !empty($this->current_album) ? $scripturl . '?media/album/' . (!empty($this->current_album['album_slug']) ? $this->current_album['album_slug'] . '.' . $this->current_album['id_album'] : $this->current_album['id_album']) . '/' : false;
	}

	public function getThumbnailFile()
	{
		global $settings;

		if (empty($this->current_album) || empty($this->current_album['thumbnail']) || strpos($this->current_album['thumbnail'], 'folder') === 0 || strpos($this->current_album['thumbnail'], 'generic/') === 0)
		{
			return array(false, $this->getThumbnailUrl());
		}

		list ($ext, $hash) = explode(',', $this->current_album['thumbnail']);
		$album_thumb = LevGal_Bootstrap::getGalleryDir() . '/albums/' . $this->current_album['id_album'] . '_' . $hash . '.dat';

		if (file_exists($album_thumb))
		{
			return array($ext, $album_thumb);
		}

		return array(false, $settings['default_theme_url'] . '/levgal_res/albums/folder.svg');
	}

	public function getThumbnailUrl()
	{
		global $settings;

		if (empty($this->current_album) || empty($this->current_album['thumbnail']))
		{
			return $settings['default_theme_url'] . '/levgal_res/albums/folder.svg';
		}

		if (strpos($this->current_album['thumbnail'], 'folder') === 0)
		{
			return $settings['default_theme_url'] . '/levgal_res/albums/' . $this->current_album['thumbnail'];
		}

		if (strpos($this->current_album['thumbnail'], 'generic/') === 0)
		{
			return $settings['default_theme_url'] . '/levgal_res/icons/' . substr($this->current_album['thumbnail'], 8);
		}

		return $this->current_album['album_url'] . 'thumb/';
	}

	public function isApproved()
	{
		return !empty($this->current_album) && !empty($this->current_album['approved']);
	}

	public function isVisible()
	{
		global $user_info;

		// Album invalid. Bye.
		if (empty($this->current_album))
		{
			return false;
		}

		// Is it admin only or something?
		if (allowedTo('lgal_manage'))
		{
			return true;
		}

		// Is it unapproved? (But album owners should be able to see their own albums)
		if (!$this->isApproved() && !allowedTo('lgal_approve_album') && !$this->isOwnedByUser())
		{
			return false;
		}

		// This is where it gets so much more complicated. Firstly, permissions *were* set, right?
		if (empty($this->current_album['perms']) || empty($this->current_album['perms']['type']))
		{
			return false;
		}

		// So, what's the deal then?
		switch ($this->current_album['perms']['type'])
		{
			case 'guests':
				return true;
			case 'members':
				return !$user_info['is_guest'];
			case 'justme':
				return $this->isOwnedByUser();
			case 'custom':
				return $this->isOwnedByUser() || count(array_intersect($user_info['groups'], $this->current_album['perms']['groups'])) > 0;
		}

		return false;
	}

	public function isOwnedByUser()
	{
		global $user_info;

		if (empty($this->current_album) || !empty($user_info['is_guest']))
		{
			return false;
		}

		// If the current user is an owner, he can see it.
		if (!empty($this->current_album['owner_cache']['member']) && in_array($user_info['id'], $this->current_album['owner_cache']['member'], true))
		{
			return true;
		}

		// If it's a group album and the member is a member of the group, they can see it.
		if (!empty($this->current_album['owner_cache']['group']) && count(array_intersect($user_info['groups'], $this->current_album['owner_cache']['group'])) > 0)
		{
			return true;
		}

		return false;
	}

	public function isLockedForItems()
	{
		return $this->current_album['locked'] & self::LOCKED_ITEMS;
	}

	public function isLockedForComments()
	{
		return $this->current_album['locked'] & self::LOCKED_COMMENTS;
	}

	public function isEditable()
	{
		if (empty($this->current_album))
		{
			return false;
		}

		// If they're a gallery manager, or they can edit any album, or they can edit their own albums (and this is their album), let them edit.
		if (allowedTo(array('lgal_manage', 'lgal_edit_album_any')) || (allowedTo('lgal_edit_album_own') && $this->isOwnedByUser()))
		{
			return true;
		}

		// If, however, it's not any of these, it *might* still be editable if it's their album and not yet finalised e.g. they want to adjust the thumbnail, something we don't offer them the initial page.
		if ($this->isOwnedByUser() && !empty($this->current_album['editable']))
		{
			return true;
		}

		return false;
	}

	public function canUploadItems()
	{
		return !$this->isLockedForItems()
			&& (allowedTo(array('lgal_additem_any', 'lgal_manage'))
				|| (allowedTo('lgal_additem_own') && $this->isOwnedByUser()));
	}

	public function loadOwnerData()
	{
		if (empty($this->current_album))
		{
			return false;
		}

		$details = array(
			'member' => array(),
			'group' => array(),
		);

		// Load members who own this group. This is easy since it basically piggybacks ElkArte's own.
		if (!empty($this->current_album['owner_cache']['member']))
		{
			// This one is rather easy.
			$details['member'] = loadMemberData($this->current_album['owner_cache']['member']);
			// Albums owned by the site will have a member attached to fetch.
			if (!empty($details['member']))
			{
				sort($details['member']);
				foreach ($details['member'] as $member)
				{
					loadMemberContext($member);
				}
			}
		}

		if (!empty($this->current_album['owner_cache']['group']))
		{
			$details['group'] = $this->current_album['owner_cache']['group'];
			$group = new LevGal_Model_Group();
			$details['group_details'] = $group->getGroupsById($details['group']);
		}

		// If empty, it's owned by the site not by any user or group
		return (empty($details['member']) && empty($details['group'])) ? false : $details;
	}

	public function countAlbumItems()
	{
		global $user_info, $modSettings;

		$db = database();

		if (empty($this->current_album))
		{
			return 0;
		}

		// If you can manage, you can see everything, as if you can approve all items.
		if (allowedTo('lgal_manage') || allowedTo('lgal_approve_item'))
		{
			return $this->current_album['num_items'] + $this->current_album['num_unapproved_items'];
		}

		// If you can see the album, you can see what's in it - but only admins/managers/approvers/album-owners-with-approve-in-own-album can see unapproved items.
		$getting_all = ($this->isOwnedByUser() && !empty($modSettings['lgal_selfmod_approve_item']));

		$criteria = '
			WHERE li.id_album = {int:id_album}';
		if (!$getting_all)
		{
			if ($user_info['is_guest'] && !empty($_SESSION['lgal_items']))
			{
				$criteria .= '
			AND (li.approved = {int:approved} OR li.id_member = {int:member} OR li.id_item IN ({array_int:my_items}))';
			}
			elseif ($user_info['is_guest'])
			{
				$criteria .= '
			AND (li.approved = {int:approved})';
			}
			else
			{
				$criteria .= '
			AND (li.approved = {int:approved} OR li.id_member = {int:member})';
			}
		}

		$request = $db->query('', '
			SELECT COUNT(id_item)
			FROM {db_prefix}lgal_items AS li' . $criteria,
			array(
				'id_album' => $this->current_album['id_album'],
				'approved' => 1,
				'member' => $user_info['id'],
				'my_items' => !empty($_SESSION['lgal_items']) ? $_SESSION['lgal_items'] : array(),
			)
		);
		list ($count) = $db->fetch_row($request);
		$db->free_result($request);

		return $count;
	}

	public function getSortingOptions()
	{
		return array(
			'date' => array(
				'asc' => 'li.time_added',
				'desc' => 'li.time_added DESC',
			),
			'name' => array(
				'asc' => 'li.item_name',
				'desc' => 'li.item_name DESC',
			),
			'views' => array(
				'asc' => 'li.num_views',
				'desc' => 'li.num_views DESC',
			),
			'comments' => array(
				'asc' => 'total_comments',
				'desc' => 'total_comments DESC',
			)
		);
	}

	public function canSeeAllItems()
	{
		global $modSettings;

		// If you can see the album, you can see what's in it - but only admins/managers/approvers/album-owners-with-approve-in-own-album can see unapproved items.
		return allowedTo('lgal_manage') || allowedTo('lgal_approve_item') || ($this->isOwnedByUser() && !empty($modSettings['lgal_selfmod_approve_item']));
	}

	public function loadAlbumItems($num_items = 24, $start = 0, $order_by = 'date', $order = 'desc', $get_description = false)
	{
		global $user_info;

		$db = database();

		$items = array();

		if (empty($this->current_album))
		{
			return $items;
		}

		$getting_all = $this->canSeeAllItems();

		$order_options = $this->getSortingOptions();
		if ($order !== 'asc' && $order !== 'desc')
		{
			$order = 'desc';
		}
		if (!isset($order_options[$order_by]))
		{
			$order_by = 'date';
			$order = 'desc';
		}

		$criteria = '
			WHERE li.id_album = {int:id_album}';
		if (!$getting_all)
		{
			if (!$user_info['is_guest'])
			{
				$criteria .= '
				AND (li.approved = {int:approved} OR li.id_member = {int:member})';
			}
			elseif (!empty($_SESSION['lgal_items']))
			{
				$criteria .= '
				AND (li.approved = {int:approved} OR li.id_item IN ({array_int:my_items}))';
			}
			else
			{
				$criteria .= '
				AND (li.approved = {int:approved})';
			}
		}

		$item_surrogate = new LevGal_Model_Item();

		$request = $db->query('', '
			SELECT 
				li.id_item, li.id_album, IFNULL(mem.id_member, 0) AS id_member, IFNULL(mem.real_name, li.poster_name) AS poster_name, li.item_name, li.item_slug,
				li.filename, li.filehash, li.extension, li.mime_type, li.time_added, li.time_updated, li.approved, li.filesize, li.width, li.height,
				li.mature, li.num_views' . ($getting_all ? ', (li.num_comments + li.num_unapproved_comments) AS total_comments' : ', li.num_comments AS total_comments') . ', li.meta
			FROM {db_prefix}lgal_items AS li
				LEFT JOIN {db_prefix}members AS mem ON (li.id_member = mem.id_member)' . $criteria . '
			ORDER BY ' . $order_options[$order_by][$order] . '
			LIMIT {int:start}, {int:limit}',
			array(
				'id_album' => $this->current_album['id_album'],
				'approved' => 1,
				'member' => $user_info['id'],
				'start' => $start,
				'limit' => $num_items,
				'my_items' => !empty($_SESSION['lgal_items']) ? $_SESSION['lgal_items'] : array(),
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			// This is very ugly but means we reuse the item model better.
			// Most importantly this means any updates to thumbnailing paths or even generic paths get handled.
			$item_surrogate->buildFromSurrogate($row);
			$urls = $item_surrogate->getItemURLs();
			$row['item_url'] = !empty($urls['item']) ? $urls['item'] : '';
			$row['thumbnail'] = !empty($urls['thumb']) ? $urls['thumb'] : '';
			$items[$row['id_item']] = $row;
		}
		$db->free_result($request);

		if (!empty($get_description) && !empty($items))
		{
			// While we could technically do it above, it isn't recommended due to screwing around
			// with order predicating.
			$item_list = LevGal_Bootstrap::getModel('LevGal_Model_ItemList');
			$item_ids = array_keys($items);
			$descriptions = $item_list->getItemDescriptionsById($item_ids, true);
			foreach ($item_ids as $id_item)
			{
				$items[$id_item]['description'] = !empty($descriptions[$id_item]) ? $descriptions[$id_item] : '';
			}
		}

		return $items;
	}

	public function getUnapprovedCommentsOnUserItems($user)
	{
		$db = database();

		$request = $db->query('', '
			SELECT 
				SUM(num_unapproved_comments)
			FROM {db_prefix}lgal_items
			WHERE id_album = {int:album}
				AND id_member = {int:user}',
			array(
				'album' => $this->current_album['id_album'],
				'user' => $user,
			)
		);
		list ($count) = $db->fetch_row($request);
		$db->free_result($request);

		return $count;
	}

	public function usersCanSeeAlbum($users)
	{
		if (empty($users) || empty($this->current_album) || empty($this->current_album['perms']['type']))
		{
			return false;
		}

		// If this album isn't approved, things are a lot, lot different.
		if (!$this->isApproved())
		{
			require_once(SUBSDIR . '/Members.subs.php');
			$users = array_intersect($users, array_merge(membersAllowedTo('lgal_manage'), membersAllowedTo('lgal_approve_album')));
		}

		// OK, fine, we need to know what the rules are on this album to see who out of the current users selected could possibly see it.
		// This is done primarily for notifications, but we can't just prune this list, items may become moved, users change permissions etc.

		// Members and guests-visible albums are easy: anyone on the 'can these people see it' list is automatically covered.
		if ($this->current_album['perms']['type'] === 'guests' || $this->current_album['perms']['type'] === 'members')
		{
			return $users;
		}

		// Failing that, a little more complex.
		if ($this->current_album['perms']['type'] === 'justme')
		{
			// First, let a list of all gallery managers.
			require_once(SUBSDIR . '/Members.subs.php');
			$managers = membersAllowedTo('lgal_manage');

			$notifying = array_intersect($users, $managers);
			$members_left = array_diff($users, $managers);

			if (!empty($members_left))
			{
				if (!empty($this->current_album['owner_cache']['member']))
				{
					$members_left = array_intersect($members_left, $this->current_album['owner_cache']['member']);
					$notifying = array_merge($notifying, $members_left);
				}
				elseif (!empty($this->current_album['owner_cache']['group']))
				{
					$groupModel = new LevGal_Model_Group();
					$notifying = array_merge($notifying, $groupModel->matchUsersInGroups($members_left, $this->current_album['owner_cache']['group']));
				}
			}

			return $notifying;
		}

		// OK, so access is determined by one or more groups. We now need to figure out which of these users may be in which of these groups.
		if ($this->current_album['perms']['type'] === 'custom')
		{
			// First, let a list of all gallery managers.
			require_once(SUBSDIR . '/Members.subs.php');
			$managers = membersAllowedTo('lgal_manage');

			$notifying = array_intersect($users, $managers);
			$members_left = array_diff($users, $managers);

			if (!empty($members_left))
			{
				$groupModel = new LevGal_Model_Group();
				$notifying = array_merge($notifying, $groupModel->matchUsersInGroups($members_left, $this->current_album['perms']['groups']));
			}

			return $notifying;
		}

		// OK then...
		return array();
	}

	public function markSeen()
	{
		$unseenModel = new LevGal_Model_Unseen();
		$unseenModel->markAlbumSeen($this->current_album['id_album']);
	}

	public function addedComment($wasApproved)
	{
		// So something has added a comment to this item.
		$db = database();

		if (empty($this->current_album))
		{
			return false;
		}

		// So, update the album.
		$db->query('', '
			UPDATE {db_prefix}lgal_albums
			SET {raw:column} = {raw:column} + 1
			WHERE id_album = {int:album}',
			array(
				'column' => !empty($wasApproved) ? 'num_comments' : 'num_unapproved_comments',
				'album' => $this->current_album['id_album'],
			)
		);

		return true;
	}

	public function approvedComment()
	{
		// So something has approved a comment to an item in this album.
		$db = database();

		if (empty($this->current_album))
		{
			return false;
		}

		// So, update the album.
		$db->query('', '
			UPDATE {db_prefix}lgal_albums
			SET num_comments = num_comments + 1,
				num_unapproved_comments = num_unapproved_comments - 1
			WHERE id_album = {int:album}',
			array(
				'album' => $this->current_album['id_album'],
			)
		);

		return true;
	}

	public function approvedItem()
	{
		return $this->updateItemApproved(true);
	}

	public function unapprovedItem()
	{
		return $this->updateItemApproved(false);
	}

	protected function updateItemApproved($new_state)
	{
		$db = database();

		if (empty($this->current_album))
		{
			return false;
		}

		// So, update the album.
		$db->query('', '
			UPDATE {db_prefix}lgal_albums
			SET num_items = num_items {raw:num_items_change} 1,
				num_unapproved_items = num_unapproved_items {raw:num_unapproved_items_change} 1
			WHERE id_album = {int:album}',
			array(
				'album' => $this->current_album['id_album'],
				'num_items_change' => $new_state ? '+' : '-',
				'num_unapproved_items_change' => $new_state ? '-' : '+',
			)
		);

		return true;
	}

	public function deletedComment($wasApproved)
	{
		$db = database();

		if (empty($this->current_album))
		{
			return false;
		}

		$db->query('', '
			UPDATE {db_prefix}lgal_albums
			SET {raw:column} = {raw:column} - 1
			WHERE id_album = {int:album}',
			array(
				'column' => !empty($wasApproved) ? 'num_comments' : 'num_unapproved_comments',
				'album' => $this->current_album['id_album'],
			)
		);

		return true;
	}

	public function deletedItem($wasApproved, $num_comments, $num_unapproved_comments)
	{
		$db = database();

		if (empty($this->current_album))
		{
			return false;
		}

		$db->query('', '
			UPDATE {db_prefix}lgal_albums
			SET {raw:column} = {raw:column} - 1,
				num_comments = num_comments - {int:num_comments},
				num_unapproved_comments = num_unapproved_comments - {int:num_unapproved_comments}
			WHERE id_album = {int:album}',
			array(
				'column' => !empty($wasApproved) ? 'num_items' : 'num_unapproved_items',
				'num_comments' => $num_comments,
				'num_unapproved_comments' => $num_unapproved_comments,
				'album' => $this->current_album['id_album'],
			)
		);

		return true;
	}

	public function addedItem($wasApproved)
	{
		$db = database();

		if (empty($this->current_album))
		{
			return false;
		}

		$db->query('', '
			UPDATE {db_prefix}lgal_albums
			SET {raw:column} = {raw:column} + 1
			WHERE id_album = {int:album}',
			array(
				'column' => !empty($wasApproved) ? 'num_items' : 'num_unapproved_items',
				'album' => $this->current_album['id_album'],
			)
		);

		return true;
	}

	public function notifyItem($item_id, $item_obj)
	{
		global $modSettings;

		// So, who wants notifications and who is going to get notifications?
		$item = $item_obj->getItemInfoById($item_id);

		$notifyModel = new LevGal_Model_Notify();
		$members = $notifyModel->getNotifyForAlbum($this->current_album['id_album']);

		if ($item['approved'])
		{
			// If it's approved, it's easy, anyone that can see the album is on the list of possible candidates.
			$members = $this->usersCanSeeAlbum($members);
		}
		else
		{
			// If not, it gets a lot more complex. Managers, approvers, and item owners if the relevant option is set can see this one.
			$groupModel = new LevGal_Model_Group();
			$groups = $groupModel->allowedTo('lgal_manage');
			$groups = array_merge($groups, $groupModel->allowedTo('lgal_approve_item'));
			$album = $item_obj->getParentAlbum();

			// If it's a group owner, we can bolt that on easily enough.
			if (!empty($modSettings['lgal_selfmod_approve_item']) && !empty($album['owner_cache']['group']))
			{
				$groups = array_merge($groups, $album['owner_cache']['group']);
			}

			// First we just tackle that little lot. We'll worry about the owner+approver combination in a moment.
			$notifying = $groupModel->matchUsersInGroups($members, $groups);

			if (!empty($modSettings['lgal_selfmod_approve_item']) && !empty($album['owner_cache']['member']))
			{
				$notifying = array_merge($notifying, array_intersect($members, $album['owner_cache']['member']));
			}
			$members = array_unique($notifying);
		}

		if (!empty($members))
		{
			$notifier = \Notifications::instance();
			$notifier->add(new Notifications_Task(
				'lgnew',
				$item_id,
				$item['id_member'],
				array(
					'id_members' => $members,
					'subject' => $item['item_name'],
					'url' => $item['item_url'],
					'status' => 'new',
				)
			));
		}
	}

	public function createAlbum($name, $slug, $approved)
	{
		$db = database();

		$db->insert('insert',
			'{db_prefix}lgal_albums',
			array('album_name' => 'string', 'album_slug' => 'string', 'thumbnail' => 'string', 'editable' => 'int', 'locked' => 'int', 'approved' => 'int', 'num_items' => 'int',
				  'num_unapproved_items' => 'int', 'num_comments' => 'int', 'num_unapproved_comments' => 'int', 'owner_cache' => 'string', 'perms' => 'string'),
			array($name, $slug, '', 0, 0, !empty($approved) ? 1 : 0, 0,
				  0, 0, 0, '', ''),
			array('id_album')
		);
		$id = $db->insert_id('{db_prefix}lgal_albums');

		if ($id !== false)
		{
			$this->current_album = array(
				'id_album' => $id,
				'album_name' => $name,
				'album_slug' => $slug,
				'editable' => 0,
				'locked' => 0,
				'num_items' => 0,
				'num_unapproved_items' => 0,
				'num_comments' => 0,
				'num_unapproved_comments' => 0,
				'approved' => !empty($approved) ? 1 : 0,
				'owner_cache' => array(),
				'perms' => array(),
			);
			$this->current_album['album_url'] = $this->getAlbumUrl();

			if (empty($approved))
			{
				$this->updateUnapprovedCount();
			}

			$search = new LevGal_Model_Search();
			$search->createAlbumEntries(array(array($id, $name)));

			// This is notification only of new album.
			call_integration_hook('integrate_lgal_create_album', array($this->current_album));
		}

		return $id;
	}

	public function updateUnapprovedCount()
	{
		$db = database();

		$request = $db->query('', '
			SELECT 
				COUNT(*)
			FROM {db_prefix}lgal_albums
			WHERE approved = {int:not_approved}',
			array(
				'not_approved' => 0,
			)
		);
		list ($unapproved) = $db->fetch_row($request);
		$db->free_result($request);

		updateSettings(array('lgal_unapproved_albums' => $unapproved));
	}

	public function getAlbumOwnership()
	{
		if (!empty($this->current_album['owner_cache']))
		{
			if (isset($this->current_album['owner_cache']['group']))
			{
				return array('type' => 'group', 'owners' => $this->current_album['owner_cache']['group']);
			}

			if (isset($this->current_album['owner_cache']['member']))
			{
				if (in_array(0, $this->current_album['owner_cache']['member'], true))
				{
					return array('type' => 'site', 'owners' => array());
				}

				return array('type' => 'member', 'owners' => $this->current_album['owner_cache']['member']);
			}
		}

		return array('type' => 'unknown', 'owners' => array());
	}

	public function setAlbumOwnership($ownership_type, $ownership_data = array())
	{
		$db = database();

		// There's a few things we need: current album, valid options.
		if (empty($this->current_album)
			|| !in_array($ownership_type, array('site', 'member', 'group'))
			|| ($ownership_type === 'member' && empty($ownership_data)))
		{
			return false;
		}

		// Ownership type = site is really just type member with id_member = 0
		if ($ownership_type === 'site')
		{
			$ownership_type = 'member';
			$ownership_data = array(0);
		}

		// First, we have to find if there are any pre-existing ownership rules. Owners first.
		$this->revokeOwnership();

		// Now whatever we are setting, we need to set it. First: make some room.
		if (!is_array($ownership_data))
		{
			$ownership_data = array($ownership_data);
		}
		foreach ($ownership_data as $selector)
		{
			$db->query('', '
				UPDATE {db_prefix}lgal_owner_' . $ownership_type . '
				SET album_pos = album_pos + 1
				WHERE id_' . $ownership_type . ' = {int:selector}',
				array(
					'selector' => $selector,
				)
			);
		}

		// Now perform the inserts: top of hierarchy, left most level.
		$insert_rows = array();
		foreach ($ownership_data as $selector)
		{
			$insert_rows[] = array($this->current_album['id_album'], $selector, 1, 0);
		}
		$db->insert('insert',
			'{db_prefix}lgal_owner_' . $ownership_type,
			array('id_album' => 'int', 'id_' . $ownership_type => 'int', 'album_pos' => 'int', 'album_level' => 'int'),
			$insert_rows,
			array('id_album', 'id_' . $ownership_type)
		);

		// Now set the owner_cache.
		$this->updateAlbum(array('owner_cache' => array($ownership_type => $ownership_data)));

		return true;
	}

	private function revokeOwnership()
	{
		$db = database();

		foreach (array('member', 'group') as $this_type)
		{
			$request = $db->query('', '
				SELECT 
					id_album, id_' . $this_type . ' AS selector, album_pos
				FROM {db_prefix}lgal_owner_' . $this_type . '
				WHERE id_album = {int:album}',
				array(
					'album' => $this->current_album['id_album'],
				)
			);

			while ($row = $db->fetch_assoc($request))
			{
				// If any of these are found, we need to remove them and bump the rest down appropriately.
				$db->query('', '
					DELETE FROM {db_prefix}lgal_owner_' . $this_type . '
					WHERE id_album = {int:album}
						AND id_' . $this_type . ' = {int:selector}',
					array(
						'album' => $row['id_album'],
						'selector' => $row['selector'],
					)
				);
				// Now we need to bump them. It doesn't matter quite so much if there's children, the hierarchy will be fine.
				$db->query('', '
					UPDATE {db_prefix}lgal_owner_' . $this_type . '
					SET album_pos = album_pos - 1
					WHERE id_' . $this_type . ' = {int:selector}
						AND album_pos > {int:old_album_pos}',
					array(
						'selector' => $row['selector'],
						'old_album_pos' => $row['album_pos'],
					)
				);
			}
			$db->free_result($request);
		}
	}

	public function addAlbumOwner($owner_type, $owner_data)
	{
		$db = database();

		// This does not support cross-ownership switches. Nor does it support adding to site albums.
		if (!isset($this->current_album['owner_cache'][$owner_type]) || (!empty($this->current_album['owner_cache']['member']) && in_array(0, $this->current_album['owner_cache']['member'], true)))
		{
			return;
		}

		$new_entries = array_diff($owner_data, $this->current_album['owner_cache'][$owner_type]);
		if (!empty($new_entries))
		{
			// First we need to add these to the hierarchy. For that, we need to make room.
			$db->query('', '
				UPDATE {db_prefix}{raw:table}
				SET album_pos = album_pos + 1
				WHERE {raw:column} IN ({array_int:owners})',
				array(
					'table' => $owner_type === 'member' ? 'lgal_owner_member' : 'lgal_owner_group',
					'column' => $owner_type === 'member' ? 'id_member' : 'id_group',
					'owners' => $new_entries,
				)
			);
			// Then we need to add them.
			$new_rows = array();
			foreach ($new_entries as $new_entry)
			{
				$new_rows[] = array($this->current_album['id_album'], $new_entry, 1, 0);
			}
			$db->insert('',
				$owner_type === 'member' ? '{db_prefix}lgal_owner_member' : '{db_prefix}lgal_owner_group',
				array('id_album' => 'int', ($owner_type === 'member' ? 'id_member' : 'id_group') => 'int', 'album_pos' => 'int', 'album_level' => 'int'),
				$new_rows,
				array('id_album', ($owner_type === 'member' ? 'id_member' : 'id_group'))
			);

			// Then we need to update what's in the table.
			$new_ownership = array_merge($this->current_album['owner_cache'][$owner_type], $new_entries);

			$this->updateAlbum(array('owner_cache' => array($owner_type => $new_ownership)));
		}
	}

	public function removeAlbumOwner($owner_type, $owner_data)
	{
		$db = database();

		// This does not support cross-ownership switches. Nor does it support adding to site albums.
		if (!isset($this->current_album['owner_cache'][$owner_type]) || (!empty($this->current_album['owner_cache']['member']) && in_array(0, $this->current_album['owner_cache']['member'], true)))
		{
			return;
		}

		$entries = array_intersect($owner_data, $this->current_album['owner_cache'][$owner_type]);
		if (!empty($entries))
		{
			// First, we need the positions in the hierarchy because we need to bump the album_pos. We don't need to fix
			// actual hierarchy since our existing methods should handle this automagically.
			$positions = array();
			$request = $db->query('', '
				SELECT 
					{raw:column} AS owner, album_pos
				FROM {db_prefix}{raw:table}
				WHERE id_album = {int:album}
					AND {raw:column} IN ({array_int:owners})',
				array(
					'column' => $owner_type === 'member' ? 'id_member' : 'id_group',
					'table' => $owner_type === 'member' ? 'lgal_owner_member' : 'lgal_owner_group',
					'album' => $this->current_album['id_album'],
					'owners' => $entries,
				)
			);
			while ($row = $db->fetch_assoc($request))
			{
				$positions[$row['owner']] = $row['album_pos'];
			}
			$db->free_result($request);

			// Now delete from the hierarchy.
			$db->query('', '
				DELETE FROM {db_prefix}{raw:table}
				WHERE id_album = {int:album}
					AND {raw:column} IN ({array_int:owners})',
				array(
					'column' => $owner_type === 'member' ? 'id_member' : 'id_group',
					'table' => $owner_type === 'member' ? 'lgal_owner_member' : 'lgal_owner_group',
					'album' => $this->current_album['id_album'],
					'owners' => $entries,
				)
			);

			// Now strip the hierarchy positions back.
			foreach ($positions as $owner => $album_pos)
			{
				$db->query('', '
					UPDATE {db_prefix}{raw:table}
					SET album_pos = album_pos - 1
					WHERE {raw:column} = {int:owner}
						AND album_pos > {int:album_pos}',
					array(
						'column' => $owner_type === 'member' ? 'id_member' : 'id_group',
						'table' => $owner_type === 'member' ? 'lgal_owner_member' : 'lgal_owner_group',
						'owner' => $owner,
						'album_pos' => $album_pos,
					)
				);
			}

			// Now, lastly, strip the entries from the owner cache.
			$new_ownership = $this->current_album['owner_cache'];
			$new_ownership[$owner_type] = array_diff($new_ownership[$owner_type], $entries);

			$this->updateAlbum(array('owner_cache' => $new_ownership));
		}
	}

	public function setAlbumPrivacy($privacy_type, $privacy_data = array())
	{
		$db = database();

		if (empty($this->current_album) || !in_array($privacy_type, array('guests', 'members', 'justme', 'custom')))
		{
			return false;
		}

		if ($privacy_type !== 'custom')
		{
			$privacy = array('type' => $privacy_type);
		}
		else
		{
			if (empty($privacy_data))
			{
				return false;
			}
			$privacy = array(
				'type' => $privacy_type,
				'groups' => $privacy_data,
			);
		}

		$request = $db->query('', '
			SELECT 
				perms
			FROM {db_prefix}lgal_albums
			WHERE id_album = {int:album}',
			array(
				'album' => $this->current_album['id_album'],
			)
		);
		list ($perms) = $db->fetch_row($request);
		$db->free_result($request);

		$perms = !empty($perms) ? Util::unserialize($perms) : array();
		$this->current_album['perms'] = array_merge((array) $perms, $privacy);

		$db->query('', '
			UPDATE {db_prefix}lgal_albums
			SET perms = {string:perms}
			WHERE id_album = {int:album}',
			array(
				'album' => $this->current_album['id_album'],
				'perms' => serialize($this->current_album['perms']),
			)
		);

		return true;
	}

	public function updateAlbum($opts)
	{
		$db = database();
		$params = [];

		// Serialized arrays
		foreach (array('owner_cache') as $var)
		{
			if (isset($opts[$var]))
			{
				$criteria[] = $var . ' = {string:' . $var . '}';
				$params[$var] = serialize($opts[$var]);
			}
		}

		// Known strings
		foreach (array('album_name', 'album_slug') as $var)
		{
			if (isset($opts[$var]))
			{
				$criteria[] = $var . ' = {string:' . $var . '}';
				$params[$var] = $opts[$var];
			}
		}

		// Known quasi-bools
		foreach (array('featured', 'approved', 'editable') as $var)
		{
			if (isset($opts[$var]))
			{
				$criteria[] = $var . ' = {int:' . $var . '}';
				$params[$var] = !empty($opts[$var]) ? 1 : 0;
			}
		}

		// Known ints
		foreach (array('locked') as $var)
		{
			if (isset($opts[$var]))
			{
				$criteria[] = $var . ' = {int:' . $var . '}';
				$params[$var] = !empty($opts[$var]) ? (int) $opts[$var] : 0;
			}
		}

		if (isset($opts['thumbnail']))
		{
			$this->removeThumbnail();
			$criteria[] = 'thumbnail = {string:thumbnail}';
			$params['thumbnail'] = $opts['thumbnail'];
		}

		if (isset($opts['perms']['type']) && ($opts['perms']['type'] !== 'custom' || isset($opts['perms']['groups'])))
		{
			$new_value = array(
				'type' => $opts['perms']['type'],
			);
			if ($opts['perms']['type'] === 'custom')
			{
				$new_value['groups'] = array();

				if (!empty($opts['perms']['groups']))
				{
					foreach ($opts['perms']['groups'] as $k => $v)
					{
						$opts['perms']['groups'][$k] = (int) $v;
					}
					$new_value['groups'] = array_diff($opts['perms']['groups'], array(1));
				}
			}
			$criteria[] = 'perms = {string:perms}';
			$params['perms'] = serialize($new_value);
		}

		if (!empty($criteria))
		{
			$db->query('', '
				UPDATE {db_prefix}lgal_albums
				SET ' . implode(', ', $criteria) . '
				WHERE id_album = {int:id_album}',
				array_merge(array('id_album' => $this->current_album['id_album']), $params)
			);
			$this->current_album = array_merge($this->current_album, $params);
			if (isset($opts['owner_cache']))
			{
				// We would rather have the unserialized version, kthx.
				$this->current_album['owner_cache'] = $opts['owner_cache'];
			}
		}

		if (isset($opts['album_name']))
		{
			$search = new LevGal_Model_Search();
			$search->updateAlbumEntry($this->current_album['id_album'], $opts['album_name']);
		}
	}

	public function deleteAlbum()
	{
		$db = database();

		if (empty($this->current_album) || empty($this->current_album['id_album']))
		{
			return;
		}

		// Deleting is not especially difficult. First, remove all the items.
		$items = array();
		$request = $db->query('', '
			SELECT id_item
			FROM {db_prefix}lgal_items
			WHERE id_album = {int:album}',
			array(
				'album' => $this->current_album['id_album'],
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$items[] = $row['id_item'];
		}
		$db->free_result($request);

		// Sometimes albums are empty.
		if (!empty($items))
		{
			$itemList = new LevGal_Model_ItemList();
			$itemList->deleteItemsByIds($items, false); // We don't need to update the album stats because we're going to remove it shortly.
		}

		// Now we have to remove ownership of such things since that's got tendrils in tables.
		$this->revokeOwnership();

		// And remove it from the search index.
		$search = new LevGal_Model_Search();
		$search->deleteAlbumEntries($this->current_album['id_album']);

		// Now put it in the moderation log.
		LevGal_Model_ModLog::logEvent('delete_album', array('album_name' => $this->current_album['album_name'], 'items_deleted' => count($items)));

		// Now remove the album itself.
		$db->query('', '
			DELETE FROM {db_prefix}lgal_albums
			WHERE id_album = {int:album}',
			array(
				'album' => $this->current_album['id_album'],
			)
		);

		// This is notification only of deleting album.
		call_integration_hook('integrate_lgal_delete_album', array($this->current_album['id_album']));
	}

	public function getAlbumFamily()
	{
		$hierarchies = array();

		/** @var $albumList \LevGal_Model_AlbumList */
		$albumList = LevGal_Bootstrap::getModel('LevGal_Model_AlbumList');
		foreach ($this->current_album['owner_cache'] as $owner_type => $owners)
		{
			$owners = (array) $owners;
			$child_albums = 0;
			foreach ($owners as $owner)
			{
				$hierarchy = $albumList->getAlbumFamilyInHierarchy($owner_type, $owner, $this->current_album['id_album']);
				if (!empty($hierarchy))
				{
					$child_albums += count(array_keys($hierarchy)) - 1;
					$hierarchies[$owner_type][$owner] = $hierarchy;
				}
			}
			$hierarchies['album_count'] = $child_albums;
		}

		return $hierarchies;
	}

	public function getOwnershipOptions()
	{
		$opts = array();
		if (allowedTo('lgal_manage'))
		{
			$opts[] = 'site';
		}
		if (allowedTo(array('lgal_adduseralbum', 'lgal_manage')))
		{
			$opts[] = 'member';
		}
		if (allowedTo(array('lgal_addgroupalbum', 'lgal_manage')))
		{
			$opts[] = 'group';
		}

		return $opts;
	}

	public function getAllowableOwnershipGroups()
	{
		global $user_info;
		static $cache = null;

		if ($cache !== null)
		{
			return $cache;
		}

		// Since group ownership is an option, we need to get the group listing that you
		// might want to bestow it upon.
		$opts = array(
			'exclude_moderator' => true,
			'exclude_postcount' => true,
		);

		// If user is not an admin/manager, they can only assign ownership to the groups they can see.
		// Also exclude hidden groups.
		if (!allowedTo('lgal_manage'))
		{
			$opts += array(
				'exclude_hidden' => true,
				'match_groups' => $user_info['groups'],
			);
		}

		$groupModel = new LevGal_Model_Group();
		$groups = $groupModel->getGroupsByCriteria($opts);
		$cache = $groups;

		return $groups;
	}

	public function getAllowableAccessGroups()
	{
		return $this->getAllowableOwnershipGroups();
	}

	public function isFeatured()
	{
		return !empty($this->current_album) && !empty($this->current_album['featured']);
	}

	public function markFeatured($is_featured)
	{
		$this->updateAlbum(array('featured' => !empty($is_featured) ? 1 : 0));
		LevGal_Model_ModLog::logEvent($is_featured ? 'feature_album' : 'unfeature_album', array('id_album' => $this->current_album['id_album']));
		call_integration_hook(!empty($is_featured) ? 'integrate_lgal_feature_album' : 'integrate_lgal_unfeature_album', array($this->current_album['id_album']));
	}

	public function markApproved()
	{
		if (empty($this->current_album))
		{
			return false;
		}

		if (!$this->isApproved())
		{
			$this->updateAlbum(array('approved' => 1));
			$this->updateUnapprovedCount();
			LevGal_Model_ModLog::logEvent('approve_album', array('id_album' => $this->current_album['id_album']));
			call_integration_hook('integrate_lgal_approve_album', array($this->current_album['id_album']));
		}

		return false;
	}

	public function setGenericThumbnail($file)
	{
		if (strpos($file, 'folder') !== 0)
		{
			$file = 'generic/' . $file;
		}
		$this->updateAlbum(array('thumbnail' => $file));
	}

	public function setThumbnailFromFile($sourcefile, $format)
	{
		$uploadModel = new LevGal_Model_Upload();
		$hash = $uploadModel->getFileHash($sourcefile);

		$base_path = LevGal_Bootstrap::getGalleryDir();
		$dest_file = $base_path . '/albums/' . $this->current_album['id_album'] . '_' . $hash . '.dat';

		if (@copy($sourcefile, $dest_file))
		{
			$this->updateAlbum(array('thumbnail' => $format . ',' . $hash));
		}
	}

	protected function removeThumbnail()
	{
		if (empty($this->current_album) || empty($this->current_album['thumbnail']) || strpos($this->current_album['thumbnail'], 'folder') === 0 || strpos($this->current_album['thumbnail'], 'generic') === 0)
		{
			return;
		}
		list (, $hash) = explode(',', $this->current_album['thumbnail']);
		$base_path = LevGal_Bootstrap::getGalleryDir();
		@unlink($base_path . '/albums/' . $this->current_album['id_album'] . '_' . $hash . '.dat');
	}
}
