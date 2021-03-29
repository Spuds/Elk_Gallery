<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.1.0 / elkarte
 */

use BBC\ParserWrapper;

/**
 * This file deals with getting information about items in bulk.
 */
class LevGal_Model_ItemList
{
	/** @var \LevGal_Model_AlbumList */
	private $album_list_model;

	protected function getAlbumListModel()
	{
		if ($this->album_list_model === null)
		{
			$this->album_list_model = LevGal_Bootstrap::getModel('LevGal_Model_AlbumList');
		}
	}

	public function getItemsById($items, $bypass_check = null)
	{
		global $scripturl, $modSettings, $user_info;

		$db = database();

		if ($items === '')
		{
			return array();
		}

		if (!is_array($items))
		{
			$items = array($items);
		}

		if (allowedTo('lgal_manage') || !empty($bypass_check))
		{
			$album_list = true;
			$criteria = '';
		}
		else
		{
			$this->getAlbumListModel();
			$album_list = $this->album_list_model->getVisibleAlbums();
			if ($user_info['is_guest'])
			{
				if (!empty($_SESSION['lgal_items']))
				{
					$criteria = '
				AND (li.approved = {int:approved} OR li.id_item IN ({array_int:my_items}))';
				}
				else
				{
					$criteria = '
				AND (li.approved = {int:approved})';
				}
			}
			else
			{
				$criteria = '
				AND (li.approved = {int:approved} OR li.id_member = {int:current_member})';
			}
		}

		if (empty($album_list))
		{
			return array();
		}

		$request = $db->query('', '
			SELECT 
				id_item, li.id_album, mem.id_member, IFNULL(mem.real_name, li.poster_name) AS poster_name,
				item_name, item_slug, mime_type, li.mature, li.num_views, li.num_comments,
				 ' . (allowedTo('lgal_manage') ? 'li.num_comments + li.num_unapproved_comments AS total_comments' : 'li.num_comments AS total_comments') . ',
				li.filehash, li.width, li.height, li.extension, li.time_added, la.album_name, la.album_slug, li.approved
			FROM {db_prefix}lgal_items AS li
				LEFT JOIN {db_prefix}members AS mem ON (li.id_member = mem.id_member)
				INNER JOIN {db_prefix}lgal_albums AS la ON (li.id_album = la.id_album)
			WHERE li.id_item IN ({array_int:items})' . ($album_list !== true ? '
				AND li.id_album IN ({array_int:album_list})' : '') . $criteria,
			array(
				'items' => $items,
				'album_list' => $album_list,
				'approved' => 1,
				'current_member' => $user_info['id'],
				'my_items' => !empty($_SESSION['lgal_items']) ? $_SESSION['lgal_items'] : array(),
			)
		);

		$item_list = array();
		$itemModel = new LevGal_Model_Item();
		while ($row = $db->fetch_assoc($request))
		{
			$itemModel->buildFromSurrogate($row);
			$item_urls = $itemModel->getItemURLs();
			$row += array(
				'item_url' => $item_urls['item'],
				'thumbnail' => $item_urls['thumb'],
				'preview' => $item_urls['preview'],
				'item_base' => $item_urls['raw'],
				'album_url' => $scripturl . '?media/album/' . (!empty($row['album_slug']) ? $row['album_slug'] . '.' . $row['id_album'] : $row['id_album']) . '/',
				'item_type' => $itemModel->getItemType(),
			);
			if (empty($modSettings['lgal_enable_mature']))
			{
				$row['mature'] = 0;
			}

			$item_list[$row['id_item']] = $row;
		}
		$db->free_result($request);

		return $item_list;
	}

	public function getItemDescriptionsById($items, $bypass_check = null, $parse_bbc = true)
	{
		$db = database();

		if (!is_array($items))
		{
			$items = array($items);
		}

		if (empty($items))
		{
			return array();
		}

		if (allowedTo('lgal_manage') || !empty($bypass_check))
		{
			$album_list = true;
		}
		else
		{
			$this->getAlbumListModel();
			$album_list = $this->album_list_model->getVisibleAlbums();
		}

		if (empty($album_list))
		{
			return array();
		}

		$request = $db->query('', '
			SELECT 
				id_item, li.description
			FROM {db_prefix}lgal_items AS li
			WHERE li.id_item IN ({array_int:items})' . ($album_list !== true ? '
				AND li.id_album IN ({array_int:album_list})' : ''),
			array(
				'items' => $items,
				'album_list' => $album_list,
			)
		);

		$item_list = array();
		// Set some defaults.
		foreach ($items as $item_id)
		{
			$item_list[$item_id] = '';
		}
		$parser = ParserWrapper::instance();
		while ($row = $db->fetch_assoc($request))
		{
			$item_list[$row['id_item']] = !empty($row['description']) ? ($parse_bbc ? $parser->parseMessage($row['description'], true) : $row['description']) : '';
		}
		$db->free_result($request);

		return $item_list;
	}

	public function getLatestItems($qty = 4)
	{
		return $this->getItemList(array(), array(), 'id_item DESC', $qty);
	}

