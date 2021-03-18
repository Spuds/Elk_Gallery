<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license proprietary
 *
 * @version 1.0 / elkarte
 */

/**
 * This file deals with getting information into and out of the search indexes.
 */
class LevGal_Model_Search
{
	/** @var array[] */
	private $search_criteria;

	public function emptyAlbumIndex()
	{
		$db = database();

		$db->query('', '
			TRUNCATE TABLE {db_prefix}lgal_search_album');
	}

	public function createAlbumEntries($rows)
	{
		$db = database();

		$db->insert('replace',
			'{db_prefix}lgal_search_album',
			array('id_album' => 'int', 'album_name' => 'string'),
			$rows,
			array('id_album', 'album_name')
		);
	}

	public function updateAlbumEntry($id_album, $album_name)
	{
		// At this point they are actually the same thing but in the future they might not be, but let's expose a sane sort of API first.
		$this->createAlbumEntries(array(array($id_album, $album_name)));
	}

	public function deleteAlbumEntries($id_albums)
	{
		$this->deleteFromIndex('album', $id_albums);
	}

	protected function prepareDescription($description)
	{
		global $modSettings;

		$current_value = !empty($modSettings['fixLongWords']) ? $modSettings['fixLongWords'] : 0;
		$modSettings['fixLongWords'] = 0;

		$description = preg_replace('~\s+~', ' ', preg_replace('~\<br(\s*)?/?\>~i', ' ', $description));
		$description = strip_tags(parse_bbc($description, true));

		$modSettings['fixLongWords'] = $current_value;

		return $description;
	}

	public function emptyItemIndex()
	{
		$db = database();

		$db->query('', '
			TRUNCATE TABLE {db_prefix}lgal_search_item');
	}

	public function createItemEntries($rows)
	{
		$db = database();

		foreach ($rows as $k => $row)
		{
			// We want the description to be bbc parsed and then only the actual text fed into the index. But we want to preserve line breaks as some kind of whitespace.
			$rows[$k]['description'] = $this->prepareDescription($row['description']);

			if (!in_array($row['item_type'], array('image', 'audio', 'video', 'document', 'archive', 'generic', 'external')))
			{
				$rows[$k]['item_type'] = 'generic';
			}
		}

		$db->insert('replace',
			'{db_prefix}lgal_search_item',
			array('id_item' => 'int', 'item_name' => 'string', 'description' => 'string', 'item_type' => 'string'),
			$rows,
			array('id_item')
		);
	}

	public function updateItemEntry($id_item, $item_name = null, $description = null, $item_type = null)
	{
		$db = database();

		$changes = array();
		if ($item_name !== null)
		{
			$changes['item_name'] = $item_name;
		}
		if ($description !== null)
		{
			$changes['description'] = $this->prepareDescription($description);
		}
		if ($item_type !== null)
		{
			$changes['item_type'] = in_array($item_type, array('image', 'audio', 'video', 'document', 'archive', 'generic', 'external')) ? $item_type : 'generic';
		}

		if (empty($changes))
		{
			return;
		}

		$criteria = array();
		foreach (array_keys($changes) as $column)
		{
			$criteria[] = $column . ' = {string:' . $column . '}';
		}

		// And we need the id_item
		$changes['id_item'] = $id_item;

		$db->query('', '
			UPDATE {db_prefix}lgal_search_item
			SET ' . implode(',', $criteria) . '
			WHERE id_item = {int:id_item}',
			$changes
		);
	}

	public function deleteItemEntries($id_items)
	{
		$this->deleteFromIndex('item', $id_items);
	}

	protected function deleteFromIndex($index, $entries)
	{
		$db = database();

		$entries = (array) $entries;

		$indexes = array(
			'album' => array('table' => '{db_prefix}lgal_search_album', 'column' => 'id_album'),
			'item' => array('table' => '{db_prefix}lgal_search_item', 'column' => 'id_item'),
		);

		if (empty($entries) || !isset($indexes[$index]))
		{
			return;
		}

		$db->query('', '
			DELETE FROM ' . $indexes[$index]['table'] . '
			WHERE ' . $indexes[$index]['column'] . ' IN ({array_int:entries})',
			array(
				'entries' => $entries,
			)
		);
	}

	public function addCriteria($criteria, $value)
	{
		$this->search_criteria[$criteria] = $value;
	}

	public function performSearch()
	{
		global $context;

		$db = database();

		// While we have this at our disposal, the reality is that we will be storing things later.
		$data = $this->search_criteria;

		// Are we searching album names?
		if (!empty($this->search_criteria['search_album_names']))
		{
			$request = $db->query('', '
				SELECT 
					id_album, MATCH(album_name) AGAINST ({string:terms}) AS score
				FROM {db_prefix}lgal_search_album
				WHERE MATCH(album_name) AGAINST ({string:terms})
					AND id_album IN ({array_int:albums})
				ORDER BY score DESC',
				array(
					'terms' => $data['search_text'],
					'albums' => $data['selected_albums'],
				)
			);
			while ($row = $db->fetch_assoc($request))
			{
				$data['results']['albums'][] = (int) $row['id_album'];
			}
			$db->free_result($request);
		}

		// Searching item names/descriptions
		// We get the score from MySQL but since we do other stuff... it's not really used. Maybe it might be in the future.
		if (!empty($this->search_criteria['search_item_names']))
		{
			$request = $db->query('', '
				SELECT 
					lsi.id_item, MATCH(lsi.item_name) AGAINST ({string:terms}) AS score
				FROM {db_prefix}lgal_search_item AS lsi
					INNER JOIN {db_prefix}lgal_items AS li ON (li.id_item = lsi.id_item)
				WHERE MATCH(lsi.item_name) AGAINST ({string:terms})
					AND id_album IN ({array_int:albums})
					AND item_type IN ({array_string:types})' . (!empty($data['search_member']) ? '
					AND li.id_member IN ({array_int:search_member})' : '') . '
				ORDER BY score DESC',
				array(
					'terms' => $data['search_text'],
					'albums' => $data['selected_albums'],
					'types' => $data['selected_search_types'],
					'search_member' => !empty($data['search_member']) ? $data['search_member'] : array(),
				)
			);
			while ($row = $db->fetch_assoc($request))
			{
				$data['results']['items'][] = (int) $row['id_item'];
			}
			$db->free_result($request);
		}

		if (!empty($this->search_criteria['search_item_descs']))
		{
			$request = $db->query('', '
				SELECT 
					lsi.id_item, MATCH(lsi.description) AGAINST ({string:terms}) AS score
				FROM {db_prefix}lgal_search_item AS lsi
					INNER JOIN {db_prefix}lgal_items AS li ON (li.id_item = lsi.id_item)
				WHERE MATCH(lsi.description) AGAINST ({string:terms})
					AND id_album IN ({array_int:albums})
					AND item_type IN ({array_string:types})' . (!empty($data['search_member']) ? '
					AND li.id_member IN ({array_int:search_member})' : '') . '
				ORDER BY score DESC',
				array(
					'terms' => $data['search_text'],
					'albums' => $data['selected_albums'],
					'types' => $data['selected_search_types'],
					'search_member' => !empty($data['search_member']) ? $data['search_member'] : array(),
				)
			);
			while ($row = $db->fetch_assoc($request))
			{
				$data['results']['items'][] = (int) $row['id_item'];
			}
			$db->free_result($request);
		}

		// Now searching by way of custom fields.
		if (!empty($this->search_criteria['selected_fields']))
		{
			$request = $db->query('', '
				SELECT 
					lcfd.id_item
				FROM {db_prefix}lgal_custom_field_data AS lcfd
					INNER JOIN {db_prefix}lgal_items AS li ON (li.id_item = lcfd.id_item)
					INNER JOIN {db_prefix}lgal_search_item AS lsi ON (lcfd.id_item = lsi.id_item)
				WHERE li.id_album IN ({array_int:albums})
					AND lcfd.id_field IN ({array_int:fields})
					AND lcfd.value LIKE {string:terms}
					AND lsi.item_type IN ({array_string:types})' . (!empty($data['search_member']) ? '
					AND li.id_member IN ({array_int:search_member})' : ''),
				array(
					'terms' => '%' . preg_replace('~\s+~', '%', strtr($data['search_text'], array('_' => '\\_', '%' => '\\%', '*' => '%'))) . '%',
					'albums' => $data['selected_albums'],
					'fields' => $data['selected_fields'],
					'types' => $data['selected_search_types'],
					'search_member' => !empty($data['search_member']) ? $data['search_member'] : array(),
				)
			);
			while ($row = $db->fetch_assoc($request))
			{
				$data['results']['items'][] = (int) $row['id_item'];
			}
			$db->free_result($request);
		}

		if (!empty($data['results']['items']))
		{
			$data['results']['items'] = array_unique($data['results']['items']);
			rsort($data['results']['items']);
		}

		// Now shove it into the table.
		$db->insert('',
			'{db_prefix}lgal_search_results',
			array('id_member' => 'int', 'timestamp' => 'int', 'searchdata' => 'string'),
			array($context['user']['id'], time(), serialize($data)),
			array('id_search')
		);

		$id = $db->insert_id('{db_prefix}lgal_search_results');

		return array(!empty($id) ? $id : false, !empty($data['results']));
	}

	public function fetchSearchResult($id)
	{
		global $context;

		$db = database();

		$request = $db->query('', '
			SELECT searchdata
			FROM {db_prefix}lgal_search_results
			WHERE id_search = {int:id_search}
				AND id_member = {int:id_member}',
			array(
				'id_search' => $id,
				'id_member' => $context['user']['id'],
			)
		);

		if ($db->num_rows($request) != 0)
		{
			list ($data) = $db->fetch_row($request);
			$result = @unserialize($data);
		}
		$db->free_result($request);

		return !empty($result) ? $result : arrya();
	}

	public function deleteExistingSearches()
	{
		$db = database();

		$db->query('', '
			TRUNCATE TABLE {db_prefix}lgal_search_results');
	}

	public function deleteSearchesBeforeTimestamp($timestamp)
	{
		$db = database();

		$db->query('', '
			DELETE FROM {db_prefix}lgal_search_results
			WHERE timestamp < {int:timestamp}',
			array(
				'timestamp' => $timestamp,
			)
		);
	}
}
