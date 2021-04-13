<?php
/**
 * @name Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license proprietary
 *
 * @version 1.0 / elkarte
 */

/**
 * This script prepares the database for all the tables and other database changes that Levertine Gallery requires.
 *
 * NOTE: This script is meant to run using the <samp><database></database></samp> elements of the package-info.xml file. This is so
 * that admins have the choice to uninstall any database data installed with the mod. Also, since using the <samp><database></samp>
 * elements automatically calls on db_extend('packages'), we will only be calling that if we are running this script standalone.
 *
 * @package levgal
 * @since 1.0
 */

// If we have found SSI.php and we are outside of ELK, then we are running standalone.
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('ELK'))
{
	require_once(dirname(__FILE__) . '/SSI.php');
}
elseif (!defined('ELK'))
{
	die('<b>Error:</b> Cannot install - please verify you put this file in the same place as ElkArte\'s SSI.php.');
}

$db = database();
$db_table = db_table();

// We also only support MySQL for now
if (strpos(strtolower($db->db_title()), 'mysql') !== 0)
{
	die('Levertine Gallery requires MySQL/MariaDB to function correctly.');
}

// We have a lot to do. Make sure as best we can that we have the time to do so.
detectServer()->setTimeLimit(600);

global $modSettings, $txt, $db_prefix, $db_type;

// Here we will update the $modSettings variables.
$mod_settings = array();
$new_settings = array(
	'lgal_installed' => time(),
);

foreach ($new_settings as $k => $v)
{
	if (!isset($modSettings[$k]))
	{
		$mod_settings[$k] = $v;
	}
}

// Anything that shouldn't be set by default won't be in the list.
// Note that the check is made to isset not empty, because empty values are
// pre-existing off values, which are not purged from the DB.

// Hook references to be added.
$hooks = array();
$hooks[] = array('hook' => 'integrate_pre_include', 'function' => 'SOURCEDIR/levgal_src/LevGal-Bootstrap.php');
$hooks[] = array('hook' => 'integrate_pre_load', 'function' => 'LevGal_Bootstrap::initialize');

// Hook references to remove.  During adaption/testing some were mistaking added as
// permanent so we clean them up during new install
$hooksRemove = array();
$hooksRemove[] = array('hook' => 'redirect', 'function' => 'LevGal_Bootstrap::hookRedirect', 'file' => '');
$hooksRemove[] = array('hook' => 'actions', 'function' => 'LevGal_Bootstrap::hookActions', 'file' => '');
$hooksRemove[] = array('hook' => 'menu_buttons', 'function' => 'LevGal_Bootstrap::hookButtons', 'file' => '');
$hooksRemove[] = array('hook' => 'additional_bbc', 'function' => 'LevGal_Bootstrap::hookBbcCodes', 'file' => '');
$hooksRemove[] = array('hook' => 'bbc_codes', 'function' => 'LevGal_Bootstrap::hookBbcCodes', 'file' => '');
$hooksRemove[] = array('hook' => 'delete_member', 'function' => 'LevGal_Model_Member::deleteMember', 'file' => '');
$hooksRemove[] = array('hook' => 'delete_members', 'function' => 'LevGal_Model_Member::deleteMembers', 'file' => '');
$hooksRemove[] = array('hook' => 'delete_membergroups', 'function' => 'LevGal_Model_Group::deleteGroup', 'file' => '');
$hooksRemove[] = array('hook' => 'action_mentions_before' , 'function' => 'LevGal_Bootstrap::hookLanguage', 'file' => '');
$hooksRemove[] = array('hook' => 'integrate_admin_areas', 'function' => 'levgal_admin_bootstrap', 'file' => 'SOURCEDIR/levgal_src/ManageLevGal.php');
$hooksRemove[] = array('hook' => 'integrate_profile_areas', 'function' => 'LevGalProfile_Controller::LevGal_profile', 'file' => '');