	public function getRandomItems($qty = 4)
	{
		return $this->getItemList(array(), array(), 'RAND()', $qty);
	}

	public function getLatestItemsForUser($user, $qty = 4)
	{
		if (empty($user))
		{
			return array();
		}

		return $this->getItemList(
			array('id_member = {int:id_member}'),
			array('id_member' => $user),
			'id_item DESC',
			$qty
		);
	}

	protected function getItemList($criteria = array(), $values = array(), $order = 'id_item DESC', $qty = 4)
	{
		$db = database();

		if (allowedTo('lgal_manage'))
		{
			$album_list = true;
		}
		else
		{
			$this->getAlbumListModel();
			$album_list = $this->album_list_model->getVisibleAlbums();
		}

		if (empty($album_list))
		{
			return array();
		}

		$query_id = $order === 'RAND()' ? 'get_random_number' : '';

		$criteria = array_merge(
			array(
				$album_list === true ? '1=1' : 'id_album IN ({array_int:album_list})',
				'approved = 1',
			),
			$criteria
		);

		// First get $qty items that are approved from albums we can see.
		$request = $db->query($query_id, '
			SELECT 
			    id_item
			FROM {db_prefix}lgal_items
			WHERE ' . implode(' AND ', $criteria) . '
			ORDER BY {raw:order}
			LIMIT {int:qty}',
			array_merge($values, array(
				'album_list' => $album_list,
				'order' => $order,
				'qty' => $qty,
			))
		);
		$item_list = array();
		while ($row = $db->fetch_assoc($request))
		{
			$item_list[$row['id_item']] = array();
		}
		$db->free_result($request);

		// Then get the rest of the details and ultimately we'll want to order by this array.
		$items = $this->getItemsById(array_keys($item_list));

		foreach (array_keys($item_list) as $item)
		{
			$item_list[$item] = $items[$item];
		}

		return $item_list;
	}

	public function moveItemsToAlbum($items, $album)
	{
		$db = database();

		$items = (array) $items;

		// First, get the item details. We need to figure out how many comments and whatnot we're moving between things.
		$stat_updates = array();
		$request = $db->query('', '
			SELECT id_item, id_album, approved, num_comments, num_unapproved_comments
			FROM {db_prefix}lgal_items
			WHERE id_item IN ({array_int:items})',
			array(
				'items' => $items,
			)
		);
		$found_items = array();
		while ($row = $db->fetch_assoc($request))
		{
			// Already there?
			if ($row['id_album'] == $album)
			{
				continue;
			}

			$found_items[] = $row['id_item'];

			if (!isset($stat_updates[$row['id_album']]))
			{
				$stat_updates[$row['id_album']] = array(
					'num_items' => 0,
					'num_unapproved_items' => 0,
					'num_comments' => 0,
					'num_unapproved_comments' => 0,
				);
			}
			$stat_updates[$row['id_album']][$row['approved'] ? 'num_items' : 'num_unapproved_items']--;
			$stat_updates[$row['id_album']]['num_comments'] -= $row['num_comments'];
			$stat_updates[$row['id_album']]['num_unapproved_comments'] -= $row['num_unapproved_comments'];
		}
		$db->free_result($request);

		if (empty($found_items))
		{
			return 0;
		}

		// So now we know how many things we're moving. Let's do that now.
		$db->query('', '
			UPDATE {db_prefix}lgal_items
			SET id_album = {int:new_album}
			WHERE id_item IN ({array_int:items})',
			array(
				'new_album' => $album,
				'items' => $found_items,
			)
		);

		// Now fix the existing albums and bundle the new album, while we're at it.
		$total_changes = array(
			'num_items' => 0,
			'num_unapproved_items' => 0,
			'num_comments' => 0,
			'num_unapproved_comments' => 0,
		);
		foreach ($stat_updates as $changes)
		{
			foreach (array_keys($total_changes) as $key)
			{
				$total_changes[$key] = -$changes[$key]; // All the others are negative since they are subtractions, this one needs to be an addition, so invert the sign.
			}
		}

		// And since we now tallied everything, we can do that one too.
		$stat_updates[$album] = $total_changes;
		call_integration_hook('integrate_lgal_move_items', array($found_items, $album));

		foreach ($stat_updates as $this_album => $changes)
		{
			$db->query('', '
				UPDATE {db_prefix}lgal_albums
				SET num_items = num_items + {int:num_items},
					num_unapproved_items = num_unapproved_items + {int:num_unapproved_items},
					num_comments = num_comments + {int:num_comments},
					num_unapproved_comments = num_unapproved_comments + {int:num_unapproved_comments}
				WHERE id_album = {int:album}',
				array_merge(array('album' => $this_album), $changes)
			);
		}

		// Also update any reports if we have any.
		$report = new LevGal_Model_Report();
		$report->itemsMovedAlbum($found_items, $album);

		return count($found_items);
	}

