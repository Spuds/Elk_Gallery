<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.0 / elkarte
 */

use BBC\ParserWrapper;

/**
 * This file deals with getting information about the moderation area.
 */
class LevGal_Model_Moderate
{
	/** @var int  */
	public const SESSION_THRESHOLD = 120; // Indicates TTL on unapproved counts. Too much? Not enough?

	public function getUnapprovedCommentsCount()
	{
		global $user_info;

		$db = database();

		if (isset($_SESSION['lgal_uc']) && (time() - $_SESSION['lgal_uc']['t'] < self::SESSION_THRESHOLD))
		{
			return $_SESSION['lgal_uc']['n'];
		}

		$unapproved = 0;
		$album_list = LevGal_Bootstrap::getModel('LevGal_Model_AlbumList');
		$albums = $album_list->getVisibleAlbums();

		$viewing_all = allowedTo(array('lgal_manage', 'lgal_approve_item'));

		if (!empty($albums))
		{
			// We are only interested in approved items with unapproved comments.
			$request = $db->query('', '
				SELECT 
					COUNT(id_comment)
				FROM {db_prefix}lgal_comments AS lc
					INNER JOIN {db_prefix}lgal_items AS li ON (lc.id_item = li.id_item)
				WHERE lc.approved = {int:not_approved}
					AND li.id_album IN ({array_int:albums})
					AND li.approved = {int:approved}' . (!$viewing_all ? '
					AND li.id_member = {int:current_member}' : ''),
				array(
					'not_approved' => 0,
					'approved' => 1,
					'albums' => $albums,
					'current_member' => $user_info['id'],
				)
			);
			list ($unapproved) = $db->fetch_row($request);
			$db->free_result($request);
		}

		$_SESSION['lgal_uc'] = array(
			'n' => $unapproved,
			't' => time(),
		);

		return $_SESSION['lgal_uc']['n'];
	}

	public function getUnapprovedItemsCount()
	{
		$db = database();

		if (isset($_SESSION['lgal_ui']) && (time() - $_SESSION['lgal_ui']['t'] < self::SESSION_THRESHOLD))
		{
			return $_SESSION['lgal_ui']['n'];
		}

		$unapproved = 0;
		// If we have extended permissions, we can approve items anywhere, otherwise we're only interested in our own albums.
		$viewing_all = allowedTo(array('lgal_manage', 'lgal_approve_item'));
		$album_list = LevGal_Bootstrap::getModel('LevGal_Model_AlbumList');
		$albums = $viewing_all ? $album_list->getVisibleAlbums() : $album_list->getUserAlbums();

		if (!empty($albums))
		{
			// We are only interested in approved items with unapproved comments.
			$request = $db->query('', '
				SELECT 
					COUNT(id_item)
				FROM {db_prefix}lgal_items AS li
				WHERE li.approved = {int:not_approved}
					AND li.id_album IN ({array_int:albums})',
				array(
					'not_approved' => 0,
					'albums' => $albums,
				)
			);
			list ($unapproved) = $db->fetch_row($request);
			$db->free_result($request);
		}

		$_SESSION['lgal_ui'] = array(
			'n' => $unapproved,
			't' => time(),
		);

		return $_SESSION['lgal_ui']['n'];
	}

	public function getUnapprovedAlbumsCount()
	{
		$db = database();

		// We shouldn't use this for the main menu but we can it for the moderation area.
		// And because it's infrequently used, we can also safely not worry about caching so much.
		$request = $db->query('', '
			SELECT 
				COUNT(*)
			FROM {db_prefix}lgal_albums
			WHERE approved = {int:not_approved}',
			array(
				'not_approved' => 0,
			)
		);
		list ($unapproved) = $db->fetch_row($request);
		$db->free_result($request);

		return $unapproved;
	}

