<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0 / elkarte
 */

namespace Addons\Levertine\Source\Model;

use Addons\Levertine\Source\LevGalBootstrap;
use BBC\ParserWrapper;
use ElkArte\User;

/**
 * This file deals with getting information about items in bulk.
 */
class ItemList
{
	/** @var AlbumList */
	private $album_list_model;

	protected function getAlbumListModel()
	{
		if ($this->album_list_model === null)
		{
			$this->album_list_model = LevGalBootstrap::getModel('AlbumList');
		}
	}

	public function getItemsById($items, $bypass_check = null)
	{
		global $scripturl, $modSettings;

		$db = database();

		if ($items === '')
		{
			return [];
		}

		if (!is_array($items))
		{
			$items = [$items];
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
			if (User::$info['is_guest'])
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

		if (empty($album_list) || empty($items))
		{
			return [];
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
			[
				'items' => $items,
				'album_list' => $album_list,
				'approved' => 1,
				'current_member' => User::$info['id'],
				'my_items' => !empty($_SESSION['lgal_items']) ? $_SESSION['lgal_items'] : [],
			]
		);

		$item_list = [];
		$itemModel = new Item();
		while ($row = $request->fetch_assoc())
		{
			$itemModel->buildFromSurrogate($row);
			$item_urls = $itemModel->getItemURLs();
			$row += [
				'item_url' => $item_urls['item'],
				'thumbnail' => $item_urls['thumb'],
				'preview' => $item_urls['preview'] ?? '',
				'preview_html' => $item_urls['preview_html'] ?? '',
				'thumb_html' => $item_urls['thumb_html'] ?? '',
				'item_base' => $item_urls['raw'],
				'album_url' => $scripturl . '?media/album/' . (!empty($row['album_slug']) ? $row['album_slug'] . '.' . $row['id_album'] : $row['id_album']) . '/',
				'item_type' => $itemModel->getItemType(),
			];
			if (empty($modSettings['lgal_enable_mature']))
			{
				$row['mature'] = 0;
			}

			$item_list[$row['id_item']] = $row;
		}
		$request->free_result();

		return $item_list;
	}

	public function getItemDescriptionsById($items, $bypass_check = null, $parse_bbc = true)
	{
		$db = database();

		if (!is_array($items))
		{
			$items = [$items];
		}

		if (empty($items))
		{
			return [];
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
			return [];
		}

		$request = $db->query('', '
			SELECT 
				id_item, li.description
			FROM {db_prefix}lgal_items AS li
			WHERE li.id_item IN ({array_int:items})' . ($album_list !== true ? '
				AND li.id_album IN ({array_int:album_list})' : ''),
			[
				'items' => $items,
				'album_list' => $album_list,
			]
		);
		$item_list = [];
		// Set some defaults.
		foreach ($items as $item_id)
		{
			$item_list[$item_id] = '';
		}
		$parser = ParserWrapper::instance();
		while ($row = $request->fetch_assoc())
		{
			$item_list[$row['id_item']] = !empty($row['description']) ? ($parse_bbc ? $parser->parseMessage($row['description'], true) : $row['description']) : '';
		}
		$request->free_result();

		return $item_list;
	}

	public function getLatestItems($qty = 8)
	{
		return $this->getItemList([], [], 'time_added DESC', $qty);
	}

	public function getRandomItems($qty = 8)
	{
		return $this->getItemList([], [], 'RAND()', $qty);
	}

	public function getLatestImages($qty = 8)
	{
		return $this->getItemList(['mime_type LIKE "image/%"'], [], 'time_added DESC', $qty);
	}

	public function getRandomImages($qty = 8)
	{
		return $this->getItemList(['mime_type LIKE "image/%"'], [], 'RAND()', $qty);
	}

	public function getLatestItemsForUser($user, $qty = 8)
	{
		if (empty($user))
		{
			return [];
		}

		return $this->getItemList(['id_member = {int:id_member}'], ['id_member' => $user], 'time_added DESC', $qty);
	}

	public function getImagesForAlbum($album, $qty = 4, $order = "RAND()")
	{
		$album = (int) $album;
		if (empty($album))
		{
			return [];
		}

		return $this->getItemList(['mime_type LIKE "image/%" AND id_album = {int:id_album}'], ['id_album' => $album], $order, $qty);
	}

	protected function getItemList($criteria = [], $values = [], $order = 'id_item DESC', $qty = 4)
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
			return [];
		}

		$query_id = $order === 'RAND()' ? 'get_random_number' : '';

		$criteria = array_merge(
			[
				$album_list === true ? '1=1' : 'id_album IN ({array_int:album_list})',
				'approved = 1',
			],
			$criteria
		);

