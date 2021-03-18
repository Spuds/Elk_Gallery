<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license proprietary
 *
 * @version 1.1.0
 */

/**
 * This file deals with providing foundations for importing data into Levertine Gallery from SMF Gallery Pro.
 */
class LevGal_Model_Importer_SGP extends LevGal_Model_Importer_Abstract
{
	const ITEMS_PER_STEP = 20;
	const COMMENTS_PER_STEP = 50;

	public function isValid()
	{
		// SMF Gallery Lite only has basic categories, SMF Gallery Pro has basic categories and user categories.
		return LevGal_Helper_Database::matchTable('{db_prefix}gallery_cat') && LevGal_Helper_Database::matchTable('{db_prefix}gallery_usercat');
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
			$steps['notify'] = $txt['levgal_importer_results_notify'];
			$steps['bookmarks'] = $txt['levgal_importer_results_bookmarks'];
		}

		$steps['maintenance'] = true;
		$steps['done'] = true;

		return $steps;
	}

	public function countAlbums()
	{
		static $count = null;

		$db = database();

		// There are main categories and there are user categories. We need to query to get both,
		// and do each one in its own substep.
		if ($count === null)
		{
			$count = 0;

			// There is a special 'redirect' category that redirects to the user categories.
			$request = $db->query('', '
				SELECT 
				       	COUNT(id_cat)
				FROM {db_prefix}gallery_cat
				WHERE redirect = 0');
			$row = $db->fetch_row($request);
			$db->free_result($request);
			$count += $row[0];

			$request = $db->query('', '
				SELECT 
				    COUNT(user_id_cat)
				FROM {db_prefix}gallery_usercat');
			$row = $db->fetch_row($request);
			$db->free_result($request);
			$count += $row[0];
		}

		return array($count, 2);
	}

	public function importAlbums($substep)
	{
		$db = database();

		$albums_to_insert = array();

		$gal_path = $this->getSGPSetting('gallery_path');

		// We do this in two steps.
		if ($substep == 0)
		{
			// This step is about importing non-user categories. We need to exclude the phantom redirection category.
			$temp_hierarchy = array();
			$request = $db->query('', '
				SELECT 
				    id_cat AS id_album, title AS album_name, id_parent, image, filename
				FROM {db_prefix}gallery_cat
				WHERE redirect = 0
				ORDER BY roworder');
			while ($row = $db->fetch_assoc($request))
			{
				// We don't actually need the roworder, we already used it for ordering, so all we need is parentage.
				$temp_hierarchy[$row['id_album']] = $row['id_parent'];

				// Almost nothing here for us; but we'll do what we can.
				$albums_to_insert[$row['id_album']] = array(
					'id_album' => $row['id_album'],
					'album_name' => $row['album_name'],
				);
				// Ownership is easy, all of these are site owned (member = 0)
				$albums_to_insert[$row['id_album']] += array(
					'owner_cache' => array('member' => array(0)),
					'owner_type' => 'member',
					'owner_data' => 0,
				);

				if (!empty($row['filename']))
				{
					$physical_file = $gal_path . 'catimgs/' . $row['filename'];
					if (file_exists($physical_file))
					{
						$albums_to_insert[$row['id_album']]['thumbnail'] = array(
							'extension' => strtolower(substr(strrchr($physical_file, '.'), 1)),
							'file' => $physical_file,
						);
					}
				}

				if (!empty($row['image']) && empty($albums_to_insert[$row['id_album']]['thumbnail']))
				{
					$albums_to_insert[$row['id_album']]['thumbnail']['url'] = $row['image'];
				}
			}
			$db->free_result($request);

			$hierarchy = $this->buildHierarchy($temp_hierarchy);
			foreach ($hierarchy as $id_album => $album_positioning)
			{
				$albums_to_insert[$id_album] += $album_positioning;
			}

			$_SESSION['lgalimport']['sgp_usercat'] = max(array_keys($albums_to_insert));
		}
		elseif ($substep == 1)
		{
			// This step is about importing user categories.
			if (empty($_SESSION['lgalimport']['sgp_usercat']))
			{
				$_SESSION['lgalimport']['sgp_usercat'] = 0; // We didn't import any previous albums?
			}

			$temp_hierarchies = array();

			$request = $db->query('', '
				SELECT 
				    user_id_cat AS id_album, title AS album_name, id_member, id_parent, image, filename
				FROM {db_prefix}gallery_usercat
				ORDER BY id_member, roworder');
			while ($row = $db->fetch_assoc($request))
			{
				$id_album = $row['id_album'] + $_SESSION['lgalimport']['sgp_usercat'];
				$temp_hierarchies[$row['id_member']][$id_album] = !empty($row['id_parent']) ? $row['id_parent'] + $_SESSION['lgalimport']['sgp_usercat'] : 0;

				// Same usual dearth of available information.
				$albums_to_insert[$id_album] = array(
					'id_album' => $id_album,
					'album_name' => $row['album_name'],
				);
				// Ownership still pretty easy.
				$albums_to_insert[$id_album] += array(
					'owner_cache' => array('member' => array((int) $row['id_member'])),
					'owner_type' => 'member',
					'owner_data' => $row['id_member'],
				);

				if (!empty($row['filename']))
				{
					$physical_file = $gal_path . 'catimgs/' . $row['filename'];
					if (file_exists($physical_file))
					{
						$albums_to_insert[$id_album]['thumbnail'] = array(
							'extension' => strtolower(substr(strrchr($physical_file, '.'), 1)),
							'file' => $physical_file,
						);
					}
				}

				if (!empty($row['image']) && empty($albums_to_insert[$id_album]['thumbnail']))
				{
					$albums_to_insert[$id_album]['thumbnail']['url'] = $row['image'];
				}
			}
			$db->free_result($request);

			// Fixing hierarchies is a bit more fun here, though.
			foreach ($temp_hierarchies as $temp_hierarchy)
			{
				$hierarchy = $this->buildHierarchy($temp_hierarchy);
				foreach ($hierarchy as $id_album => $album_positioning)
				{
					$albums_to_insert[$id_album] += $album_positioning;
				}
			}
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
		static $count = null;

		$db = database();

		if ($count === null)
		{
			$request = $db->query('', '
				SELECT 
				    COUNT(id_picture)
				FROM {db_prefix}gallery_pic');
			list ($count) = $db->fetch_row($request);
			$db->free_result($request);
		}

		return array($count, ceil($count / self::ITEMS_PER_STEP));
	}

	public function importItems($substep)
	{
		$db = database();

		list (, $substeps) = $this->countItems();

		if ($substep >= $substeps || $substep < 0 || $substeps == 0)
		{
			return array(true, $substeps);
		}

		$files_to_import = array();
		$gal_path = $this->getSGPSetting('gallery_path');

		$request = $db->query('', '
			SELECT 
				gp.id_picture AS id_item, gp.id_cat, gp.user_id_cat, IFNULL(mem.id_member, 0) AS id_member,
				mem.real_name AS poster_name, gp.title AS item_name, gp.date AS time_added, gp.approved,
				gp.filename, gp.allowcomments, gp.description, gp.views AS num_views, gp.type AS type_id,
				gp.videofile
			FROM {db_prefix}gallery_pic AS gp
				LEFT JOIN {db_prefix}members AS mem ON (gp.id_member = mem.id_member)
			ORDER BY gp.id_picture
			LIMIT {int:start}, {int:limit}',
			array(
				'start' => $substep * self::ITEMS_PER_STEP,
				'limit' => self::ITEMS_PER_STEP,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$new_item = array(
				'id_item' => $row['id_item'],
				'id_album' => !empty($row['id_cat']) ? $row['id_cat'] : $row['user_id_cat'] + $_SESSION['lgalimport']['sgp_usercat'],
				'id_member' => $row['id_member'],
				'poster_name' => $row['poster_name'],
				'item_name' => $row['item_name'],
				'description' => $row['description'],
				'time_added' => $row['time_added'],
				'num_views' => $row['num_views'],
				'approved' => $row['approved'],
				'comments_enabled' => !empty($row['allowcomments']),
				'filename' => $row['filename'],
			);

			switch ($row['type_id'])
			{
				case 0: // regular uploaded file
					$physical_file = $gal_path . $row['filename'];
					if (!file_exists($physical_file))
					{
						continue 2;
					}
					$new_item['physical_file'] = $physical_file;
					break;
				case 1: // uploaded video file
					$physical_file = $gal_path . 'videos/' . $row['videofile'];
					if (!file_exists($physical_file))
					{
						continue 2;
					}
					$new_item['physical_file'] = $physical_file;
					$new_item['filename'] = $row['videofile'];
					break;
				case 5: // uploaded external file
					$externalModel = LevGal_Bootstrap::getModel('LevGal_Model_External');
					$url_data = $externalModel->getURLData($row['videofile']);

					if (empty($url_data['provider']))
					{
						continue 2;
					}

					$new_item['external_url'] = $row['videofile'];
					$new_item['external_data'] = $url_data;
					break;
				default:
					continue 2;
			}

			$files_to_import[$row['id_item']] = $new_item;
		}
		$db->free_result($request);

		if (empty($_SESSION['lgalimport']['items']))
		{
			$_SESSION['lgalimport']['items'] = 0;
		}
		$_SESSION['lgalimport']['items'] += $this->insertItems($files_to_import);

		return array($substep + 1 == $substeps, $substeps);
	}

	public function countComments()
	{
		static $count = null;

		$db = database();

		if ($count === null)
		{
			// This is deliberately not a simple select; there is no point selecting any comment
			// where the item doesn't exist.
			$request = $db->query('', '
				SELECT 
				    COUNT(gc.id_comment)
				FROM {db_prefix}gallery_comment AS gc
					INNER JOIN {db_prefix}gallery_pic AS gp ON (gc.id_picture = gp.id_picture)');
			list ($count) = $db->fetch_row($request);
			$db->free_result($request);
		}

		return array($count, ceil($count / self::COMMENTS_PER_STEP));
	}

	public function importComments($substep)
	{
		$db = database();

		list (, $substeps) = $this->countComments();

		if ($substep >= $substeps || $substep < 0 || $substeps == 0)
		{
			return array(true, $substeps);
		}

		$comments_to_import = array();

		$request = $db->query('', '
			SELECT 
				gc.id_comment AS id_comment, gc.id_picture AS id_item, IFNULL(mem.id_member, 0) AS id_author,
				mem.real_name AS author_name, mem.email_address AS author_email, mem.member_ip AS author_ip,
				gc.comment, gc.approved, gc.date AS time_added, medit.real_name AS modified_name,
				gc.lastmodified AS modified_time
			FROM {db_prefix}gallery_comment AS gc
				INNER JOIN {db_prefix}lgal_items AS li ON (gc.id_picture = li.id_item)
				LEFT JOIN {db_prefix}members AS mem ON (gc.id_member = mem.id_member)
				LEFT JOIN {db_prefix}members AS medit ON (gc.modified_id_member = medit.id_member)
			ORDER BY id_comment ASC
			LIMIT {int:start}, {int:limit}',
			array(
				'start' => $substep * self::COMMENTS_PER_STEP,
				'limit' => self::COMMENTS_PER_STEP,
			)
		);
		while ($row = $db->fetch_assoc($request))
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
				'modified_name' => $row['modified_name'],
				'modified_time' => $row['modified_time'],
			);
		}
		$db->free_result($request);

		if (empty($_SESSION['lgalimport']['comments']))
		{
			$_SESSION['lgalimport']['comments'] = 0;
		}
		$_SESSION['lgalimport']['comments'] += $this->insertComments($comments_to_import);

		return array($substep + 1 == $substeps, $substeps);
	}

	public function countCustomfields()
	{
		static $count = null;

		$db = database();

		if ($count === null)
		{
			$request = $db->query('', '
				SELECT 
				    COUNT(id_custom)
				FROM {db_prefix}gallery_custom_field');
			list ($count) = $db->fetch_row($request);
			$db->free_result($request);
		}

		return array($count, !empty($count) ? 2 : 1);
	}

	public function importCustomfields($substep)
	{
		$db = database();

		list($count) = $this->countCustomfields();
		if (empty($count))
		{
			return array(true, 1);
		}

		if ($substep == 0)
		{
			// So we know there are fields to import.
			$fields_to_import = array();
			$pos = 1;

			$request = $db->query('', '
				SELECT 
				    gcf.id_custom AS id_field, gcf.id_cat, gcf.title, gcf.defaultvalue, gcf.is_required
				FROM {db_prefix}gallery_custom_field AS gcf
				ORDER BY gcf.roworder, gcf.id_custom');
			while ($row = $db->fetch_assoc($request))
			{
				$field = array(
					'id_field' => $row['id_field'],
					'field_name' => !empty($row['title']) ? $row['title'] : '',
					'field_type' => 'text',
					'field_options' => array(),
					'field_config' => array(),
					'field_pos' => $pos++,
					'active' => true,
					'can_search' => false,
					'default_val' => !empty($row['defaultvalue']) ? $row['defaultvalue'] : '',
					'placement' => 0,
				);
				if (!empty($row['is_required']))
				{
					$field['field_config']['required'] = true;
				}
				if (!empty($row['id_cat']))
				{
					$field['field_config']['albums'] = array((int) $row['id_cat']);
				}
				else
				{
					$field['field_config']['all_albums'] = true;
				}

				$fields_to_import[$row['id_field']] = $field;
			}
			$db->free_result($request);

			$this->insertCustomFields($fields_to_import);

			return array(false, 2);
		}
		elseif ($substep == 1)
		{
			$values_to_import = array();
			require_once(SUBSDIR . '/Post.subs.php');

			$request = $db->query('', '
				SELECT 
				    gcfd.id_picture AS id_item, gcfd.id_custom AS id_field, gcfd.value
				FROM {db_prefix}gallery_custom_field_data AS gcfd
					INNER JOIN {db_prefix}lgal_custom_field AS lcf ON (gcfd.id_custom = lcf.id_field)
					INNER JOIN {db_prefix}lgal_items AS li ON (li.id_item = gcfd.id_picture)');
			while ($row = $db->fetch_assoc($request))
			{
				// Because this was never supporting bbc before but we can't take the chance here.
				preparsecode($row['value']);

				$values_to_import[] = $row;
			}
			$db->free_result($request);

			$this->insertCustomFieldValues($values_to_import);

			$_SESSION['lgalimport']['customfields'] = true;
		}

		return array(true, 2);
	}

	public function importTags($substep)
	{
		$db = database();

		list (, $substeps) = $this->countItems();

		if ($substep >= $substeps || $substep < 0 || $substeps == 0)
		{
			return array(true, $substeps);
		}

		$request = $db->query('', '
			SELECT
				gp.id_picture AS id_item, gp.keywords
			FROM {db_prefix}gallery_pic AS gp
				INNER JOIN {db_prefix}lgal_items AS li ON (gp.id_picture = li.id_item)
			ORDER BY gp.id_picture
			LIMIT {int:start}, {int:limit}',
			array(
				'start' => $substep * self::ITEMS_PER_STEP,
				'limit' => self::ITEMS_PER_STEP,
			)
		);
		$item_tag_map = array();
		while ($row = $db->fetch_assoc($request))
		{
			$tags = explode(' ', preg_replace('~\s+~', ' ', $row['keywords']));
			$processed_tags = array();
			foreach ($tags as $tag)
			{
				$tag = Util::htmltrim($tag);
				if (!empty($tag) && preg_match('~[a-z0-9]+~i', $tag))
				{
					$processed_tags[] = $tag;
				}
			}

			if (!empty($processed_tags))
			{
				$item_tag_map[$row['id_item']] = $processed_tags;
			}
		}
		$db->free_result($request);

		if (!empty($item_tag_map))
		{
			$this->insertTags($item_tag_map);
			$_SESSION['lgalimport']['tags'] = true;
		}

		return array($substep + 1 == $substeps, $substeps);
	}

	public function importUnseen($substep)
	{
		$db = database();

		if ($substep != 0)
		{
			return array(true, 1);
		}

		$seen_log = array();

		$time = time();
		$request = $db->query('', '
			SELECT 
			    glmv.id_member, glmv.id_picture AS id_item
			FROM {db_prefix}gallery_log_mark_view AS glmv
				INNER JOIN {db_prefix}lgal_items AS li ON (glmv.id_picture = li.id_item)
				INNER JOIN {db_prefix}members AS mem ON (glmv.id_member = mem.id_member)');
		while ($row = $db->fetch_assoc($request))
		{
			$seen_log[$row['id_member']][$row['id_item']] = $time;
		}
		$db->free_result($request);

		foreach ($seen_log as $member => $items)
		{
			$this->insertUnseen($member, $items);
		}

		$_SESSION['lgalimport']['unseen'] = true;

		return array(true, 1);
	}

	public function importNotify($substep)
	{
		$db = database();

		if ($substep != 0)
		{
			return array(true, 1);
		}

		$notify_log = array();

		$request = $db->query('', '
			SELECT 
			    gp.id_picture, gp.id_member, gp.sendemail
			FROM {db_prefix}gallery_pic AS gp
				INNER JOIN {db_prefix}lgal_items AS li ON (gp.id_picture = li.id_item)
				INNER JOIN {db_prefix}members AS mem ON (gp.id_member = mem.id_member)
			WHERE gp.sendemail = 1');
		while ($row = $db->fetch_assoc($request))
		{
			$notify_log[$row['id_member']][] = $row['id_picture'];
		}
		$db->free_result($request);

		foreach ($notify_log as $id_member => $items)
		{
			$this->insertNotifyItems($id_member, $items);
		}

		$_SESSION['lgalimport']['notify'] = true;

		return array(true, 1);
	}

	public function importBookmarks($substep)
	{
		$db = database();

		if ($substep != 0)
		{
			return array(true, 1);
		}

		$bookmark_log = array();
		$request = $db->query('', '
			SELECT 
			    gf.id_picture, gf.id_member
			FROM {db_prefix}gallery_favorites AS gf
				INNER JOIN {db_prefix}lgal_items AS li ON (gf.id_picture = li.id_item)
				INNER JOIN {db_prefix}members AS mem ON (gf.id_member = mem.id_member)');
		while ($row = $db->fetch_assoc($request))
		{
			$bookmark_log[$row['id_member']][] = $row['id_picture'];
		}
		$db->free_result($request);

		foreach ($bookmark_log as $id_member => $items)
		{
			$this->insertBookmarks($id_member, $items);
		}

		$_SESSION['lgalimport']['bookmarks'] = true;

		return array(true, 1);
	}

	protected function getSGPSetting($setting)
	{
		global $modSettings;

		if (!empty($modSettings[$setting]))
		{
			return $modSettings[$setting];
		}

		switch ($setting)
		{
			case 'gallery_path':
				return BOARDDIR . '/gallery/';

			default:
				return null;
		}
	}
}
