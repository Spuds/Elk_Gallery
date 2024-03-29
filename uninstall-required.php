<?php
/**
 * @name Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.1 / elkarte
 */

/**
 * This script removes all the basic data uninstall, allowing lossless reinstall
 *
 * NOTE: This script is meant to run using the <samp><code></code></samp> elements of our
 * package-info.xml file. This is because certain items in the database and within ElkArte will
 * need to be removed regardless of whether the user wants to keep data or not, for example
 * Levertine Gallery hooks need to be deactivated.
 *
 * @package levgal
 * @since 1.0
 */

/**
 * Before attempting to execute, this file attempts to load SSI.php to enable access to the database functions.
*/

// If we have found SSI.php and we are outside of ElkArte, then we are running standalone.
if (file_exists(__DIR__ . '/SSI.php') && !defined('ELK'))
{
	require_once(__DIR__ . '/SSI.php');
}
// If we are outside ELK and can't find SSI.php, then throw an error
elseif (!defined('ELK'))
{
	die('<b>Error:</b> Cannot uninstall - please verify you put this file in the same place as ElkArte\'s SSI.php.');
}

global $modSettings;

// Clear in-situ mod settings that may cause temporary problems
unset($modSettings['lgal_import_rendering'], $modSettings['lgal_installed']);

$db = database();
$db_table = db_table();

// 1. Removing the permanent hooks
$hooks = array(
	'integrate_pre_include' => '$sourcedir/levgal_src/LevGal_Bootstrap.php',
	'integrate_pre_load' => 'LevGal_Bootstrap::initialize',
);

foreach ($hooks as $hook => $callable)
{
	remove_integration_function($hook, $callable);
}

// 2. Removing the scheduled task.
$request = $db->query('', '
	SELECT 
		id_task
	FROM {db_prefix}scheduled_tasks
	WHERE task = {string:levgal}',
	array(
		'levgal' => 'levgal_maintenance',
	)
);
if ($row =$db->fetch_assoc($request))
{
	$db->query('', '
		DELETE FROM {db_prefix}log_scheduled_tasks
		WHERE id_task = {int:id_task}',
		array(
			'id_task' => $row['id_task'],
		)
	);
	$db->query('', '
		DELETE FROM {db_prefix}scheduled_tasks
		WHERE id_task = {int:id_task}',
		array(
			'id_task' => $row['id_task'],
		)
	);
}
$db->free_result($request);

// 3. Remove portals
// 3.1 SimplePortal
if (matchTable('{db_prefix}sp_functions'))
{
	$db = database();

	// Prevent it from being added as a new block.
	$db->query('', '
		DELETE FROM {db_prefix}sp_functions
		WHERE name = {string:sp_levgal}',
		array(
			'sp_levgal' => 'sp_levgal',
		)
	);
	// Disable any existing blocks of this type.
	$db->query('', '
		UPDATE {db_prefix}sp_blocks
		SET state = 0
		WHERE type = {string:sp_levgal}',
		array(
			'sp_levgal' => 'sp_levgal',
		)
	);
}
// 3.2 TinyPortal
if (matchTable('{db_prefix}tp_blocks'))
{
	// Disable any existing blocks of this type.
	$db->query('', '
		UPDATE {db_prefix}tp_blocks
		SET off = 1
		WHERE body LIKE {string:levgal}',
		array(
			'levgal' => '%LevGal_Portal_TinyPortal%',
		)
	);
}

// 4. Remove mentions
$current = !empty($modSettings['enabled_mentions']) ? explode(',', $modSettings['enabled_mentions']) : array();
$updated = array('enabled_mentions' => implode(',', array_diff($current, array('lgcomment', 'lgnew', 'lglike'))));
updateSettings($updated);

// Turn off existing ones
require_once(SUBSDIR . '/Mentions.subs.php');
toggleMentionsVisibility('lgcomment', false);
toggleMentionsVisibility('lgnew', false);
toggleMentionsVisibility('lglike', false);

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
