<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0 / elkarte
 */

namespace Addons\Levertine\Source\Model\Importer;

use Addons\Levertine\Source\Helper\Image;
use Addons\Levertine\Source\Helper\Sanitiser;
use Addons\Levertine\Source\LevGalBootstrap;
use Addons\Levertine\Source\ManageLevGalMaint;
use Addons\Levertine\Source\Model\File;
use Addons\Levertine\Source\Model\Item;
use Addons\Levertine\Source\Model\Upload;

/**
 * This file deals with providing foundations for importing data into Levertine Gallery
 */
abstract class Importer
{
	abstract public function isValid();

	abstract public function countAlbums();

	abstract public function importAlbums($substep);

	abstract public function countItems();

	abstract public function importItems($substep);

	abstract public function importDocs($substep);

	abstract public function stepsForImport();

	protected function _makeSlug($name)
	{
		return Sanitiser::sanitiseSlug($name);
	}

	public function doesOverwrite()
	{
		return true;
	}

	public function importOverwrite()
	{
		$db = database();

		$tables = ['lgal_albums', 'lgal_owner_member', 'lgal_owner_group', 'lgal_items',
						'lgal_comments', 'lgal_log_events', 'lgal_bookmarks', 'lgal_log_seen', 'lgal_likes',
						'lgal_notify', 'lgal_reports', 'lgal_report_body', 'lgal_report_comment',
						'lgal_search_album', 'lgal_search_item', 'lgal_search_results', 'lgal_custom_field',
						'lgal_custom_field_data', 'lgal_tags', 'lgal_tag_items'];
		foreach ($tables as $table)
		{
			$db->query('', '
				TRUNCATE TABLE {db_prefix}{raw:table}',
				[
					'table' => $table,
				]
			);
		}

		// And clean up unapproved counts.
		$_SESSION['lgal_uc'] = $_SESSION['lgal_ui'] = [
			'n' => 0,
			't' => time(),
		];
		updateSettings(['lgal_reports' => serialize(['items' => 0, 'comments' => 0])]);

		unset ($_SESSION['lgalimport']);

		return [true, 1];
	}

	public function importMaintenance()
	{
		require_once(ADDONSDIR . '/Levertine/ManageLevGalMaint.php');
		$maintance = new ManageLevGalMaint();
		$maintance->pre_dispatch();
		$maintance->action_levgal_maint_recount('', false);

		return [true, 1];
	}

