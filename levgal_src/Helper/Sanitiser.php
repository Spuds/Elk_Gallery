<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.0 / elkarte
 */

/**
 * This file deals with certain sanitising instructions.
 */
class LevGal_Helper_Sanitiser
{
	public static function sanitiseSlugFromPost($var)
	{
		return self::sanitiseSlug(!empty($_POST[$var]) ? $_POST[$var] : '');
	}

	public static function sanitiseSlug($var)
	{
		// We don't do htmlspecialchars because we have different plans for it.
		// In fact, we just start with stripping easy cases - convert space or _ to - and lowercase it.
		$var = strtolower(strtr($var, array(' ' => '-', '_' => '-')));
		// Strip all the characters we don't accept - we accept the following: a-z0-9%-
		$var = preg_replace('~[^a-z0-9%-]+~', '', $var);
		// Strip duplicates
		$var = preg_replace('~\-+~', '-', $var);
		// Lastly, cap to 50 characters and trim any leading or trailing stuff
		return trim(substr($var, 0, 50), '-');
	}

	public static function sanitiseUrl($var)
	{
		// Soft-fix domains without any kind of schema, because users may not be nice about it.
		if (substr($var, 0, 7) !== 'http://' && substr($var, 0, 8) !== 'https://')
		{
			$var = (substr($var, 0, 2) === '//' ? 'https:' : 'https://') . $var;
		}

		return filter_var(trim($var), FILTER_VALIDATE_URL);
	}

	public static function sanitiseUrlFromPost($var)
	{
		return self::sanitiseUrl(!empty($_POST[$var]) ? $_POST[$var] : '');
	}

	public static function sanitiseThingNameFromPost($var)
	{
		return self::sanitiseThingName(!empty($_POST[$var]) ? $_POST[$var] : '');
	}

	public static function sanitiseThingName($var)
	{
		// htmlspecialchars it, strip icky whitespace, cut to length.
		$var = Util::htmlspecialchars($var);
		$var = strtr($var, array("\r" => '', "\n" => '', "\t" => ''));
		$var = preg_replace('~\s+~', ' ', $var);
		$var = Util::strlen($var) > 100 ? Util::substr($var, 0, 100) : $var;

		return Util::htmltrim($var);
	}

	public static function sanitiseText($var, $max_length = null)
	{
		$content = Util::htmltrim(Util::htmlspecialchars($var));

		if (!empty($content) && $max_length !== null)
		{
			$content = Util::substr($content, 0, $max_length);
		}

		return $content;
	}

	public static function sanitiseTextFromPost($var, $max_length = null)
	{
		return self::sanitiseText(!empty($_POST[$var]) ? $_POST[$var] : '', $max_length);
	}

	public static function sanitiseBBCText($var, $max_length)
	{
		require_once(SUBSDIR . '/Post.subs.php');

		$var = self::sanitiseText($var, $max_length);
		preparsecode($var);

		return $var;
	}

	public static function sanitiseBBCTextFromPost($var, $max_length = null)
	{
		return self::sanitiseBBCText(!empty($_POST[$var]) ? $_POST[$var] : '', $max_length);
	}

	public static function sanitiseInt($var, $min = null, $max = null)
	{
		$var = (int) $var;
		if (!empty($min) && !empty($max))
		{
			$var = LevGal_Bootstrap::clamp($var, $min, $max);
		}

		return $var;
	}

	public static function sanitiseIntFromPost($var, $min = null, $max = null)
	{
		return self::sanitiseInt($_POST[$var] ?? 0, $min, $max);
	}

	public static function sanitiseUsernameFromPost($var)
	{
		// First, get the username.
		$username = self::sanitiseTextFromPost($var, 30);
		require_once(SOURCEDIR . '/Members.subs.php');

		return array(!empty($username) && !isReservedName($username, 0, true, false), $username);
	}

	public static function sanitiseEmailFromPost($var)
	{
		global $txt;

		$email = !empty($_POST[$var]) ? trim($_POST[$var]) : '';
		$sanitised = filter_var($email, FILTER_VALIDATE_EMAIL);

		if (!empty($sanitised))
		{
			// So we have a valid email address. Let's see if it would be banned.
			isBannedEmail($sanitised, 'cannot_post', sprintf($txt['you_are_post_banned'], $txt['guest_title']));

			return array(true, $sanitised);
		}
		else
		{
			// Oh dear, not even sanitisable? Let's make sure we return something so that we
			// can redisplay whatever the user did enter back into the form for them to get it right.
			return array(false, Util::htmlspecialchars($email, ENT_QUOTES));
		}
	}
}
