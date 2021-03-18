<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license proprietary
 *
 * @version 1.1.0
 */

/**
 * This file deals with providing foundations for importing data into Levertine Gallery from
 * SMF Packs Multimedia Gallery.
 */
class LevGal_Model_Importer_SMFPacks extends LevGal_Model_Importer_Abstract
{
	const ITEMS_PER_STEP = 20;
	const COMMENTS_PER_STEP = 50;

	public function isValid()
	{
		// SMF Gallery Lite only has basic categories, SMF Gallery Pro has basic categories and user categories.
		return LevGal_Helper_Database::matchTable('{db_prefix}multimedia_categories');
	}

	public function stepsForImport()
	{
		global $txt;

		$steps = array();

		list ($total_albums) = $this->countAlbums();
		list ($total_items) = $this->countItems();

		$steps['overwrite'] = true;
		$steps['albums'] = LevGal_Helper_Format::numstring('lgal_albums', $total_albums);

		if (!empty($total_items))
		{
			$steps['items'] = LevGal_Helper_Format::numstring('lgal_items', $total_items);

			list ($total_comments) = $this->countComments();
			if (!empty($total_comments))
			{
				$steps['comments'] = LevGal_Helper_Format::numstring('lgal_comments', $total_comments);
			}

			list ($custom_fields) = $this->countCustomfields();
			if (!empty($custom_fields))
			{
				$steps['customfields'] = $txt['levgal_importer_results_customfields'];
			}
			$steps['tags'] = $txt['levgal_importer_results_tags'];
			$steps['unseen'] = $txt['levgal_importer_results_unseen'];
			$steps['bookmarks'] = $txt['levgal_importer_results_bookmarks'];
		}

		$steps['maintenance'] = true;
		$steps['done'] = true;

		return $steps;
	}

	public function countAlbums()
	{
		global $smcFunc;
		static $count = null;

		if ($count === null)
		{
			// We need to exclude category 1 because this is the album container for user galleries.
			$request = $smcFunc['db_query']('', '
				SELECT COUNT(id_cat)
				FROM {db_prefix}multimedia_categories
				WHERE id_cat != 1
					AND redirect = {string:empty}',
				array(
					'empty' => '',
				)
			);
			list ($count) = $smcFunc['db_fetch_row']($request);
			$smcFunc['db_free_result']($request);
		}

		// Because of the emphasis on site albums, we can probably do this in just 2 steps.
		return array($count, 2);
	}