	protected function insertAlbums($albums)
	{
		global $txt, $context;

		$db = database();

		$insert_rows = [];
		$search_rows = [];
		$hierarchy = [];

		$uploadModel = new Upload();
		$gal_path = LevGalBootstrap::getGalleryDir();

		foreach ($albums as $id_album => $album)
		{
			$new_album = [
				'id_album' => $id_album,
				'album_name' => !empty($album['album_name']) ? $album['album_name'] : sprintf($txt['levgal_importer_album_fallback'], $id_album),
				'album_slug' => '',
				'thumbnail' => '',
				'editable' => 0,
				'locked' => $album['locked'] ?? 0,
				'num_items' => 0,
				'num_unapproved_items' => 0,
				'num_comments' => 0,
				'num_unapproved_comments' => 0,
				'owner_cache' => !empty($album['owner_cache']) ? serialize($album['owner_cache']) : 'a:1:{s:6:"member";a:1:{i:0;i:' . (int) $context['user']['id'] . ';}}', // owned by the admin user
				'perms' => !empty($album['perms']) ? serialize($album['perms']) : 'a:1:{s:4:"type";s:6:"justme";}',
				'approved' => isset($album['approved']) ? (!empty($album['approved']) ? 1 : 0) : 1, // if the system doesn't have approval, assume approved
				'featured' => !empty($album['featured']) ? 1 : 0, // not all support it, those that don't... can safely ignore it.
				'description' => !empty($album['description']) ? $album['description'] : '',
				'sort' => !empty($album['sort']) ? $album['sort'] : 'date|desc',
			];
			$new_album['album_slug'] = !empty($album['album_slug']) ? $album['album_slug'] : $this->_makeSlug($new_album['album_name']);

			if (isset($album['thumbnail']))
			{
				if (isset($album['thumbnail']['file']))
				{
					$hash = $uploadModel->getFileHash($album['thumbnail']['file']);
					$new_file = $gal_path . '/albums/' . $id_album . '_' . $hash . '.dat';

					if (@copy($album['thumbnail']['file'], $new_file))
					{
						$new_album['thumbnail'] = $album['thumbnail']['extension'] . ',' . $hash;
					}
				}
				elseif (isset($album['thumbnail']['url']))
				{
					$hash = $uploadModel->getFileHash($album['thumbnail']['url']);
					$new_file = $gal_path . '/albums/' . $id_album . '_' . $hash . '.dat';

					require_once(SUBSDIR . '/Package.subs.php');
					$thumbnail_data = fetch_web_data($album['thumbnail']['url']);

					if (!empty($thumbnail_data))
					{
						$ext = '';
						if (str_starts_with($thumbnail_data, "\x89\x50\x4E\x47"))
						{
							$ext = 'png';
						}
						elseif (str_starts_with($thumbnail_data, 'GIF8'))
						{
							$ext = 'gif';
						}
						elseif (str_starts_with($thumbnail_data, "\xFF\xD8") && str_contains($thumbnail_data, 'JFIF'))
						{
							$ext = 'jpg';
						}

						if (!empty($ext) && @file_put_contents($new_file, $thumbnail_data))
						{
							$new_album['thumbnail'] = $ext . ',' . $hash;
						}
					}
				}
			}

			$album['owner_data'] = is_array($album['owner_data']) ? $album['owner_data'] : (array) $album['owner_data'];
			foreach ($album['owner_data'] as $owner)
			{
				$hierarchy[$album['owner_type']][] = [$id_album, $owner, $album['album_pos'], $album['album_level']];
			}
			$insert_rows[] = $new_album;
			$search_rows[] = [$id_album, $new_album['album_name'], $new_album['description']];
		}

		$import_count = 0;
		if (!empty($insert_rows))
		{
			// Insert the album
			$request = $db->insert('replace',
				'{db_prefix}lgal_albums',
				['id_album' => 'int', 'album_name' => 'string', 'album_slug' => 'string', 'thumbnail' => 'string', 'editable' => 'int',
					  'locked' => 'int', 'num_items' => 'int', 'num_unapproved_items' => 'int', 'num_comments' => 'int', 'num_unapproved_comments' => 'int',
					  'owner_cache' => 'string', 'perms' => 'string', 'approved' => 'int', 'featured' => 'int', 'description' => 'string', 'sort' => 'string'],
				$insert_rows,
				['id_album']
			);
			$import_count = $request->affected_rows();

			// Insert into the hierarchy
			if (isset($hierarchy['member']))
			{
				$db->insert('replace',
					'{db_prefix}lgal_owner_member',
					['id_album' => 'int', 'id_member' => 'int', 'album_pos' => 'int', 'album_level' => 'int'],
					$hierarchy['member'],
					['id_album', 'id_member']
				);
			}
			if (isset($hierarchy['group']))
			{
				$db->insert('replace',
					'{db_prefix}lgal_owner_group',
					['id_album' => 'int', 'id_group' => 'int', 'album_pos' => 'int', 'album_level' => 'int'],
					$hierarchy['member'],
					['id_album', 'id_member']
				);
			}

			// Insert into the search index
			if (!empty($search_rows))
			{
				$searchModel = LevGalBootstrap::getModel('Search');
				$searchModel->createAlbumEntries($search_rows);
			}
		}

		return (int) $import_count;
	}

