<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.0 / elkarte
 */

/**
 * This file deals with the fetching and updating of unseen counts.
 */
class LevGal_Model_Unseen
{
	/** @var int  */
	public const UNSEEN_THRESHOLD = 2;
	/** @var LevGal_Model_AlbumList */
	private $album_list;
	/** @var mixed */
	private $albums;

	protected function getVisibleAlbums()
	{
		if (allowedTo('lgal_manage'))
		{
			$this->albums = true;
		}
		else
		{
			if (empty($this->album_list))
			{
				$this->album_list = new LevGal_Model_AlbumList();
			}
			$this->albums = $this->album_list->getVisibleAlbums();
		}
	}

	public function updateUnseenItems()
	{
		global $user_info, $user_settings;

		$db = database();

		if (!empty($user_settings['lgal_new']))
		{
			// First, get the list of albums we can see.
			$this->getVisibleAlbums();

			if (empty($this->albums))
			{
				// No albums? Reset the new counter to 0 and the unseen count as well.
				updateMemberData($user_info['id'], array('lgal_new' => 0, 'lgal_unseen' => 0));
				$user_settings['lgal_new'] = 0;
				$user_settings['lgal_unseen'] = 0;

				return;
			}

			$viewing_all = allowedTo(array('lgal_manage', 'lgal_approve_item'));

			// Note: your own items are not included in the case of non-moderator with item unapproved
			$request = $db->query('', '
				SELECT 
					COUNT(li.id_item) AS item_count, COUNT(ls.id_item) AS seen_count
				FROM {db_prefix}lgal_items AS li
					LEFT JOIN {db_prefix}lgal_log_seen AS ls ON (ls.id_item = li.id_item AND ls.id_member = {int:current_member} AND ls.view_time >= li.time_updated - {int:unseen_threshold})
				WHERE ' . ($this->albums !== true ? 'li.id_album IN ({array_int:albums})' : '1=1') . (!$viewing_all ? '
					AND li.approved = 1' : ''),
				array(
					'current_member' => $user_info['id'],
					'albums' => $this->albums,
					'unseen_threshold' => self::UNSEEN_THRESHOLD,
				)
			);
			list ($items, $seen) = $db->fetch_row($request);
			$db->free_result($request);

			$unseen = $items - $seen;
			updateMemberData($user_info['id'], array('lgal_new' => 0, 'lgal_unseen' => $unseen));
			$user_settings['lgal_new'] = 0;
			$user_settings['lgal_unseen'] = $unseen;
		}
	}

