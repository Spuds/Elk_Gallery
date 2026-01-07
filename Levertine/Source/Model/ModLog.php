<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.0 / elkarte
 */

/**
 * This file deals with logging and accessing the moderation log for media items.
 */
class LevGal_Model_ModLog
{
	public static function logEvent($event, $event_details = array())
	{
		self::logEvents(array(array('event' => $event, 'details' => $event_details,)));
	}

	public static function logEvents($events)
	{
		global $user_info;

		$db = database();

		$inserts = array();
		$time = time();
		foreach ($events as $id_event => $event)
		{
			if (empty($event['event']))
			{
				unset ($events[$id_event]);
				continue;
			}

			// Prise some stuff out of the details if provided.
			$album = isset($event['details']['id_album']) ? LevGal_Bootstrap::clamp((int) $event['details']['id_album'], 0, 16777215) : 0;
			unset ($event['details']['id_album']);

			$item = isset($event['details']['id_item']) ? LevGal_Bootstrap::clamp((int) $event['details']['id_item'], 0, 0x7FFFFFFF) : 0;
			unset ($event['details']['id_item']);

			$comment = isset($event['details']['id_comment']) ? LevGal_Bootstrap::clamp((int) $event['details']['id_comment'], 0, 0x7FFFFFFF) : 0;
			unset ($event['details']['id_comment']);

			$inserts[] = array(
				$time, $user_info['id'], $user_info['ip'], $event['event'], $album, $item, $comment, serialize($event['details']),
			);
		}

		if (!empty($inserts))
		{
			$db->insert('insert',
				'{db_prefix}lgal_log_events',
				array(
					'timestamp' => 'int', 'id_member' => 'int', 'ip' => 'string', 'event' => 'string', 'id_album' => 'int', 'id_item' => 'int', 'id_comment' => 'int', 'details' => 'string',
				),
				$inserts,
				array('id_event')
			);
		}
	}

	public static function getCountItems()
	{
		$db = database();

		$request = $db->query('', '
			SELECT 
			    COUNT(id_event)
			FROM {db_prefix}lgal_log_events');
		list ($count) = $db->fetch_row($request);
		$db->free_result($request);

		return $count;
	}

	public static function getItems($start, $items_per_page, $sort)
	{
		global $txt, $scripturl;

		$db = database();

		// We may have this, we may not. Get it anyway.
		loadLanguage('levgal_lng/LevGal-ModLog');

		$seeIP = allowedTo('moderate_forum');
		$entries = array();

		$request = $db->query('', '
			SELECT
				le.id_event, mem.id_member, le.ip, le.timestamp, le.event, le.id_album, la.id_album AS match_id_album, le.id_item, li.id_item AS match_id_item,
				le.id_comment, le.details, mem.real_name, mg.group_name, la.album_name, la.album_slug, li.item_name, li.item_slug
			FROM {db_prefix}lgal_log_events AS le
				LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = le.id_member)
				LEFT JOIN {db_prefix}membergroups AS mg ON (mg.id_group = CASE WHEN mem.id_group = {int:reg_group_id} THEN mem.id_post_group ELSE mem.id_group END)
				LEFT JOIN {db_prefix}lgal_albums AS la ON (la.id_album = le.id_album)
				LEFT JOIN {db_prefix}lgal_items AS li ON (li.id_item = le.id_item)
			ORDER BY {raw:sort}
			LIMIT {int:start}, {int:limit}',
			array(
				'reg_group_id' => 0,
				'sort' => $sort,
				'start' => $start,
				'limit' => $items_per_page,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$row['details'] = !empty($row['details']) ? Util::unserialize($row['details']) : array();
			if (!empty($row['id_album']))
			{
				// An album was indicated but does it still exist?
				if (!empty($row['match_id_album']))
				{
					$row['album_url'] = $scripturl . '?media/album/' . (!empty($row['album_slug']) ? $row['album_slug'] . '.' . $row['id_album'] : $row['id_album']) . '/';
					$row['album_link'] = '<a href="' . $row['album_url'] . '">' . $row['album_name'] . '</a>';
				}
				else
				{
					$row['album_link'] = sprintf($txt['lgal_modlog_album_deleted'], $row['id_album']);
				}
			}

			if (!empty($row['id_item']))
			{
				// An item was indicated but still exists?
				if (!empty($row['match_id_item']))
				{
					$row['item_url'] = $scripturl . '?media/item/' . (!empty($row['item_slug']) ? $row['item_slug'] . '.' . $row['id_item'] : $row['id_item']) . '/';
					$row['item_link'] = '<a href="' . $row['item_url'] . '">' . $row['item_name'] . '</a>';
				}
				else
				{
					$row['item_link'] = sprintf($txt['lgal_modlog_item_deleted'], $row['id_item']);
				}
			}
			if (!empty($row['id_comment']))
			{
				// The item still has to exist for this to work.
				if (!empty($row['match_id_item']))
				{
					$row['comment_url'] = $scripturl . '?media/comment/' . $row['id_comment'] . '/';
					$row['item_url'] = $scripturl . '?media/item/' . (!empty($row['item_slug']) ? $row['item_slug'] . '.' . $row['id_item'] : $row['id_item']) . '/';
					$row['item_link'] = '<a href="' . $row['item_url'] . '">' . $row['item_name'] . '</a>';
				}
				else
				{
					$row['item_link'] = sprintf($txt['lgal_modlog_item_deleted'], $row['id_item']);
					$row['event'] = 'approve_comment_deleted';
				}
			}

			$event_text = $txt['lgal_ev_' . $row['event']] ?? $row['event'];
			if (preg_match_all('~\{([a-z_]+)\}~i', $event_text, $matches))
			{
				$replace = array();
				foreach ($matches[1] as $match)
				{
					$replace['{' . $match . '}'] = $row[$match] ?? $row['details'][$match] ?? $match;
				}
				$event_text = strtr($event_text, $replace);
			}

			$entries[$row['id_event']] = array(
				'id' => $row['id_event'],
				'ip' => $seeIP ? $row['ip'] : $txt['logged'],
				'member' => !empty($row['id_member']) && !empty($row['real_name']) ? '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['real_name'] . '</a>' : $txt['guest'],
				'position' => empty($row['real_name']) && empty($row['group_name']) ? $txt['guest'] : $row['group_name'],
				'time' => LevGal_Helper_Format::time($row['timestamp']),
				'timestamp' => forum_time(true, $row['timestamp']),
				'event' => $row['event'],
				'event_text' => $event_text,
			);
		}
		$db->free_result($request);

		return $entries;
	}

	public static function emptyLog()
	{
		$db = database();

		$db->query('truncate_table', '
			TRUNCATE {db_prefix}lgal_log_events');
	}

	public static function removeItems($items)
	{
		$db = database();

		$db->query('', '
			DELETE FROM {db_prefix}lgal_log_events
			WHERE id_event IN ({array_string:delete_actions})',
			array(
				'delete_actions' => array_unique($items),
			)
		);
	}
}