	protected function insertItems($items)
	{
		global $txt, $modSettings;

		$db = database();

		$insert_rows = [];
		$search_rows = [];
		$thumbMax = $modSettings['attachmentThumbWidth'] ?: 125;

		$uploadModel = new Upload();
		$itemModel = new Item();
		$fileModel = new File();

		foreach ($items as $id_item => $item)
		{
			detectServer()->setTimeLimit(300);
			$new_item = [
				'id_item' => $id_item,
				'id_album' => $item['id_album'],
				'id_member' => $item['id_member'],
				'poster_name' => !empty($item['poster_name']) ? $item['poster_name'] : $txt['levgal_guest'],
				'item_name' => $item['item_name'],
				'item_slug' => '',
				'filename' => !empty($item['filename']) ? $item['filename'] : '',
				'filehash' => '',
				'extension' => '',
				'mime_type' => '',
				'time_added' => $item['time_added'],
				'time_updated' => !empty($item['time_updated']) && $item['time_updated'] > $item['time_added'] ? $item['time_updated'] : $item['time_added'],
				'description' => !empty($item['description']) ? $item['description'] : '',
				'approved' => isset($item['approved']) ? ($item['approved'] ? 1 : 0) : 1, // in case a gallery doesn't support this
				'editable' => 0,
				'comment_state' => !empty($item['comments_enabled']) ? 0 : 2, // 2 = comments disabled, 0 = comments enabled
				'filesize' => 0,
				'width' => 0,
				'height' => 0,
				'mature' => !empty($item['mature']) ? 1 : 0, // not all galleries support this so make non-mature by default in that case
				'num_views' => !empty($item['num_views']) ? $item['num_views'] : 0,
				'num_comments' => 0,
				'num_unapproved_comments' => 0,
				'meta' => '',
			];
			$new_item['item_slug'] = !empty($item['item_slug']) ? $item['item_slug'] : $this->_makeSlug($item['item_name']);

			if (!empty($item['physical_file']))
			{
				$new_item['filehash'] = $uploadModel->getFileHash($item['filename']);
				$new_item['extension'] = $uploadModel->getExtension($item['filename']);
				$new_item['filesize'] = filesize($item['physical_file']);

				$path = $fileModel->makePath($new_item['filehash']);
				$base_file = $path . '/' . $id_item . '_' . $new_item['filehash'] . (!empty($new_item['extension']) ? '_' . $new_item['extension'] : '');

				// Copy the file from the old gallery to the new one.
				if (@copy($item['physical_file'], $base_file . '.dat'))
				{
					$itemModel->buildFromSurrogate($new_item);
					$meta = $itemModel->getMetadata(true);
					$new_item['mime_type'] = $meta['mime_type'];
					if (isset($meta['meta']['width'], $meta['meta']['height']))
					{
						$new_item['width'] = $meta['meta']['width'];
						$new_item['height'] = $meta['meta']['height'];
						unset ($meta['meta']['width'], $meta['meta']['height']);
					}

					// If we were doing the orientation fix, now would be the time - but we shouldn't attempt to guess.

					// Hmm, thumbnail. We do need to build our previews and stuff... let's get that done.
					if (isset($meta['thumbnail']))
					{
						$itemModel->setThumbnail($meta['thumbnail']);
					}
					else
					{
						$itemModel->getThumbnail();
					}

					// And if there's an actual manual one to import? Do that next, replacing the thumbnail (not the preview).
					if (!empty($item['import_thumb']))
					{
						$image = new Image(true, true);
						$ext = $image->loadImageFromFile($item['import_thumb']);
						if ($ext)
						{
							$image->resizeToNewFile($thumbMax, $base_file . '_thumb_' . $ext . '.dat', $ext);
							$meta['meta']['thumb_uploaded'] = true;
						}
					}

					if (!empty($meta['meta']))
					{
						$new_item['meta'] = serialize($meta['meta']);
					}

					// OK, we did everything we need here, add it to the pile.
					$insert_rows[] = $new_item;
					$search_rows[] = [
						'id_item' => $id_item,
						'item_name' => $new_item['item_name'],
						'description' => $new_item['description'],
						'item_type' => $itemModel->getItemType(),
					];
				}
				else
				{
					@unlink($base_file . '.dat');
				}
			}
			elseif (!empty($item['external_data']) && !empty($item['external_url']))
			{
				// External media doesn't need most of the regular stuff but it does need a few things.
				$new_item['filehash'] = $uploadModel->getFileHash($item['external_url']);
				$new_item['mime_type'] = $item['external_data']['mime_type'];
				unset ($item['external_data']['mime_type']);

				if (!empty($item['import_thumb']))
				{
					$path = $fileModel->makePath($new_item['filehash']);
					$base_file = $path . '/' . $id_item . '_' . $new_item['filehash'] . (!empty($new_item['extension']) ? '_' . $new_item['extension'] : '');
					$image = new Image(true, true);
					$ext = $image->loadImageFromFile($item['import_thumb']);
					if ($ext)
					{
						$image->resizeToNewFile($thumbMax, $base_file . '_thumb_' . $ext . '.dat', $ext);
						$item['external_data']['thumb_uploaded'] = true;
					}
				}
				elseif (!empty($item['external_thumbnail']))
				{
					$path = $fileModel->makePath($new_item['filehash']);
					$base_file = $path . '/' . $id_item . '_' . $new_item['filehash'] . (!empty($new_item['extension']) ? '_' . $new_item['extension'] : '');
					$image = new Image(true, true);
					$image->loadImageFromString($item['external_thumbnail']['data']);
					$ext = $item['external_thumbnail']['image_mime'] === 'image/png' ? 'png' : 'jpg';
					$image->resizeToNewFile($thumbMax, $base_file . '_thumb_' . $ext . '.dat', $ext);
				}

				$new_item['meta'] = serialize($item['external_data']);

				$insert_rows[] = $new_item;
				$search_rows[] = [
					'id_item' => $id_item,
					'item_name' => $new_item['item_name'],
					'description' => $new_item['description'],
					'item_type' => 'external',
				];
			}
		}

		// And insert!
		if (!empty($insert_rows))
		{
			$request = $db->insert('replace',
				'{db_prefix}lgal_items',
				['id_item' => 'int', 'id_album' => 'int', 'id_member' => 'int', 'poster_name' => 'string',
					  'item_name' => 'string', 'item_slug' => 'string', 'filename' => 'string', 'filehash' => 'string',
					  'extension' => 'string', 'mime_type' => 'string', 'time_added' => 'int', 'time_updated' => 'int',
					  'description' => 'string', 'approved' => 'int', 'editable' => 'int', 'comment_state' => 'int',
					  'filesize' => 'int', 'width' => 'int', 'height' => 'int', 'mature' => 'int', 'num_views' => 'int',
					  'num_comments' => 'int', 'num_unapproved_comments' => 'int', 'meta' => 'string'],
				$insert_rows,
				['id_item']
			);
			$item_count = $request->affected_rows();

			// Insert into the search index
			if (!empty($search_rows))
			{
				$searchModel = LevGalBootstrap::getModel('Search');
				$searchModel->createItemEntries($search_rows);
			}

			return (int) $item_count;
		}

		return 0;
	}

