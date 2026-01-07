<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0 / elkarte
 */

namespace Addons\Levertine\Source\Model;

use ElkArte\User;
use Addons\Levertine\Source\LevGalBootstrap;

/**
 * This file deals with handling notifications.
 */
class Notify
{
	public function getUserNotifyPref($user)
	{
		global $context;

		$db = database();

		if (empty($user))
		{
			return 0;
		}

		if ($context['id_member'] === $user)
		{
			// The current user's value is already available in User::$settings.
			$value = User::$settings['lgal_notify'];
		}
		else
		{
			// Non-current user's isn't, loadMemberData(..., ..., 'profile') doesn't load everything with *, but only the
			// columns it knows are there. So we have to go get this ourselves.
			$request = $db->query('', '
				SELECT 
					lgal_notify
				FROM {db_prefix}members
				WHERE id_member = {int:id_member}',
				[
					'id_member' => $user,
				]
			);
			[$value] = $request->fetch_row();
			$request->free_result();
		}

		return $value;
	}

	public function getSiteEnableNotifications()
	{
		global $modSettings;

		$enabledNotifications = ['lgcomment' => false, 'lgnew' => false, 'lglike' => false];
		if (!empty($modSettings['enabled_mentions']))
		{
			$check = explode(',', $modSettings['enabled_mentions']);
			$enabledNotifications['lgcomment'] = in_array('lgcomment', $check);
			$enabledNotifications['lgnew'] = in_array('lgnew', $check);
			$enabledNotifications['lglike'] = in_array('lglike', $check);
		}

		return $enabledNotifications;
	}

	public function getNotifyAlbumsForUser($user)
	{
		global $scripturl;

		$db = database();

		if (empty($user))
		{
			return [];
		}

		$album_list = true;
		if (!allowedTo('lgal_manage'))
		{
			$album_list_model = LevGalBootstrap::getModel('AlbumList');
			$album_list = $album_list_model->getVisibleAlbums();
		}

		$notifications = [];
		$request = $db->query('', '
			SELECT 
				ln.id_album, la.album_name, la.album_slug
			FROM {db_prefix}lgal_notify AS ln
				INNER JOIN {db_prefix}lgal_albums AS la ON (ln.id_album = la.id_album)
			WHERE ln.id_member = {int:user}
				AND ln.id_album > 0' . ($album_list !== true ? '
				AND ln.id_album IN ({array_int:album_list})' : '') . '
			ORDER BY la.album_name',
			[
				'user' => $user,
				'album_list' => $album_list,
			]
		);
		while ($row = $request->fetch_assoc())
		{
			$notifications[$row['id_album']] = [
				'name' => $row['album_name'],
				'url' => $scripturl . '?media/album/' . (!empty($row['album_slug']) ? $row['album_slug'] . '.' . $row['id_album'] : $row['id_album']) . '/',
			];
		}
		$request->free_result();

		return $notifications;
	}

	public function getNotifyItemsForUser($user)
	{
		global $scripturl;

		$db = database();

		if (empty($user))
		{
			return [];
		}

		$album_list = true;
		if (!allowedTo('lgal_manage'))
		{
			$album_list_model = LevGalBootstrap::getModel('AlbumList');
			$album_list = $album_list_model->getVisibleAlbums();
		}

		$notifications = [];
		$request = $db->query('', '
			SELECT 
				ln.id_item, li.item_name, li.item_slug, li.id_album, li.poster_name, la.album_name, la.album_slug
			FROM {db_prefix}lgal_notify AS ln
				INNER JOIN {db_prefix}lgal_items AS li ON (ln.id_item = li.id_item)
				INNER JOIN {db_prefix}lgal_albums AS la ON (li.id_album = la.id_album)
			WHERE ln.id_member = {int:user}
				AND li.id_item > 0' . ($album_list !== true ? '
				AND li.id_album IN ({array_int:album_list})' : '') . '
			ORDER BY li.item_name',
			[
				'user' => $user,
				'album_list' => $album_list,
			]
		);
		while ($row = $request->fetch_assoc())
		{
			$notifications[$row['id_item']] = [
				'item_name' => $row['item_name'],
				'poster_name' => $row['poster_name'],
				'item_url' => $scripturl . '?media/item/' . (!empty($row['item_slug']) ? $row['item_slug'] . '.' . $row['id_item'] : $row['id_item']) . '/',
				'album_name' => $row['album_name'],
				'album_url' => $scripturl . '?media/album/' . (!empty($row['album_slug']) ? $row['album_slug'] . '.' . $row['id_album'] : $row['id_album']) . '/',
			];
		}
		$request->free_result();

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
			[
				'user' => $user,
				'album' => $album,
			]
		);
		[$count] = $request->fetch_row();
		$request->free_result();

