<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license proprietary
 *
 * @version 1.0 / elkarte
 */

/**
 * This file deals with handling notifications.
 */
class LevGal_Model_Notify
{
	public function getUserNotifyPref($user)
	{
		global $context, $user_settings;

		$db = database();

		if (empty($user))
		{
			return 0;
		}

		if ($context['id_member'] == $user)
		{
			// Current user's value is already available in $user_settings.
			$value = $user_settings['lgal_notify'];
		}
		else
		{
			// Non-current user's isn't, loadMemberData(..., ..., 'profile') doesn't load everything with *, but only the columns it knows are there.
			// So we have to go get this ourselves.
			$request = $db->query('', '
				SELECT lgal_notify
				FROM {db_prefix}members
				WHERE id_member = {int:id_member}',
				array(
					'id_member' => $user,
				)
			);
			list ($value) = $db->fetch_row($request);
			$db->free_result($request);
		}

		return $value;
	}

	public function getNotifyAlbumsForUser($user)
	{
		global $scripturl;

		$db = database();

		if (empty($user))
		{
			return array();
		}

		$album_list = true;
		if (!allowedTo('lgal_manage'))
		{
			$album_list_model = LevGal_Bootstrap::getModel('LevGal_Model_AlbumList');
			$album_list = $album_list_model->getVisibleAlbums();
		}

		$notifications = array();
		$request = $db->query('', '
			SELECT 
				ln.id_album, la.album_name, la.album_slug
			FROM {db_prefix}lgal_notify AS ln
				INNER JOIN {db_prefix}lgal_albums AS la ON (ln.id_album = la.id_album)
			WHERE ln.id_member = {int:user}
				AND ln.id_album > 0' . ($album_list !== true ? '
				AND ln.id_album IN ({array_int:album_list})' : '') . '
			ORDER BY la.album_name',
			array(
				'user' => $user,
				'album_list' => $album_list,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$notifications[$row['id_album']] = array(
				'name' => $row['album_name'],
				'url' => $scripturl . '?media/album/' . (!empty($row['album_slug']) ? $row['album_slug'] . '.' . $row['id_album'] : $row['id_album']) . '/',
			);
		}
		$db->free_result($request);

		return $notifications;
	}

	public function getNotifyItemsForUser($user)
	{
		global $scripturl;

		$db = database();

		if (empty($user))
		{
			return array();
		}

		$album_list = true;
		if (!allowedTo('lgal_manage'))
		{
			$album_list_model = LevGal_Bootstrap::getModel('LevGal_Model_AlbumList');
			$album_list = $album_list_model->getVisibleAlbums();
		}

		$notifications = array();
		$request = $db->query('', '
			SELECT 
				ln.id_item, li.item_name, li.item_slug, li.id_album, la.album_name, la.album_slug
			FROM {db_prefix}lgal_notify AS ln
				INNER JOIN {db_prefix}lgal_items AS li ON (ln.id_item = li.id_item)
				INNER JOIN {db_prefix}lgal_albums AS la ON (li.id_album = la.id_album)
			WHERE ln.id_member = {int:user}
				AND li.id_item > 0' . ($album_list !== true ? '
				AND li.id_album IN ({array_int:album_list})' : '') . '
			ORDER BY li.item_name',
			array(
				'user' => $user,
				'album_list' => $album_list,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$notifications[$row['id_item']] = array(
				'item_name' => $row['item_name'],
				'item_url' => $scripturl . '?media/item/' . (!empty($row['item_slug']) ? $row['item_slug'] . '.' . $row['id_item'] : $row['id_item']) . '/',
				'album_name' => $row['album_name'],
				'album_url' => $scripturl . '?media/album/' . (!empty($row['album_slug']) ? $row['album_slug'] . '.' . $row['id_album'] : $row['id_album']) . '/',
			);
		}
		$db->free_result($request);

		return $notifications;
	}

	public function getNotifyAlbumStatus($album, $user)
	{
		$db = database();

		$request = $db->query('', '
			SELECT 
				COUNT(id_member)
			FROM {db_prefix}lgal_notify
			WHERE id_member = {int:user}
				AND id_album = {int:album}',
			array(
				'user' => $user,
				'album' => $album,
			)
		);
		list ($count) = $db->fetch_row($request);
		$db->free_result($request);

		return $count != 0;
	}

	public function setNotifyAlbum($album, $user)
	{
		$db = database();

		$album = (array) $album;
		$rows = array();
		foreach ($album as $id_album)
		{
			$rows[] = array($user, $id_album, 0);
		}

		$db->insert('replace',
			'{db_prefix}lgal_notify',
			array('id_member' => 'int', 'id_album' => 'int', 'id_item' => 'int'),
			$rows,
			array('id_member', 'id_album')
		);
	}

	public function unsetNotifyAlbum($album, $user)
	{
		$db = database();

		$album = (array) $album;

		$db->query('', '
			DELETE FROM {db_prefix}lgal_notify
			WHERE id_member = {int:user}
				AND id_album IN ({array_int:album})',
			array(
				'user' => $user,
				'album' => $album,
			)
		);
	}

	public function unsetAllNotifyAlbum($album)
	{
		$db = database();

		if (empty($album))
		{
			return;
		}

		$album = (array) $album;

		$db->query('', '
			DELETE FROM {db_prefix}lgal_notify
			WHERE id_album IN ({array_int:album})',
			array(
				'album' => $album,
			)
		);
	}

	public function getNotifyItemStatus($item, $user)
	{
		$db = database();

		$request = $db->query('', '
			SELECT 
				COUNT(id_member)
			FROM {db_prefix}lgal_notify
			WHERE id_member = {int:user}
				AND id_item = {int:item}',
			array(
				'user' => $user,
				'item' => $item,
			)
		);
		list ($count) = $db->fetch_row($request);
		$db->free_result($request);

		return $count != 0;
	}

	public function setNotifyItem($item, $user)
	{
		$db = database();

		$item = (array) $item;
		$rows = array();
		foreach ($item as $id_item)
		{
			$rows[] = array($user, 0, $id_item);
		}

		$db->insert('replace',
			'{db_prefix}lgal_notify',
			array('id_member' => 'int', 'id_album' => 'int', 'id_item' => 'int'),
			$rows,
			array('id_member', 'id_item')
		);
	}

	public function unsetNotifyItem($item, $user)
	{
		$db = database();

		$item = (array) $item;

		$db->query('', '
			DELETE FROM {db_prefix}lgal_notify
			WHERE id_member = {int:user}
				AND id_item IN ({array_int:item})',
			array(
				'user' => $user,
				'item' => $item,
			)
		);
	}

	public function unsetAllNotifyItem($item)
	{
		$db = database();

		if (empty($item))
		{
			return;
		}

		$item = (array) $item;

		$db->query('', '
			DELETE FROM {db_prefix}lgal_notify
			WHERE id_item IN ({array_int:item})',
			array(
				'item' => $item,
			)
		);
	}

	public function removeAllNotifyForUser($user)
	{
		$db = database();

		$db->query('', '
			DELETE FROM {db_prefix}lgal_notify
			WHERE id_member = {int:user}',
			array(
				'user' => $user,
			)
		);
	}

	public function getNotifyForItem($item)
	{
		$db = database();

		// Get the people who opted into this notification - and are opted in to notification emails in their profile.
		$users = array();
		$request = $db->query('', '
			SELECT 
				ln.id_member
			FROM {db_prefix}lgal_notify AS ln
				INNER JOIN {db_prefix}members AS mem ON (ln.id_member = mem.id_member AND mem.lgal_notify = 1)
			WHERE id_item = {int:item}
				AND mem.is_activated < {int:banned_status}',
			array(
				'item' => $item,
				'banned_status' => 10,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$users[] = $row['id_member'];
		}
		$db->free_result($request);

		return $users;
	}

	public function getNotifyForAlbum($album)
	{
		$db = database();

		// Get the people who opted into this notification - and are opted in to notification emails in their profile.
		$users = array();
		$request = $db->query('', '
			SELECT
			 	ln.id_member
			FROM {db_prefix}lgal_notify AS ln
				INNER JOIN {db_prefix}members AS mem ON (ln.id_member = mem.id_member AND mem.lgal_notify = 1)
			WHERE id_album = {int:album}
				AND mem.is_activated < {int:banned_status}',
			array(
				'album' => $album,
				'banned_status' => 10,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$users[] = $row['id_member'];
		}
		$db->free_result($request);

		return $users;
	}
}