	protected function insertComments($comments)
	{
		global $txt;

		$db = database();

		$insert_rows = [];

		foreach ($comments as $id_comment => $comment)
		{
			if (empty($comment['comment']))
			{
				continue;
			}

			$insert_rows[] = [
				'id_comment' => $id_comment,
				'id_item' => $comment['id_item'],
				'id_author' => !empty($comment['id_author']) ? $comment['id_author'] : 0,
				'author_name' => !empty($comment['author_name']) ? $comment['author_name'] : $txt['levgal_guest'],
				'author_email' => !empty($comment['author_email']) ? $comment['author_email'] : 'guest@example.com', // It's not like we would have anything better at this point.
				'author_ip' => !empty($comment['author_ip']) ? $comment['author_ip'] : '0.0.0.0',
				'modified_name' => !empty($comment['modified_name']) ? $comment['modified_name'] : '',
				'modified_time' => !empty($comment['modified_time']) ? $comment['modified_time'] : 0,
				'comment' => $comment['comment'],
				'approved' => isset($comment['approved']) ? (!empty($comment['approved']) ? 1 : 0) : 1, // for systems that don't support it
				'time_added' => !empty($comment['time_added']) ? $comment['time_added'] : 0,
			];
		}

		if (!empty($insert_rows))
		{
			$request = $db->insert('replace',
				'{db_prefix}lgal_comments',
				['id_comment' => 'int', 'id_item' => 'int', 'id_author' => 'int', 'author_name' => 'string',
					  'author_email' => 'string', 'author_ip' => 'string', 'modified_name' => 'string', 'modified_time' => 'int',
					  'comment' => 'string', 'approved' => 'int', 'time_added' => 'int'],
				$insert_rows,
				['id_comment']
			);

			return $request->affected_rows();
		}

		return 0;
	}

