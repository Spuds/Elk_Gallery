<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.0 / elkarte
 */

/**
 * This file deals with items that users have liked.
 */
class LevGal_Model_Like
{
	protected function clearCacheByItems($id_items)
	{
		$id_items = (array) $id_items;
		foreach ($id_items as $id_item)
		{
			cache_put_data('lgal_likes_i' . $id_item, null);
		}
	}

	public function getLikesByItem($id_item)
	{
		$db = database();

		$cache_key = 'lgal_likes_i' . $id_item;
		$cache_ttl = 150;

		if (($temp = cache_get_data($cache_key, $cache_ttl)) === null)
		{
			$request = $db->query('', '
				SELECT 
					mem.id_member, mem.real_name
				FROM {db_prefix}lgal_likes AS l
					INNER JOIN {db_prefix}members AS mem ON (l.id_member = mem.id_member)
				WHERE l.id_item = {int:item}
				ORDER BY l.like_time ASC',
				array(
					'item' => $id_item,
				)
			);
			$temp = array();
			while ($row = $db->fetch_assoc($request))
			{
				$temp[$row['id_member']] = $row['real_name'];
			}

			cache_put_data($cache_key, $temp, $cache_ttl);
		}

		return $temp;
	}

	public function likeItem($id_item)
	{
		global $user_info;

		$db = database();

		$db->insert('replace',
			'{db_prefix}lgal_likes',
			array('id_item' => 'int', 'id_member' => 'int', 'like_time' => 'int'),
			array($id_item, $user_info['id'], time()),
			array('id_item', 'id_member')
		);

		call_integration_hook('integrate_lgal_like_item', array($id_item));
		$this->clearCacheByItems($id_item);
	}

	public function unlikeItem($id_item)
	{
		global $user_info;

		$db = database();

		$db->query('', '
			DELETE FROM {db_prefix}lgal_likes
			WHERE id_item = {int:item}
				AND id_member = {int:member}',
			array(
				'item' => $id_item,
				'member' => $user_info['id'],
			)
		);

		call_integration_hook('integrate_lgal_unlike_item', array($id_item));
		$this->clearCacheByItems($id_item);
	}

	public function deleteLikesByItems($id_items)
	{
		$db = database();

		$id_items = (array) $id_items;

		$db->query('', '
			DELETE FROM {db_prefix}lgal_likes
			WHERE id_item IN ({array_int:item})',
			array(
				'item' => $id_items,
			)
		);

		$this->clearCacheByItems($id_items);
	}

	public function deleteLikesByMembers($id_members)
	{
		$db = database();

		$id_members = (array) $id_members;
		$items = array();

		$request = $db->query('', '
			SELECT id_item
			FROM {db_prefix}lgal_likes
			WHERE id_member IN ({array_int:members})',
			array(
				'members' => $id_members,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$items[] = $row['id_item'];
		}
		$db->free_result($request);

		if (!empty($items))
		{
			$db->query('', '
				DELETE FROM {db_prefix}lgal_likes
				WHERE id_member IN ({array_int:members})',
				array(
					'members' => $id_members,
				)
			);
			$this->clearCacheByItems(array_unique($items));
		}
	}
}