	public function deleteItemsByIds($items, $update_album = false)
	{
		global $modSettings;

		$db = database();

		$items = (array) $items;

		// First, we get all the items' details that we actually care about. Enough to build surrogates for later, anyway.
		$request = $db->query('', '
			SELECT 
				li.id_item, li.id_album, li.id_member, li.poster_name,
				item_name, item_slug, mime_type, li.approved, li.mature, li.num_views, li.num_comments, li.num_unapproved_comments,
				li.filehash, li.extension, li.time_added, la.album_name, la.album_slug
			FROM {db_prefix}lgal_items AS li
				LEFT JOIN {db_prefix}lgal_albums AS la ON (li.id_album = la.id_album)
			WHERE id_item IN ({array_int:items})',
			array(
				'items' => $items,
			)
		);
		$data = array();
		while ($row = $db->fetch_assoc($request))
		{
			$data[] = $row;
		}
		$db->free_result($request);

		if (empty($data))
		{
			return;
		}

		// Dispatch any hooks.
		call_integration_hook('integrate_lgal_delete_items', array($items));

		// Now, as we have this, we can prune the DB contents for this.
		$db->query('', '
			DELETE FROM {db_prefix}lgal_items
			WHERE id_item IN ({array_int:items})',
			array(
				'items' => $items,
			)
		);

		// Now delete comments, bookmarks, likes, unseen, notify
		$commentModel = new LevGal_Model_Comment();
		$commentModel->deleteCommentsByItems($items);

		$bookmarkModel = new LevGal_Model_Bookmark();
		$bookmarkModel->removeAllBookmarksFromItem($items);

		$likeModel = new LevGal_Model_Like();
		$likeModel->deleteLikesByItems($items);

		$unseenModel = new LevGal_Model_Unseen();
		$unseenModel->removeItemsById($items);

		$notifyModel = new LevGal_Model_Notify();
		$notifyModel->unsetAllNotifyItem($items);

		$searchModel = new LevGal_Model_Search();
		$searchModel->deleteItemEntries($items);

		$cfModel = new LevGal_Model_Custom();
		$cfModel->deleteFieldsByItems($items);

		// Now the files have to go. Do NOT use the getModel here for this. We may, or may not, be calling from an item model itself.
		$itemModel = new LevGal_Model_Item();
		$approved = 0;
		$comments = 0;
		$log_events = array();
		foreach ($data as $item)
		{
			$log_events[] = $item['item_name'];
			if ($item['approved'])
			{
				$approved++;
			}
			$comments += $item['num_comments'];
			$itemModel->buildFromSurrogate($item);
			$itemModel->deleteFiles();
		}

		// We need to refresh the current unapproved count, might as well do it just once.
		$itemModel->updateUnapprovedCount();

		// And prune any reports of this little lot.
		$reportModel = new LevGal_Model_Report();
		$reportModel->deleteReportsByItems($items);

		// Now fix the global stats. We only care about the items we knew were approved already.
		if (!empty($approved) && !empty($modSettings['lgal_total_items']))
		{
			$total_items = $modSettings['lgal_total_items'] - $approved;
			if ($total_items < 0)
			{
				$total_items = 0;
			}
			updateSettings(array('lgal_total_items' => $total_items));
		}

		if (!empty($comments) && !empty($modSettings['lgal_total_comments']))
		{
			$total_comments = $modSettings['lgal_total_comments'] - $comments;
			if ($total_comments < 0)
			{
				$total_comments = 0;
			}
			updateSettings(array('lgal_total_comments' => $total_comments));
		}

		// We might need to update the album stats, we might not. Let's do this.
		if ($update_album)
		{
			$changes = array();
			foreach ($data as $item)
			{
				if (!isset($changes[$item['id_album']]))
				{
					$changes[$item['id_album']] = array(
						'id_album' => $item['id_album'],
						'num_items' => 0,
						'num_unapproved_items' => 0,
						'num_comments' => 0,
						'num_unapproved_comments' => 0,
					);
				}

				$changes[$item['id_album']][$item['approved'] ? 'num_items' : 'num_unapproved_items']++;
				$changes[$item['id_album']]['num_comments'] += $item['num_comments'];
				$changes[$item['id_album']]['num_unapproved_comments'] += $item['num_unapproved_comments'];
			}

			foreach ($changes as $changed_values)
			{
				$db->query('', '
					UPDATE {db_prefix}lgal_albums
					SET num_items = num_items - {int:num_items},
						num_unapproved_items = num_unapproved_items - {int:num_unapproved_items},
						num_comments = num_comments - {int:num_comments},
						num_unapproved_comments = num_unapproved_comments - {int:num_unapproved_comments}
					WHERE id_album = {int:id_album}',
					$changed_values
				);
			}

			// Handle the moderation log: if we're not caring about album stats updates, we're not caring about the moderation log either
			// i.e. deleting the album.
			$log = array();
			foreach ($log_events as $item_name)
			{
				$log[] = array('event' => 'delete_item', 'details' => array('item_name' => $item_name));
			}
			LevGal_Model_ModLog::logEvents($log);
		}
	}
}