	protected function insertUnseen($memID, $items)
	{
		$db = database();

		$insert_rows = [];

		foreach ($items as $id_item => $last_seen)
		{
			$insert_rows[] = [$id_item, $memID, $last_seen];
		}

		if (!empty($insert_rows))
		{
			$db->insert('replace',
				'{db_prefix}lgal_log_seen',
				['id_item' => 'int', 'id_member' => 'int', 'view_time' => 'int'],
				$insert_rows,
				['id_item', 'id_member']
			);
		}
	}

	protected function insertNotifyAlbums($memID, $albums)
	{
		$db = database();

		$insert_rows = [];

		foreach ($albums as $album)
		{
			$insert_rows[] = [$memID, $album, 0];
		}

		if (!empty($insert_rows))
		{
			$db->insert('replace',
				'{db_prefix}lgal_notify',
				['id_member' => 'int', 'id_album' => 'int', 'id_item' => 'int'],
				$insert_rows,
				['id_member', 'id_album']
			);
		}
	}

	protected function insertNotifyItems($memID, $items)
	{
		$db = database();

		$insert_rows = [];

		foreach ($items as $item)
		{
			$insert_rows[] = [$memID, 0, $item];
		}

		if (!empty($insert_rows))
		{
			$db->insert('replace',
				'{db_prefix}lgal_notify',
				['id_member' => 'int', 'id_album' => 'int', 'id_item' => 'int'],
				$insert_rows,
				['id_member', 'id_item']
			);
		}
	}

	protected function insertBookmarks($memID, $items)
	{
		$db = database();

		$insert_rows = [];

		$time = time();
		foreach ($items as $item)
		{
			$insert_rows[] = [$memID, $item, $time];
		}

		if (!empty($insert_rows))
		{
			$db->insert('replace',
				'{db_prefix}lgal_bookmarks',
				['id_member' => 'int', 'id_item' => 'int', 'timestamp' => 'int'],
				$insert_rows,
				['id_member', 'id_item']
			);

			$bookmarkModel = LevGalBootstrap::getModel('Bookmark');
			$bookmarkModel->resetUserCache($memID);
		}
	}

	protected function insertCustomFields($fields_to_import)
	{
		global $txt;

		$db = database();

		$insert_rows = [];

		foreach ($fields_to_import as $field)
		{
			$insert_rows[] = [
				'id_field' => $field['id_field'],
				'field_name' => !empty($field['field_name']) ? $field['field_name'] : sprintf($txt['levgal_importer_cfield_fallback'], $field['id_field']),
				'description' => !empty($field['description']) ? $field['description'] : '',
				'field_type' => $field['field_type'],
				'field_options' => !empty($field['field_options']) ? implode(',', $field['field_options']) : '',
				'field_config' => !empty($field['field_config']) ? serialize($field['field_config']) : '',
				'field_pos' => $field['field_pos'],
				'active' => !empty($field['active']) ? 1 : 0,
				'can_search' => !empty($field['can_search']) ? 1 : 0,
				'default_val' => !empty($field['default_val']) ? $field['default_val'] : '',
				'placement' => !empty($field['placement']) ? $field['placement'] : 0,
			];
		}

		if (!empty($insert_rows))
		{
			$request = $db->insert('replace',
				'{db_prefix}lgal_custom_field',
				['id_field' => 'int', 'field_name' => 'string', 'description' => 'string', 'field_type' => 'string',
					  'field_options' => 'string', 'field_config' => 'string', 'field_pos' => 'int', 'active' => 'int',
					  'can_search' => 'int', 'default_val' => 'string', 'placement' => 'int'],
				$insert_rows,
				['id_field']
			);

			return $request->affected_rows();
		}

		return 0;
	}

	protected function insertCustomFieldValues($values_to_import)
	{
		$db = database();

		if (!empty($values_to_import))
		{
			$items = [];
			foreach ($values_to_import as $triplet)
			{
				if (!isset($items[$triplet['id_item']]))
				{
					$items[$triplet['id_item']] = true;
				}
			}

			$db->insert('replace',
				'{db_prefix}lgal_custom_field_data',
				['id_item' => 'int', 'id_field' => 'int', 'value' => 'string'],
				$values_to_import,
				['id_item', 'id_field']
			);

			$db->query('', '
				UPDATE {db_prefix}lgal_items
				SET has_custom = 1
				WHERE id_item IN ({array_int:items})',
				[
					'items' => array_keys($items),
				]
			);
		}
	}