// Now, we move on to adding new tables to the database.
$tables = array();
$tables[] = array(
	'table_name' => '{db_prefix}lgal_albums',
	'columns' => array(
		db_field('id_album', 'mediumint', 0, true, true),
		db_field('album_name', 'varchar', 255),
		db_field('album_slug', 'varchar', 255),
		db_field('thumbnail', 'varchar', 255),
		db_field('editable', 'tinyint'),
		db_field('locked', 'tinyint'),
		db_field('approved', 'tinyint'),
		db_field('num_items', 'int'),
		db_field('num_unapproved_items', 'int'),
		db_field('num_comments', 'int'),
		db_field('num_unapproved_comments', 'int'),
		db_field('featured', 'tinyint'),
		db_field('owner_cache', 'text'),
		db_field('perms', 'text'),
	),
	'indexes' => array(
		array(
			'columns' => array('id_album'),
			'type' => 'primary',
		),
	),
);
$tables[] = array(
	'table_name' => '{db_prefix}lgal_owner_member',
	'columns' => array(
		db_field('id_album', 'mediumint'),
		db_field('id_member', 'mediumint'),
		db_field('album_pos', 'mediumint'),
		db_field('album_level', 'tinyint'),
	),
	'indexes' => array(
		array(
			'columns' => array('id_album', 'id_member'),
			'type' => 'primary',
		),
		array(
			'columns' => array('id_member'),
			'type' => 'index',
		),
	),
);
$tables[] = array(
	'table_name' => '{db_prefix}lgal_owner_group',
	'columns' => array(
		db_field('id_album', 'mediumint'),
		db_field('id_group', 'mediumint'),
		db_field('album_pos', 'mediumint'),
		db_field('album_level', 'tinyint'),
	),
	'indexes' => array(
		array(
			'columns' => array('id_album', 'id_group'),
			'type' => 'primary',
		),
		array(
			'columns' => array('id_group'),
			'type' => 'index',
		),
	),
);
$tables[] = array(
	'table_name' => '{db_prefix}lgal_items',
	'columns' => array(
		db_field('id_item', 'int', 0, true, true),
		db_field('id_album', 'mediumint'),
		db_field('id_member', 'mediumint'),
		db_field('poster_name', 'varchar', 80),
		db_field('item_name', 'varchar', 255),
		db_field('item_slug', 'varchar', 255),
		db_field('filename', 'varchar', 255),
		db_field('filehash', 'varchar', 40),
		db_field('extension', 'varchar', 255),
		db_field('mime_type', 'varchar', 255),
		db_field('time_added', 'int'),
		db_field('time_updated', 'int'),
		db_field('description', 'text'),
		db_field('approved', 'tinyint'),
		db_field('editable', 'tinyint'),
		db_field('comment_state', 'tinyint'),
		db_field('filesize', 'bigint'),
		db_field('width', 'mediumint'),
		db_field('height', 'mediumint'),
		db_field('mature', 'tinyint'),
		db_field('num_views', 'mediumint'),
		db_field('num_comments', 'mediumint'),
		db_field('num_unapproved_comments', 'mediumint'),
		db_field('has_custom', 'tinyint'),
		db_field('has_tag', 'tinyint'),
		db_field('meta', 'text'),
	),
	'indexes' => array(
		array(
			'columns' => array('id_item'),
			'type' => 'primary',
		),
		array(
			'columns' => array('mime_type'),
			'type' => 'index',
		),
	),
);
$tables[] = array(
	'table_name' => '{db_prefix}lgal_comments',
	'columns' => array(
		db_field('id_comment', 'int', 0, true, true),
		db_field('id_item', 'int'),
		db_field('id_author', 'mediumint'),
		db_field('author_name', 'varchar', 80),
		db_field('author_email', 'varchar', 255),
		db_field('author_ip', 'varchar', 255),
		db_field('modified_name', 'varchar', 80),
		db_field('modified_time', 'int'),
		db_field('comment', 'text'),
		db_field('approved', 'tinyint'),
		db_field('time_added', 'int'),
	),
	'indexes' => array(
		array(
			'columns' => array('id_comment'),
			'type' => 'primary',
		),
		array(
			'columns' => array('id_item'),
			'type' => 'index',
		),
		array(
			'columns' => array('id_author'),
			'type' => 'index',
		),
	),
);
$tables[] = array(
	'table_name' => '{db_prefix}lgal_log_events',
	'columns' => array(
		db_field('id_event', 'int', 0, true, true),
		db_field('timestamp', 'int'),
		db_field('id_member', 'mediumint'),
		db_field('ip', 'varchar', 80),
		db_field('event', 'varchar', 80),
		db_field('id_album', 'mediumint'),
		db_field('id_item', 'int'),
		db_field('id_comment', 'int'),
		db_field('details', 'text'),
	),
	'indexes' => array(
		array(
			'columns' => array('id_event'),
			'type' => 'primary',
		),
	),
);
$tables[] = array(
	'table_name' => '{db_prefix}lgal_bookmarks',
	'columns' => array(
		db_field('id_member', 'mediumint'),
		db_field('id_item', 'int'),
		db_field('timestamp', 'int'),
	),
	'indexes' => array(
		array(
			'columns' => array('id_member', 'id_item'),
			'type' => 'primary',
		),
	),
);
$tables[] = array(
	'table_name' => '{db_prefix}lgal_log_seen',
	'columns' => array(
		db_field('id_item', 'int'),
		db_field('id_member', 'mediumint'),
		db_field('view_time', 'int'),
	),
	'indexes' => array(
		array(
			'columns' => array('id_item', 'id_member'),
			'type' => 'primary',
		),
		array(
			'columns' => array('view_time'),
			'type' => 'index',
		),
	),
);
$tables[] = array(
	'table_name' => '{db_prefix}lgal_likes',
	'columns' => array(
		db_field('id_item', 'int'),
		db_field('id_member', 'mediumint'),
		db_field('like_time', 'int'),
	),
	'indexes' => array(
		array(
			'columns' => array('id_item', 'id_member'),
			'type' => 'primary',
		),
		array(
			'columns' => array('id_member'),
			'type' => 'index',
		),
	),
);
$tables[] = array(
	'table_name' => '{db_prefix}lgal_notify',
	'columns' => array(
		db_field('id_member', 'mediumint'),
		db_field('id_album', 'mediumint'),
		db_field('id_item', 'int'),
	),
	'indexes' => array(
		array(
			'columns' => array('id_member', 'id_album', 'id_item'),
			'type' => 'unique',
		),
		array(
			'columns' => array('id_member', 'id_item'),
			'type' => 'index',
		),
	),
);
$tables[] = array(
	'table_name' => '{db_prefix}lgal_reports',
	'columns' => array(
		db_field('id_report', 'mediumint', 0, true, true),
		db_field('id_comment', 'int'),
		db_field('id_item', 'int'),
		db_field('id_album', 'mediumint'),
		db_field('content_id_poster', 'mediumint'),
		db_field('content_poster_name', 'varchar', 255),
		db_field('body', 'text'),
		db_field('time_started', 'int'),
		db_field('time_updated', 'int'),
		db_field('num_reports', 'smallint'),
		db_field('closed', 'tinyint'),
	),
	'indexes' => array(
		array(
			'columns' => array('id_report'),
			'type' => 'primary',
		),
		array(
			'columns' => array('id_comment'),
			'type' => 'index',
		),
		array(
			'columns' => array('id_item'),
			'type' => 'index',
		),
		array(
			'columns' => array('id_album'),
			'type' => 'index',
		),
		array(
			'columns' => array('content_id_poster'),
			'type' => 'index',
		),
	),
);
$tables[] = array(
	'table_name' => '{db_prefix}lgal_report_body',
	'columns' => array(
		db_field('id_rep_body', 'int', 0, true, true),
		db_field('id_report', 'mediumint'),
		db_field('id_member', 'mediumint'),
		db_field('member_name', 'varchar', 255),
		db_field('email_address', 'varchar', 255),
		db_field('ip_address', 'varchar', 255),
		db_field('body', 'text'),
		db_field('time_sent', 'int'),
	),
	'indexes' => array(
		array(
			'columns' => array('id_rep_body'),
			'type' => 'primary',
		),
		array(
			'columns' => array('id_report'),
			'type' => 'index',
		),
		array(
			'columns' => array('id_member'),
			'type' => 'index',
		),
	),
);
$tables[] = array(
	'table_name' => '{db_prefix}lgal_report_comment',
	'columns' => array(
		db_field('id_rep_comment', 'int', 0, true, true),
		db_field('id_report', 'mediumint'),
		db_field('id_member', 'mediumint'),
		db_field('member_name', 'varchar', 255),
		db_field('log_time', 'int'),
		db_field('comment', 'text'),
	),
	'indexes' => array(
		array(
			'columns' => array('id_rep_comment'),
			'type' => 'primary',
		),
		array(
			'columns' => array('id_report'),
			'type' => 'index',
		),
	),
);
$tables[] = array(
	'table_name' => '{db_prefix}lgal_search_album',
	'columns' => array(
		db_field('id_album', 'mediumint'),
		db_field('album_name', 'varchar', 255),
	),
	'indexes' => array(
		array(
			'columns' => array('id_album'),
			'type' => 'primary',
		),
	),
	'parameters' => array(
		'requires_fulltext' => array('album_name'),
	),
);
$tables[] = array(
	'table_name' => '{db_prefix}lgal_search_item',
	'columns' => array(
		db_field('id_item', 'int'),
		db_field('item_name', 'varchar', 255),
		db_field('description', 'text'),
		db_field('item_type', 'varchar', 10),
	),
	'indexes' => array(
		array(
			'columns' => array('id_item'),
			'type' => 'primary',
		),
		array(
			'columns' => array('item_type'),
			'type' => 'index',
		),
	),
	'parameters' => array(
		'requires_fulltext' => array('item_name', 'description'),
	),
);
$tables[] = array(
	'table_name' => '{db_prefix}lgal_search_results',
	'columns' => array(
		db_field('id_search', 'int', 0, true, true),
		db_field('id_member', 'mediumint'),
		db_field('timestamp', 'int'),
		db_field('searchdata', 'text'),
	),
	'indexes' => array(
		array(
			'columns' => array('id_search'),
			'type' => 'primary',
		),
	),
);
$tables[] = array(
	'table_name' => '{db_prefix}lgal_custom_field',
	'columns' => array(
		db_field('id_field', 'smallint', 0, true, true), // SGP has mediumint, Aeva has int, but at least in Aeva's case that's the author not doing create_table correctly.
		db_field('field_name', 'varchar', 255),
		db_field('description', 'text'),
		db_field('field_type', 'varchar', 20),
		db_field('field_options', 'text'), // For radio/select, the different options are comma separated
		db_field('field_config', 'text'), // Serialised array for configuration beyond what's here (should be charset safe), but that we don't need to do lookups against.
		db_field('field_pos', 'smallint'),
		db_field('active', 'tinyint'),
		db_field('can_search', 'tinyint'),
		db_field('default_val', 'text'), // This needs to remain unserialised in case of a UTF-8 conversion which wouldn't touch the serialised values
		db_field('placement', 'tinyint'),
	),
	'indexes' => array(
		array(
			'columns' => array('id_field'),
			'type' => 'primary',
		),
		// You might assume we'd index can_search or active here but in practice the lack of cardinality plus the typical size of table makes it not worth it.
	),
);
$tables[] = array(
	'table_name' => '{db_prefix}lgal_custom_field_data',
	'columns' => array(
		db_field('id_item', 'int'),
		db_field('id_field', 'smallint'),
		db_field('value', 'text'),
	),
	'indexes' => array(
		array(
			'columns' => array('id_item', 'id_field'),
			'type' => 'primary',
		),
	),
);
$tables[] = array(
	'table_name' => '{db_prefix}lgal_tags',
	'columns' => array(
		db_field('id_tag', 'mediumint', 0, true, true),
		db_field('tag_name', 'varchar', 80),
		db_field('tag_slug', 'varchar', 80),
	),
	'indexes' => array(
		array(
			'columns' => array('id_tag'),
			'type' => 'primary',
		),
		array(
			'columns' => array('tag_name'),
			'type' => 'index',
		),
	),
);
$tables[] = array(
	'table_name' => '{db_prefix}lgal_tag_items',
	'columns' => array(
		db_field('id_tag', 'mediumint'),
		db_field('id_item', 'int'),
	),
	'indexes' => array(
		array(
			'columns' => array('id_item', 'id_tag'),
			'type' => 'primary',
		),
	),
);
// If you add any more tables, please update LevGal_Model_Importer_Abstract::importOverwrite().

