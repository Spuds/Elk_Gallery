<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.0 / elkarte
 */

/**
 * This file deals with fixing things in the gallery's database.
 */
class LevGal_Model_Maintenance
{
	public function recalculateTotalItems()
	{
		$db = database();

		$request = $db->query('', '
			SELECT 
			    COUNT(id_item)
			FROM {db_prefix}lgal_items
			WHERE approved = 1');
		list ($count) = $db->fetch_row($request);
		$db->free_result($request);

		updateSettings(array('lgal_total_items' => $count));
	}

	public function recalculateTotalComments()
	{
		$db = database();

		$request = $db->query('', '
			SELECT 
			    COUNT(id_comment)
			FROM {db_prefix}lgal_comments
			WHERE approved = 1');
		list ($count) = $db->fetch_row($request);
		$db->free_result($request);

		updateSettings(array('lgal_total_comments' => $count));
	}

	public function fixItemStats()
	{
		$db = database();

		// First, get the items and their current stats. This seems to be faster than globbing queries together.
		$items = array();
		$request = $db->query('', '
			SELECT 
			    id_item, num_comments, num_unapproved_comments
			FROM {db_prefix}lgal_items');
		while ($row = $db->fetch_assoc($request))
		{
			$items[$row['id_item']] = array(
				'stored_comments' => $row['num_comments'],
				'stored_unapproved' => $row['num_unapproved_comments'],
				'actual_comments' => 0,
				'actual_unapproved' => 0,
			);
		}
		$db->free_result($request);

		// Now get the actual amounts.
		$request = $db->query('', '
			SELECT 
			    id_item, COUNT(approved) AS comment_count, approved
			FROM {db_prefix}lgal_comments
			GROUP BY id_item, approved');
		while ($row = $db->fetch_assoc($request))
		{
			// We don't have it? This is not something we should deal with here.
			if (!isset($items[$row['id_item']]))
			{
				continue;
			}

			$items[$row['id_item']][$row['approved'] == 1 ? 'actual_comments' : 'actual_unapproved'] = $row['comment_count'];
		}
		$db->free_result($request);

		// Any to fix?
		foreach ($items as $id_item => $stats)
		{
			if ($stats['stored_comments'] != $stats['actual_comments'] || $stats['stored_unapproved'] != $stats['actual_unapproved'])
			{
				$db->query('', '
					UPDATE {db_prefix}lgal_items
					SET num_comments = {int:actual_comments},
						num_unapproved_comments = {int:actual_unapproved}
					WHERE id_item = {int:id_item}',
					array(
						'id_item' => $id_item,
						'actual_comments' => $stats['actual_comments'],
						'actual_unapproved' => $stats['actual_unapproved'],
					)
				);
			}
		}
	}

	public function fixAlbumStats()
	{
		$db = database();

		// First, get the existing figures.
		$albums = array();
		$request = $db->query('', '
			SELECT 
			    id_album, num_items, num_unapproved_items, num_comments, num_unapproved_comments
			FROM {db_prefix}lgal_albums');
		while ($row = $db->fetch_assoc($request))
		{
			$albums[$row['id_album']] = array(
				'id_album' => $row['id_album'],
				'stored_items' => $row['num_items'],
				'stored_unapproved_items' => $row['num_unapproved_items'],
				'stored_comments' => $row['num_comments'],
				'stored_unapproved_comments' => $row['num_unapproved_comments'],
				'actual_items' => 0,
				'actual_unapproved_items' => 0,
				'actual_comments' => 0,
				'actual_unapproved_comments' => 0,
			);
		}
		$db->free_result($request);

		// Now, get the actual stats.
		$request = $db->query('', '
			SELECT 
			    id_album, COUNT(id_item) AS item_count, SUM(num_comments) AS total_comments, SUM(num_unapproved_comments) AS total_unapproved, approved
			FROM {db_prefix}lgal_items
			GROUP BY id_album, approved');
		while ($row = $db->fetch_assoc($request))
		{
			// We don't have it? This is not something we should deal with here.
			if (!isset($albums[$row['id_album']]))
			{
				continue;
			}

			$albums[$row['id_album']][$row['approved'] == 1 ? 'actual_items' : 'actual_unapproved_items'] = $row['item_count'];
			$albums[$row['id_album']]['actual_comments'] += $row['total_comments'];
			$albums[$row['id_album']]['actual_unapproved_comments'] += $row['total_unapproved'];
		}
		$db->free_result($request);

		// Any to fix?
		foreach ($albums as $stats)
		{
			if ($stats['stored_items'] != $stats['actual_items'] || $stats['stored_unapproved_items'] != $stats['actual_unapproved_items'] || $stats['stored_comments'] != $stats['actual_comments'] || $stats['stored_unapproved_comments'] != $stats['actual_unapproved_comments'])
			{
				$db->query('', '
					UPDATE {db_prefix}lgal_albums
					SET num_items = {int:actual_items},
						num_unapproved_items = {int:actual_unapproved_items},
						num_comments = {int:actual_comments},
						num_unapproved_comments = {int:actual_unapproved_comments}
					WHERE id_album = {int:id_album}',
					$stats
				);
			}
		}
	}

	public function fixOrphanAlbumHierarchy($substep)
	{
		$db = database();

		$count = 0;
		$substeps = 4;

		if ($substep == 0)
		{
			// This looks for cases of entries in the lgal_owner_* tables where albums don't exist.
			$hierarchy = $this->findBrokenReferences('lgal_owner_member', 'id_album', 'lgal_albums', 'id_album');
			$count += $this->deleteRows('lgal_owner_member', 'id_album', $hierarchy);

			$hierarchy = $this->findBrokenReferences('lgal_owner_group', 'id_album', 'lgal_albums', 'id_album');
			$count += $this->deleteRows('lgal_owner_group', 'id_album', $hierarchy);
		}
		elseif ($substep == 1)
		{
			// This looks for cases of entries in the lgal_owner_* tables where the relevant matching tables don't exist.
			$hierarchy = $this->findBrokenReferences('lgal_owner_member', 'id_member', 'members', 'id_member', true);
			$count += $this->deleteRows('lgal_owner_member', 'id_member', $hierarchy);

			$hierarchy = $this->findBrokenReferences('lgal_owner_group', 'id_group', 'membergroups', 'id_group', true);
			$count += $this->deleteRows('lgal_owner_group', 'id_group', $hierarchy);
		}
		elseif ($substep == 2)
		{
			// This looks at the owner_cache details and tries to verify the owners actually exist. We'll find lom/log later.
			$members = array();
			$groups = array();
			$details = array();

			$request = $db->query('', '
				SELECT 
				    id_album, owner_cache
				FROM {db_prefix}lgal_albums');
			while ($row = $db->fetch_assoc($request))
			{
				$owner_cache = Util::unserialize($row['owner_cache']);
				$changed = false;

				foreach (array('member', 'group') as $type)
				{
					if (isset($owner_cache[$type]) && !is_array($owner_cache[$type]))
					{
						$owner_cache[$type] = (array) $owner_cache[$type];
						$changed = true;
					}
				}

				if (isset($owner_cache['member']) && in_array(0, $owner_cache['member'], true) && count($owner_cache['member']) != 1)
				{
					// We had multiple owners but one of them was the site. Should reset that to the members left.
					$owner_cache['member'] = array_diff($owner_cache['member'], array(0));
					$changed = true;
				}

				// Meanwhile let's get everything for comparison in a minute.
				if (isset($owner_cache['member']))
				{
					foreach ($owner_cache['member'] as $member)
					{
						if (!empty($member))
						{
							$members[$member][] = $row['id_album'];
						}
					}
				}
				if (isset($owner_cache['group']))
				{
					foreach ($owner_cache['group'] as $group)
					{
						if (!empty($group))
						{
							$groups[$group][] = $row['id_album'];
						}
					}
				}

				// Just in case we don't have anything at this point... make sure we leave here with *something*: site owned.
				if (empty($owner_cache) || (empty($owner_cache['member']) && empty($owner_cache['group'])))
				{
					// There was no valid owner at all before.
					$owner_cache = array('member' => array(0));
					$changed = true;
				}

				if ($changed)
				{
					$db->query('', '
						UPDATE {db_prefix}lgal_albums
						SET owner_cache = {string:owner_cache}
						WHERE id_album = {int:id_album}',
						array(
							'owner_cache' => serialize($owner_cache),
							'id_album' => $row['id_album'],
						)
					);
					$count++;
				}

				$details[$row['id_album']] = $owner_cache;
			}
			$db->free_result($request);

			// So we had some member ids attached to albums but that don't actually exist? OH NOES.
			$changed_albums = array();
			if (!empty($members))
			{
				$loaded_members = loadMemberData(array_keys($members), false, 'minimal');
				$loaded_members[] = 0; // Because we know 0 is a valid owner.
				$not_loaded = array_diff(array_keys($members), $loaded_members);

				foreach ($not_loaded as $member)
				{
					foreach ($members[$member] as $album)
					{
						$details[$album]['member'] = array_diff($details[$album]['member'], array($member));
						$changed_albums[$album] = true;
					}
				}
			}

			// Do groups too.
			if (!empty($groups))
			{
				$available_groups = array();
				// Can't have post count groups.
				$request = $db->query('', '
					SELECT 
					    id_group
					FROM {db_prefix}membergroups AS mg
					WHERE mg.min_posts < 0');
				while ($row = $db->fetch_assoc($request))
				{
					$available_groups[] = (int) $row['id_group'];
				}
				$db->free_result($request);

				$not_available = array_diff(array_keys($groups), $available_groups);

				foreach ($not_available as $group)
				{
					foreach ($groups[$group] as $album)
					{
						$details[$album]['group'] = array_diff($details[$album]['group'], array($group));
						$changed_albums[$album] = true;
					}
				}
			}

			// Did anything change?
			if (!empty($changed_albums))
			{
				$count += count($changed_albums);
				foreach (array_keys($changed_albums) as $album)
				{
					// Get the details for this album... or if we ended up stripping everyone from it... reset to site ownership.
					$owner_cache = !empty($details[$album]['member']) || !empty($details[$album]['group']) ? $details[$album] : array('member' => array(0));

					$db->query('', '
						UPDATE {db_prefix}lgal_albums
						SET owner_cache = {string:owner_cache}
						WHERE id_album = {int:id_album}',
						array(
							'owner_cache' => serialize($owner_cache),
							'id_album' => $album,
						)
					);
				}
			}
		}
		elseif ($substep == 3)
		{
			// This one matches what's in the albums table against what's in the lom/log tables.
			$albums = array();
			$request = $db->query('', '
				SELECT 
				    id_album, id_member
				FROM {db_prefix}lgal_owner_member');
			while ($row = $db->fetch_assoc($request))
			{
				$albums[$row['id_album']]['member'][] = $row['id_member'];
			}
			$db->free_result($request);

			$request = $db->query('', '
				SELECT 
				    id_album, id_group
				FROM {db_prefix}lgal_owner_group');
			while ($row = $db->fetch_assoc($request))
			{
				$albums[$row['id_album']]['group'][] = $row['id_group'];
			}
			$db->free_result($request);

			$insert_rows = array();
			$request = $db->query('', '
				SELECT 
				    id_album, owner_cache
				FROM {db_prefix}lgal_albums');
			while ($row = $db->fetch_assoc($request))
			{
				$owner_cache = Util::unserialize($row['owner_cache']);

				// So, let's compare what the albums table says against what the lom/log tables say.
				if (isset($owner_cache['member']))
				{
					// If this wasn't an array, it really should be.
					if (!is_array($owner_cache['member']))
					{
						$owner_cache['member'] = (array) $owner_cache['member'];
						$db->query('', '
							UPDATE {db_prefix}lgal_albums
							SET owner_cache = {string:owner_cache}
							WHERE id_album = {int:id_album}',
							array(
								'owner_cache' => serialize($owner_cache),
								'id_album' => $row['id_album'],
							)
						);
					}
					// Member owned albums shouldn't have group entries.
					if (!empty($albums[$row['id_album']]['group']))
					{
						$this->deleteRows('lgal_owner_group', 'id_album', $row['id_album']);
					}

					$owners = !empty($albums[$row['id_album']]['member']) ? $albums[$row['id_album']]['member'] : array();

					// Prune any records that shouldn't exist.
					$shouldnt_exist = array_diff($owners, $owner_cache['member']);
					if (!empty($shouldnt_exist))
					{
						$db->query('', '
							DELETE FROM {db_prefix}lgal_owner_member
							WHERE id_album = {int:album}
								AND id_member IN ({array_int:members})',
							array(
								'album' => $row['id_album'],
								'members' => $shouldnt_exist,
							)
						);
					}

					// Make note of records that should exist.
					$should_exist = array_diff($owner_cache['member'], $owners);
					foreach ($should_exist as $member)
						{
							$insert_rows['member'][$member][] = array($row['id_album'], $member);
						}
				}

				if (isset($owner_cache['group']))
				{
					// If this wasn't an array, it really should be.
					if (!is_array($owner_cache['group']))
					{
						$owner_cache['group'] = (array) $owner_cache['group'];
						$db->query('', '
							UPDATE {db_prefix}lgal_albums
							SET owner_cache = {string:owner_cache}
							WHERE id_album = {int:id_album}',
							array(
								'owner_cache' => serialize($owner_cache),
								'id_album' => $row['id_album'],
							)
						);
					}
					// Group owned albums shouldn't have member entries.
					if (!empty($albums[$row['id_album']]['member']))
					{
						$this->deleteRows('lgal_owner_member', 'id_album', $row['id_album']);
					}

					$owners = !empty($albums[$row['id_album']]['group']) ? $albums[$row['id_album']]['group'] : array();

					// Prune any records that shouldn't exist.
					$shouldnt_exist = array_diff($owners, $owner_cache['group']);
					if (!empty($shouldnt_exist))
					{
						$db->query('', '
							DELETE FROM {db_prefix}lgal_owner_group
							WHERE id_album = {int:album}
								AND id_group IN ({array_int:groups})',
							array(
								'album' => $row['id_album'],
								'groups' => $shouldnt_exist,
							)
						);
					}

					// Make note of records that should exist.
					$should_exist = array_diff((array) $owner_cache['group'], $owners);
					foreach ($should_exist as $group)
						{
							$insert_rows['group'][$group][] = array($row['id_album'], $group);
						}
				}
			}
			$db->free_result($request);

			// Lastly, we need to fix up some rows.
			if (!empty($insert_rows['member']))
			{
				// Make some room first though.
				$rows = array();
				$pos = 1;
				foreach ($insert_rows['member'] as $member => $member_rows)
				{
					$db->query('', '
						UPDATE {db_prefix}lgal_owner_member
						SET album_pos = album_pos + {int:rows}
						WHERE id_member = {int:id_member}',
						array(
							'rows' => count($member_rows),
							'id_member' => $member,
						)
					);
					foreach ($member_rows as $member_row)
					{
						list ($id_album, $id_member) = $member_row;
						$rows[] = array($id_album, $id_member, $pos++, 0);
					}
				}

				$db->insert('insert',
					'{db_prefix}lgal_owner_member',
					array('id_album' => 'int', 'id_member' => 'int', 'album_pos' => 'int', 'album_level' => 'int'),
					$rows,
					array('id_album', 'id_member')
				);
			}

			if (!empty($insert_rows['group']))
			{
				// Make some room.
				$rows = array();
				$pos = 1;
				foreach ($insert_rows['group'] as $group => $group_rows)
				{
					$db->query('', '
						UPDATE {db_prefix}lgal_owner_group
						SET album_pos = album_pos + {int:rows}
						WHERE id_group = {int:id_group}',
						array(
							'rows' => count($group_rows),
							'id_group' => $group,
						)
					);
					foreach ($group_rows as $group_row)
					{
						list ($id_album, $id_group) = $group_row;
						$rows[] = array($id_album, $id_group, $pos++, 0);
					}
				}

				$db->insert('insert',
					'{db_prefix}lgal_owner_group',
					array('id_album' => 'int', 'id_group' => 'int', 'album_pos' => 'int', 'album_level' => 'int'),
					$rows,
					array('id_album', 'id_group')
				);
			}
		}

		return array($substep + 1 >= $substeps, $substeps, $count);
	}

	public function fixOrphanItems()
	{
		global $user_info, $txt;

		$db = database();

		$items = array();
		$request = $db->query('', '
			SELECT 
			    li.id_item
			FROM {db_prefix}lgal_items AS li
				LEFT JOIN {db_prefix}lgal_albums AS la ON (li.id_album = la.id_album)
			WHERE la.id_album IS NULL'
		);
		while ($row = $db->fetch_assoc($request))
		{
			$items[] = $row['id_item'];
		}
		$db->free_result($request);

		if (empty($items))
		{
			return array(true, 1, 0);
		}

		// Oh dear. This makes life *very* complicated. We have items outside of an album, but we
		// don't know anything about the state of play with them.
		// Safest thing to do is create a new album.
		$album = new LevGal_Model_Album();
		$album_id = $album->createAlbum($txt['levgal_recovered_album'], '', '', true);
		$album->setAlbumOwnership('member', $user_info['id']);
		$album->setAlbumPrivacy('justme');

		$itemList = new LevGal_Model_ItemList();

		return array(true, 1, $itemList->moveItemsToAlbum($items, $album_id));
	}

	public function fixOrphanComments()
	{
		$db = database();

		$comments = array();
		$request = $db->query('', '
			SELECT 
			    lc.id_comment
			FROM {db_prefix}lgal_comments AS lc
				LEFT JOIN {db_prefix}lgal_items AS li ON (lc.id_item = li.id_item)
			WHERE li.id_item IS NULL'
		);
		while ($row = $db->fetch_assoc($request))
		{
			$comments[] = $row['id_comment'];
		}
		$db->free_result($request);

		return array(true, 1, $this->deleteRows('lgal_comments', 'id_comment', $comments));
	}

	public function fixOrphanBookmarks()
	{
		$bookmarks = $this->findBrokenReferences('lgal_bookmarks', 'id_item', 'lgal_items', 'id_item');
		$count = $this->deleteRows('lgal_bookmarks', 'id_item', $bookmarks);

		$bookmarks = $this->findBrokenReferences('lgal_bookmarks', 'id_member', 'members', 'id_member');

		return array(true, 1, $count + $this->deleteRows('lgal_bookmarks', 'id_member', $bookmarks));
	}

	public function fixOrphanLikes()
	{
		$likes = $this->findBrokenReferences('lgal_likes', 'id_item', 'lgal_items', 'id_item');
		$count = $this->deleteRows('lgal_likes', 'id_item', $likes);

		$likes = $this->findBrokenReferences('lgal_likes', 'id_member', 'members', 'id_member');

		return array(true, 1, $count + $this->deleteRows('lgal_likes', 'id_member', $likes));
	}

	public function fixOrphanTags()
	{
		$tags = $this->findBrokenReferences('lgal_tag_items', 'id_item', 'lgal_items', 'id_item');
		$count = $this->deleteRows('lgal_tag_items', 'id_item', $tags);

		$tags = $this->findBrokenReferences('lgal_tag_items', 'id_tag', 'lgal_tags', 'id_tag');

		return array(true, 1, $count + $this->deleteRows('lgal_tag_items', 'id_tag', $tags));
	}

	public function fixOrphanNotify()
	{
		$count = 0;
		$notify = $this->findBrokenReferences('lgal_notify', 'id_album', 'lgal_albums', 'id_album', true);
		$count += $this->deleteRows('lgal_notify', 'id_album', $notify);

		$notify = $this->findBrokenReferences('lgal_notify', 'id_item', 'lgal_items', 'id_item', true);
		$count += $this->deleteRows('lgal_notify', 'id_item', $notify);

		$notify = $this->findBrokenReferences('lgal_notify', 'id_member', 'members', 'id_member');
		$count += $this->deleteRows('lgal_notify', 'id_member', $notify);

		return array(true, 1, $count);
	}

	public function fixOrphanUnseen()
	{
		$unseen = $this->findBrokenReferences('lgal_log_seen', 'id_item', 'lgal_items', 'id_item');
		$count = $this->deleteRows('lgal_log_seen', 'id_item', $unseen);

		$unseen = $this->findBrokenReferences('lgal_log_seen', 'id_member', 'members', 'id_member');

		return array(true, 1, $count + $this->deleteRows('lgal_log_seen', 'id_member', $unseen));
	}

	public function fixOrphanReports()
	{
		$count = 0;
		// Reports must have a item attached - even if they are comment reports, their parent item will still be attached. Let's clean these first.
		$reports = $this->findBrokenReferences('lgal_reports', 'id_item', 'lgal_items', 'id_item');
		$count += $this->deleteRows('lgal_reports', 'id_item', $reports);

		// Reports for comments where the comment no longer exists also need to be pruned.
		$reports = $this->findBrokenReferences('lgal_reports', 'id_comment', 'lgal_comments', 'id_comment', true);
		$count += $this->deleteRows('lgal_reports', 'id_comment', $reports);

		// The neat part is that the above might trigger some of the following. This just minimises the overall effort actually required since we would have to go look *anyway*.
		// Report comments must have a parent report.
		$reports = $this->findBrokenReferences('lgal_report_comment', 'id_report', 'lgal_reports', 'id_report');
		$count += $this->deleteRows('lgal_report_comment', 'id_report', $reports);

		// Report bodies must have a parent report.
		$reports = $this->findBrokenReferences('lgal_report_body', 'id_report', 'lgal_reports', 'id_report');
		$count += $this->deleteRows('lgal_report_body', 'id_report', $reports);

		// Reports must have child bodies. Whereas we might have bodies without a parent, we might also have parents without a child.
		$reports = $this->findBrokenReferences('lgal_reports', 'id_report', 'lgal_report_body', 'id_report');
		$count += $this->deleteRows('lgal_reports', 'id_report', $reports);

		return array(true, 1, $count);
	}

	public function fixOrphanCustomFields()
	{
		$count = 0;
		// First, values that don't have a field.
		$values = $this->findBrokenReferences('lgal_custom_field_data', 'id_field', 'lgal_custom_field', 'id_field');
		$count += $this->deleteRows('lgal_custom_field_data', 'id_field', $values);

		// Second, values that don't have an item.
		$values = $this->findBrokenReferences('lgal_custom_field_data', 'id_item', 'lgal_items', 'id_item');
		$count += $this->deleteRows('lgal_custom_field_data', 'id_item', $values);

		return array(true, 1, $count);
	}

	public function checkMissingFiles($substep)
	{
		$db = database();

		$items_per_step = 50;

		$request = $db->query('', '
			SELECT 
			    COUNT(id_item)
			FROM {db_prefix}lgal_items');
		list ($count) = $db->fetch_row($request);
		$db->free_result($request);

		$substeps = ceil($count / $items_per_step);

		if ($substep >= $substeps)
		{
			$substeps =  empty($substeps) ? 1 : $substeps;
			return array(true, $substeps, false);
		}

		// So, there's something to do.
		$base_path = LevGal_Bootstrap::getGalleryDir();
		$items_without_files = array();
		$request = $db->query('', '
			SELECT 
				id_item, filehash, extension, mime_type
			FROM {db_prefix}lgal_items
			ORDER BY id_item
			LIMIT {int:start}, {int:limit}',
			array(
				'start' => $substep * $items_per_step,
				'limit' => $items_per_step,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$base_folder = $base_path . '/files/' . $row['filehash'][0] . '/' . $row['filehash'][0] . $row['filehash'][1];

			// External ones may not have any actual files.
			if (strpos($row['mime_type'], 'external') === 0)
			{
				continue;
			}

			if (!file_exists($base_path . '/files/' . $row['filehash'][0]) || !file_exists($base_folder))
			{
				$items_without_files[] = $row['id_item'];
				continue;
			}
			$files = glob($base_folder . '/' . $row['id_item'] . '_' . $row['filehash'] . '*.dat');

			// So there should at least be a core file.
			$file = $row['id_item'] . '_' . $row['filehash'] . (!empty($row['extension']) ? '_' . $row['extension'] : '') . '.dat';
			if (!in_array($base_folder . '/' . $file, $files, true))
			{
				$items_without_files[] = $row['id_item'];
			}
		}

		// Since we don't have actual files (and externally embedded doesn't count), we need to go deleting.
		if (!empty($items_without_files))
		{
			$item_list = new LevGal_Model_ItemList();
			$item_list->deleteItemsByIds($items_without_files, true);
		}

		return array($substep + 1 >= $substeps, $substeps, count($items_without_files));
	}

	public function checkExtraFiles($substep)
	{
		$db = database();

		if ($substep < 0 || $substep > 15)
		{
			return array(true, 16, false);
		}

		$files_deleted = 0;

		$excludable = array('.', '..');

		$base_folder = dechex($substep);
		$base_path = LevGal_Bootstrap::getGalleryDir();
		if (!file_exists($base_path . '/files/' . $base_folder))
		{
			// This section doesn't exist, nothing to do, except moving onwards!
			return array($substep == 15, 16, 0);
		}

		$files = scandir($base_path . '/files/' . $base_folder);
		$files = array_diff($files, $excludable);
		$items = array();
		foreach ($files as $file)
		{
			// First of all, search for things like files in files/x/ that shouldn't be there.
			if (!preg_match('~^' . $base_folder . '[0-9a-f]$~i', $file))
			{
				@unlink($base_path . '/files/' . $base_folder . '/' . $file);
				$files_deleted++;
				continue;
			}

			// So let's see if the folder is a folder
			$subdir_path = $base_path . '/files/' . $base_folder . '/' . $file;
			$subdir = @scandir($subdir_path);
			if (empty($subdir))
			{
				@unlink($subdir_path);
				$files_deleted++;
				continue;
			}

			$subdir = array_diff($subdir, $excludable);

			// Is it empty?
			if (empty($subdir))
			{
				@rmdir($subdir_path);
				continue;
			}

			// So, there's something in this folder we need to look at.
			foreach ($subdir as $entry)
			{
				if (!preg_match('~^(\d+)_' . $file . '[0-9a-f]+.*\.dat$~', $entry, $match))
				{
					@unlink($base_path . '/files/' . $base_folder . '/' . $file . '/' . $entry);
					$files_deleted++;
					continue;
				}

				$items[$match[1]][] = $base_folder . '/' . $file . '/' . $entry;
			}
		}

		$item_ids = array_keys($items);
		if (!empty($item_ids))
		{
			$request = $db->query('', '
				SELECT 
					id_item, filehash, extension
				FROM {db_prefix}lgal_items
				WHERE id_item IN ({array_int:items})',
				array(
					'items' => $item_ids,
				)
			);
			while ($row = $db->fetch_assoc($request))
			{
				$file_base = $row['filehash'][0] . '/' . $row['filehash'][0] . $row['filehash'][1] . '/' . $row['id_item'] . '_' . $row['filehash'] . (!empty($row['extension']) ? '_' . $row['extension'] : '');
				foreach ($items[$row['id_item']] as $this_file)
				{
					// This is for deleting things that don't match the details supplied by the database (for a given item)
					if (strpos($this_file, $file_base) !== 0)
					{
						@unlink($base_path . '/files/' . $this_file);
						$files_deleted++;
					}
					unset ($items[$row['id_item']]);
				}
			}
			$db->free_result($request);

			foreach ($items as $item_list)
				{
					foreach ($item_list as $this_file)
					{
						@unlink($base_path . '/files/' . $this_file);
						$files_deleted++;
					}
				}
		}

		return array($substep == 15, 16, $files_deleted);
	}

	public function checkAlbumFiles()
	{
		$db = database();

		$hashes = array();
		$request = $db->query('', '
			SELECT 
				id_album, thumbnail
			FROM {db_prefix}lgal_albums
			WHERE thumbnail != {string:empty}',
			array(
				'empty' => '',
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			// Unspecified or generic thumbnails don't have files, so we will want to exclude these.
			if (strpos($row['thumbnail'], 'folder') === 0 || strpos($row['thumbnail'], 'generic') === 0)
			{
				continue;
			}
			list (, $hash) = explode(',', $row['thumbnail']);
			$hashes[$row['id_album']] = $hash;
		}
		$db->free_result($request);

		$base_path = LevGal_Bootstrap::getGalleryDir();
		$file_list = @scandir($base_path . '/albums');
		$file_list = array_diff($file_list, array('.', '..', '.htaccess'));

		$files_deleted = 0;

		$located = array();

		foreach ($file_list as $file)
		{
			// The file isn't even remotely valid? Or it smells valid but turns out not to be?
			if (!preg_match('~(\d+)_.+\.dat$~', $file, $match) || !isset($hashes[$match[1]]) || $file != $match[1] . '_' . $hashes[$match[1]] . '.dat')
			{
				@unlink($base_path . '/albums/' . $file);
				$files_deleted++;
				continue;
			}

			// While we're here, let's log this one for in a minute.
			$located[$match[1]] = $file;
		}

		// OK, so we know that what's in the folder matches what the DB says. But is everything the DB says accurate?
		$albums_not_found = array();
		foreach ($hashes as $id_album => $hash)
		{
			$actual_file = $id_album . '_' . $hash . '.dat';
			if (!isset($located[$id_album]) || $located[$id_album] != $actual_file)
			{
				$albums_not_found[] = $id_album;
			}
		}

		if (!empty($albums_not_found))
		{
			$db->query('', '
				UPDATE {db_prefix}lgal_albums
				SET thumbnail = {string:empty}
				WHERE id_album IN ({array_int:albums})',
				array(
					'empty' => '',
					'albums' => $albums_not_found,
				)
			);
		}

		return array(true, 1, $files_deleted);
	}

	protected function findBrokenReferences($from_table, $from_column, $to_table, $to_column, $exclude_empty = false)
	{
		$db = database();

		// Because I like more readable queries in my query log, thank you very much.
		$aliases = array(
			'lgal_albums' => 'la',
			'lgal_bookmarks' => 'lb',
			'lgal_items' => 'li',
			'lgal_likes' => 'll',
			'lgal_log_seen' => 'ls',
			'lgal_notify' => 'ln',
			'lgal_owner_group' => 'log',
			'lgal_owner_member' => 'lom',
			'lgal_reports' => 'lr',
			'lgal_report_body' => 'lrb',
			'lgal_report_comment' => 'lrc',
			'lgal_tags' => 'lt',
			'lgal_tag_items' => 'lti',
			'membergroups' => 'mg',
			'members' => 'mem',
		);

		$matches = array();
		$request = $db->query('', '
			SELECT 
				{raw:from_table_alias}.{raw:from_column}
			FROM {db_prefix}{raw:from_table} AS {raw:from_table_alias}
				LEFT JOIN {db_prefix}{raw:to_table} AS {raw:to_table_alias} ON ({raw:from_table_alias}.{raw:from_column} = {raw:to_table_alias}.{raw:to_column})
			WHERE {raw:to_table_alias}.{raw:to_column} IS NULL' . ($exclude_empty ? '
				AND {raw:from_table_alias}.{raw:from_column} > 0' : ''),
			array(
				'from_table' => $from_table,
				'from_table_alias' => $aliases[$from_table] ?? $from_table,
				'from_column' => $from_column,
				'to_table' => $to_table,
				'to_table_alias' => $aliases[$to_table] ?? $to_table,
				'to_column' => $to_column,
			)
		);
		while ($row = $db->fetch_row($request))
		{
			$matches[] = $row[0];
		}
		$db->free_result($request);

		return $matches;
	}

	protected function deleteRows($table, $column, $values)
	{
		$db = database();

		if (empty($values))
		{
			return 0;
		}
		$values = (array) $values;

		$db->query('', '
			DELETE FROM {db_prefix}{raw:table}
			WHERE {raw:column} IN ({array_int:values})',
			array(
				'table' => $table,
				'column' => $column,
				'values' => $values,
			)
		);

		return $db->affected_rows();
	}
}
