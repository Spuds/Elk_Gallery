<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0 / elkarte
 */

namespace Addons\Levertine\Source\Model;

use Addons\Levertine\Source\Helper\Sanitiser;
use Addons\Levertine\Source\LevGalBootstrap;
use ElkArte\Helper\Util;

/**
 * This file deals with tags on items.
 */
class Tag
{
	public function getTagsByItemId($item)
	{
		global $scripturl;

		$db = database();

		$tags = [];
		$request = $db->query('', '
			SELECT lt.id_tag, lt.tag_name, lt.tag_slug
			FROM {db_prefix}lgal_tag_items AS lti
				INNER JOIN {db_prefix}lgal_tags AS lt ON (lti.id_tag = lt.id_tag)
			WHERE lti.id_item = {int:item}
			ORDER BY lt.tag_name',
			[
				'item' => $item,
			]
		);
		while ($row = $request->fetch_assoc())
		{
			$tags[$row['id_tag']] = [
				'name' => $row['tag_name'],
				'url' => $scripturl . '?media/tag/' . (!empty($row['tag_slug']) ? $row['tag_slug'] . '.' . $row['id_tag'] : $row['id_tag']) . '/',
			];
		}
		$request->free_result();

		return $tags;
	}

	public function removeTagsFromItems($items)
	{
		$db = database();

		$items = (array) $items;

		// Delete the item/tag relationship.
		$db->query('', '
			DELETE FROM {db_prefix}lgal_tag_items
			WHERE id_item IN ({array_int:items})',
			[
				'items' => $items,
			]
		);
	}

	public function prepareTagString($tag_string)
	{
		$tag_string = preg_replace('~\s+~', ' ', $tag_string);
		$tags = explode(',', $tag_string);
		$new_tags = [];
		foreach ($tags as $tag)
		{
			$tag = Util::htmltrim($tag);
			if (empty($tag) || !preg_match('~[a-z0-9]+~i', $tag))
			{
				continue;
			}
			$new_tags[] = [
				'raw' => $tag,
			];
		}

		if (empty($new_tags))
		{
			return [];
		}

		// Having figured out what tags we might want, let's do something about this real quick.
		foreach ($new_tags as $k => $tag)
		{
			$new_tags[$k]['html'] = Util::htmlspecialchars($tag['raw'], ENT_QUOTES);
		}

		return $new_tags;
	}

	public function setTagsOnItem($item, $tag_string)
	{
		$db = database();

		$new_tags = $this->prepareTagString($tag_string);

		$tag_list = [];
		foreach ($new_tags as $tag)
		{
			$tag_list[] = $tag['html'];
		}

		$tags_to_apply = [];
		$request = $db->query('', '
			SELECT 
				id_tag, tag_name
			FROM {db_prefix}lgal_tags
			WHERE tag_name IN ({array_string:tags})',
			[
				'tags' => $tag_list,
			]
		);
		while ($row = $request->fetch_assoc())
		{
			// Add this to the list of ones we know we need to add
			$tags_to_apply[] = $row['id_tag'];
			// And remove this from the list of ones we thought we might have to add
			foreach ($new_tags as $k => $v)
			{
				if ($v['html'] === $row['tag_name'])
				{
					unset ($new_tags[$k]);
				}
			}
		}
		$request->free_result();

		if (!empty($new_tags))
		{
			foreach ($new_tags as $tag)
			{
				$db->insert('',
					'{db_prefix}lgal_tags',
					['tag_name' => 'string', 'tag_slug' => 'string'],
					[$tag['html'], Sanitiser::sanitiseSlug($tag['raw'])],
					['id_tag']
				);
				$tags_to_apply[] = $db->insert_id('{db_prefix}lgal_tags');
			}
		}

		if (!empty($tags_to_apply))
		{
			$rows = [];
			foreach ($tags_to_apply as $id_tag)
			{
				$rows[] = [$id_tag, $item];
			}
			$db->insert('',
				'{db_prefix}lgal_tag_items',
				['id_tag' => 'int', 'id_item' => 'int'],
				$rows,
				['id_item', 'id_tag']
			);
		}
	}

