<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0 / elkarte
 */

namespace Addons\Levertine\Source\Helper;

use ElkArte\Exceptions\Exception;
use ElkArte\Http\Headers;
use ElkArte\Languages\Txt;

/**
 * This file deals with HTTP responses and the specifics required to handle them.
 */
class Http
{
	public static function fatalError($msg, $response_code = 403, $error_log = false)
	{
		// We probably want our language file if we don't already have it.
		Txt::load('Levertine/LevGal');
		Txt::load('Levertine/LevGal-Errors');
		self::setResponse($response_code);
		throw new Exception($msg, $error_log);
	}

	public static function jsonResponse($array, $response_code = 403)
	{
		// Just in case something was output first
		$template_layers = theme()->getLayers();
		$template_layers->removeAll();

		$mime_type = 'application/json';

		self::setResponse($response_code);
		while (@ob_get_level() > 0)
		{
			@ob_end_clean();
		}

		$headers = Headers::instance();
		$headers->removeHeader('all');
		$headers->contentType($mime_type, 'UTF-8');
		$headers->sendHeaders();
		die(json_encode($array, JSON_THROW_ON_ERROR));
	}

	public static function hardRedirect($url)
	{
		while (@ob_get_level() > 0)
		{
			@ob_end_clean();
		}

		header('Location: ' . $url, true, 301);
		exit;
	}

	public static function setResponse($response_code)
	{
		$headers = [
			200 => '200 OK',
			206 => '206 Partial Content',
			301 => '301 Moved Permanently',
			302 => '302 Found',
			304 => '304 Not Modified',
			400 => '400 Bad Request',
			403 => '403 Forbidden',
			404 => '404 Not Found',
			406 => '406 Not Acceptable',
			416 => '416 Requested Range Not Satisfiable',
			500 => '500 Internal Server Error',
		];

		if (!isset($headers[$response_code]))
		{
			$response_code = 500;
		}

		// We may need to negate content encoding in these cases.
		if (in_array($response_code, [404, 500], true))
		{
			header('Content-Encoding: none');
		}

		header(detectServer()->getProtocol() . ' ' . $headers[$response_code]);
	}

	public static function setResponseExit($response_code, $response_body = '', $content_type = 'text/plain')
	{
		self::setResponse($response_code);
		if (!empty($response_body) && !empty($content_type))
		{
			header('Content-Type: ' . $content_type);
		}
		die($response_body);
	}
}
