<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.0 / elkarte
 */

/**
 * This file deals with HTTP responses and the specifics required to handle them.
 */
class LevGal_Helper_Http
{
	public static function fatalError($msg, $response_code = 403, $error_log = false)
	{
		// We probably want our language file if we don't already have it.
		loadLanguage('levgal_lng/LevGal');
		loadLanguage('levgal_lng/LevGal-Errors');
		self::setResponse($response_code);
		throw new Elk_Exception($msg, $error_log);
	}

	public static function jsonResponse($array, $response_code = 403)
	{
		// Just in case something was output first
		Template_Layers::instance()->removeAll();

		$mime_type = 'application/json';

		self::setResponse($response_code);
		@ob_end_clean();
		header('Content-Type: ' . $mime_type . '; charset=UTF-8');
		die(json_encode($array));
	}

	public static function hardRedirect($url)
	{
		@ob_end_clean();
		header('Location: ' . $url, true, 301);
		exit;
	}

	public static function setResponse($response_code)
	{
		// Even if the request is HTTP/1.0, we should generally be responding with 1.1 as per specification.
		$headers = array(
			200 => 'HTTP/1.1 200 OK',
			206 => 'HTTP/1.1 206 Partial Content',
			301 => 'HTTP/1.1 301 Moved Permanently',
			302 => 'HTTP/1.1 302 Found',
			304 => 'HTTP/1.1 304 Not Modified',
			400 => 'HTTP/1.1 400 Bad Request',
			403 => 'HTTP/1.1 403 Forbidden',
			404 => 'HTTP/1.1 404 Not Found',
			406 => 'HTTP/1.1 406 Not Acceptable',
			416 => 'HTTP/1.1 416 Requested Range Not Satisfiable',
			500 => 'HTTP/1.1 500 Internal Server Error',
		);

		if (!isset($headers[$response_code]))
		{
			$response_code = 500;
		}

		// We may need to negate content encoding in these cases.
		if (in_array($response_code, array(404, 500)))
		{
			header('Content-Encoding: none');
		}

		header($headers[$response_code], true);
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
