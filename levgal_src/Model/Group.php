<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.0 / elkarte
 */

/**
 * This file deals with getting group information since SMF has no suitable shared code.
 */
class LevGal_Model_Group
{
	public function getGroupsById($groups = array(), $order = 'name')
	{
		$db = database();

		$groups = (array) $groups;
		if (empty($groups))
		{
			return array();
		}

		$orders = array(
			'name' => 'group_name',
			'id' => 'id_group',
		);

		$request = $db->query('', '
			SELECT 
				id_group, group_name, online_color, {raw:stars_column} AS stars
			FROM {db_prefix}membergroups AS mg
			WHERE id_group IN ({array_int:groups})',
			array(
				'groups' => $groups,
				'stars_column' => 'icons',
			)
		);
		$details = $this->processQueryResult($request, $orders[$order] ?? $orders['name']);
		$db->free_result($request);

		return $details;
	}

	public function getGroupsByCriteria($criteria, $order = 'name')
	{
		$db = database();

		$sql_clauses = array('1=1');
		if (!empty($criteria['exclude_moderator']))
		{
			$sql_clauses[] = 'mg.id_group != 3';
		}
		if (!empty($criteria['exclude_postcount']))
		{
			$sql_clauses[] = 'mg.min_posts < 0';
		}
		if (!empty($criteria['exclude_hidden']))
		{
			$sql_clauses[] = 'mg.hidden < 2';
		}
		if (!empty($criteria['match_groups']) && is_array($criteria['match_groups']))
		{
			$sql_clauses[] = 'mg.id_group IN ({array_int:match_groups})';
		}

		$orders = array(
			'name' => 'group_name',
			'id' => 'id_group',
		);

		$request = $db->query('', '
			SELECT 
			    id_group, group_name, online_color, icons AS stars
			FROM {db_prefix}membergroups AS mg
			WHERE ' . implode(' AND ', $sql_clauses),
			array(
				'match_groups' => !empty($criteria['match_groups']) ? $criteria['match_groups'] : array(),
			)
		);
		$details = $this->processQueryResult($request, $orders[$order] ?? $orders['name']);
		$db->free_result($request);

		return $details;
	}

	public function getSimpleGroupList()
	{
		return $this->getGroupsByCriteria(array(
			'exclude_moderator' => true,
			'exclude_postcount' => true,
		));
	}

	private function processQueryResult($request, $sort)
	{
		global $context, $settings, $txt;

		$db = database();

		// Include Default Registered Members
		$details = array(
			0 => array(
				'id_group' => 0,
				'group_name' => $txt['levgal_registered_members'],
				'online_color' => '',
				'color_name' => $txt['levgal_registered_members'],
				'stars' => '',
			)
		);

		while ($row = $db->fetch_assoc($request))
		{
			$id_group = array_shift($row);
			$stars = !empty($row['stars']) ? explode('#', $row['stars']) : array(0, '');
			$row['color_name'] = !empty($row['online_color']) ? '<span style="color: ' . $row['online_color'] . '">' . $row['group_name'] . '</span>' : $row['group_name'];
			$row['stars'] = str_repeat('<img src="' . str_replace('$language', $context['user']['language'], isset($stars[1]) ? $settings['images_url'] . '/group_icons/' . $stars[1] : '') . '" alt="*" />', empty($stars[0]) || empty($stars[1]) ? 0 : $stars[0]);
			$details[$id_group] = $row;
		}

		$keys = array_keys($details);
		$array_col = array_column($details, $sort);
		array_multisort($array_col, SORT_ASC, SORT_STRING, $details, $keys);

		return array_combine($keys, $details);
	}

	public function allowedTo($permission, $board_id = null)
	{
		require_once(SUBSDIR . '/Members.subs.php');
		$groups = groupsAllowedTo($permission, $board_id);
		sort($groups['allowed']);

		return $groups['allowed'];
	}

	public function matchUsersInGroups($users, $groups)
	{
		$db = database();

		$matched_users = array();

		if (empty($users))
		{
			return array();
		}

		$request = $db->query('', '
			SELECT 
				id_member, id_group, additional_groups
			FROM {db_prefix}members
			WHERE id_member IN ({array_int:users})',
			array(
				'users' => $users,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$user_groups = explode(',', $row['additional_groups']);
			// Load.php reminds us these can go bad, so quick sanitisation.
			foreach ($user_groups as $k => $v)
			{
				$user_groups[$k] = (int) $v;
			}
			$user_groups[] = $row['id_group'];
			if (count(array_intersect($user_groups, $groups)) > 0)
			{
				$matched_users[] = $row['id_member'];
			}
		}
		$db->free_result($request);

		return $matched_users;
	}

	public static function deleteGroup($group_ids)
	{
		global $modSettings;

		$db = database();

		$only_owner = array();
		$updated_albums = array();

		$request = $db->query('', '
			SELECT 
				id_album, owner_cache
			FROM {db_prefix}lgal_albums
			ORDER BY null');
		while ($row = $db->fetch_assoc($request))
		{
			$row['owner_cache'] = Util::unserialize($row['owner_cache']);
			if (!isset($row['owner_cache']['group']))
			{
				continue;
			}

			if (count(array_intersect($group_ids, $row['owner_cache']['group'])) > 0)
			{
				$updated_albums[$row['id_album']] = $row['owner_cache'];
				$updated_albums[$row['id_album']]['group'] = array_diff($updated_albums[$row['id_album']]['group'], $group_ids);
				if (count($updated_albums[$row['id_album']]['group']) == 0)
				{
					$only_owner[] = $row['id_album'];
					unset ($updated_albums[$row['id_album']]['group']);
					$updated_albums[$row['id_album']]['member'] = array(0);
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
				DELETE FROM {db_prefix}log_owner_group
				WHERE id_group IN ({array_int:id_group})',
				array(
					'id_group' => $group_ids,
				)
			);

			// Step 3. For those where we just deleted the only owner, put something in the hierarchy for them - and make room
			// if we have to.
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

		// For those groups we've removed, we need to fix the quotas they used to have.
		foreach (array('image', 'audio', 'video', 'document', 'archive', 'generic') as $type)
		{
			$quotas = isset($modSettings['lgal_' . $type . '_quotas']) ? Util::unserialize($modSettings['lgal_' . $type . '_quotas']) : array();
			if (empty($quotas))
			{
				continue;
			}
			$new_quotas = array();
			foreach ($quotas as $quota)
			{
				// $quota[0] is the group list for this quota
				if (!empty($quota[0]) && count(array_intersect($group_ids, $quota[0])) > 0)
				{
					// Exclude the group(s) we're deleting, and if there's something left, drop it into the new array.
					$quota[0] = array_diff($quota[0], $group_ids);
					if (!empty($quota[0]))
					{
						$new_quotas[] = $quota;
					}
				}
				else
				{
					// These aren't the quotas we're looking for. Move along. You can go about your business.
					$new_quotas[] = $quota;
				}
			}

			updateSettings(array('lgal_' . $type . '_quotas' => serialize($new_quotas)));
		}
	}
}