// Oh joy, we've now made it to extra rows...
$rows = array();
$rows[] = array(
	'method' => 'ignore',
	'table_name' => '{db_prefix}themes',
	'columns' => array('id_member' => 'int', 'id_theme' => 'int', 'variable' => 'string', 'value' => 'string'),
	'data' => array(
		// Items that should only be accessible by $options for current user
		array(-1, 1, 'lgal_show_mature', 0),
		// Items that should potentially be exposed via $settings for cross-user access
		array(0, 1, 'lgal_show_bookmarks', 1),
	),
	'keys' => array('id_member', 'id_theme', 'variable')
);
$rows[] = array(
	'method' => 'replace',
	'table_name' => '{db_prefix}scheduled_tasks',
	'columns' => array(
		'next_time' => 'int',
		'time_offset' => 'int',
		'time_regularity' => 'int',
		'time_unit' => 'string',
		'disabled' => 'int',
		'task' => 'string',
	),
	'data' => array(
		strtotime('tomorrow'),
		0,
		1,
		'd',
		0,
		'levgal_maintenance',
	),
	'keys' => array('task'),
);

// Now we can add a new column to an existing table
$columns = array();
$columns[] = array(
	'table_name' => '{db_prefix}members',
	'column_info' => db_field('lgal_new', 'tinyint', 0, true, false, 1),
	'parameters' => array(),
	'if_exists' => 'ignore',
	'error' => 'fatal',
);
$columns[] = array(
	'table_name' => '{db_prefix}members',
	'column_info' => db_field('lgal_unseen', 'mediumint'),
	'parameters' => array(),
	'if_exists' => 'ignore',
	'error' => 'fatal',
);
$columns[] = array(
	'table_name' => '{db_prefix}members',
	// all this just to supply the default value
	'column_info' => db_field('lgal_notify', 'tinyint', 0, true, false, 1),
	'parameters' => array(),
	'if_exists' => 'ignore',
	'error' => 'fatal',
);

