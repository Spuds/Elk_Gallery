<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.0.1
 */

/**
 * This file deals with providing foundations for importing data into Levertine Gallery from SMF Gallery Lite.
 */
class LevGal_Model_Importer_SGL extends LevGal_Model_Importer_Abstract
{
	const ITEMS_PER_STEP = 20;
	const COMMENTS_PER_STEP = 50;

	public function isValid()
	{
		// SMF Gallery Lite only has basic categories, SMF Gallery Pro has basic categories and user categories.
		return LevGal_Helper_Database::matchTable('{db_prefix}gallery_cat') && !LevGal_Helper_Database::matchTable('{db_prefix}gallery_usercat');
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
			$steps['tags'] = $txt['levgal_importer_results_tags'];
		}

		$steps['maintenance'] = true;
		$steps['done'] = true;

		return $steps;
	}

	public function countAlbums()
	{
		$db = database();
		static $count = null;

		// Because it only has simple categories, there is unlikely to be too many of them, so it's probably OK to create them all in a single step
		if ($count === null)
		{
			$request = $db->query('', '
				SELECT COUNT(id_cat)
				FROM {db_prefix}gallery_cat');
			list ($count) = $db->fetch_row($request);
			$db->free_result($request);
		}

		return array($count, 1);
	}

	public function importAlbums($substep)
	{
		$db = database();

		if ($substep > 0)
		{
			return array(true, 1);
		}

		$albums_to_insert = array();

		$request = $db->query('', '
			SELECT id_cat AS id_album, title AS album_name, roworder
			FROM {db_prefix}gallery_cat
			ORDER BY roworder');
		while ($row = $db->fetch_assoc($request))
		{
			// SGL cats have almost nothing of interest for us.
			$albums_to_insert[$row['id_album']] = array(
				'id_album' => $row['id_album'],
				'album_name' => $row['album_name'],
			);
			// Ownership is straightforward enough; everything is site owned (therefore member = 0)
			$albums_to_insert[$row['id_album']] += array(
				'owner_cache' => array('member' => array(0)),
				'owner_type' => 'member',
				'owner_data' => 0,
			);
			// Access is simple, there's absolutely nothing in the way of access control to deal with.
			$albums_to_insert[$row['id_album']]['perms'] = array(
				'type' => 'guests',
			);
			// The hierarchy is easy.
			$albums_to_insert[$row['id_album']] += array(
				'album_pos' => $row['roworder'],
				'album_level' => 0,
			);
		}
		$db->free_result($request);

		if (empty($_SESSION['lgalimport']['albums']))
		{
			$_SESSION['lgalimport']['albums'] = 0;
		}
		$_SESSION['lgalimport']['albums'] += $this->insertAlbums($albums_to_insert);

		return array(true, 1);
	}

	public function countItems()
	{
		$db = database();
		static $count = null;

		if ($count === null)
		{
			$request = $db->query('', '
				SELECT COUNT(id_picture)
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
		$gal_path = $this->getSGLSetting('gallery_path');

		$request = $db->query('', '
			SELECT gp.id_picture AS id_item, gp.id_cat AS id_album, IFNULL(mem.id_member, 0) AS id_member,
				mem.real_name AS poster_name, gp.title AS item_name, gp.date AS time_added, gp.approved, gp.filename,
				gp.allowcomments, gp.description, gp.views AS num_views
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
			$physical_file = $gal_path . $row['filename'];
			if (!file_exists($physical_file))
			{
				continue;
			}

			$files_to_import[$row['id_item']] = array(
				'id_item' => $row['id_item'],
				'id_album' => $row['id_album'],
				'id_member' => $row['id_member'],
				'poster_name' => $row['poster_name'],
				'item_name' => $row['item_name'],
				'time_added' => $row['time_added'],
				'num_views' => $row['num_views'],
				'approved' => $row['approved'],
				'comments_enabled' => !empty($row['allowcomments']),
				'filename' => $row['filename'],
				'physical_file' => $physical_file,
			);
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
		$db = database();
		static $count = null;

		if ($count === null)
		{
			// This is deliberately not a simple select; there is no point selecting any comment
			// where the item doesn't exist.
			$request = $db->query('', '
				SELECT COUNT(gc.id_comment)
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
			SELECT gc.id_comment AS id_comment, gc.id_picture AS id_item, IFNULL(mem.id_member, 0) AS id_author,
				mem.real_name AS author_name, mem.email_address AS author_email, mem.member_ip AS author_ip,
				gc.comment, gc.approved, gc.date AS time_added
			FROM {db_prefix}gallery_comment AS gc
				INNER JOIN {db_prefix}lgal_items AS li ON (gc.id_picture = li.id_item)
				LEFT JOIN {db_prefix}members AS mem ON (gc.id_member = mem.id_member)
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

	public function importTags($substep)
	{
		$db = database();

		list (, $substeps) = $this->countItems();

		if ($substep >= $substeps || $substep < 0 || $substeps == 0)
		{
			return array(true, $substeps);
		}

		$request = $db->query('', '
			SELECT gp.id_picture AS id_item, gp.keywords
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

	protected function getSGLSetting($setting)
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