	public function getItemsByTagId($id_tag)
	{
		$db = database();

		$tag_name = '';
		$tag_slug = '';
		$item_ids = [];

		// This one is actually fairly straightforward; Model_ItemList will permission-check for us.
		// So all we need to do is grab the tags first.
		$request = $db->query('', '
			SELECT 
				lt.tag_name, lt.tag_slug, lti.id_item
			FROM {db_prefix}lgal_tags AS lt
				INNER JOIN {db_prefix}lgal_tag_items AS lti ON (lt.id_tag = lti.id_tag)
			WHERE lt.id_tag = {int:id_tag}',
			[
				'id_tag' => $id_tag,
			]
		);
		while ($row = $request->fetch_row())
		{
			[$tag_name, $tag_slug, $id] = $row;
			$item_ids[] = $id;
		}
		$request->free_result();

		if (empty($item_ids))
		{
			return [];
		}

		$itemList = LevGalBootstrap::getModel('ItemList');
		$items = $itemList->getItemsById($item_ids);

		return [
			'tag_name' => $tag_name,
			'tag_slug' => $tag_slug,
			'items' => $items,
		];
	}

	public function getTagCloud()
	{
		global $scripturl;

		$db = database();

		$tags = [];

		$album_list = true;
		if (!allowedTo('lgal_manage'))
		{
			/** @var $albums AlbumList */
			$albums = LevGalBootstrap::getModel('AlbumList');
			$album_list = $albums->getVisibleAlbums();
		}

		if (empty($album_list))
		{
			return [];
		}

		// So we know which albums. Time to get tags.
		$request = $db->query('', '
			SELECT 
			    lt.id_tag, lt.tag_name, lt.tag_slug, COUNT(li.id_item) AS count
			FROM {db_prefix}lgal_tags AS lt
				INNER JOIN {db_prefix}lgal_tag_items AS lti ON (lt.id_tag = lti.id_tag)
				INNER JOIN {db_prefix}lgal_items AS li ON (lti.id_item = li.id_item)
			WHERE ' . ($album_list === true ? '1=1' : 'li.id_album IN ({array_int:album_list})') . '
				AND li.approved = {int:approved}
			GROUP BY lt.id_tag
			ORDER BY lt.tag_name',
			[
				'album_list' => $album_list,
				'approved' => 1,
			]
		);
		while ($row = $request->fetch_assoc())
		{
			$tags[$row['id_tag']] = [
				'name' => $row['tag_name'],
				'url' => $scripturl . '?media/tag/' . (!empty($row['tag_slug']) ? $row['tag_slug'] . '.' . $row['id_tag'] : $row['id_tag']) . '/',
				'count' => (int) $row['count'],
			];
		}
		$request->free_result();

		return $tags;
	}

	public function getSiteTags()
	{
		global $modSettings, $txt;

		// Site defined ones
		$tags = [];
		$inUseTags = [];
		if (!empty($modSettings['lgal_tag_items_list']))
		{
			$tagString = Util::htmlspecialchars($modSettings['lgal_tag_items_list'], ENT_QUOTES);

			$tags = array_map('trim', explode(',', $tagString));
			natsort($tags);
		}

		// Tags in use, in albums they have write permission on
		$cloudTags = $this->getTagCloud();
		foreach ($cloudTags as $tag)
		{
			if ($tag['name'] !== $txt['levgal_tagcloud_none'] && !in_array($tag['name'], $tags, true))
			{
				$inUseTags[] = Util::htmlspecialchars($tag['name'], ENT_QUOTES);
			}
		}

		$tags = array_merge($tags, $inUseTags);
		natsort($tags);

		return $tags;
	}
}