// Update mod settings if applicable
updateSettings($mod_settings);

// Create new tables, if any
foreach ($tables as $table)
{
	if (!isset($table['if_exists']))
	{
		$table['if_exists'] = 'ignore';
	}

	if (!isset($table['error']))
	{
		$table['error'] = 'fatal';
	}

	if (!isset($table['parameters']))
	{
		$table['parameters'] = array();
	}

	$db_table->db_create_table($table['table_name'], $table['columns'], $table['indexes'], $table['parameters'], $table['if_exists'], $table['error']);

	foreach ($table['columns'] as $table_info)
	{
		$columns[] = array(
			'table_name' => $table['table_name'],
			'column_info' => $table_info,
			'parameters' => array(),
			'if_exists' => 'ignore',
			'error' => 'fatal',
		);
	}

	// This table requires one or more fulltext indexes. If for some weird-ass reason others exist,
	// leave them alone, but we need to verify the ones the installer demands.
	if (!empty($table['parameters']['requires_fulltext']) && ($db_type === 'mysql') )
	{
		$indexes_to_build = $table['parameters']['requires_fulltext'];
		$request = $db->query('', '
			SHOW INDEX
			FROM ' . $table['table_name']
		);
		while ($row = $db->fetch_assoc($request))
		{
			if (in_array($row['Column_name'], $indexes_to_build)
				&& ((isset($row['Index_type']) && $row['Index_type'] === 'FULLTEXT') || (isset($row['Comment']) && $row['Comment'] === 'FULLTEXT')))
			{
				$indexes_to_build = array_diff($indexes_to_build, array($row['Column_name']));
			}
		}
		$db->free_result($request);

		foreach ($indexes_to_build as $index)
		{
			$db->query('', '
				ALTER TABLE ' . $table['table_name'] . '
				DROP INDEX {raw:index}',
				array(
					'db_error_skip' => true,
					'index' => $index,
				)
			);

			$db->query('', '
				ALTER TABLE ' . $table['table_name'] . '
				ADD FULLTEXT {raw:index} ({raw:index})',
				array(
					'index' => $index,
				)
			);
		}
	}
}