	public function getUnseenCountByAlbum()
	{
		global $user_info, $scripturl;

		$db = database();

		// First, get the list of albums we can see.
		$this->getVisibleAlbums();

		// No albums for you?
		if (empty($this->albums))
		{
			return array();
		}

		$viewing_all = allowedTo(array('lgal_manage', 'lgal_approve_item'));

		// Note: your own items are not included in the case of non-moderator with item unapproved
		$unseen_albums = array();
		$request = $db->query('', '
			SELECT 
				li.id_album, la.album_name, la.album_slug, COUNT(li.id_item) AS item_count, COUNT(ls.id_item) AS seen_count
			FROM {db_prefix}lgal_items AS li
				INNER JOIN {db_prefix}lgal_albums AS la ON (li.id_album = la.id_album)
				LEFT JOIN {db_prefix}lgal_log_seen AS ls ON (ls.id_item = li.id_item AND ls.id_member = {int:current_member} AND ls.view_time >= li.time_updated - {int:unseen_threshold})
			WHERE ' . ($this->albums !== true ? 'li.id_album IN ({array_int:albums})' : '1=1') . (!$viewing_all ? '
				AND li.approved = 1' : '') . '
			GROUP BY li.id_album
			ORDER BY la.album_name',
			array(
				'current_member' => $user_info['id'],
				'albums' => $this->albums,
				'unseen_threshold' => self::UNSEEN_THRESHOLD,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			if ($row['item_count'] > $row['seen_count'])
			{
				$unseen_albums[$row['id_album']] = array(
					'album_name' => $row['album_name'],
					'album_slug' => $row['album_slug'],
					'unseen' => $row['item_count'] - $row['seen_count'],
					'album_url' => $scripturl . '?media/album/' . (!empty($row['album_slug']) ? $row['album_slug'] . '.' . $row['id_album'] : $row['id_album']) . '/',
					'filter_url' => $scripturl . '?media/unseen/' . (!empty($row['album_slug']) ? $row['album_slug'] . '.' . $row['id_album'] : $row['id_album']) . '/',
				);
			}
		}
		$db->free_result($request);

		return $unseen_albums;
	}

	public function getUnseenItems($start = 0, $limit = 24, $album_filter = 0)
	{
		global $user_info;

		$db = database();

		// First, get the list of albums we can see.
		$this->getVisibleAlbums();

		// No albums for you?
		if (empty($this->albums))
		{
			return array();
		}

		$viewing_all = allowedTo(array('lgal_manage', 'lgal_approve_item'));
		$unseen_items = array();
		$request = $db->query('', '
			SELECT 
				li.id_item
			FROM {db_prefix}lgal_items AS li
				LEFT JOIN {db_prefix}lgal_log_seen AS ls ON (ls.id_item = li.id_item AND ls.id_member = {int:current_member} AND ls.view_time >= li.time_updated - {int:unseen_threshold})
			WHERE ' . ($this->albums !== true ? 'li.id_album IN ({array_int:albums})' : '1=1') . (!$viewing_all ? '
				AND li.approved = 1' : '') . '
				AND ls.id_item IS NULL' . (!empty($album_filter) ? '
				AND li.id_album = {int:album_filter}' : '') . '
			GROUP BY li.id_item
			ORDER BY li.id_item DESC
			LIMIT {int:start}, {int:limit}',
			array(
				'current_member' => $user_info['id'],
				'albums' => $this->albums,
				'start' => $start,
				'limit' => $limit,
				'album_filter' => $album_filter,
				'unseen_threshold' => self::UNSEEN_THRESHOLD,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$unseen_items[$row['id_item']] = true;
		}
		$db->free_result($request);

		$itemModel = new LevGal_Model_ItemList();
		$items = $itemModel->getItemsById(array_keys($unseen_items), true);

		// Now we rebuild it, in order.
		foreach (array_keys($unseen_items) as $id_item)
		{
			if (isset($items[$id_item]))
			{
				$unseen_items[$id_item] = $items[$id_item];
			}
			else
			{
				unset ($unseen_items[$id_item]);
			}
		}

		return $unseen_items;
	}

	public function markAlbumSeen($id_album)
	{
		global $modSettings, $user_info;

		$album_obj = new LevGal_Model_Album();
		$album_obj->getAlbumById($id_album);

		$marking_all = allowedTo(array('lgal_manage', 'lgal_approve_item'))
			|| (!empty($modSettings['lgal_selfmod_approve_item']) && $album_obj->isOwnedByUser());

		$db = database();

		// Step one: get all the items we're dealing with.
		$log_seen = array();
		$time = time();
		$request = $db->query('', '
			SELECT 
				id_item
			FROM {db_prefix}lgal_items
			WHERE id_album = {int:album}' . (!$marking_all ? '
				AND approved = 1' : ''),
			array(
				'album' => $id_album,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$log_seen[] = array($row['id_item'], $user_info['id'], $time);
		}
		$db->free_result($request);

		// Step two: update things.
		if (!empty($log_seen))
		{
			$db->insert('replace',
				'{db_prefix}lgal_log_seen',
				array('id_item' => 'int', 'id_member' => 'int', 'view_time' => 'int'),
				$log_seen,
				array('id_item', 'id_member')
			);
		}

		// Step three: force this to be cleaned in terms of marking seen.
		$this->markForRecount($user_info['id']);
	}

	public function removeItemsById($id_items)
	{
		$db = database();

		$id_items = (array) $id_items;

		// First, remove any lagging entries for this in the seen log.
		$db->query('', '
			DELETE FROM {db_prefix}lgal_log_seen
			WHERE id_item IN ({array_int:id_item})',
			array(
				'id_item' => $id_items,
			)
		);
		// Second, flag everyone for recount
		$this->markForRecount();
	}

	public function markForRecount($user = null)
	{
		global $user_settings;

		// First we update the database.
		updateMemberData($user, array('lgal_new' => 1));
		// Second we make sure it's done softly here too.
		$user_settings['lgal_new'] = 1;
	}

	public function removeUnseenByMember($memID)
	{
		$db = database();

		$db->query('', '
			DELETE FROM {db_prefix}lgal_log_seen
			WHERE id_member = {int:id_member}',
			array(
				'id_member' => $memID,
			)
		);
	}
}
