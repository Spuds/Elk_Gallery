<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0 / elkarte
 */

namespace Addons\Levertine\Source\Model;

use Addons\Levertine\Source\Helper\Format;
use ElkArte\Cache\Cache;
use ElkArte\User;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * This file deals with getting information about the gallery as a whole from a statistical point of view.
 */
class Stats
{
	/** @var AlbumList */
	private $album_list_model;

	/** @var string  */
	private $groups;

	public function __construct()
	{
		$groups = User::$info['groups'];
		sort($groups);
		$this->groups = implode('-', $groups);
	}

	protected function getAlbumList()
	{
		if (empty($this->album_list_model))
		{
			$this->album_list_model = new AlbumList();
		}
	}

	protected function getVisibleAlbums()
	{
		if (allowedTo('lgal_manage'))
		{
			return true;
		}

		$this->getAlbumList();

		return $this->album_list_model->getVisibleAlbums();
	}

	public function getInstalledTime()
	{
		global $modSettings;

		return [
			'timestamp' => $modSettings['lgal_installed'],
			'time_formatted' => Format::time($modSettings['lgal_installed']),
		];
	}

	public function timeSinceInstall()
	{
		$time = $this->getInstalledTime();

		return time() - $time['timestamp'];
	}

	public function getTotalItems()
	{
		global $modSettings;

		return !empty($modSettings['lgal_total_items']) ? $modSettings['lgal_total_items'] : 0;
	}

	public function getTotalComments()
	{
		global $modSettings;

		return !empty($modSettings['lgal_total_comments']) ? $modSettings['lgal_total_comments'] : 0;
	}

	public function getTotalAlbums()
	{
		// This gets all the albums that exist, whether or not they are visible.
		$this->getAlbumList();

		return $this->album_list_model->getAlbumCount();
	}

	public function getTotalGallerySize()
	{
		global $modSettings;

		if (!class_exists('RecursiveIteratorIterator'))
		{
			return false;
		}

		if (($temp = Cache::instance()->get('lgal_file_size', 500)) === null)
		{
			$temp = 0;
			$path = realpath(strtr($modSettings['lgal_dir'] . '/files/', ['$boarddir' => BOARDDIR]));
			$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
			foreach ($objects as $name => $object)
			{
				if (!is_dir($name))
				{
					$temp += $object->getSize();
				}
			}
			Cache::instance()->put('lgal_file_size', $temp, 500);
		}

		return $temp;
	}

