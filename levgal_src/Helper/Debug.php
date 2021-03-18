<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license proprietary
 *
 * @version 1.0 / elkarte
 */

/**
 * This file deals with debugging.
 */
class LevGal_Helper_Debug
{
	public static function queryLog()
	{
		global $db_show_debug, $db_count, $db_cache;
		if (isset($db_show_debug) && $db_show_debug === true)
		{
			header('X-Mainquery-Count: ' . $db_count);
			foreach ($db_cache as $query_id => $query)
			{
				header('X-Query-' . $query_id . ': ' . $query['q']);
			}
		}
	}
}
