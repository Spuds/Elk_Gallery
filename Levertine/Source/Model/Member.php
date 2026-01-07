<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.0 / elkarte
 */

/**
 * This file deals with certain member-related activities that ElkArte will need us to perform.
 */
class LevGal_Model_Member
{
	public static function deleteMembers($users)
	{
		foreach ($users as $user)
		{
			self::deleteMember($user);
		}
	}

	public static function deleteMember($memID)
	{
		$db = database();

		// Deleting a member does encourage us to houseclean some data that otherwise won't be of any use.

		// They won't be getting notifications.
		$notifyModel = LevGal_Bootstrap::getModel('LevGal_Model_Notify');
		$notifyModel->removeAllNotifyForUser($memID);

		// They won't be using their unseen data any more.
		$unseenModel = LevGal_Bootstrap::getModel('LevGal_Model_Unseen');
		$unseenModel->removeUnseenByMember($memID);

		// They also can't have liked anything.
		$likeModel = LevGal_Bootstrap::getModel('LevGal_Model_Like');
		$likeModel->deleteLikesByMembers($memID);

		// And they won't have any bookmarks either.
		$bookmarkModel = LevGal_Bootstrap::getModel('LevGal_Model_Bookmark');
		$bookmarkModel->removeAllBookmarksFromUser($memID);

		// Album ownership needs fixing. But this is not something any of the other models should
		// really bother with much.
		$only_owner = array();
		$updated_albums = array();

		$request = $db->query('', '
			SELECT 
				id_album, owner_cache
			FROM {db_prefix}lgal_albums
			ORDER BY null'
		);
		while ($row = $db->fetch_assoc($request))
		{
			$row['owner_cache'] = Util::unserialize($row['owner_cache']);
			if (isset($row['owner_cache']['member']) && in_array($memID, $row['owner_cache']['member'], true))
			{
				$updated_albums[$row['id_album']] = $row['owner_cache'];
				if (count($row['owner_cache']['member']) == 1)
				{
					$only_owner[] = $row['id_album'];
					$updated_albums[$row['id_album']]['member'] = array(0);
				}
				else
				{
					$updated_albums[$row['id_album']]['member'] = array_diff($updated_albums[$row['id_album']]['member'], array($memID));
				}
			}
		}
		$db->free_result($request);

		if (!empty($updated_albums))
		{
			// Step 1. Fix the album ownership itself in the albums table.
			foreach ($updated_albums as $id_album => $owner_cache)
			{
				$db->query('', '
					UPDATE {db_prefix}lgal_albums
					SET owner_cache = {string:owner_cache}
					WHERE id_album = {int:id_album}',
					array(
						'id_album' => $id_album,
						'owner_cache' => serialize($owner_cache),
					)
				);
			}

			// Step 2. Delete all instances from the hierarchy table.
			$db->query('', '
				DELETE FROM {db_prefix}log_owner_member
				WHERE id_member = {int:id_member}',
				array(
					'id_member' => $memID,
				)
			);

			// Step 3. For those where we just deleted the only owner, put something in the hierarchy for them - and make room if we have to.
			// Remember: these are now 'site albums', which are owned by member 0.
			if (!empty($only_owner))
			{
				$db->query('', '
					UPDATE {db_prefix}log_owner_member
					SET album_pos = album_pos + 1
					WHERE id_album IN ({array_int:albums})
						AND id_member = 0',
					array(
						'albums' => $only_owner,
					)
				);

				$insert_rows = array();
				foreach ($only_owner as $id_album)
				{
					$insert_rows[] = array('id_album' => $id_album, 'id_member' => 0, 'album_pos' => 1, 'album_level' => 0);
				}
				$db->insert('replace',
					'{db_prefix}log_owner_member',
					array('id_album' => 'int', 'id_member' => 'int', 'album_pos' => 'int', 'album_level' => 'int'),
					$insert_rows,
					array('id_album', 'id_member')
				);
			}
		}
	}