		return (int) $count !== 0;
	}

	public function setNotifyAlbum($album, $user)
	{
		$db = database();

		$album = (array) $album;
		$rows = [];
		foreach ($album as $id_album)
		{
			$rows[] = [$user, $id_album, 0];
		}

		$db->insert('replace',
			'{db_prefix}lgal_notify',
			['id_member' => 'int', 'id_album' => 'int', 'id_item' => 'int'],
			$rows,
			['id_member', 'id_album']
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
			[
				'user' => $user,
				'album' => $album,
			]
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
			[
				'album' => $album,
			]
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
			[
				'user' => $user,
				'item' => $item,
			]
		);
		[$count] = $request->fetch_row();
		$request->free_result();

		return (int) $count !== 0;
	}

	public function setNotifyItem($item, $user)
	{
		$db = database();

		$item = (array) $item;
		$rows = [];
		foreach ($item as $id_item)
		{
			$rows[] = [$user, 0, $id_item];
		}

		$db->insert('replace',
			'{db_prefix}lgal_notify',
			['id_member' => 'int', 'id_album' => 'int', 'id_item' => 'int'],
			$rows,
			['id_member', 'id_item']
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
			[
				'user' => $user,
				'item' => $item,
			]
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
			[
				'item' => $item,
			]
		);
	}

	public function removeAllNotifyForUser($user)
	{
		$db = database();

		$db->query('', '
			DELETE FROM {db_prefix}lgal_notify
			WHERE id_member = {int:user}',
			[
				'user' => $user,
			]
		);
	}

	public function getNotifyForItem($item)
	{
		$db = database();

		// Get the people who opted into this notification - and are opted in to notification emails in their profile.
		$users = [];
		$request = $db->query('', '
			SELECT 
				ln.id_member
			FROM {db_prefix}lgal_notify AS ln
				INNER JOIN {db_prefix}members AS mem ON (ln.id_member = mem.id_member AND mem.lgal_notify = 1)
			WHERE id_item = {int:item}
				AND mem.is_activated < {int:banned_status}',
			[
				'item' => $item,
				'banned_status' => 10,
			]
		);
		while ($row = $request->fetch_assoc())
		{
			$users[] = $row['id_member'];
		}
		$request->free_result();

		return $users;
	}

	public function getNotifyForAlbum($album)
	{
		$db = database();

		// Get the people who opted into this notification - and are opted in to notification emails in their profile.
		$users = [];
		$request = $db->query('', '
			SELECT
			 	ln.id_member
			FROM {db_prefix}lgal_notify AS ln
				INNER JOIN {db_prefix}members AS mem ON (ln.id_member = mem.id_member AND mem.lgal_notify = 1)
			WHERE id_album = {int:album}
				AND mem.is_activated < {int:banned_status}',
			[
				'album' => $album,
				'banned_status' => 10,
			]
		);
		while ($row = $request->fetch_assoc())
		{
			$users[] = $row['id_member'];
		}
		$request->free_result();

		return $users;
	}
}
