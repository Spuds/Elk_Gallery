<?php
/**
 * @name Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.1 / elkarte
 */

/**
 * This script removes all the extraneous data if the user requests it be removed on uninstall.
 *
 * NOTE: This script is meant to run using the <samp><database></database></samp> elements of the
 * package-info.xml file. The install script, run through <samp><database></samp> elements would have set up
 * the tables and so on. This script runs from <samp><database></samp> during uninstallation only when
 * the user requests that data should be cleared, so this script deals with it; note that table removal
 * will be dealt with by ELK itself because of <samp><database></samp> handling, so this is for things like
 * settings that are not covered in <samp><database></samp>.
 *
 * @package levgal
 * @since 1.0
 */

/**
 *	Before attempting to execute, this file attempts to load SSI.php to enable access to the database functions.
*/

// If we have found SSI.php and we are outside of ElkArte, then we are running standalone.
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('ELK'))
{
	require_once(dirname(__FILE__) . '/SSI.php');
}
// If we are outside ElkArte and can't find SSI.php, then throw an error
elseif (!defined('ELK'))
{
	die('<b>Error:</b> Cannot uninstall - please verify you put this file in the same place as ElkArte\'s SSI.php.');
}

$db = database();

$to_remove = array(
	'lgal_installed',
	'lgal_dir',
	'lgal_xsendfile',
	'lgal_selfmod_approve_item',
	'lgal_selfmod_approve_comment',
	'lgal_selfmod_edit_comment',
	'lgal_selfmod_delete_comment',
	'lgal_selfmod_lock_comment',
	'lgal_comments_per_page',
	'lgal_items_per_page',
	'lgal_enable_image',
	'lgal_enable_audio',
	'lgal_enable_video',
	'lgal_enable_document',
	'lgal_enable_archive',
	'lgal_enable_generic',
	'lgal_enable_external',
	'lgal_image_formats',
	'lgal_audio_formats',
	'lgal_video_formats',
	'lgal_document_formats',
	'lgal_archive_formats',
	'lgal_generic_formats',
	'lgal_external_formats',
	'lgal_image_quotas',
	'lgal_audio_quotas',
	'lgal_video_quotas',
	'lgal_document_quotas',
	'lgal_archive_quotas',
	'lgal_generic_quotas',
	'lgal_max_space',
	'lgal_chunk_size',
	'lgal_reports',
	'lgal_enable_mature',
	'lgal_meta_data',
	'lgal_social',
	'lgal_feed_enable_item',
	'lgal_feed_enable_album',
	'lgal_feed_items_item',
	'lgal_feed_items_album',
	'lgal_unapproved_items',
	'lgal_unapproved_comments',
	'lgal_unapproved_albums',
	'lgal_import_rendering',
	'lgal_tag_items_list',
	'lgal_tag_items_list_more',
);

global $modSettings;

// Clear in-situ mod settings
foreach ($to_remove as $setting)
{
	if (isset($modSettings[$setting]))
	{
		unset ($modSettings[$setting]);
	}
}

// Remove from the database; updateSettings can actually remove them but this is easy :(
if (!empty($to_remove))
{
	$db->query('', '
		DELETE FROM {db_prefix}settings
		WHERE variable IN ({array_string:settings})',
		array(
			'settings' => $to_remove,
		)
	);
}

// And tell ElkArte we've updated $modSettings
updateSettings(array(
	'settings_updated' => time(),
));

// And we need to clear permissions.
$permissions = array(
	'lgal_view',
	'lgal_manage',
	'lgal_adduseralbum',
	'lgal_addgroupalbum',
	'lgal_addalbum_approve',
	'lgal_approve_album',
	'lgal_edit_album_own',
	'lgal_edit_album_any',
	'lgal_delete_album_own',
	'lgal_delete_album_any',
	'lgal_additem_own',
	'lgal_additem_any',
	'lgal_addbulk',
	'lgal_additem_approve',
	'lgal_approve_item',
	'lgal_edit_item_own',
	'lgal_edit_item_any',
	'lgal_delete_item_own',
	'lgal_delete_item_any',
	'lgal_comment',
	'lgal_comment_appr',
	'lgal_approve_comment',
	'lgal_edit_comment_own',
	'lgal_edit_comment_any',
	'lgal_delete_comment_own',
	'lgal_delete_comment_any',
);
if (!empty($permissions))
{
	$db->query('', '
		DELETE FROM {db_prefix}permissions
		WHERE permission IN ({array_string:permissions})',
		array(
			'permissions' => $permissions,
		)
	);
}

// And user preferences.
$preferences = array(
	'lgal_show_mature',
	'lgal_show_bookmarks',
);
if (!empty($preferences))
{
	$db->query('', '
		DELETE FROM {db_prefix}themes
		WHERE variable IN ({array_string:preferences})',
		array(
			'preferences' => $preferences,
		)
	);
}

if (!function_exists('matchTable'))
{
	function matchTable($table_name)
	{
		global $db_prefix;
		static $table_list = null;

		$db = database();

		if ($table_list === null)
		{
			$table_list = $db->db_list_tables();
		}

		$real_prefix = preg_match('~^(`?)(.+?)\\1\\.(.*?)$~', $db_prefix, $match) === 1 ? $match[3] : $db_prefix;

		return in_array(str_replace('{db_prefix}', $real_prefix, $table_name), $table_list);
	}
}
