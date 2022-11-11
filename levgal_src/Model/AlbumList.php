<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.0 / elkarte
 */

/**
 * This file deals with getting information about albums in bulk.
 */
class LevGal_Model_AlbumList
{
	public function getAlbumCount()
	{
		$db = database();

		// This gets all albums, regardless of whether the user can see them.
		$request = $db->query('', '
			SELECT
			    COUNT(id_album)
			FROM {db_prefix}lgal_albums');
		list ($count) = $db->fetch_row($request);
		$db->free_result($request);

		return $count;
	}

	public function getUserAlbums()
	{
		global $user_info;
		static $album_list = null;

		$db = database();

		// We might already have done this.
		if ($album_list !== null)
		{
			return $album_list;
		}

		// Guests can't own any albums.
		if (!empty($user_info['is_guest']))
		{
			return array();
		}

		$cache_key = 'lgal_album_owned_' . $user_info['id'];
		$cache_ttl = 420;

		// This is pretty ugly but short of building everything with an instance of
		// Model_Album with a surrogate... bleh.
		if (($temp = cache_get_data($cache_key, $cache_ttl)) === null)
		{
			$temp = array();
			$request = $db->query('', '
				SELECT 
				    id_album, owner_cache
				FROM {db_prefix}lgal_albums
				ORDER BY null');
			while ($row = $db->fetch_assoc($request))
			{
				$row['owner_cache'] = Util::unserialize($row['owner_cache']);
				if (isset($row['owner_cache']['member']) && in_array($user_info['id'], $row['owner_cache']['member'], true))
				{
					$temp[] = $row['id_album'];
				}
				elseif (isset($row['owner_cache']['group']) && count(array_intersect($row['owner_cache']['group'], $user_info['groups'])) > 0)
				{
					$temp[] = $row['id_album'];
				}
			}
			$db->free_result($request);
			sort($temp);
			cache_put_data($cache_key, $temp, $cache_ttl);
			$album_list = $temp;
		}

		return $temp;
	}

	public function getVisibleAlbums()
	{
		global $user_info;
		static $album_list = null;

		$db = database();

		// We might already have done this.
		if ($album_list !== null)
		{
			return $album_list;
		}

		$groups = $user_info['groups'];
		sort($groups);

		$cache_key = 'lgal_album_access_m' . $user_info['id'];
		$cache_ttl = 420;

		// Managers are easy, they see all.
		if (allowedTo('lgal_manage'))
		{
			if (($temp = cache_get_data($cache_key, $cache_ttl)) === null)
			{
				$temp = array();
				$request = $db->query('', '
					SELECT 
					    id_album
					FROM {db_prefix}lgal_albums
					ORDER BY id_album'
				);
				while ($row = $db->fetch_assoc($request))
				{
					$temp[] = (int) $row['id_album'];
				}
				$db->free_result($request);
				cache_put_data($cache_key, $temp, $cache_ttl);
				$album_list = $temp;
			}

			return $temp;
		}

		// This is pretty ugly but short of building everything with an instance of Model_Album with a surrogate... bleh.
		if (($temp = cache_get_data($cache_key, $cache_ttl)) === null)
		{
			$temp = array();
			$request = $db->query('', '
				SELECT 
				    id_album, approved, owner_cache, perms
				FROM {db_prefix}lgal_albums
				ORDER BY null'
			);
			while ($row = $db->fetch_assoc($request))
			{
				$row['owner_cache'] = Util::unserialize($row['owner_cache']);
				$row['perms'] = Util::unserialize($row['perms']);

				// Something wrong with it?
				if (!isset($row['perms']['type']))
				{
					continue;
				}

				// Not approved?
				if (empty($row['approved']) && !allowedTo('lgal_approve_album'))
				{
					if ((!empty($row['owner_cache']['member']) && !in_array($user_info['id'], $row['owner_cache']['member'], true)) || (!empty($row['owner_cache']['group']) && count(array_intersect($row['owner_cache']['group'], $user_info['groups'])) == 0))
					{
						continue;
					}
				}

				switch ($row['perms']['type'])
				{
					// Everyone can see guests albums.
					case 'guests':
						$temp[] = $row['id_album'];
						break;
					// Only if the relevant user is not a guest can they see this album.
					case 'members':
						if (empty($user_info['is_guest']))
						{
							$temp[] = $row['id_album'];
						}
						break;
					// So there's groups. Managers and owners need to get a free pass though.
					// And we can safely fall through to the justme crowd since the rules are the same in that case.
					case 'custom':
						if (count(array_intersect($groups, $row['perms']['groups'])) > 0)
						{
							$temp[] = $row['id_album'];
						}
						break;
					// Just the owners (and managers but we already checked them) falling through from custom.
					case 'justme':
						if (empty($user_info['is_guest']))
						{
							if (isset($row['owner_cache']['member']) && in_array($user_info['id'], $row['owner_cache']['member'], true))
							{
								$temp[] = $row['id_album'];
							}
							elseif (isset($row['owner_cache']['group']) && count(array_intersect($row['owner_cache']['group'], $groups)) > 0)
							{
								$temp[] = $row['id_album'];
							}
						}
						break;
				}
			}
			$db->free_result($request);
			sort($temp);
			cache_put_data($cache_key, $temp, $cache_ttl);
			$album_list = $temp;
		}

		return $temp;
	}

	public function getAlbumHierarchy($type = 'member', $id = 0)
	{
		global $modSettings;

		$db = database();

		if (!in_array($type, array('site', 'member', 'group')))
		{
			return array();
		}

		// Site albums are really just member albums with (user)$id = 0.
		if ($type === 'site')
		{
			$type = 'member';
			$id = 0;
		}

		// First, let's figure out if we can see everything. true means we don't have to bother checking.
		$album_list = true;
		if (!allowedTo('lgal_manage'))
		{
			$album_list = $this->getVisibleAlbums();
		}

		// Nothing to do?
		if (empty($album_list))
		{
			return array();
		}

		// Second, get the actual hierarchy.
		$hierarchy = array();
		$request = $db->query('', '
			SELECT 
				id_album, id_{raw:type}, album_pos, album_level
			FROM {db_prefix}lgal_owner_{raw:type}
			WHERE ' . ($album_list === true ? '1=1' : 'id_album IN ({array_int:album_list})') . '
				AND id_{raw:type} = {int:selector}
			ORDER BY album_pos',
			array(
				'type' => $type,
				'selector' => $id,
				'album_list' => $album_list,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$album_id = array_shift($row);
			$hierarchy[$album_id] = $row;
		}
		$db->free_result($request);
		if (empty($hierarchy))
		{
			return array();
		}

		// And this to simplify our life later.
		$albumModel = new LevGal_Model_Album();
		$is_approver = allowedTo(array('lgal_manage', 'lgal_approve_item'));

		$request = $db->query('', '
			SELECT 
				id_album, album_name, album_slug, thumbnail, locked, approved, num_items, num_unapproved_items, num_comments,
				num_unapproved_comments, featured, owner_cache, perms
			FROM {db_prefix}lgal_albums
			WHERE id_album IN ({array_int:album_list})
			',
			array(
				'album_list' => array_keys($hierarchy),
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$albumModel->buildFromSurrogate($row);
			$hierarchy[$row['id_album']] += $albumModel->getAlbumById($row['id_album']);
			$hierarchy[$row['id_album']]['see_unapproved'] = !empty($row['num_unapproved_items']) && ($is_approver || (!empty($modSettings['lgal_selfmod_approve_item']) && $albumModel->isOwnedByUser()));
		}
		$db->free_result($request);

		return $this->fixHierarchy($hierarchy);
	}

	public function getAlbumFamilyInHierarchy($owner_type, $owner, $album)
	{
		// So first we get the hierarchy of the owner in full.
		$album = (int) $album;
		$hierarchy = $this->getAlbumHierarchy($owner_type, $owner);
		if (empty($hierarchy[$album]))
		{
			return array();
		}
		$album_level = $hierarchy[$album]['album_level'];
		$level_shift = max($album_level - 1, 0);

		$map = array_keys($hierarchy);
		$position = array_search($album, $map, true);

		// So, first we want to figure out what albums after our position are ones we are keeping.
		$pruning = array();
		$pruning_rest = false;
		for ($i = $position + 1, $n = count($map); $i < $n; $i++)
		{
			// We hit a sibling album, or something else in the hierarchy out of this subtree. We'll need to prune everything after.
			if ($pruning_rest || $hierarchy[$map[$i]]['album_level'] <= $album_level)
			{
				$pruning[] = $map[$i];
				$pruning_rest = true;
			}
			// If we're more than a child level down, prune that too.
			elseif ($hierarchy[$map[$i]]['album_level'] >= $album_level + 2)
			{
				$pruning[] = $map[$i];
			}
		}

		// Now to prune the tree before our array.
		$pruning_rest = false;
		$found_parent = false;
		for ($i = $position - 1; $i >= 0; $i--)
		{
			// If this is at the same level or deeper than where we started, we don't want it.
			if ($hierarchy[$map[$i]]['album_level'] >= $album_level)
			{
				$pruning[] = $map[$i];
			}
			elseif ($pruning_rest || $hierarchy[$map[$i]]['album_level'] <= $album_level - 2)
			{
				$pruning[] = $map[$i];
				$pruning_rest = true;
			}
			elseif ($hierarchy[$map[$i]]['album_level'] === $album_level - 1)
			{
				if ($found_parent)
				{
					$pruning[] = $map[$i];
					$pruning_rest = true;
				}
				else
				{
					$found_parent = true;
				}
			}
		}

		// Prune what needs pruning
		foreach ($pruning as $prune)
		{
			unset ($hierarchy[$prune]);
		}

		// Fix album levels
		foreach (array_keys($hierarchy) as $id_album)
		{
			$hierarchy[$id_album]['album_level'] -= $level_shift;
		}

		return $hierarchy;
	}

	public function getAlbumHierarchyByOwners()
	{
		global $context, $settings, $txt;

		$db = database();

		$hierarchies = array(
			'site' => 0,
			'members' => array(),
			'groups' => array(),
		);

		// What albums are we looking at?
		$album_list = true;
		if (!allowedTo('lgal_manage'))
		{
			$album_list = $this->getVisibleAlbums();
		}

		if (empty($album_list))
		{
			return $hierarchies;
		}

		// First, traverse the owned-by-members table which includes owned-by-site albums
		$request = $db->query('', '
			SELECT 
			    lom.id_member, mem.real_name, COUNT(lom.id_album) AS albums
			FROM {db_prefix}lgal_owner_member AS lom
				LEFT JOIN {db_prefix}members AS mem ON (lom.id_member = mem.id_member)' . ($album_list === true ? '' : '
			WHERE lom.id_album IN ({array_int:album_list})') . '
			GROUP BY lom.id_member, mem.real_name
			ORDER BY mem.real_name',
			array(
				'album_list' => $album_list,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			if (empty($row['id_member']))
			{
				$hierarchies['site'] = $row['albums'];
			}
			else
			{
				$hierarchies['members'][$row['id_member']] = array(
					'name' => $row['real_name'],
					'count' => $row['albums'],
				);
			}
		}
		$db->free_result($request);

		// Second, the same for group-owned albums.
		$request = $db->query('', '
			SELECT 
				log.id_group, mg.group_name, mg.online_color, mg.{raw:stars_column} AS stars,
				COUNT(log.id_album) AS albums
			FROM {db_prefix}lgal_owner_group AS log
				LEFT JOIN {db_prefix}membergroups AS mg ON (log.id_group = mg.id_group)' . ($album_list === true ? '' : '
			WHERE log.id_album IN ({array_int:album_list})') . '
			GROUP BY log.id_group, mg.group_name, mg.online_color, mg.{raw:stars_column}
			ORDER BY mg.group_name',
			array(
				'album_list' => $album_list,
				'stars_column' => 'icons',
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$stars = !empty($row['stars']) ? explode('#', $row['stars']) : array(0, '');
			$stars = str_repeat('<img src="' . str_replace('$language', $context['user']['language'], isset($stars[1]) ? $settings['images_url'] . '/group_icons/' . $stars[1] : '') . '" alt="*" />', empty($stars[0]) || empty($stars[1]) ? 0 : $stars[0]);

			// Account for Default Registered Members
			$hierarchies['groups'][$row['id_group']] = array(
				'name' => $row['group_name'] ?? $txt['levgal_registered_members'],
				'color_name' => !empty($row['online_color']) ? '<span style="color: ' . $row['online_color'] . '">' . $row['group_name'] . '</span>' : ($row['group_name'] ?? $txt['levgal_registered_members']),
				'stars' => $stars,
				'count' => $row['albums'],
			);
		}
		$db->free_result($request);

		return $hierarchies;
	}

	public function getAllHierarchies()
	{
		global $scripturl;

		$db = database();

		$hierarchies = array(
			'site' => array(),
			'member' => array(),
			'member_unsorted' => array(),
			'group' => array(),
			'group_unsorted' => array(),
		);

		$album_list = true;
		if (!allowedTo('lgal_manage'))
		{
			$album_list = $this->getVisibleAlbums();
		}

		// Nothing to do?
		if (empty($album_list))
		{
			unset ($hierarchies['member_unsorted'], $hierarchies['group_unsorted']);

			return $hierarchies;
		}

		// We can get site and member albums together, and save a query in the process.
		$request = $db->query('', '
			SELECT 
			    lom.id_album, lom.id_member, lom.album_pos, lom.album_level, la.album_name, la.album_slug
			FROM {db_prefix}lgal_owner_member AS lom
				INNER JOIN {db_prefix}lgal_albums AS la ON (lom.id_album = la.id_album)' . ($album_list !== true ? '
			WHERE lom.id_album IN ({array_int:album_list})' : '') . '
			ORDER BY lom.id_member, lom.album_pos',
			array(
				'album_list' => $album_list,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$row['album_url'] = $scripturl . '?media/album/' . (!empty($row['album_slug']) ? $row['album_slug'] . '.' . $row['id_album'] : $row['id_album']) . '/';
			if (empty($row['id_member']))
			{
				$hierarchies['site'][$row['id_album']] = $row;
				continue;
			}

			$hierarchies['member_unsorted'][$row['id_member']][$row['id_album']] = $row;
		}
		$db->free_result($request);

		// Group albums are not difficult either.
		$request = $db->query('', '
			SELECT 
			    log.id_album, log.id_group, log.album_pos, log.album_level, la.album_name, la.album_slug
			FROM {db_prefix}lgal_owner_group AS log
				INNER JOIN {db_prefix}lgal_albums AS la ON (log.id_album = la.id_album)' . ($album_list !== true ? '
			WHERE log.id_album IN ({array_int:album_list})' : '') . '
			ORDER BY log.id_group, log.album_pos',
			array(
				'album_list' => $album_list,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$row['album_url'] = $scripturl . '?media/album/' . (!empty($row['album_slug']) ? $row['album_slug'] . '.' . $row['id_album'] : $row['id_album']) . '/';
			$hierarchies['group_unsorted'][$row['id_group']][$row['id_album']] = $row;
		}
		$db->free_result($request);

		// Now the real fun begins. We need to firstly fix hierarchies and then process to resort the list because it's quicker
		// to do that here rather than splice in the member/group names into the above.
		if (!empty($hierarchies['site']))
		{
			$hierarchies['site'] = $this->fixHierarchy($hierarchies['site']);
		}

		if (!empty($hierarchies['member_unsorted']))
		{
			$request = $db->query('', '
				SELECT
				 	id_member, real_name
				FROM {db_prefix}members
				WHERE id_member IN ({array_int:members})
				ORDER BY real_name',
				array(
					'members' => array_keys($hierarchies['member_unsorted']),
				)
			);
			while ($row = $db->fetch_assoc($request))
			{
				$hierarchies['member'][$row['id_member']] = array(
					'member_name' => $row['real_name'],
					'albums' => $this->fixHierarchy($hierarchies['member_unsorted'][$row['id_member']]),
				);
			}
			$db->free_result($request);
		}
		unset ($hierarchies['member_unsorted']);

		if (!empty($hierarchies['group_unsorted']))
		{
			$request = $db->query('', '
				SELECT 
					id_group, group_name
				FROM {db_prefix}membergroups
				WHERE id_group IN ({array_int:groups})
				ORDER BY group_name',
				array(
					'groups' => array_keys($hierarchies['group_unsorted']),
				)
			);
			while ($row = $db->fetch_assoc($request))
			{
				$hierarchies['group'][$row['id_group']] = array(
					'group_name' => $row['group_name'],
					'albums' => $this->fixHierarchy($hierarchies['group_unsorted'][$row['id_group']]),
				);
			}
		}
		unset ($hierarchies['group_unsorted']);

		return $hierarchies;
	}

	protected function fixHierarchy($hierarchy)
	{
		// Fix incidents of album_level for 1 >> 2 >> 3 where 2 is hidden, or other such excitement.
		// We're only fixing this softly because the hierarchy may not be complete (so you can't force-fix it here)
		$current_level = -1;
		$previous_level = -1;
		foreach ($hierarchy as $id_album => $album)
		{
			// Whatever we might have had before, force the first one to have a root level of 0 since this is how it needs to be.
			if ($current_level == -1)
			{
				$hierarchy[$id_album]['album_level'] = 0;
				$current_level = 0;
				$previous_level = 0;
				continue;
			}

			// This is a bit strange. Consider 1 > 2 > 3 > 4 and 1 > 2 > 3 > 5 where 1,2,3 are not visible.
			// We need to flatten 4 and 5 to level 1 in that situation, and we can't use 4's level to guess 5's.
			// So if the previous album is at the same level as this album, we can flatten it to the new level.
			if ($album['album_level'] > $current_level && ($album['album_level'] - $current_level > 1 || $album['album_level'] == $previous_level))
			{
				if ($album['album_level'] == $previous_level)
				{
					// This album is at the same level the previous one was at before we moved it
					$hierarchy[$id_album]['album_level'] = $current_level;
				}
				else
				{
					// This album is at a deeper level than the previous album so it needs to be moved to the correct hierarchical depth
					$hierarchy[$id_album]['album_level'] = $current_level + 1;
				}
			}
			$current_level = $hierarchy[$id_album]['album_level'];
			$previous_level = $album['album_level'];
		}

		return $hierarchy;
	}

	public function getAlbumsById($albums, $bypass_check = null)
	{
		$db = database();

		$album_list = true;
		if (!allowedTo('lgal_manage') && !$bypass_check)
		{
			$album_list = $this->getVisibleAlbums();
		}

		if (empty($album_list))
		{
			return array();
		}

		// Now, we're being selective. We can be smart about this.
		if ($album_list === true)
		{
			// We can see everything, let's just grab the ones we asked for.
			$album_list = (array) $albums;
		}
		else
		{
			$album_list = array_intersect($album_list, (array) $albums);
		}

		$request = $db->query('', '
			SELECT 
				id_album, album_name, album_slug, thumbnail, locked, approved, num_items, num_unapproved_items, num_comments,
				num_unapproved_comments, featured, owner_cache, perms
			FROM {db_prefix}lgal_albums
			WHERE id_album IN ({array_int:albums})
			ORDER BY id_album',
			array(
				'albums' => $album_list,
			)
		);
		$albumModel = new LevGal_Model_Album();
		$selected_albums = array();
		while ($row = $db->fetch_assoc($request))
		{
			// Looks weird but essentially means we get everything processed for us, like album and thumbnail URLs.
			$albumModel->buildFromSurrogate($row);
			$selected_albums[$row['id_album']] = $albumModel->getAlbumById($row['id_album']);
		}
		$db->free_result($request);

		return $selected_albums;
	}

	public function getFeaturedAlbums()
	{
		$db = database();

		$album_list = true;
		if (!allowedTo('lgal_manage'))
		{
			$album_list = $this->getVisibleAlbums();
		}

		if (empty($album_list))
		{
			return array();
		}

		$request = $db->query('', '
			SELECT 
			    id_album, album_name, album_slug, thumbnail, locked, approved, num_items, num_unapproved_items, num_comments,
				num_unapproved_comments, featured, owner_cache, perms
			FROM {db_prefix}lgal_albums
			WHERE ' . ($album_list !== true ? 'id_album IN ({array_int:albums})
				AND ' : '') . 'featured = {int:featured}
			ORDER BY album_name',
			array(
				'albums' => $album_list,
				'featured' => 1,
			)
		);
		$albumModel = new LevGal_Model_Album();
		$featured = array();
		while ($row = $db->fetch_assoc($request))
		{
			// Looks weird but essentially means we get everything processed for us, like album and thumbnail URLs.
			$albumModel->buildFromSurrogate($row);
			$featured[$row['id_album']] = $albumModel->getAlbumById($row['id_album']);

			$featured[$row['id_album']]['album_count'] = $albumModel->getAlbumFamily()['album_count'];
		}
		$db->free_result($request);

		return $featured;
	}
}