	public function getItemCount($memID)
	{
		global $context;

		$db = database();

		$albumModel = LevGal_Bootstrap::getModel('LevGal_Model_AlbumList');
		$album_list = $albumModel->getVisibleAlbums();
		if(empty($album_list))
		{
			return 0;
		}

		$can_see_all = allowedTo(array('lgal_manage', 'lgal_approve_item')) || $context['user']['id'] == $memID;

		// This will be inaccurate for cases of looking at another member's items where they have posted in an album you own
		// and that you can approve but you couldn't approve generally.
		$request = $db->query('', '
			SELECT 
				COUNT(id_item)
			FROM {db_prefix}lgal_items AS li
			WHERE li.id_member = {int:member}' . ($album_list !== true ? '
				AND li.id_album IN ({array_int:album_list})' : '') . ($can_see_all ? '' : '
				AND li.approved = {int:approved}'),
			array(
				'member' => $memID,
				'album_list' => $album_list,
				'approved' => 1,
			)
		);

		$count = 0;
		if ($db->num_rows($request))
		{
			list ($count) = $db->fetch_row($request);
		}

		$db->free_result($request);

		return $count;
	}

	public function getItemList($memID, $limit = 24, $start = 0)
	{
		global $context;

		$db = database();

		$albumModel = LevGal_Bootstrap::getModel('LevGal_Model_AlbumList');
		$album_list = $albumModel->getVisibleAlbums();
		if (empty($album_list))
		{
			return 0;
		}

		$can_see_all = allowedTo(array('lgal_manage', 'lgal_approve_item')) || $context['user']['id'] == $memID;

		$ids = array();

		$request = $db->query('', '
			SELECT 
				id_item
			FROM {db_prefix}lgal_items AS li
			WHERE li.id_member = {int:member}' . ($album_list !== true ? '
				AND li.id_album IN ({array_int:album_list})' : '') . ($can_see_all ? '' : '
				AND li.approved = {int:approved}') . '
			ORDER BY id_item DESC
			LIMIT {int:start}, {int:limit}',
			array(
				'member' => $memID,
				'album_list' => $album_list,
				'approved' => 1,
				'start' => $start,
				'limit' => $limit,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$ids[] = $row['id_item'];
		}
		$db->free_result($request);

		return $this->getSortedItems($ids);
	}

	public function getLikeIssuedCount($memID)
	{
		global $context;

		$db = database();

		$albumModel = LevGal_Bootstrap::getModel('LevGal_Model_AlbumList');
		$album_list = $albumModel->getVisibleAlbums();
		if (empty($album_list))
		{
			return 0;
		}

		$can_see_all = allowedTo(array('lgal_manage', 'lgal_approve_item')) || $context['user']['id'] == $memID;

		$request = $db->query('', '
			SELECT 
				COUNT(li.id_item)
			FROM {db_prefix}lgal_likes AS ll
				INNER JOIN {db_prefix}lgal_items AS li ON (ll.id_item = li.id_item)
			WHERE ll.id_member = {int:member}' . ($album_list !== true ? '
				AND li.id_album IN ({array_int:album_list})' : '') . ($can_see_all ? '' : '
				AND li.approved = {int:approved}'),
			array(
				'member' => $memID,
				'album_list' => $album_list,
				'approved' => 1,
			)
		);

		list ($count) = $db->fetch_row($request);
		$db->free_result($request);

		return $count;
	}

	public function getItemsLikeIssued($memID, $limit = 24, $start = 0)
	{
		global $context;

		$db = database();

		$albumModel = LevGal_Bootstrap::getModel('LevGal_Model_AlbumList');
		$album_list = $albumModel->getVisibleAlbums();
		if (empty($album_list))
		{
			return 0;
		}

		$can_see_all = allowedTo(array('lgal_manage', 'lgal_approve_item')) || $context['user']['id'] == $memID;

		$ids = array();

		$request = $db->query('', '
			SELECT 
				li.id_item
			FROM {db_prefix}lgal_likes AS ll
				INNER JOIN {db_prefix}lgal_items AS li ON (ll.id_item = li.id_item)
			WHERE ll.id_member = {int:member}' . ($album_list !== true ? '
				AND li.id_album IN ({array_int:album_list})' : '') . ($can_see_all ? '' : '
				AND li.approved = {int:approved}') . '
			ORDER BY id_item DESC
			LIMIT {int:start}, {int:limit}',
			array(
				'member' => $memID,
				'album_list' => $album_list,
				'approved' => 1,
				'start' => $start,
				'limit' => $limit,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$ids[] = $row['id_item'];
		}
		$db->free_result($request);

		return $this->getSortedItems($ids);
	}

	public function getLikeReceivedCount($memID)
	{
		global $context;

		$db = database();

		$albumModel = LevGal_Bootstrap::getModel('LevGal_Model_AlbumList');
		$album_list = $albumModel->getVisibleAlbums();
		if (empty($album_list))
		{
			return 0;
		}

		$can_see_all = allowedTo(array('lgal_manage', 'lgal_approve_item')) || $context['user']['id'] == $memID;

		$request = $db->query('', '
			SELECT 
				COUNT(li.id_item)
			FROM {db_prefix}lgal_items AS li
				INNER JOIN {db_prefix}lgal_likes AS ll ON (ll.id_item = li.id_item)
			WHERE li.id_member = {int:member}' . ($album_list !== true ? '
				AND li.id_album IN ({array_int:album_list})' : '') . ($can_see_all ? '' : '
				AND li.approved = {int:approved}'),
			array(
				'member' => $memID,
				'album_list' => $album_list,
				'approved' => 1,
			)
		);

		list ($count) = $db->fetch_row($request);
		$db->free_result($request);

		return $count;
	}

	public function getItemsReceivedIssued($memID, $limit = 24, $start = 0)
	{
		global $context;

		$db = database();

		$albumModel = LevGal_Bootstrap::getModel('LevGal_Model_AlbumList');
		$album_list = $albumModel->getVisibleAlbums();
		if (empty($album_list))
		{
			return 0;
		}

		$can_see_all = allowedTo(array('lgal_manage', 'lgal_approve_item')) || $context['user']['id'] == $memID;

		$ids = array();

		$request = $db->query('', '
			SELECT 
				li.id_item
			FROM {db_prefix}lgal_items AS li
				INNER JOIN {db_prefix}lgal_likes AS ll ON (ll.id_item = li.id_item)
			WHERE li.id_member = {int:member}' . ($album_list !== true ? '
				AND li.id_album IN ({array_int:album_list})' : '') . ($can_see_all ? '' : '
				AND li.approved = {int:approved}') . '
			ORDER BY id_item DESC
			LIMIT {int:start}, {int:limit}',
			array(
				'member' => $memID,
				'album_list' => $album_list,
				'approved' => 1,
				'start' => $start,
				'limit' => $limit,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$ids[] = $row['id_item'];
		}
		$db->free_result($request);

		return $this->getSortedItems($ids);
	}

	protected function getSortedItems($ids)
	{
		if (empty($ids))
		{
			return array();
		}

		$items = array();
		$itemList = LevGal_Bootstrap::getModel('LevGal_Model_ItemList');
		$item_details = $itemList->getItemsById($ids);

		// Whatever order they came out of the database in, make sure we return them in that order to our caller. I know technically we could
		// do something like sort by the array key to get PK ordering but in future we might not want that.
		foreach ($ids as $id)
		{
			if (isset($item_details[$id]))
			{
				$items[$id] = $item_details[$id];
			}
		}

		return $items;
	}

	public function getFromAutoSuggest($autosuggest)
	{
		global $user_profile;

		$members = array();
		$members_display = array();

		// First, against the autosuggested list with JavaScripty goodness.
		if (isset($_POST[$autosuggest . '_list']) && is_array($_POST[$autosuggest . '_list']))
		{
			foreach ($_POST[$autosuggest . '_list'] as $member)
			{
				$member = (int) $member;
				if ($member > 0)
				{
					$members[] = $member;
				}
			}
			$loaded = loadMemberData($members, false, 'minimal');
			$members = array();
			foreach ($loaded as $member)
			{
				$members[] = (int) $member;
				$members_display[$member] = $user_profile[$member]['real_name'];
			}
		}
		// Then against the textual searchbox.
		if (!empty($_POST[$autosuggest]))
		{
			require_once(SUBSDIR . '/Auth.subs.php');
			$found_members = findMembers($_POST[$autosuggest]);
			foreach ($found_members as $id_member => $member)
				{
					$id_member = (int) $id_member;
					$members[] = $id_member;
					$members_display[$id_member] = $member['name'];
				}
		}
		$members = array_unique($members);

		return array($members, $members_display);
	}
}
