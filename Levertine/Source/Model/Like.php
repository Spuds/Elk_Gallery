<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0 / elkarte
 */

namespace Addons\Levertine\Source\Model;

use Addons\Levertine\Source\LevGalBootstrap;
use ElkArte\Cache\Cache;
use ElkArte\Notifications\Notifications;
use ElkArte\Notifications\NotificationsTask;
use ElkArte\User;

/**
 * This file deals with items that users have liked.
 */
class Like
{
	protected function clearCacheByItems($id_items)
	{
		$id_items = (array) $id_items;
		foreach ($id_items as $id_item)
		{
			Cache::instance()->put('lgal_likes_i' . $id_item, null);
		}
	}

	public function getLikesByItem($id_item)
	{
		$db = database();

		$cache_key = 'lgal_likes_i' . $id_item;
		$cache_ttl = 150;

		if (($temp = Cache::instance()->get($cache_key, $cache_ttl)) === null)
		{
			$request = $db->query('', '
				SELECT
					mem.id_member, mem.real_name
				FROM {db_prefix}lgal_likes AS l
					INNER JOIN {db_prefix}members AS mem ON (l.id_member = mem.id_member)
				WHERE l.id_item = {int:item}
				ORDER BY l.like_time ASC',
				[
					'item' => $id_item,
				]
			);
			$temp = [];
			while ($row = $request->fetch_assoc())
			{
				$temp[$row['id_member']] = $row['real_name'];
			}

			Cache::instance()->put($cache_key, $temp, $cache_ttl);
		}

		return $temp;
	}

	public function likeItem($id_item)
	{
		$db = database();

		$db->insert('replace',
			'{db_prefix}lgal_likes',
			['id_item' => 'int', 'id_member' => 'int', 'like_time' => 'int'],
			[$id_item, User::$info['id'], time()],
			['id_item', 'id_member']
		);

		call_integration_hook('integrate_lgal_like_item', [$id_item]);
		$this->likeMention($id_item);
		$this->clearCacheByItems($id_item);
	}

	public function likeMention($itemID)
	{
		global $modSettings;

		if (empty($itemID) || empty($modSettings['mentions_enabled']))
		{
			return;
		}

		$itemModel = LevGalBootstrap::getModel('Item');
		$item_details = $itemModel->getItemInfoById($itemID);

		// Lets add in a mention to the member that just had their item liked
		$notifier = Notifications::instance();
		$notifier->add(new NotificationsTask(
			'lglike',
			$itemID,
			User::$info['id'],
			[
				'id_members' => $item_details['id_member'],
				'subject' => $item_details['item_name'],
				'status' => $item_details['approved'] ? 'new' : 'unapproved']
			));

		// Need to call send this now as an ajax event and will not follow normal flow
		$notifier->send();
	}

	public function unlikeItem($id_item)
	{
		$db = database();

		$db->query('', '
			DELETE FROM {db_prefix}lgal_likes
			WHERE id_item = {int:item}
				AND id_member = {int:member}',
			[
				'item' => $id_item,
				'member' => User::$info['id'],
			]
		);

		call_integration_hook('integrate_lgal_unlike_item', [$id_item]);
		$this->clearCacheByItems($id_item);
	}

	public function deleteLikesByItems($id_items)
	{
		$db = database();

		$id_items = (array) $id_items;

		$db->query('', '
			DELETE FROM {db_prefix}lgal_likes
			WHERE id_item IN ({array_int:item})',
			[
				'item' => $id_items,
			]
		);

		$this->clearCacheByItems($id_items);
	}

	public function deleteLikesByMembers($id_members)
	{
		$db = database();

		$id_members = (array) $id_members;
		$items = [];

		$request = $db->query('', '
			SELECT 
				id_item
			FROM {db_prefix}lgal_likes
			WHERE id_member IN ({array_int:members})',
			[
				'members' => $id_members,
			]
		);
		while ($row = $request->fetch_assoc())
		{
			$items[] = $row['id_item'];
		}
		$request->free_result();

		if (!empty($items))
		{
			$db->query('', '
				DELETE FROM {db_prefix}lgal_likes
				WHERE id_member IN ({array_int:members})',
				[
					'members' => $id_members,
				]
			);
			$this->clearCacheByItems(array_unique($items));
		}
	}
}
