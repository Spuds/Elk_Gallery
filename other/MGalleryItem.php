<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.2 / elkarte
 */

$blankGif = "\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\x00\x00\x00\x21\xF9\x04\x01\x00\x00\x00\x00\x2C\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x44\x01\x00\x3B";

if (file_exists(__DIR__ . '/SSI.php'))
{
	require_once(__DIR__ . '/SSI.php');
}
else
{
	sendBlankGif($blankGif);
}

if (!isset($_GET['id']))
{
	sendBlankGif($blankGif);
}

global $settings, $context;

// What was requested
$item_id = (int) $_GET['id'];
$type = isset($_GET['thumb']) ? 'thumb' : (isset($_GET['preview']) ? 'preview' : 'raw');

// Grab the details
$itemModel = new LevGal_Model_Item();
$item_details = $itemModel->getItemInfoById($item_id);

list($path, $filename, $mime) = getFileInfo($item_details, $itemModel, $settings, $type);

if (!$path)
{
	header('HTTP/1.1 404 Not Found');
	die('Error! Item not found');
}

// Send the headers and file
sendData($filename, $mime, $path);

// Update the view count
$db = database();
$db->query('', 'UPDATE {db_prefix}lgal_items SET num_views = num_views + 1 WHERE id_item = {int:item}', ['item' => $item_id]);

/**
 * Send a blank GIF image as the HTTP response.
 *
 * @param string $blankGif The base64 encoded string of the blank GIF image.
 * @return void
 *
 * The method sets the Content-Type header to "image/gif" and outputs
 * the provided blank GIF image, then terminates the script execution.
 */
function sendBlankGif($blankGif)
{
	header('Content-Type: image/gif');
	die($blankGif);
}

/**
 * Retrieve file information.
 *
 * @param array $item_details The details of the item.
 * @param ItemModel $itemModel The item model instance.
 * @param array $settings The application settings.
 * @param string $type The type of the file.
 * @return array The file information array with three elements: file path, file name, and mime type.
 *               If the item does not exist or is not visible, it returns the default invalid file information:
 *               - File path: the invalid file icon path in the default theme directory.
 *               - File name: 'denied.png'.
 *               - Mime type: 'image/png'.
 */
function getFileInfo($item_details, $itemModel, $settings, $type)
{
	// Does the item exist? Can they see it if it does?
	if (empty($item_details) || !$itemModel->isVisible())
	{
		return [$settings['theme_dir'] . '/levgal_res/icons/_invalid.png', 'denied.png', 'image/png'];
	}

	$item_paths = $itemModel->getFilePaths();
	$path = $item_paths[$type];

	return [$path, $item_details['filename'], $item_details['mime_type']];
}

/**
 * Send data to the client.
 *
 * @param string $filename The name of the file to be sent.
 * @param string $mime The MIME type of the file.
 * @param string $path The file path.
 */
function sendData($filename, $mime, $path)
{
	header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 525600 * 60) . ' GMT');
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($path)) . ' GMT');
	header('Content-Type: ' . (!empty($mime) && strpos($mime, 'image/') === 0 ? $mime : 'application/octet-stream'));
	header('Content-Disposition: inline; filename="' . $filename . (preg_match('~[\x80-\xFF]~', $filename) ? "; filename*=UTF-8''" . rawurlencode($filename) : ''));
	header('Content-Length: ' . filesize($path));

	echo file_get_contents($path);
}