	public function importAlbums($substep)
	{
		global $smcFunc;

		$gal_path = $this->getSMFPacksSetting('multimedia_upload_dir');

		$albums_to_insert = array();
		if ($substep == 0)
		{
			// This step is about importing the albums the admin set up.
			$request = $smcFunc['db_query']('', '
				SELECT id_cat AS id_album, name AS album_name, icon, permissions, id_parent
				FROM {db_prefix}multimedia_categories
				WHERE id_cat != 1
					AND id_user_cat = 0
					AND redirect = {string:empty}
				ORDER BY sort_method, id_album',
				array(
					'empty' => '',
				)
			);
			while ($row = $smcFunc['db_fetch_assoc']($request))
			{
				// Now, let's grab the hierarchy data. We don't have row order, it's purely implied.
				$temp_hierarchy[$row['id_album']] = $row['id_parent'];

				$albums_to_insert[$row['id_album']] = array(
					'id_album' => $row['id_album'],
					'album_name' => $row['album_name'],
				);
				// Ownership is easy: site owned.
				$albums_to_insert[$row['id_album']] += array(
					'owner_cache' => array('member' => array(0)),
					'owner_type' => 'member',
					'owner_data' => 0,
				);
				// Access is not that difficult. The list is a CSV of group ids, but we can clean it up a touch.
				$groups = explode(',', $row['permissions']);
				$groups = array_diff($groups, array(1, 3));
				foreach ($groups as $k => $v)
				{
					$groups[$k] = (int) $v;
				}
				$albums_to_insert[$row['id_album']]['perms'] = array(
					'type' => 'custom',
					'groups' => array_unique($groups),
				);

				// And is there a thumbnail?
				if (!empty($row['icon']))
				{
					$physical_file = $gal_path . '/' . $row['icon'];
					if (file_exists($physical_file))
					{
						$albums_to_insert[$row['id_album']]['thumbnail'] = array(
							'extension' => strtolower(substr(strrchr($row['icon'], '.'), 1)),
							'file' => $physical_file,
						);
					}
				}
			}
			$smcFunc['db_free_result']($request);

			$hierarchy = $this->buildHierarchy($temp_hierarchy);
			foreach ($hierarchy as $id_album => $album_positioning)
			{
				$albums_to_insert[$id_album] += $album_positioning;
			}
		}
		elseif ($substep == 1)
		{
			// This step is about importing user categories.
			$row_pos = 1;
			$request = $smcFunc['db_query']('', '
				SELECT id_cat AS id_album, name AS album_name, id_user_cat AS id_member, icon,
					permissions
				FROM {db_prefix}multimedia_categories
				WHERE id_user_cat > 0
					AND redirect = {string:empty}
				ORDER BY id_member, sort_method, id_album',
				array(
					'empty' => '',
				)
			);
			while ($row = $smcFunc['db_fetch_assoc']($request))
			{
				// Member albums are quite straightforward, there's no hierarchy to contend with.
				$albums_to_insert[$row['id_album']] = array(
					'id_album' => $row['id_album'],
					'album_name' => $row['album_name'],
				);
				// Ownership is easy, it's by a member.
				$albums_to_insert[$row['id_album']] += array(
					'owner_cache' => array('member' => array((int) $row['id_member'])),
					'owner_type' => 'member',
					'owner_data' => (int) $row['id_member'],
				);
				// Access is not that difficult. The list is a CSV of group ids, but we can clean it up a touch.
				$groups = explode(',', $row['permissions']);
				$groups = array_diff($groups, array(1, 3));
				foreach ($groups as $k => $v)
				{
					$groups[$k] = (int) $v;
				}
				$albums_to_insert[$row['id_album']]['perms'] = array(
					'type' => 'custom',
					'groups' => array_unique($groups),
				);
				// And hierarchy is kind of sad.
				$albums_to_insert[$row['id_album']] += array(
					'album_pos' => $row_pos++,
					'album_level' => 0,
				);

				// And is there a thumbnail?
				if (!empty($row['icon']))
				{
					$physical_file = $gal_path . '/' . $row['icon'];
					if (file_exists($physical_file))
					{
						$albums_to_insert[$row['id_album']]['thumbnail'] = array(
							'extension' => strtolower(substr(strrchr($row['icon'], '.'), 1)),
							'file' => $physical_file,
						);
					}
				}
			}
			$smcFunc['db_free_result']($request);
		}

		if (empty($_SESSION['lgalimport']['albums']))
		{
			$_SESSION['lgalimport']['albums'] = 0;
		}
		$_SESSION['lgalimport']['albums'] += $this->insertAlbums($albums_to_insert);

		return array($substep != 0, 2);
	}