	public function getTopPosters()
	{
		global $scripturl;

		$db = database();

		$cache_ttl = 450;
		if (($temp = Cache::instance()->get('lgal_top_posters', $cache_ttl)) === null)
		{
			$request = $db->query('', '
				SELECT 
				    mem.id_member, mem.real_name, COUNT(li.id_item) AS count
				FROM {db_prefix}lgal_items AS li
					INNER JOIN {db_prefix}members AS mem ON (li.id_member = mem.id_member)
				WHERE li.id_member != 0
					AND li.approved = 1
				GROUP BY mem.id_member
				ORDER BY count DESC
				LIMIT 10');
			$max = 0;
			$temp = [];
			while ($row = $request->fetch_assoc())
			{
				$temp[] = $row;
				if ($row['count'] > $max)
				{
					$max = (int) $row['count'];
				}
			}
			$request->free_result();

			// Now to add percentages.
			foreach ($temp as $k => $v)
			{
				$v['real_name'] = strtr($v['real_name'], ['&amp;' => '&', '&lt;' => '<', '&gt;' => '>', '&quot;' => '"', "'" => '`']);
				$temp[$k]['real_name'] = $v['real_name'];
				$temp[$k]['percent'] = $max > 0 ? round($v['count'] / $max * 100) : 0;
				$temp[$k]['count_format'] = comma_format($v['count']);
				$temp[$k]['item'] = '<a href="' . $scripturl . '?action=profile;u=' . $v['id_member'] . '">' . $v['real_name'] . '</a>';
				$temp[$k]['link'] = json_encode($scripturl . '?action=profile;u=' . $v['id_member']);
			}
			Cache::instance()->put('lgal_top_posters', $temp, $cache_ttl);
		}

		return $temp;
	}

	public function getTopAlbums()
	{
		global $scripturl;

		$db = database();

		$cache_ttl = 360;
		$cache_key = 'lgal_top_albums-' . $this->groups;
		if (($temp = Cache::instance()->get($cache_key, $cache_ttl)) === null)
		{
			$album_list = $this->getVisibleAlbums();

			$request = $db->query('', '
				SELECT 
				    id_album, album_name, album_slug, num_items AS count
				FROM {db_prefix}lgal_albums
				WHERE ' . ($album_list === true ? '1=1' : (empty($album_list) ? '1=0' : 'id_album IN ({array_int:album_list})')) . '
				ORDER BY count DESC
				LIMIT 10',
				[
					'album_list' => $album_list,
				]
			);
			$max = 0;
			$temp = [];
			while ($row = $request->fetch_assoc())
			{
				$temp[] = $row;
				if ($row['count'] > $max)
				{
					$max = (int) $row['count'];
				}
			}
			$request->free_result();

			// Now to add percentages.
			foreach ($temp as $k => $v)
			{
				$v['album_name'] = strtr($v['album_name'], ['&amp;' => '&', '&lt;' => '<', '&gt;' => '>', '&quot;' => '"', "'" => '`']);
				$temp[$k]['album_name'] = $v['album_name'];
				$temp[$k]['percent'] = $max > 0 ? round($v['count'] / $max * 100) : 0;
				$temp[$k]['count_format'] = comma_format($v['count']);
				$temp[$k]['item'] = '<a href="' . $scripturl . '?media/album/' . (!empty($v['album_slug']) ? $v['album_slug'] . '.' . $v['id_album'] : $v['id_album']) . '/">' . $v['album_name'] . '</a>';
				$temp[$k]['link'] = json_encode($scripturl . '?media/album/' . (!empty($v['album_slug']) ? $v['album_slug'] . '.' . $v['id_album'] : $v['id_album']));
			}
			Cache::instance()->put($cache_key, $temp, $cache_ttl);
		}

		return $temp;
	}

	public function getTopItemsByComments()
	{
		global $scripturl;

		$db = database();

		$cache_ttl = 360;
		$cache_key = 'lgal_top_items_by_comments-' . $this->groups;
		if (($temp = Cache::instance()->get($cache_key, $cache_ttl)) === null)
		{
			$album_list = $this->getVisibleAlbums();

			$request = $db->query('', '
				SELECT 
				    id_item, item_name, item_slug, num_comments AS count
				FROM {db_prefix}lgal_items
				WHERE ' . ($album_list === true ? '1=1' : (empty($album_list) ? '1=0' : 'id_album IN ({array_int:album_list})')) . '
					AND approved = 1
				ORDER BY count DESC
				LIMIT 10',
				[
					'album_list' => $album_list,
				]
			);
			$max = 0;
			$temp = [];
			while ($row = $request->fetch_assoc())
			{
				$temp[] = $row;
				if ($row['count'] > $max)
				{
					$max = (int) $row['count'];
				}
			}
			$request->free_result();

			// Now to add percentages.
			foreach ($temp as $k => $v)
			{
				$v['item_name'] = strtr($v['item_name'], ['&amp;' => '&', '&lt;' => '<', '&gt;' => '>', '&quot;' => '"', "'" => '`']);
				$temp[$k]['item_name'] = $v['item_name'];
				$temp[$k]['percent'] = $max > 0 ? round($v['count'] / $max * 100) : 0;
				$temp[$k]['count_format'] = comma_format($v['count']);
				$temp[$k]['item'] = '<a href="' . $scripturl . '?media/item/' . (!empty($v['item_slug']) ? $v['item_slug'] . '.' . $v['id_item'] : $v['id_item']) . '/">' . $v['item_name'] . '</a>';
				$temp[$k]['link'] = json_encode($scripturl . '?media/item/' . (!empty($v['item_slug']) ? $v['item_slug'] . '.' . $v['id_item'] : $v['id_item']));
			}
			Cache::instance()->put($cache_key, $temp, $cache_ttl);
		}

		return $temp;
	}

	public function getTopItemsByViews()
	{
		global $scripturl;

		$db = database();

		$cache_ttl = 360;
		$cache_key = 'lgal_top_items_by_views-' . $this->groups;
		if (($temp = Cache::instance()->get($cache_key, $cache_ttl)) === null)
		{
			$album_list = $this->getVisibleAlbums();

			$request = $db->query('', '
				SELECT 
				    id_item, item_name, item_slug, num_views AS count
				FROM {db_prefix}lgal_items
				WHERE ' . ($album_list === true ? '1=1' : (empty($album_list) ? '1=0' : 'id_album IN ({array_int:album_list})')) . '
					AND approved = 1
				ORDER BY count DESC
				LIMIT 10',
				[
					'album_list' => $album_list,
				]
			);
			$max = 0;
			$temp = [];
			while ($row = $request->fetch_assoc())
			{
				$temp[] = $row;
				if ($row['count'] > $max)
				{
					$max = (int) $row['count'];
				}
			}
			$request->free_result();

			// Now to add percentages.
			foreach ($temp as $k => $v)
			{
				$v['item_name'] = strtr($v['item_name'], ['&amp;' => '&', '&lt;' => '<', '&gt;' => '>', '&quot;' => '"', "'" => '`']);
				$temp[$k]['item_name'] = $v['item_name'];
				$temp[$k]['percent'] = $max > 0 ? round($v['count'] / $max * 100) : 0;
				$temp[$k]['count_format'] = comma_format($v['count']);
				$temp[$k]['item'] = '<a href="' . $scripturl . '?media/item/' . (!empty($v['item_slug']) ? $v['item_slug'] . '.' . $v['id_item'] : $v['id_item']) . '/">' . $v['item_name'] . '</a>';
				$temp[$k]['link'] = json_encode($scripturl . '?media/item/' . (!empty($v['item_slug']) ? $v['item_slug'] . '.' . $v['id_item'] : $v['id_item']));
			}
			Cache::instance()->put($cache_key, $temp, $cache_ttl);
		}

		return $temp;
	}

	public function getCountsByItemType()
	{
		$db = database();

		$counts = [
			'image' => 0,
			'audio' => 0,
			'video' => 0,
			'document' => 0,
			'archive' => 0,
			'generic' => 0,
			'external' => 0,
		];

		$request = $db->query('', '
			SELECT 
			    item_type, COUNT(item_type) AS count
			FROM {db_prefix}lgal_search_item
			GROUP BY item_type');
		while ($row = $request->fetch_assoc())
		{
			if (isset($counts[$row['item_type']]))
			{
				$counts[$row['item_type']] = (int) $row['count'];
			}
		}
		$request->free_result();

		return $counts;
	}
}