	protected function getInsertedTags()
	{
		$db = database();

		$tags = [];
		$request = $db->query('', '
			SELECT id_tag, tag_name
			FROM {db_prefix}lgal_tags');
		while ($row = $request->fetch_assoc())
		{
			$tags[$row['tag_name']] = $row['id_tag'];
		}
		$request->free_result();

		return $tags;
	}

	protected function getTagIds($tag_list)
	{
		$db = database();

		$available_tags = $this->getInsertedTags();
		$not_available = [];

		// Find out which ones we don't have.
		foreach ($tag_list as $tag)
		{
			if (!isset($available_tags[$tag]))
			{
				$not_available[] = $tag;
			}
		}

		foreach ($not_available as $tag)
			{
				$raw = un_htmlspecialchars($tag);
				$db->insert('',
					'{db_prefix}lgal_tags',
					['tag_name' => 'string', 'tag_slug' => 'string'],
					[$tag, Sanitiser::sanitiseSlug($raw)],
					['id_tag']
				);
				$available[$tag] = $db->insert_id('{db_prefix}lgal_tags');
			}

		// Assemble a final list.
		$final = [];
		foreach ($tag_list as $tag)
		{
			if (!empty($available[$tag]))
			{
				$final[$tag] = $available[$tag];
			}
		}

		return $final;
	}

	protected function insertTags($item_tag_map)
	{
		$db = database();

		if (empty($item_tag_map))
		{
			return;
		}

		$tag_list = [];
		foreach ($item_tag_map as $item_id => $tags)
		{
			foreach ($tags as $tag)
			{
				$tag_list[] = $tag;
			}
		}
		$tag_map = $this->getTagIds($tag_list);

		// Now we have the list of tags we can insert
		$insert_rows = [];
		$item_id_map = [];
		foreach ($item_tag_map as $item_id => $tags)
		{
			foreach ($tags as $tag)
			{
				if (!empty($tag_map[$tag]))
				{
					$insert_rows[] = [$tag_map[$tag], $item_id];
					$item_id_map[$item_id] = true;
				}
			}
		}
		if (!empty($insert_rows))
		{
			$db->insert('',
				'{db_prefix}lgal_tag_items',
				['id_tag' => 'int', 'id_item' => 'int'],
				$insert_rows,
				['id_item', 'id_tag']
			);

			$db->query('', '
				UPDATE {db_prefix}lgal_items
				SET has_tag = 1
				WHERE id_item IN ({array_int:ids})',
				[
					'ids' => array_keys($item_id_map),
				]
			);
		}
	}

	protected function buildHierarchy($temp_hierarchy)
	{
		$hierarchy = [];
		$new_hierarchy = [];

		// First, step through the hierarchy. If there are entries with invalid parents, sift them out right away.
		// This also, conveniently, sifts out top level parents at the same time.
		foreach ($temp_hierarchy as $id_album => $album_parent)
		{
			if (!isset($temp_hierarchy[$album_parent]) && !isset($hierarchy[$album_parent]))
			{
				$hierarchy[$id_album] = ['album_level' => 0];
				unset ($temp_hierarchy[$id_album]);
			}
		}

		// Now, step through what's left. We can do the rest recursively because we know we should have parents for everything.
		foreach ($hierarchy as $id_album => $album)
		{
			$new_hierarchy[$id_album] = $album;
			$this->fetchHierarchyChildren($new_hierarchy, $temp_hierarchy, $id_album);
		}

		// Now to traverse and add the album position.
		$album_pos = 1;
		foreach (array_keys($new_hierarchy) as $id_album)
		{
			$new_hierarchy[$id_album]['album_pos'] = $album_pos++;
		}

		return $new_hierarchy;
	}

	protected function fetchHierarchyChildren(&$new_hierarchy, $hierarchy, $parent)
	{
		foreach ($hierarchy as $id_album => $id_parent)
		{
			if ($parent == $id_parent)
			{
				$new_hierarchy[$id_album] = ['album_level' => $new_hierarchy[$id_parent]['album_level'] + 1];
				$this->fetchHierarchyChildren($new_hierarchy, $hierarchy, $id_album);
			}
		}
	}
}
