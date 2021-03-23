<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.0.3
 */

/**
 * This file provides the integration for SimplePortal.
 *
 * @package levgal
 * @since 1.0
 */
class LevGal_Portal_SimplePortal
{
	public static function portal($parameters, $id, $return_parameters = false)
	{
		if ($return_parameters)
		{
			return self::getParameters();
		}
		else
		{
			self::execute($parameters, $id);
		}
	}

	protected static function getParameters()
	{
		return array(
			'type' => 'select',
			'rows' => 'int',
			'columns' => 'int',
		);
	}

	protected static function execute($parameters, $id)
	{
		global $txt, $scripturl;

		$type = !empty($parameters['type']) ? (int) $parameters['type'] : 0;
		$rows = !empty($parameters['rows']) ? abs((int) $parameters['rows']) : 0;
		$columns = !empty($parameters['columns']) ? abs((int) $parameters['columns']) : 0;
		if (empty($rows))
		{
			$rows = 1;
		}
		if (empty($columns))
		{
			$columns = 4;
		}

		if (!allowedTo(array('lgal_view', 'lgal_manage')))
		{
			echo '
								', $txt['error_sp_no_pictures_found'];

			return;
		}

		$qty = $rows * $columns;
		switch ($type)
		{
			case 0:
			default:
				$itemList = LevGal_Bootstrap::getModel('LevGal_Model_ItemList');
				$items = $itemList->getLatestItems($qty);
				break;
			case 1:
				$itemList = LevGal_Bootstrap::getModel('LevGal_Model_ItemList');
				$items = $itemList->getRandomItems($qty);
				break;
		}

		if (empty($items))
		{
			echo '
								', $txt['error_sp_no_pictures_found'];

			return;
		}

		echo '
								<table class="sp_auto_align sp_levgal sp_levgal_', $id, '" style="width:100%;">';

		$cellwidth = floor(100 / $columns);

		$list = array_keys($items);
		for ($y = 0; $y < $rows; $y++)
		{
			if (empty($list[$y * $columns]))
			{
				continue;
			}
			echo '
									<tr>';
			for ($x = 0; $x < $columns; $x++)
			{
				$pos = $y * $columns + $x;
				if (empty($list[$pos]))
				{
					continue;
				}
				$item = &$items[$list[$pos]];
				echo '
										<td style="width:', $cellwidth, '%;">
											<div class="sp_image smalltext">
												<a href="', $item['item_url'], '">', $item['item_name'], '</a><br />
												<a href="', $item['item_url'], '"><img src="', $item['thumbnail'], '" alt="', $item['item_name'], '" title="', $item['item_name'], '" /></a><br />
												', $txt['lgal_posted_by'], ' ', !empty($item['id_member']) ? '<a href="' . $scripturl . '?action=profile;u=' . $item['id_member'] . '">' . $item['poster_name'] . '</a>' : $item['poster_name'], '<br />
												', $txt['lgal_posted_in'], ' <a href="', $item['album_url'], '">', $item['album_name'], '</a><br />
											</div>
										</td>';
			}
			echo '
									</tr>';
		}

		echo '
								</table>';
	}

	public function isPortalInstalled()
	{
		return LevGal_Helper_Database::matchTable('{db_prefix}sp_functions');
	}

	public function ensureInstalled()
	{
		$db = database();

		$request = $db->query('', '
			SELECT 
				id_function
			FROM {db_prefix}sp_functions
			WHERE name = {string:sp_levgal}',
			array(
				'sp_levgal' => 'sp_levgal',
			)
		);
		if ($db->num_rows($request) == 0)
		{
			$db->insert('',
				'{db_prefix}sp_functions',
				array('function_order' => 'int', 'name' => 'string'),
				array(0, 'sp_levgal'),
				array('id_function')
			);
		}
		$db->free_result($request);
	}
}
