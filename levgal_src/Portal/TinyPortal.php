<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license proprietary
 *
 * @version 1.0.4
 */

/**
 * This file provides the integration handler for TinyPortal.
 *
 * @package levgal
 * @since 1.0.2
 */
class LevGal_Portal_TinyPortal
{
	protected static function hasPermission()
	{
		global $txt;
		if (allowedTo(array('lgal_view', 'lgal_manage')))
		{
			return true;
		}

		loadLanguage('levgal_lng/LevGal');
		echo $txt['lgal_no_items'];
	}

	public static function getRandomItems($columns, $rows)
	{
		if (self::hasPermission())
		{
			$itemList = LevGal_Bootstrap::getModel('LevGal_Model_ItemList');
			$qty = abs($columns * $rows);
			$items = $itemList->getRandomItems($qty);

			return self::displayItems($columns, $rows, $items);
		}
	}

	public static function getLatestItems($columns, $rows)
	{
		if (self::hasPermission())
		{
			$itemList = LevGal_Bootstrap::getModel('LevGal_Model_ItemList');
			$qty = abs($columns * $rows);
			$items = $itemList->getLatestItems($qty);

			return self::displayItems($columns, $rows, $items);
		}
	}

	protected static function displayItems($columns, $rows, $items)
	{
		global $txt;

		if (empty($items))
		{
			loadLanguage('levgal_lng/LevGal');
			echo $txt['lgal_no_items'];

			return;
		}

		echo '
								<table class="tp_levgal smalltext centertext" style="width:100%;">';

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
											<div>
												<a href="', $item['item_url'], '">', $item['item_name'], '</a><br />
												<a href="', $item['item_url'], '"><img src="', $item['thumbnail'], '" alt="', $item['item_name'], '" title="', $item['item_name'], '" /></a>
											</div>
										</td>';
			}
			echo '
									</tr>';
		}

		echo '
								</table>';
	}
}
