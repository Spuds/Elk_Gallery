<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.0 / elkarte
 */

/**
 * This file deals with tags on items.
 */
class LevGal_Model_Tag
{
	public function getTagsByItemId($item)
	{
		global $scripturl;

		$db = database();

		$tags = array();
		$request = $db->query('', '
			SELECT lt.id_tag, lt.tag_name, lt.tag_slug
			FROM {db_prefix}lgal_tag_items AS lti
				INNER JOIN {db_prefix}lgal_tags AS lt ON (lti.id_tag = lt.id_tag)
			WHERE lti.id_item = {int:item}
			ORDER BY lt.tag_name',
			array(
				'item' => $item,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$tags[$row['id_tag']] = array(
				'name' => $row['tag_name'],
				'url' => $scripturl . '?media/tag/' . (!empty($row['tag_slug']) ? $row['tag_slug'] . '.' . $row['id_tag'] : $row['id_tag']) . '/',
			);
		}
		$db->free_result($request);

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
			array(
				'items' => $items,
			)
		);
	}

	public function prepareTagString($tag_string)
	{
		$tag_string = preg_replace('~\s+~', ' ', $tag_string);
		$tags = explode(',', $tag_string);
		$new_tags = array();
		foreach ($tags as $tag)
		{
			$tag = Util::htmltrim($tag);
			if (empty($tag) || !preg_match('~[a-z0-9]+~i', $tag))
			{
				continue;
			}
			$new_tags[] = array(
				'raw' => $tag,
			);
		}

		if (empty($new_tags))
		{
			return array();
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

		$tag_list = array();
		foreach ($new_tags as $tag)
		{
			$tag_list[] = $tag['html'];
		}

		$tags_to_apply = array();
		$request = $db->query('', '
			SELECT 
				id_tag, tag_name
			FROM {db_prefix}lgal_tags
			WHERE tag_name IN ({array_string:tags})',
			array(
				'tags' => $tag_list,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			// Add this to the list of ones we know we need to add
			$tags_to_apply[] = $row['id_tag'];
			// And remove this from the list of ones we thought we might have to add
			foreach ($new_tags as $k => $v)
			{
				if ($v['html'] == $row['tag_name'])
				{
					unset ($new_tags[$k]);
				}
			}
		}
		$db->free_result($request);

		if (!empty($new_tags))
		{
			foreach ($new_tags as $tag)
			{
				$db->insert('',
					'{db_prefix}lgal_tags',
					array('tag_name' => 'string', 'tag_slug' => 'string'),
					array($tag['html'], LevGal_Helper_Sanitiser::sanitiseSlug($tag['raw'])),
					array('id_tag')
				);
				$tags_to_apply[] = $db->insert_id('{db_prefix}lgal_tags', 'id_tag');
			}
		}

		if (!empty($tags_to_apply))
		{
			$rows = array();
			foreach ($tags_to_apply as $id_tag)
			{
				$rows[] = array($id_tag, $item);
			}
			$db->insert('',
				'{db_prefix}lgal_tag_items',
				array('id_tag' => 'int', 'id_item' => 'int'),
				$rows,
				array('id_item', 'id_tag')
			);
		}
	}

	public function getItemsByTagId($id_tag)
	{
		$db = database();

		$tag_name = '';
		$tag_slug = '';
		$item_ids = array();

		// This one is actually fairly straightforward; Model_ItemList will permission-check for us.
		// So all we need to do is grab the tags first.
		$request = $db->query('', '
			SELECT 
				lt.tag_name, lt.tag_slug, lti.id_item
			FROM {db_prefix}lgal_tags AS lt
				INNER JOIN {db_prefix}lgal_tag_items AS lti ON (lt.id_tag = lti.id_tag)
			WHERE lt.id_tag = {int:id_tag}',
			array(
				'id_tag' => $id_tag,
			)
		);
		while ($row = $db->fetch_row($request))
		{
			list ($tag_name, $tag_slug, $id) = $row;
			$item_ids[] = $id;
		}
		$db->free_result($request);

		if (empty($item_ids))
		{
			return array();
		}

		$itemList = LevGal_Bootstrap::getModel('LevGal_Model_ItemList');
		$items = $itemList->getItemsById($item_ids);

		return array(
			'tag_name' => $tag_name,
			'tag_slug' => $tag_slug,
			'items' => $items,
		);
	}

	public function getTagCloud()
	{
		global $scripturl;

		$db = database();

		$tags = array();

		$album_list = true;
		if (!allowedTo('lgal_manage'))
		{
			/** @var $albums \LevGal_Model_AlbumList */
			$albums = LevGal_Bootstrap::getModel('LevGal_Model_AlbumList');
			$album_list = $albums->getVisibleAlbums();
		}

		if (empty($album_list))
		{
			return array();
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
			array(
				'album_list' => $album_list,
				'approved' => 1,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$tags[$row['id_tag']] = array(
				'name' => $row['tag_name'],
				'url' => $scripturl . '?media/tag/' . (!empty($row['tag_slug']) ? $row['tag_slug'] . '.' . $row['id_tag'] : $row['id_tag']) . '/',
				'count' => (int) $row['count'],
			);
		}
		$db->free_result($request);

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
		if (!empty($modSettings['lgal_tag_items_list_more']))
		{
			$cloudTags = $this->getTagCloud();
			foreach ($cloudTags as $tag)
			{
				if ($tag['name'] !== $txt['levgal_tagcloud_none'] && !in_array($tag['name'], $tags, true))
				{
					$inUseTags[] = Util::htmlspecialchars($tag['name'], ENT_QUOTES);
				}
			}

			natsort($inUseTags);
			$tags = array_merge($tags, $inUseTags);
		}

		return $tags;
	}
}
