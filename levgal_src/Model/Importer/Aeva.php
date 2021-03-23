<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.0.4
 */

/**
 * This file deals with providing foundations for importing data into Levertine Gallery from Aeva Media.
 */
class LevGal_Model_Importer_Aeva extends LevGal_Model_Importer_Abstract
{
	const ITEMS_PER_STEP = 5;
	const COMMENTS_PER_STEP = 50;

	public function isValid()
	{
		if (LevGal_Helper_Database::matchTable('{db_prefix}aeva_media'))
		{
			// So we found a table, did we find any albums in it? If not, probably should
			// just stop right there.
			$owners = $this->getAlbumOwnerCount();

			return !empty($owners);
		}

		return false;
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
		}

		$steps['maintenance'] = true;
		$steps['done'] = true;

		return $steps;
	}

	public function countAlbums()
	{
		$total_albums = 0;
		$suggested_steps = 0;

		// In Aeva, there is a hierarchy of albums per album owner (plus possibly 1 extra
		// for 'albums without an owner') The plan here is to import one group of albums at
		// a time, e.g. all of Arantor's albums in one hit.
		$owners = $this->getAlbumOwnerCount();
		foreach ($owners as $album_count)
		{
			$suggested_steps++;
			$total_albums += $album_count;
		}

		return array($total_albums, $suggested_steps);
	}

	protected function getAlbumOwnerCount()
	{
		$db = database();

		static $owners = null;

		if ($owners !== null)
		{
			return $owners;
		}

		$owners = array();
		$request = $db->query('', '
			SELECT 
			    album_of, COUNT(id_album) AS count
			FROM {db_prefix}aeva_albums
			GROUP BY album_of
			ORDER BY album_of ASC');
		while ($row = $db->fetch_assoc($request))
		{
			$owners[$row['album_of']] = $row['count'];
		}
		$db->free_result($request);

		return $owners;
	}

	public function importAlbums($substep)
	{
		$db = database();

		$owners = $this->getAlbumOwnerCount();
		$substeps = count($owners);

		if ($substep >= $substeps || $substep < 0 || $substeps == 0)
		{
			return array(true, $substeps);
		}

		$owners = array_keys($owners);
		$this_owner = $owners[$substep];

		$albums_to_insert = array();

		$gal_path = $this->getAevaSetting('data_dir_path');

		$request = $db->query('', '
			SELECT 
				aa.id_album, aa.album_of AS id_member, aa.name AS album_name, aa.approved, aa.featured,
				aa.access, aa.a_order AS album_pos, aa.child_level AS album_level, aa.icon, af.filename,
				af.directory
			FROM {db_prefix}aeva_albums AS aa
				LEFT JOIN {db_prefix}aeva_files AS af ON (af.id_file = aa.icon)
			WHERE album_of = {int:member}
			ORDER BY a_order',
			array(
				'member' => $this_owner,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$albums_to_insert[$row['id_album']] = array(
				'id_album' => $row['id_album'],
				'album_name' => $row['album_name'],
				'approved' => $row['approved'],
				'featured' => $row['featured'],
			);
			// Ownership and access are a little bit trickier, but only just. See,
			// we have to set up two sets of data here!
			$albums_to_insert[$row['id_album']] += array(
				'owner_cache' => array('member' => array((int) $row['id_member'])),
				'owner_type' => 'member',
				'owner_data' => (int) $row['id_member'],
			);
			// Access is even more weird-ass.
			$groups = explode(',', $row['access']);
			foreach ($groups as $k => $v)
			{
				$groups[$k] = (int) $v;
			}
			$albums_to_insert[$row['id_album']]['perms'] = array(
				'type' => 'custom',
				'groups' => array_unique($groups),
			);
			// And for the hierarchy we need to do a couple of tweaks.
			$albums_to_insert[$row['id_album']] += array(
				// I have no idea why Aeva's increments are all even numbers.
				'album_pos' => $row['album_pos'] / 2,
				'album_level' => $row['album_level'],
			);

			if (!empty($row['filename']) && !empty($row['directory']) && $row['directory'] !== 'generic_images')
			{
				$physical_file = $gal_path . '/' . $row['directory'] . '/' . $this->getAevaEncryptedFilename($row['filename'], $row['icon']);
				if (file_exists($physical_file))
				{
					$albums_to_insert[$row['id_album']]['thumbnail'] = array(
						'extension' => $this->getAevaExtension($row['filename']),
						'file' => $physical_file,
					);
				}
			}
		}
		$db->free_result($request);

		if (empty($_SESSION['lgalimport']['albums']))
		{
			$_SESSION['lgalimport']['albums'] = 0;
		}
		$_SESSION['lgalimport']['albums'] += $this->insertAlbums($albums_to_insert);

		return array($substep + 1 == $substeps, $substeps);
	}

	public function countItems()
	{
		$db = database();

		static $count = null;

		if ($count === null)
		{
			$request = $db->query('', '
				SELECT 
				    COUNT(id_media)
				FROM {db_prefix}aeva_media');
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
		$gal_path = $this->getAevaSetting('data_dir_path');

		$request = $db->query('', '
			SELECT 
				am.id_media AS id_item, am.album_id as id_album, am.id_member, am.member_name AS poster_name,
				am.title AS item_name, am.time_added, am.last_edited AS time_updated, am.approved, am.views AS num_views,
				af.id_file, af.filename, af.filesize, af.directory, at.id_file AS id_thumb, at.directory AS thumb_dir,
				at.filename AS thumb_file, am.embed_url, am.description
			FROM {db_prefix}aeva_media AS am
				LEFT JOIN {db_prefix}aeva_files AS af ON (am.id_file = af.id_file)
				LEFT JOIN {db_prefix}aeva_files AS at ON (am.id_thumb = at.id_file)
			ORDER BY am.id_media
			LIMIT {int:start}, {int:limit}',
			array(
				'start' => $substep * self::ITEMS_PER_STEP,
				'limit' => self::ITEMS_PER_STEP,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$physical_file = false;
			$url_data = false;
			$external_url = false;

			if (!empty($row['embed_url']))
			{
				if (preg_match('~^\[url=(.+?)\]~i', $row['embed_url'], $match) && !empty($match[1]))
				{
					if ($pos = strpos($match[1], '#'))
					{
						$match[1] = substr($match[1], 0, $pos);
					}
					$externalModel = LevGal_Bootstrap::getModel('LevGal_Model_External');
					$url_data = $externalModel->getURLData($match[1]);

					if (empty($url_data['provider']))
					{
						continue;
					}

					$external_url = $match[1];
				}
			}
			else
			{
				$physical_file = $gal_path . '/' . $row['directory'] . '/' . $this->getAevaEncryptedFilename($row['filename'], $row['id_file']);
				if (!file_exists($physical_file))
				{
					continue;
				}
			}

			$files_to_import[$row['id_item']] = array(
				'id_item' => $row['id_item'],
				'id_album' => $row['id_album'],
				'id_member' => $row['id_member'],
				'description' => preg_replace('/\[smg id=(\d+)(.*?)\]/is', '[media]$1[/media]', $row['description']),
				'poster_name' => $row['poster_name'],
				'item_name' => $row['item_name'],
				'time_added' => $row['time_added'],
				'time_updated' => $row['time_updated'],
				'num_views' => $row['num_views'],
				'approved' => $row['approved'],
				'comments_enabled' => true,
				'filename' => $row['filename'],
				'physical_file' => $physical_file,
				'external_url' => $external_url,
				'external_data' => $url_data,
			);

			// Someone might have uploaded a custom thumbnail in place of what might be generated otherwise.
			if (!empty($row['thumb_dir']) && $row['thumb_dir'] !== 'generic_images' && !empty($row['thumb_file']) && $row['filename'] !== 'thumb_' . $row['thumb_file'])
			{
				$thumb_path = $gal_path . '/' . $row['thumb_dir'] . '/' . $this->getAevaEncryptedFilename($row['thumb_file'], $row['id_thumb'], !$this->getAevaSetting('clear_thumbnames'));
				if (@file_exists($thumb_path))
				{
					$files_to_import[$row['id_item']]['import_thumb'] = $thumb_path;
				}
			}
		}
		$db->free_result($request);

		if (empty($_SESSION['lgalimport']['items']) || $substep === 0)
		{
			$_SESSION['lgalimport']['items'] = 0;
		}
		$_SESSION['lgalimport']['items'] += $this->insertItems($files_to_import);;

		return array($substep + 1 == $substeps, $substeps);
	}

	public function countComments()
	{
		global $db_prefix;

		$db = database();
		$dbTable = db_table();

		static $count = null;

		if ($count === null && $dbTable->table_exists($db_prefix . 'aeva_comments'))
		{
			// This is deliberately not a simple select; there is no point selecting any comment
			// where the item doesn't exist.
			$request = $db->query('', '
				SELECT
				    COUNT(ac.id_comment)
				FROM {db_prefix}aeva_comments AS ac
					INNER JOIN {db_prefix}aeva_media AS am ON (ac.id_media = am.id_media)');
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
				ac.id_comment, ac.id_media AS id_item, IFNULL(mem.id_member, 0) AS id_author,
				mem.real_name AS author_name, mem.email_address AS author_email, mem.member_ip AS author_ip,
				ac.last_edited_name AS modified_name, ac.last_edited AS modified_time, ac.message AS comment,
				ac.approved, ac.posted_on AS time_added
			FROM {db_prefix}aeva_comments AS ac
				INNER JOIN {db_prefix}lgal_items AS li ON (ac.id_media = li.id_item)
				LEFT JOIN {db_prefix}members AS mem ON (ac.id_member = mem.id_member)
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
				'modified_name' => $row['modified_name'],
				'modified_time' => $row['modified_time'],
				'comment' => $row['comment'],
				'approved' => $row['approved'],
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

	public function importUnseen($substep)
	{
		$db = database();

		if ($substep != 0)
		{
			return array(true, 1);
		}

		// This is a much simpler step than the others. It's just bulk inserting of simple rows.
		$seen_log = array(
			'all' => array(),
			'members' => array(),
		);

		$request = $db->query('', '
			SELECT 
			    alm.id_media, li.id_item, alm.id_member, alm.time
			FROM {db_prefix}aeva_log_media AS alm
				LEFT JOIN {db_prefix}lgal_items AS li ON (alm.id_media = li.id_item)
				INNER JOIN {db_prefix}members AS mem ON (alm.id_member = mem.id_member)');
		while ($row = $db->fetch_assoc($request))
		{
			// If id_media = 0, that is code for 'everything'
			if (empty($row['id_media']))
			{
				$seen_log['all'][$row['id_member']] = $row['time'];
			}
			elseif (!empty($row['id_item']))
			{
				$seen_log['members'][$row['id_member']][$row['id_media']] = $row['time'];
			}
		}
		$db->free_result($request);

		// If we have anyone that had 'everything' seen, we have to do something about this.
		if (!empty($seen_log['all']))
		{
			$items = array();
			$request = $db->query('', '
				SELECT 
				    am.id_media
				FROM {db_prefix}aeva_media AS am');
			while ($row = $db->fetch_assoc($request))
			{
				$items[] = $row['id_media'];
			}
			$db->free_result($request);

			foreach ($seen_log['all'] as $member => $time)
			{
				foreach ($items as $id_item)
				{
					if (!isset($seen_log['members'][$member][$id_item]) || $seen_log['members'][$member][$id_item] < $time)
					{
						$seen_log['members'][$member][$id_item] = $time;
					}
				}
			}
		}

		foreach ($seen_log['members'] as $member => $items)
		{
			$this->insertUnseen($member, $items);
		}

		$_SESSION['lgalimport']['unseen'] = true;

		return array(true, 1);
	}

	public function countCustomfields()
	{
		global $db_prefix;

		$db = database();
		$dbTable = db_table();

		static $count = null;

		if ($count === null && $dbTable->table_exists($db_prefix . 'aeva_fields'))
		{
			$request = $db->query('', '
				SELECT 
				    COUNT(id_field)
				FROM {db_prefix}aeva_fields');
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
			$field_mapping = array(
				'checkbox' => 'checkbox',
				'radio' => 'radio',
				'select' => 'select',
				'text' => 'text',
				'textbox' => 'largetext',
			);
			$request = $db->query('', '
				SELECT 
				    afi.id_field, afi.name, afi.type, afi.options, afi.required, afi.searchable,
					afi.description, afi.bbc, afi.albums
				FROM {db_prefix}aeva_fields AS afi
				ORDER BY afi.id_field DESC');
			while ($row = $db->fetch_assoc($request))
			{
				$field_type = $field_mapping[$row['type']] ?? 'text';
				if (!empty($row['options']) && $field_type === 'checkbox')
				{
					$field_type = 'multiselect';
				}
				$field = array(
					'id_field' => $row['id_field'],
					'field_name' => !empty($row['name']) ? $row['name'] : '',
					'description' => !empty($row['description']) ? $row['description'] : '', // this should be preparsecode'd already, yay!
					'field_type' => $field_type,
					'field_options' => !empty($row['options']) ? explode(',', $row['options']) : array(),
					'field_config' => array(),
					'field_pos' => $pos++,
					'active' => true,
					'can_search' => !empty($row['searchable']),
					'default_val' => '',
					'placement' => 0,
				);
				if (!empty($row['bbc']))
				{
					$field['field_config']['bbc'] = true;
				}
				if (!empty($row['required']))
				{
					$field['field_config']['required'] = true;
				}
				if ($row['albums'] === 'all_albums')
				{
					$field['field_config']['all_albums'] = true;
				}
				else
				{
					$albums = explode(',', $row['albums']);
					foreach ($albums as $v)
					{
						$field['field_config']['albums'][] = (int) $v;
					}
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

			$request = $db->query('', '
				SELECT 
				    afd.id_media AS id_item, afd.id_field, afd.value, lcf.field_type
				FROM {db_prefix}aeva_field_data AS afd
					INNER JOIN {db_prefix}lgal_custom_field AS lcf ON (afd.id_field = lcf.id_field)
					INNER JOIN {db_prefix}lgal_items AS li ON (li.id_item = afd.id_media)');
			while ($row = $db->fetch_assoc($request))
			{
				if ($row['field_type'] === 'multiselect')
				{
					$row['value'] = str_replace(', ', ',', $row['value']);
				}
				unset ($row['field_type']);
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
				am.id_media AS id_item, am.keywords
			FROM {db_prefix}aeva_media AS am
				INNER JOIN {db_prefix}lgal_items AS li ON (am.id_media = li.id_item)
			ORDER BY am.id_media
			LIMIT {int:start}, {int:limit}',
			array(
				'start' => $substep * self::ITEMS_PER_STEP,
				'limit' => self::ITEMS_PER_STEP,
			)
		);
		$item_tag_map = array();
		while ($row = $db->fetch_assoc($request))
		{
			$tags = explode(',', preg_replace('~\s+~', ' ', $row['keywords']));
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

	protected function getAevaSetting($setting)
	{
		$db = database();

		static $amSettings = null;

		if ($amSettings === null)
		{
			// This could be cached but for the number of queries this is likely to be,
			// for the period of time it is likely to be... doesn't seem worth it.
			$amSettings = array();
			$request = $db->query('', '
				SELECT 
				    name, value
				FROM {db_prefix}aeva_settings');
			while ($row = $db->fetch_assoc($request))
			{
				$amSettings[$row['name']] = $row['value'];
			}
			$db->free_result($request);
		}

		return $amSettings[$setting] ?? null;
	}

	protected function getAevaEncryptedFilename($filename, $id, $use_enc = true)
	{
		global $db_character_set;

		$clean_name = $filename;
		// Remove international characters (windows-1252)
		// These lines should never be needed again. Still, behave.
		if (empty($db_character_set) || $db_character_set !== 'utf8')
		{
			$clean_name = strtr($filename,
				"\x8a\x8e\x9a\x9e\x9f\xc0\xc1\xc2\xc3\xc4\xc5\xc7\xc8\xc9\xca\xcb\xcc\xcd\xce\xcf\xd1\xd2\xd3\xd4\xd5\xd6\xd8\xd9\xda\xdb\xdc\xdd\xe0\xe1\xe2\xe3\xe4\xe5\xe7\xe8\xe9\xea\xeb\xec\xed\xee\xef\xf1\xf2\xf3\xf4\xf5\xf6\xf8\xf9\xfa\xfb\xfc\xfd\xff",
				'SZszYAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy');
			$clean_name = strtr($clean_name, array("\xde" => 'TH', "\xfe" => 'th',
			   "\xd0" => 'DH', "\xf0" => 'dh', "\xdf" => 'ss', "\x8c" => 'OE',
				"\x9c" => 'oe', "\xc6" => 'AE', "\xe6" => 'ae', "\xb5" => 'u'));
		}

		// Sorry, no spaces, dots, or anything else but letters allowed.
		// Largely similar to how old SMF generates its encrypted filenames but with a
		// few Aeva-specific quirks.
		$clean_name = preg_replace(array('/\s/', '/[^\w_\.-]/'), array('_', ''), $clean_name);
		$ext = $this->getAevaExtension($filename);
		$enc_name = $id . '_' . strtr($clean_name, '.', '_') . md5($clean_name) . '_ext' . $ext;
		$clean_name = substr(sha1($id), 0, 2) . sha1($id . $clean_name) . '.' . $ext;

		return $use_enc ? $enc_name : $clean_name;
	}

	protected function getAevaExtension($file)
	{
		$filename = ltrim($file, '/');

		if (strpos($filename, '.') !== false)
		{
			return strtolower(substr(strrchr($filename, '.'), 1));
		}
		elseif (strpos($filename, '_') !== false)
		{
			$ext_part = substr(strrchr($filename, '_'), 1);
			if (substr($ext_part, 0, 3) === 'ext')
			{
				return strtolower(substr($ext_part, 3));
			}
		}

		return false;
	}
}