	public function getVisibleUnapprovedComments($start, $limit, $order, $albums)
	{
		global $user_info, $scripturl;

		$db = database();

		// If can approve anything, it doesn't have to be just the user's own items
		$viewing_all = allowedTo(array('lgal_manage', 'lgal_approve_comment'));
		$view_profile = allowedTo('profile_view_any');

		// We are only interested in approved items with unapproved comments.
		$request = $db->query('', '
			SELECT 
				lc.id_comment, lc.id_item, li.item_name, li.item_slug, li.id_member AS item_poster, mem.id_member,
				IFNULL(mem.real_name, lc.author_name) AS author_name, lc.time_added, la.id_album, la.album_name,
				la.album_slug, lc.comment
			FROM {db_prefix}lgal_comments AS lc
				INNER JOIN {db_prefix}lgal_items AS li ON (lc.id_item = li.id_item)
				LEFT JOIN {db_prefix}members AS mem ON (lc.id_author = mem.id_member)
				INNER JOIN {db_prefix}lgal_albums AS la ON (la.id_album = li.id_album)
			WHERE lc.approved = {int:not_approved}' . ($albums !== true ? '
				AND li.id_album IN ({array_int:albums})' : '') . (!$viewing_all ? '
				AND li.id_member = {int:current_member}' : '') . '
				AND li.approved = {int:approved}
			ORDER BY lc.id_comment ' . ($order === 'desc' ? 'DESC' : 'ASC') . '
			LIMIT {int:start}, {int:limit}',
			array(
				'start' => $start,
				'limit' => $limit,
				'not_approved' => 0,
				'approved' => 1,
				'albums' => $albums,
				'current_member' => $user_info['id'],
			)
		);
		$comments = array();
		$parser = ParserWrapper::instance();
		while ($row = $db->fetch_assoc($request))
		{
			$comments[$row['id_comment']] = array(
				'comment_url' => $scripturl . '?media/comment/' . $row['id_comment'] . '/',
				'author' => $view_profile && !empty($row['id_member']) ? '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['author_name'] . '</a>' : $row['author_name'],
				'album_name' => $row['album_name'],
				'album_url' => $scripturl . '?media/album/' . (!empty($row['album_slug']) ? $row['album_slug'] . '.' . $row['id_album'] : $row['id_album']) . '/',
				'item_name' => $row['item_name'],
				'item_url' => $scripturl . '?media/item/' . (!empty($row['item_slug']) ? $row['item_slug'] . '.' . $row['id_item'] : $row['id_item']) . '/',
				'time_added' => $row['time_added'],
				'time_added_format' => LevGal_Helper_Format::time($row['time_added']),
				'comment_body' => $parser->parseMessage($row['comment'], true),
			);
		}
		$db->free_result($request);

		return $comments;
	}