// Create new rows, if any
foreach ($rows as $row)
{
	$db->insert($row['method'], $row['table_name'], $row['columns'], $row['data'], $row['keys']);
}

// Create new columns, if any
foreach ($columns as $column)
{
	$db_table->db_add_column($column['table_name'], $column['column_info'], $column['parameters'], $column['if_exists'], $column['error']);
}

// Add integration hooks, if any
foreach ($hooks as $hook)
{
	add_integration_function($hook['hook'], $hook['function']);
}

// Hook removal for cleanup
foreach ($hooksRemove as $hook)
{
	remove_integration_function($hook['hook'], $hook['function'], $hook['file']);
}

// Are we done?
if (ELK === 'SSI')
{
	echo 'Database changes are complete!';
}

/**
 * Helper function to prepare db query
 *
 * @param $name
 * @param $type
 * @param int $size
 * @param bool $unsigned
 * @param false $auto
 * @param int $default
 * @return array
 */
function db_field($name, $type, $size = 0, $unsigned = true, $auto = false, $default = 0)
{
	$fields = array(
		'varchar' => array(
			'auto' => false,
			'type' => 'varchar',
			'size' => $size == 0 ? 50 : $size,
			'null' => false,
		),
		'text' => array(
			'auto' => false,
			'type' => 'text',
			'null' => false,
		),
		'mediumtext' => array(
			'auto' => false,
			'type' => 'mediumtext',
			'null' => false,
		),
		'tinyint' => array(
			'auto' => $auto,
			'type' => 'tinyint',
			'default' => $default,
			'size' => empty($unsigned) ? 4 : 3,
			'unsigned' => $unsigned,
			'null' => false,
		),
		'smallint' => array(
			'auto' => $auto,
			'type' => 'smallint',
			'default' => $default,
			'size' => empty($unsigned) ? 6 : 5,
			'unsigned' => $unsigned,
			'null' => false,
		),
		'mediumint' => array(
			'auto' => $auto,
			'type' => 'mediumint',
			'default' => $default,
			'size' => 8,
			'unsigned' => $unsigned,
			'null' => false,
		),
		'int' => array(
			'auto' => $auto,
			'type' => 'int',
			'default' => $default,
			'size' => empty($unsigned) ? 11 : 10,
			'unsigned' => $unsigned,
			'null' => false,
		),
		'bigint' => array(
			'auto' => $auto,
			'type' => 'bigint',
			'default' => $default,
			'size' => 21,
			'unsigned' => $unsigned,
			'null' => false,
		),
	);

	$field = $fields[$type];
	$field['name'] = $name;

	return $field;
}
