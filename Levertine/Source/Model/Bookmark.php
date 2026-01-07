<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0 / elkarte
 */

namespace Addons\Levertine\Source\Model;

use Addons\Levertine\Source\Helper\Format;
use ElkArte\Cache\Cache;
use ElkArte\User;

/**
 * This file deals with items that users have bookmarked.
 */
class Bookmark
{
	/** @var array  */
	private $cache;

	public function __construct()
	{
		$this->cache = [];
	}

	public function getBookmarksForUser($user = 0)
	{
		$db = database();

		$user = empty($user) ? User::$info['id'] : $user;
		if (empty($user))
		{
			return [];
		}

		if (isset($this->cache[$user]))
		{
			return $this->cache[$user];
		}

		$cache_key = 'lgal_bookmarks_u' . $user;
		$cache_ttl = 120;
		if (($cache = Cache::instance()->get($cache_key, $cache_ttl)) === null)
		{
			$request = $db->query('', '
				SELECT id_item, timestamp
				FROM {db_prefix}lgal_bookmarks
				WHERE id_member = {int:user}',
				[
					'user' => $user,
				]
			);
			$cache = [];
			while ($row = $request->fetch_assoc())
			{
				$cache[$row['id_item']] = $row['timestamp'];
			}
			$request->free_result();

			Cache::instance()->put($cache_key, $cache, $cache_ttl);
		}
		$this->cache[$user] = $cache;

		return $this->cache[$user];
	}

	public function isBookmarked($item, $user = 0)
	{
		$user = empty($user) ? User::$info['id'] : $user;
		if (empty($user))
		{
			return false;
		}

		// Whatever, we need to know if the user has bookmarked it.
		if (!isset($this->cache[$user]))
		{
			$this->getBookmarksForUser();
		}

		return isset($this->cache[$user][$item]);
	}

	public function bookmarkedTime($item, $user = 0)
	{
		// Since we must already have loaded the cache magic.
		if ($this->isBookmarked($item, $user))
		{
			return $this->cache[$user][$item];
		}

		return false;
	}

	public function setBookmark($item, $user = 0)
	{
		$db = database();

		$user = empty($user) ? User::$info['id'] : $user;
		if (empty($user))
		{
			return false;
		}

		// Whatever, we need to know if the user has bookmarked it.
		if (!isset($this->cache[$user]))
		{
			$this->getBookmarksForUser();
		}

		// Is it set?
		if (!isset($this->cache[$user][$item]))
		{
			$time = time();
			$db->insert('replace',
				'{db_prefix}lgal_bookmarks',
				['id_member' => 'int', 'id_item' => 'int', 'timestamp' => 'int'],
				[$user, $item, $time],
				['id_member', 'id_item']
			);
			$this->cache[$user][$item] = $time;
		}

		$this->resetUserCache($user);

		return true;
	}

	public function unsetBookmark($item, $user = 0)
	{
		$db = database();

		$user = empty($user) ? User::$info['id'] : $user;
		if (empty($user))
		{
			return false;
		}

		// Whatever, we need to know if the user has bookmarked it.
		if (!isset($this->cache[$user]))
		{
			$this->getBookmarksForUser();
		}

		if (isset($this->cache[$user][$item]))
		{
			unset ($this->cache[$user][$item]);
			$db->query('', '
				DELETE FROM {db_prefix}lgal_bookmarks
				WHERE id_member = {int:user}
					AND id_item = {int:item}',
				[
					'user' => $user,
					'item' => $item,
				]
			);
		}

		$this->resetUserCache($user);

		return true;
	}

	public function resetUserCache($user)
	{
		$cache_key = 'lgal_bookmarks_u' . $user;
		Cache::instance()->put($cache_key, null);
	}

	public function getBookmarkList($user = 0)
	{
		global $scripturl;

		$user = empty($user) ? User::$info['id'] : $user;
		if (empty($user))
		{
			return [];
		}

		$bookmarks = [];

		$this->getBookmarksForUser($user);
		if (empty($this->cache[$user]))
		{
			return [];
		}

		// Sort by timestamp while retaining ids.
		asort($this->cache[$user]);

		$item_list = new ItemList();
		$items = $item_list->getItemsById(array_keys($this->cache[$user]));

		foreach ($this->cache[$user] as $id_item => $timestamp)
		{
			if (isset($items[$id_item]))
			{
				$bookmarks[$id_item] = [
					'item_name' => $items[$id_item]['item_name'],
					'item_url' => $items[$id_item]['item_url'],
					'item_thumbnail' => $items[$id_item]['thumbnail'],
					'album_name' => $items[$id_item]['album_name'],
					'album_url' => $items[$id_item]['album_url'],
					'poster_name' => $items[$id_item]['poster_name'],
					'poster_link' => !empty($items[$id_item]['id_member']) ? '<a href="' . $scripturl . '?action=profile;u=' . $items[$id_item]['id_member'] . '">' . $items[$id_item]['poster_name'] . '</a>' : $items[$id_item]['poster_name'],
					'item_added' => $items[$id_item]['time_added'],
					'item_added_format' => Format::time($items[$id_item]['time_added']),
					'bookmark_timestamp' => $timestamp,
					'bookmark_timestamp_format' => Format::time($timestamp),
					'num_views' => comma_format($items[$id_item]['num_views']),
					'num_comments' => comma_format($items[$id_item]['num_comments']),
				];
			}
		}

		return $bookmarks;
	}

	public function removeAllBookmarksFromItem($item = [])
	{
		$db = database();

		$item = (array) $item;
		if (empty($item))
		{
			return;
		}

		$db->query('', '
			DELETE FROM {db_prefix}lgal_bookmarks
			WHERE id_item IN ({array_int:item})',
			[
				'item' => $item,
			]
		);
	}

	public function removeAllBookmarksFromUser($memID)
	{
		$db = database();

		$db->query('', '
			DELETE FROM {db_prefix}lgal_bookmarks
			WHERE id_member = {int:id_member}',
			[
				'id_member' => $memID,
			]
		);
	}
}
