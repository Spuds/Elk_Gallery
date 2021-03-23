<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.0 / elkarte
 */

/**
 * This file deals with some database internals.
 */
class LevGal_Helper_Database
{
	public static function matchTable($table_name)
	{
		global $db_prefix;
		static $table_list = null;

		if ($table_list === null)
		{
			$table_list = self::getTableList();
		}

		// See db_create_table for basis of this.
		$real_prefix = preg_match('~^(`?)(.+?)\\1\\.(.*?)$~', $db_prefix, $match) === 1 ? $match[3] : $db_prefix;

		return in_array(str_replace('{db_prefix}', $real_prefix, $table_name), $table_list);
	}

	private static function getTableList()
	{
		$db = database();

		return $db->db_list_tables();
	}
}