	public function getVisibleUnapprovedItems($start, $limit, $order, $albums)
	{
		global $scripturl;

		$db = database();

		$view_profile = allowedTo('profile_view_any');
		$items = array();

		$request = $db->query('', '
			SELECT 
				li.id_item, li.item_name, li.item_slug, mem.id_member, IFNULL(mem.real_name, li.poster_name) AS poster_name,
				li.time_added, la.id_album, la.album_name, la.album_slug
			FROM {db_prefix}lgal_items AS li
				LEFT JOIN {db_prefix}members AS mem ON (li.id_member = mem.id_member)
				INNER JOIN {db_prefix}lgal_albums AS la ON (li.id_album = la.id_album)
			WHERE li.approved = {int:not_approved}' . ($albums !== true ? '
				AND li.id_album IN ({array_int:albums})' : '') . '
			ORDER BY li.id_item ' . ($order === 'desc' ? 'DESC' : 'ASC') . '
			LIMIT {int:start}, {int:limit}',
			array(
				'start' => $start,
				'limit' => $limit,
				'not_approved' => 0,
				'albums' => $albums,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$items[$row['id_item']] = array(
				'author' => $view_profile && !empty($row['id_member']) ? '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['poster_name'] . '</a>' : $row['poster_name'],
				'album_name' => $row['album_name'],
				'album_url' => $scripturl . '?media/album/' . (!empty($row['album_slug']) ? $row['album_slug'] . '.' . $row['id_album'] : $row['id_album']) . '/',
				'item_name' => $row['item_name'],
				'item_url' => $scripturl . '?media/item/' . (!empty($row['item_slug']) ? $row['item_slug'] . '.' . $row['id_item'] : $row['id_item']) . '/',
				'time_added' => $row['time_added'],
				'time_added_format' => LevGal_Helper_Format::time($row['time_added']),
			);
		}
		$db->free_result($request);

		return $items;
	}

	public function getVisibleUnapprovedAlbums($start, $limit, $order, $albums)
	{
		global $scripturl, $user_profile, $context;

		$db = database();

		$view_profile = allowedTo('profile_view_any');
		$unapproved_albums = array();

		$request = $db->query('', '
			SELECT 
				la.id_album, la.album_name, la.album_slug, la.owner_cache, la.description
			FROM {db_prefix}lgal_albums AS la
			WHERE la.approved = {int:not_approved}' . ($albums !== true ? '
				AND la.id_album IN ({array_int:albums})' : '') . '
			ORDER BY la.id_album ' . ($order === 'desc' ? 'DESC' : 'ASC') . '
			LIMIT {int:start}, {int:limit}',
			array(
				'start' => $start,
				'limit' => $limit,
				'not_approved' => 0,
				'albums' => $albums,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$unapproved_albums[$row['id_album']] = array(
				'album_name' => $row['album_name'],
				'description' => $row['description'],
				'album_url' => $scripturl . '?media/album/' . (!empty($row['album_slug']) ? $row['album_slug'] . '.' . $row['id_album'] : $row['id_album']) . '/',
				'owner_cache' => Util::unserialize($row['owner_cache']),
				'owner' => array(),
			);
		}
		$db->free_result($request);

		// Getting the owners.
		$members = array();
		$groups = array();
		foreach ($unapproved_albums as $id_album => $album)
		{
			if (!empty($album['owner_cache']['member']))
			{
				if (in_array(0, $album['owner_cache']['member'], true))
				{
					$unapproved_albums[$id_album]['owner'] = array($context['forum_name']);
					unset ($unapproved_albums[$id_album]['owner_cache']);
					continue;
				}
				$members = array_merge($members, $album['owner_cache']['member']);
			}
			elseif (!empty($album['owner_cache']['group']))
			{
				$groups = array_merge($groups, $album['owner_cache']['group']);
			}
		}
		if (!empty($members))
		{
			loadMemberData($members, false, 'minimal');
		}
		if (!empty($groups))
		{
			$groupModel = new LevGal_Model_Group();
			$group_data = $groupModel->getGroupsById($groups);
		}

		foreach ($unapproved_albums as $id_album => $album)
		{
			if (!empty($album['owner_cache']['member']))
			{
				foreach ($album['owner_cache']['member'] as $member)
				{
					if (isset($user_profile[$member]))
					{
						$unapproved_albums[$id_album]['owner'][] = $view_profile ? '<a href="' . $scripturl . '?action=profile;u=' . $member . '">' . $user_profile[$member]['real_name'] . '</a>' : $user_profile[$member]['real_name'];
					}
				}
			}
			elseif (!empty($album['owner_cache']['group']))
			{
				foreach ($album['owner_cache']['group'] as $group)
				{
					if (isset($group_data[$group]))
					{
						$unapproved_albums[$id_album]['owner'][] = $group_data[$group]['color_name'];
					}
				}
			}

			unset ($unapproved_albums[$id_album]['owner_cache']);
		}

		return $unapproved_albums;
	}

	public function getReportedComments($start, $limit, $order, $state = 'open')
	{
		global $scripturl;

		$db = database();

		$view_profile = allowedTo('profile_view_any');
		$reports = array();

		// At least we don't have to do permissions tests; this stuff is lgal_manage only so they can see everything.
		$request = $db->query('', '
			SELECT 
				lr.id_report, lr.id_comment, lr.id_item, lr.id_album, IFNULL(mem.id_member, lr.content_id_poster) AS content_id_poster,
				IFNULL(mem.real_name, lr.content_poster_name) AS content_poster_name, lr.body, lr.time_started, lr.time_updated, lr.num_reports,
				li.item_name, li.item_slug, la.id_album, la.album_name, la.album_slug, lc.time_added AS comment_time, lc.comment
			FROM {db_prefix}lgal_reports AS lr
				LEFT JOIN {db_prefix}members AS mem ON (lr.content_id_poster = mem.id_member)
				INNER JOIN {db_prefix}lgal_items AS li ON (lr.id_item = li.id_item)
				INNER JOIN {db_prefix}lgal_albums AS la ON (li.id_album = la.id_album)
				iNNER JOIN {db_prefix}lgal_comments AS lc ON (lr.id_comment = lc.id_comment)
			WHERE lr.closed = {int:closed_state}
				AND lr.id_comment != 0
			ORDER BY lr.id_report ' . ($order === 'desc' ? 'DESC' : 'ASC') . '
			LIMIT {int:start}, {int:limit}',
			array(
				'start' => $start,
				'limit' => $limit,
				'closed_state' => $state === 'open' ? 0 : 1,
			)
		);
		$parser = ParserWrapper::instance();
		while ($row = $db->fetch_assoc($request))
		{
			$reports[$row['id_report']] = array(
				'author' => $view_profile && !empty($row['content_id_poster']) ? '<a href="' . $scripturl . '?action=profile;u=' . $row['content_id_poster'] . '">' . $row['content_poster_name'] . '</a>' : $row['content_poster_name'],
				'report_url' => $scripturl . '?media/moderate/' . $row['id_report'] . '/report/',
				'comment_url' => $scripturl . '?media/comment/' . $row['id_comment'] . '/',
				'item_name' => $row['item_name'],
				'item_url' => $scripturl . '?media/item/' . (!empty($row['item_slug']) ? $row['item_slug'] . '.' . $row['id_item'] : $row['id_item']) . '/',
				'album_name' => $row['album_name'],
				'album_url' => $scripturl . '?media/album/' . (!empty($row['album_slug']) ? $row['album_slug'] . '.' . $row['id_album'] : $row['id_album']) . '/',
				'comment' => $parser->parseMessage($row['comment'], true),
				'comment_time' => $row['comment_time'],
				'comment_time_format' => LevGal_Helper_Format::time($row['comment_time']),
				'time_started' => $row['time_started'],
				'time_started_format' => LevGal_Helper_Format::time($row['time_started']),
				'time_updated' => $row['time_updated'],
				'time_updated_format' => LevGal_Helper_Format::time($row['time_updated']),
			);
		}
		$db->free_result($request);

		return $reports;
	}

	public function getReportedItems($start, $limit, $order, $state = 'open', $detailed = false)
	{
		global $scripturl;

		$db = database();

		$view_profile = allowedTo('profile_view_any');
		$reports = array();

		// At least we don't have to do permissions tests; this stuff is lgal_manage only so they can see everything.
		$request = $db->query('', '
			SELECT 
				lr.id_report, lr.id_item, lr.id_album, IFNULL(mem.id_member, lr.content_id_poster) AS content_id_poster,
				IFNULL(mem.real_name, lr.content_poster_name) AS content_poster_name, lr.body, lr.time_started, lr.time_updated, lr.num_reports,
				li.item_name, li.item_slug, la.id_album, la.album_name, la.album_slug, li.time_added
			FROM {db_prefix}lgal_reports AS lr
				LEFT JOIN {db_prefix}members AS mem ON (lr.content_id_poster = mem.id_member)
				INNER JOIN {db_prefix}lgal_items AS li ON (lr.id_item = li.id_item)
				INNER JOIN {db_prefix}lgal_albums AS la ON (li.id_album = la.id_album)
			WHERE lr.closed = {int:closed_state}
				AND lr.id_comment = 0
			ORDER BY lr.id_report ' . ($order === 'desc' ? 'DESC' : 'ASC') . '
			LIMIT {int:start}, {int:limit}',
			array(
				'start' => $start,
				'limit' => $limit,
				'closed_state' => $state === 'open' ? 0 : 1,
			)
		);
		$item_ids = array();
		while ($row = $db->fetch_assoc($request))
		{
			$item_ids[] = $row['id_item'];
			$reports[$row['id_report']] = array(
				'author' => $view_profile && !empty($row['content_id_poster']) ? '<a href="' . $scripturl . '?action=profile;u=' . $row['content_id_poster'] . '">' . $row['content_poster_name'] . '</a>' : $row['content_poster_name'],
				'report_url' => $scripturl . '?media/moderate/' . $row['id_report'] . '/report/',
				'id_item' => $row['id_item'],
				'item_name' => $row['item_name'],
				'item_url' => $scripturl . '?media/item/' . (!empty($row['item_slug']) ? $row['item_slug'] . '.' . $row['id_item'] : $row['id_item']) . '/',
				'album_name' => $row['album_name'],
				'album_url' => $scripturl . '?media/album/' . (!empty($row['album_slug']) ? $row['album_slug'] . '.' . $row['id_album'] : $row['id_album']) . '/',
				'time_added' => $row['time_started'],
				'time_added_format' => LevGal_Helper_Format::time($row['time_added']),
				'time_started' => $row['time_started'],
				'time_started_format' => LevGal_Helper_Format::time($row['time_started']),
				'time_updated' => $row['time_updated'],
				'time_updated_format' => LevGal_Helper_Format::time($row['time_updated']),
			);
		}
		$db->free_result($request);

		// If doing the detailed list, we want thumbnails and descriptions.
		if ($detailed)
		{
			$itemList = new LevGal_Model_ItemList();
			$items = $itemList->getItemsById($item_ids);
			$descriptions = $itemList->getItemDescriptionsById($item_ids);
			foreach ($reports as $id_report => $report)
			{
				$reports[$id_report]['thumbnail'] = !empty($items[$report['id_item']]['thumbnail']) ? $items[$report['id_item']]['thumbnail'] : '';
				$reports[$id_report]['description'] = !empty($descriptions[$report['id_item']]) ? $descriptions[$report['id_item']] : '';
			}
		}

		return $reports;
	}
}