	public function countItems()
	{
		global $smcFunc;
		static $count = null;

		if ($count === null)
		{
			$request = $smcFunc['db_query']('', '
				SELECT COUNT(id_item)
				FROM {db_prefix}multimedia');
			list ($count) = $smcFunc['db_fetch_row']($request);
			$smcFunc['db_free_result']($request);
		}

		return array($count, ceil($count / self::ITEMS_PER_STEP));
	}

	public function importItems($substep)
	{
		global $smcFunc;

		list (, $substeps) = $this->countItems();

		if ($substep >= $substeps || $substep < 0 || $substeps == 0)
		{
			return array(true, $substeps);
		}

		$files_to_import = array();
		$gal_path = $this->getSMFPacksSetting('multimedia_upload_dir');

		$request = $smcFunc['db_query']('', '
			SELECT mm.id_item, mm.name AS item_name, mm.cat_id AS id_album, mm.icon, IFNULL(mem.id_member, 0) AS id_member,
				IFNULL(mem.real_name, {string:empty}) AS poster_name, mm.date AS time_added, mm.views AS num_views,
				mm.approved, mm.real_filename AS filename, mm.description, mm.video_url
			FROM {db_prefix}multimedia AS mm
				LEFT JOIN {db_prefix}members AS mem ON (mm.author = mem.id_member)
			ORDER BY mm.id_item
			LIMIT {int:start}, {int:limit}',
			array(
				'empty' => '',
				'start' => $substep * self::ITEMS_PER_STEP,
				'limit' => self::ITEMS_PER_STEP,
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$physical_file = false;
			$url_data = false;
			$external_url = false;
			$thumbnail = false;

			if (!empty($row['video_url']))
			{
				if ($pos = strpos($row['video_url'], '#'))
				{
					$row['video_url'] = substr($row['video_url'], 0, $pos);
				}
				$externalModel = LevGal_Bootstrap::getModel('LevGal_Model_External');
				$url_data = $externalModel->getURLData($row['video_url']);

				if (empty($url_data['provider']))
				{
					continue;
				}

				$external_url = $row['video_url'];
				$thumbnail = $externalModel->getThumbnail();
			}
			else
			{
				$physical_file = $gal_path . '/' . $row['icon'];
				if (!file_exists($physical_file))
				{
					continue;
				}
			}

			$files_to_import[$row['id_item']] = array(
				'id_item' => $row['id_item'],
				'id_album' => $row['id_album'],
				'id_member' => $row['id_member'],
				'poster_name' => $row['poster_name'],
				'item_name' => $row['item_name'],
				'description' => preg_replace('/\[mg\](\d+)\[/mg\]/is', '[media]$1[/media]', $row['description']),
				'time_added' => $row['time_added'],
				'num_views' => $row['num_views'],
				'approved' => $row['approved'],
				'comments_enabled' => true,
				'filename' => $row['filename'],
				'physical_file' => $physical_file,
				'external_url' => $external_url,
				'external_data' => $url_data,
				'external_thumbnail' => $thumbnail,
			);
		}
		$smcFunc['db_free_result']($request);

		if (empty($_SESSION['lgalimport']['items']))
		{
			$_SESSION['lgalimport']['items'] = 0;
		}
		$_SESSION['lgalimport']['items'] += $this->insertItems($files_to_import);

		return array($substep + 1 == $substeps, $substeps);
	}

	public function countComments()
	{
		global $smcFunc;
		static $count = null;

		if ($count === null)
		{
			// This is deliberately not a simple select; there is no point selecting any comment
			// where the item doesn't exist.
			$request = $smcFunc['db_query']('', '
				SELECT COUNT(mmc.id_comment)
				FROM {db_prefix}multimedia_comments AS mmc
					INNER JOIN {db_prefix}multimedia AS mm ON (mm.id_item = mmc.id_item)');
			list ($count) = $smcFunc['db_fetch_row']($request);
			$smcFunc['db_free_result']($request);
		}

		return array($count, ceil($count / self::COMMENTS_PER_STEP));
	}

	public function importComments($substep)
	{
		global $smcFunc;

		list (, $substeps) = $this->countComments();

		if ($substep >= $substeps || $substep < 0 || $substeps == 0)
		{
			return array(true, $substeps);
		}

		$comments_to_import = array();

		$request = $smcFunc['db_query']('', '
			SELECT mmc.id_comment, mmc.id_item, IFNULL(mem.id_member, 0) AS id_author,
				mem.real_name AS author_name, mem.email_address AS author_email, mem.member_ip AS author_ip,
				mmc.comment, mmc.approved, mmc.date AS time_added, mmc.last_modified AS modified_time, mmc.modified_name
			FROM {db_prefix}multimedia_comments AS mmc
				INNER JOIN {db_prefix}lgal_items AS li ON (mmc.id_item = li.id_item)
				LEFT JOIN {db_prefix}members AS mem ON (mmc.author = mem.id_member)
			ORDER BY id_comment ASC
			LIMIT {int:start}, {int:limit}',
			array(
				'start' => $substep * self::COMMENTS_PER_STEP,
				'limit' => self::COMMENTS_PER_STEP,
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$comments_to_import[$row['id_comment']] = array(
				'id_comment' => $row['id_comment'],
				'id_item' => $row['id_item'],
				'id_author' => $row['id_author'],
				'author_name' => $row['author_name'],
				'author_email' => $row['author_email'],
				'author_ip' => $row['author_ip'],
				'comment' => $row['comment'],
				'time_added' => $row['time_added'],
				'modified_time' => $row['modified_time'],
				'modified_name' => $row['modified_name'],
			);
		}
		$smcFunc['db_free_result']($request);

		if (empty($_SESSION['lgalimport']['comments']))
		{
			$_SESSION['lgalimport']['comments'] = 0;
		}
		$_SESSION['lgalimport']['comments'] += $this->insertComments($comments_to_import);

		return array($substep + 1 == $substeps, $substeps);
	}

	public function countCustomfields()
	{
		global $smcFunc;
		static $count = null;

		if ($count === null)
		{
			$request = $smcFunc['db_query']('', '
				SELECT COUNT(id_field)
				FROM {db_prefix}multimedia_fields');
			list ($count) = $smcFunc['db_fetch_row']($request);
			$smcFunc['db_free_result']($request);
		}

		return array($count, !empty($count) ? 2 : 1);
	}

	public function importCustomfields($substep)
	{
		global $smcFunc;

		list ($count) = $this->countCustomfields();
		if (empty($count))
		{
			return array(true, 1);
		}

		if ($substep == 0)
		{
			// So there are fields to import.
			$fields_to_import = array();
			$pos = 1;

			// To get from the internal ids to ours...
			$field_mapping = array(
				0 => 'largetext',
				1 => 'text',
				2 => 'multiselect',
				3 => 'select',
				4 => 'radio',
			);

			// We will need to get albums that are not owned 'by the site'.
			$user_albums = $this->getUserAlbums();

			$request = $smcFunc['db_query']('', '
				SELECT mmf.id_field, mmf.name AS field_name, mmf.description AS description, mmf.type AS field_type,
					mmf.options AS field_options, mmf.required AS required, mmf.categories
				FROM {db_prefix}multimedia_fields AS mmf
				ORDER BY custom_order');
			while ($row = $smcFunc['db_fetch_assoc']($request))
			{
				$field = array(
					'id_field' => $row['id_field'],
					'field_name' => $row['field_name'],
					'description' => $row['description'],
					'field_type' => $field_mapping[$row['field_type']],
					'field_options' => array(),
					'field_config' => array(),
					'field_pos' => $pos++,
					'active' => true,
					'can_search' => false,
					'default_val' => '',
					'placement' => 0,
				);
				// All fields were bbc parsed but not all of them can be in our world.
				if (in_array($field['field_type'], array('text', 'largetext', 'multiselect', 'radio')))
				{
					$field['field_config']['bbc'] = true;
				}

				// Field required?
				if (!empty($row['required']))
				{
					$field['field_config']['required'] = true;
				}

				// Now we have to do a bit of quick preparation on multi-value items.
				if (in_array($field['field_type'], array('multiselect', 'select', 'radio')))
				{
					$options = explode('|', $row['field_options']);
					foreach ($options as $v)
					{
						$field['field_options'][] = trim(str_replace(',', '', $v));
					}
				}

				// Now which albums this applies to.
				if ($row['categories'] === '0')
				{
					$field['field_config']['all_albums'] = true;
				}
				else
				{
					// In this system, we might find 1,2,4 where 1 represents all user-owned albums.
					$albums = explode(',', $row['categories']);
					if (in_array(1, $albums))
					{
						$albums = array_merge($user_albums, array_diff($albums, array(1)));
					}
					foreach ($albums as $v)
					{
						$field['field_config']['albums'][] = (int) $v;
					}
				}

				$fields_to_import[$row['id_field']] = $field;
			}
			$smcFunc['db_free_result']($request);

			$this->insertCustomFields($fields_to_import);

			return array(false, 2);
		}
		elseif ($substep == 1)
		{
			$values_to_import = array();

			$request = $smcFunc['db_query']('', '
				SELECT mmfd.id_item, mmfd.id_field, mmfd.content AS value, lcf.field_type
				FROM {db_prefix}multimedia_fields_data AS mmfd
					INNER JOIN {db_prefix}lgal_custom_field AS lcf ON (mmfd.id_field = lcf.id_field)
					INNER JOIN {db_prefix}lgal_items AS li ON (li.id_item = mmfd.id_item)');
			while ($row = $smcFunc['db_fetch_assoc']($request))
			{
				if ($row['field_type'] === 'multiselect')
				{
					$row['value'] = str_replace(', ', ',', $row['value']);
				}
				unset ($row['field_type']);
				$values_to_import[] = $row;
			}
			$smcFunc['db_free_result']($request);

			$this->insertCustomFieldValues($values_to_import);

			$_SESSION['lgalimport']['customfields'] = true;
		}

		return array(true, 2);
	}

	protected function getUserAlbums()
	{
		global $smcFunc;

		$user_albums = array();

		$request = $smcFunc['db_query']('', '
			SELECT id_album, owner_cache
			FROM {db_prefix}lgal_albums');
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$owner_cache = @unserialize($row['owner_cache']);
			if (empty($owner_cache))
			{
				continue;
			}
			if (empty($owner_cache['member']) || !in_array(0, $owner_cache['member']))
			{
				$user_albums[] = (int) $row['id_album'];
			}
		}
		$smcFunc['db_free_result']($request);

		return $user_albums;
	}

	public function importTags($substep)
	{
		global $smcFunc;

		list (, $substeps) = $this->countItems();

		if ($substep >= $substeps || $substep < 0 || $substeps == 0)
		{
			return array(true, $substeps);
		}

		$request = $smcFunc['db_query']('', '
			SELECT mm.id_item
			FROM {db_prefix}multimedia AS mm
				INNER JOIN {db_prefix}lgal_items AS li ON (mm.id_item = li.id_item)
			ORDER BY mm.id_item
			LIMIT {int:start}, {int:limit}',
			array(
				'start' => $substep * self::ITEMS_PER_STEP,
				'limit' => self::ITEMS_PER_STEP,
			)
		);
		$item_ids = array();
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$item_ids[] = (int) $row['id_item'];
		}
		$smcFunc['db_free_result']($request);

		// Unlike the others, this particular gallery actively stores them pre-split, so we don't have to manually split anything.
		// We just segment by id here rather than trying to segment by ids against this table.
		if (!empty($item_ids))
		{
			$item_tag_map = array();
			$request = $smcFunc['db_query']('', '
				SELECT id_item, tag
				FROM {db_prefix}multimedia_tags
				WHERE id_item IN ({array_int:item_ids})',
				array(
					'item_ids' => $item_ids,
				)
			);
			while ($row = $smcFunc['db_fetch_assoc']($request))
			{
				$item_tag_map[$row['id_item']][] = $row['tag'];
			}
		}

		if (!empty($item_tag_map))
		{
			$this->insertTags($item_tag_map);
			$_SESSION['lgalimport']['tags'] = true;
		}

		return array($substep + 1 == $substeps, $substeps);
	}

	public function importUnseen($substep)
	{
		global $smcFunc;

		if ($substep != 0)
		{
			return array(true, 1);
		}

		$seen_log = array();

		$time = time();
		$request = $smcFunc['db_query']('', '
			SELECT mml.id_member, mml.id_item
			FROM {db_prefix}multimedia_log AS mml
				INNER JOIN {db_prefix}lgal_items AS li ON (mml.id_item = li.id_item)
				INNER JOIN {db_prefix}members AS mem ON (mml.id_member = mem.id_member)');
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$seen_log[$row['id_member']][$row['id_item']] = $time;
		}
		$smcFunc['db_free_result']($request);

		foreach ($seen_log as $member => $items)
		{
			$this->insertUnseen($member, $items);
		}

		$_SESSION['lgalimport']['unseen'] = true;

		return array(true, 1);
	}

	public function importBookmarks($substep)
	{
		global $smcFunc;

		if ($substep != 0)
		{
			return array(true, 1);
		}

		$bookmark_log = array();
		$request = $smcFunc['db_query']('', '
			SELECT mmf.id_item, mmf.id_member
			FROM {db_prefix}multimedia_favorites AS mmf
				INNER JOIN {db_prefix}lgal_items AS li ON (mmf.id_item = li.id_item)
				INNER JOIN {db_prefix}members AS mem ON (mmf.id_member = mem.id_member)');
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$bookmark_log[$row['id_member']][] = $row['id_item'];
		}
		$smcFunc['db_free_result']($request);

		foreach ($bookmark_log as $id_member => $items)
		{
			$this->insertBookmarks($id_member, $items);
		}

		$_SESSION['lgalimport']['bookmarks'] = true;

		return array(true, 1);
	}

	protected function getSMFPacksSetting($setting)
	{
		global $modSettings;

		if (!empty($modSettings[$setting]))
		{
			return $modSettings[$setting];
		}

		return null;
	}
}
