<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.0 / elkarte
 */

/**
 * This file deals with various LevGal-specific formatting.
 */
class LevGal_Helper_Format
{
	public static function filesize($bytes)
	{
		global $txt;

		// up to 1MB, display 1,023KB
		if ($bytes < (1024 * 1024))
		{
			return sprintf($txt['lgal_size_kb'], comma_format($bytes / 1024, 1));
		}
		// 1MB to 1GB, display 1,023MB
		elseif ($bytes < (1024 * 1024 * 1024))
		{
			return sprintf($txt['lgal_size_mb'], comma_format($bytes / 1024 / 1024, 1));
		}
		// otherwise round to GBs... eek.
		return sprintf($txt['lgal_size_gb'], comma_format($bytes / 1024 / 1024 / 1024, 1));
	}

	public static function time($timestamp, $offset_type = false)
	{
		global $user_info;

		$time_format = $user_info['time_format'];
		$user_info['time_format'] = strtr($user_info['time_format'], array('%A' => '%a', '%B' => '%b', ':%S' => '', '-%S' => ''));
		$time = standardTime($timestamp, false, $offset_type);
		$user_info['time_format'] = $time_format;

		return $time;
	}

	public static function humantime($time)
	{
		$secs = (int) $time;
		if ($secs < 3600)
		{
			return sprintf('%02s:%02s', floor($secs / 60), $secs % 60);
		}
		else
		{
			return sprintf('%s:%02s:%02s', floor($secs / 3600), floor(($secs % 3600) / 60), $secs % 60);
		}
	}

	public static function numstring($string, $num)
	{
		global $txt;

		$entry = isset($txt[$string . '_' . $num]) ? $string . '_' . $num : $string . '_x';

		return sprintf($txt[$entry], comma_format($num));
	}
}