		// First get $qty items that are approved from albums we can see.
		$request = $db->query($query_id, '
			SELECT 
			    id_item
			FROM {db_prefix}lgal_items
			WHERE ' . implode(' AND ', $criteria) . '
			ORDER BY ' . $order . ' 
			LIMIT {int:qty}',
			array_merge($values, [
				'album_list' => $album_list,
				'order' => $order,
				'qty' => $qty,
			])
		);
		$item_list = [];
		while ($row = $request->fetch_assoc())
		{
			$item_list[$row['id_item']] = [];
		}
		$request->free_result();

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
		$stat_updates = [];
		$request = $db->query('', '
			SELECT id_item, id_album, approved, num_comments, num_unapproved_comments
			FROM {db_prefix}lgal_items
			WHERE id_item IN ({array_int:items})',
			[
				'items' => $items,
			]
		);
		$found_items = [];
		while ($row = $request->fetch_assoc())
		{
			// Already there?
			if ($row['id_album'] == $album)
			{
				continue;
			}

			$found_items[] = $row['id_item'];

			if (!isset($stat_updates[$row['id_album']]))
			{
				$stat_updates[$row['id_album']] = [
					'num_items' => 0,
					'num_unapproved_items' => 0,
					'num_comments' => 0,
					'num_unapproved_comments' => 0,
				];
			}
			$stat_updates[$row['id_album']][$row['approved'] ? 'num_items' : 'num_unapproved_items']--;
			$stat_updates[$row['id_album']]['num_comments'] -= $row['num_comments'];
			$stat_updates[$row['id_album']]['num_unapproved_comments'] -= $row['num_unapproved_comments'];
		}
		$request->free_result();

		if (empty($found_items))
		{
			return 0;
		}

		// So now we know how many things we're moving. Let's do that now.
		$db->query('', '
			UPDATE {db_prefix}lgal_items
			SET id_album = {int:new_album}
			WHERE id_item IN ({array_int:items})',
			[
				'new_album' => $album,
				'items' => $found_items,
			]
		);

		// Now fix the existing albums and bundle the new album, while we're at it.
		$total_changes = [
			'num_items' => 0,
			'num_unapproved_items' => 0,
			'num_comments' => 0,
			'num_unapproved_comments' => 0,
		];
		foreach ($stat_updates as $changes)
		{
			foreach (array_keys($total_changes) as $key)
			{
				$total_changes[$key] = -$changes[$key]; // All the others are negative since they are subtractions, this one needs to be an addition, so invert the sign.
			}
		}

		// And since we now tallied everything, we can do that one too.
		$stat_updates[$album] = $total_changes;
		call_integration_hook('integrate_lgal_move_items', [$found_items, $album]);

		foreach ($stat_updates as $this_album => $changes)
		{
			$db->query('', '
				UPDATE {db_prefix}lgal_albums
				SET num_items = num_items + {int:num_items},
					num_unapproved_items = num_unapproved_items + {int:num_unapproved_items},
					num_comments = num_comments + {int:num_comments},
					num_unapproved_comments = num_unapproved_comments + {int:num_unapproved_comments}
				WHERE id_album = {int:album}',
				array_merge(['album' => $this_album], $changes)
			);
		}

		// Also update any reports if we have any.
		$report = new Report();
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
			[
				'items' => $items,
			]
		);
		$data = [];
		while ($row = $request->fetch_assoc())
		{
			$data[] = $row;
		}
		$request->free_result();

		if (empty($data))
		{
			return;
		}

		// Dispatch any hooks.
		call_integration_hook('integrate_lgal_delete_items', [$items]);

		// Now, as we have this, we can prune the DB contents for this.
		$db->query('', '
			DELETE FROM {db_prefix}lgal_items
			WHERE id_item IN ({array_int:items})',
			[
				'items' => $items,
			]
		);

		// Now delete comments, bookmarks, likes, unseen, notify
		$commentModel = new Comment();
		$commentModel->deleteCommentsByItems($items);

		$bookmarkModel = new Bookmark();
		$bookmarkModel->removeAllBookmarksFromItem($items);

		$likeModel = new Like();
		$likeModel->deleteLikesByItems($items);

		$unseenModel = new Unseen();
		$unseenModel->removeItemsById($items);

		$notifyModel = new Notify();
		$notifyModel->unsetAllNotifyItem($items);

		$searchModel = new Search();
		$searchModel->deleteItemEntries($items);

		$cfModel = new Custom();
		$cfModel->deleteFieldsByItems($items);

		// Now the files have to go. Do NOT use the getModel here for this. We may, or may not, be calling from an item model itself.
		$itemModel = new Item();
		$approved = 0;
		$comments = 0;
		$log_events = [];
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
		$reportModel = new Report();
		$reportModel->deleteReportsByItems($items);

		// Now fix the global stats. We only care about the items we knew were approved already.
		if (!empty($approved) && !empty($modSettings['lgal_total_items']))
		{
			$total_items = $modSettings['lgal_total_items'] - $approved;
			if ($total_items < 0)
			{
				$total_items = 0;
			}
			updateSettings(['lgal_total_items' => $total_items]);
		}

		if (!empty($comments) && !empty($modSettings['lgal_total_comments']))
		{
			$total_comments = $modSettings['lgal_total_comments'] - $comments;
			if ($total_comments < 0)
			{
				$total_comments = 0;
			}
			updateSettings(['lgal_total_comments' => $total_comments]);
		}

		// We might need to update the album stats, we might not. Let's do this.
		if ($update_album)
		{
			$changes = [];
			foreach ($data as $item)
			{
				if (!isset($changes[$item['id_album']]))
				{
					$changes[$item['id_album']] = [
						'id_album' => $item['id_album'],
						'num_items' => 0,
						'num_unapproved_items' => 0,
						'num_comments' => 0,
						'num_unapproved_comments' => 0,
					];
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
			$log = [];
			foreach ($log_events as $item_name)
			{
				$log[] = ['event' => 'delete_item', 'details' => ['item_name' => $item_name]];
			}
			ModLog::logEvents($log);
		}
	}
}
