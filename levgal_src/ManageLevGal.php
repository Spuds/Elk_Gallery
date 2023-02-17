<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.0 / elkarte
 */

/**
 * This file deals with some fundamental things for the admin panel.
 */

/**
 * Function to add LevGal's admin area to ElkArte's own.
 *
 * Also initialises the permissions hooking, done here rather than in the main installer as this
 * will always be run for LevGal installations, and there is no need to maintain an extra hook
 * permanently on top of this one.
 */
function levgal_admin_bootstrap(&$admin_areas)
{
	global $txt;

	loadLanguage('levgal_lng/LevGal');
	loadLanguage('levgal_lng/ManageLevGal');

	$admin_areas['media'] = array(
		'title' => $txt['levgal'],
		'permission' => array('admin_forum'),
		'areas' => array(
			'lgaldash' => array(
				'label' => $txt['levgal_admindash'],
				'file' => '../levgal_src/ManageLevGal-Dash.php',
				'controller' => 'ManageLevGalDash_Controller',
				'function' => 'action_index',
				'icon' => '../../../default/levgal_res/admin/admin.png',
				'subsections' => array(
					'index' => array($txt['levgal_admindash']),
					'modlog' => array($txt['levgal_modlog']),
					'credits' => array($txt['levgal_credits']),
				),
			),
			'lgalsettings' => array(
				'label' => $txt['levgal_settings'],
				'file' => '../levgal_src/ManageLevGal.php',
				'function' => 'levgal_adminSettings',
				'icon' => '../../../default/levgal_res/admin/settings.png',
			),
			'lgalperms' => array(
				'label' => $txt['levgal_perms'],
				'file' => '../levgal_src/ManageLevGal-Perms.php',
				'controller' => 'ManageLevGalPerms_Controller',
				'function' => 'action_index',
				'icon' => '../../../default/levgal_res/admin/perms.png',
			),
			'lgalquotas' => array(
				'label' => $txt['levgal_quotas'],
				'file' => '../levgal_src/ManageLevGal-Quotas.php',
				'controller' => 'ManageLevGalQuotas_Controller',
				'function' => 'action_index',
				'icon' => '../../../default/levgal_res/admin/quotas.png',
			),
			'lgalcfields' => array(
				'label' => $txt['levgal_cfields'],
				'file' => '../levgal_src/ManageLevGal-CFields.php',
				'controller' => 'ManageLevGalCFields_Controller',
				'function' => 'action_index',
				'icon' => '../../../default/levgal_res/admin/cfields.png',
			),
			'lgalmaint' => array(
				'label' => $txt['levgal_maint'],
				'file' => '../levgal_src/ManageLevGal-Maint.php',
				'controller' => 'ManageLevGalMaint_Controller',
				'function' => 'action_index',
				'icon' => '../../../default/levgal_res/admin/maint.png',
			),
			'lgalnotify' => array(
				'label' => $txt['levgal_notify'],
				'controller' => 'ManageFeatures_Controller',
				'function' => 'action_notificationsSettings_display',
				'icon' => '../../../default/levgal_res/admin/notify.png',
			),
			'lgalimport' => array(
				'label' => $txt['levgal_importers'],
				'file' => '../levgal_src/ManageLevGal-Importer.php',
				'function' => 'levgal_adminImport',
				'icon' => '../../../default/levgal_res/admin/importer.png',
			),
		),
	);

	// Reports need some special loving.
	if (!empty($_GET['area']) && $_GET['area'] === 'reports')
	{
		// We don't technically *need* per se to declare these but PHPMD would very much prefer if we did.
		$relabelPermissions = array();
		$hiddenPermissions = $relabelPermissions;
		$leftPermissionGroups = $relabelPermissions;
		$permissionList = $relabelPermissions;
		$permissionGroups = $relabelPermissions;
		levgal_admin_permissions($permissionGroups, $permissionList, $leftPermissionGroups, $hiddenPermissions, $relabelPermissions);
	}

	// Admin function need some extra help
	if (!empty($_GET['area']) && strpos($_GET['area'], 'lgal') === 0)
	{
		loadCSSFile('admin_lg.css', ['subdir' => 'levgal_res', 'stale' => LEVGAL_VERSION]);
		loadJavascriptFile('admin_lg.js', ['subdir' => 'levgal_res', 'stale' => LEVGAL_VERSION]);
		addInlineJavascript('closeFieldsets();', true);
	}

	add_integration_function('integrate_load_permissions', 'levgal_admin_permissions', 'SOURCEDIR/levgal_src/ManageLevGal.php',false);
	add_integration_function('integrate_delete_membergroups', 'LevGal_Model_Group::deleteGroup', '',false);
}

/**
 * Extends the admin privileged session time.
 *
 * ElkArte by default escalates a session while in the admin area for 1 hour. Some maintenance
 * routines may not complete in that time, so for those instances, we can call this function to
 * extend the escalated time for 3 more minutes (per iteration of maintenance/importing) to try
 * to complete the routines appropriately.
 */
function levgal_extend_admin_time()
{
	global $modSettings;

	$refreshTime = 3600; // by default allows 1 hour.
	$extendTime = 180; // We want to extend the session by 3 minutes.
	$extension_limit = time() - $refreshTime + $extendTime; // Since it will take the time as entering admin session, then add one hour before checking, so we have to adjust this.
	if (empty($modSettings['securityDisable']) && (empty($_SESSION['admin_time']) || $_SESSION['admin_time'] < $extension_limit))
	{
		$_SESSION['admin_time'] = $extension_limit;
	}
}

/**
 * Proxy function for getting permissions into ElkArte's core.
 *
 * LevGal uses the standard permissions setup where possible, which has the advantage of keeping
 * performance lean, but for a usability perspective, keeping permissions contained within LevGal's
 * admin area is important. So we need to tell the permissions code that there are new permissions
 * but we don't want to actually show them.
 */
function levgal_admin_permissions(&$permissionGroups, &$permissionList, &$leftPermissionGroups, &$hiddenPermissions, &$relabelPermissions)
{
	global $txt;

	$levgal_perms = array(
		'lgal_view' => false,
		'lgal_manage' => false,
		'lgal_adduseralbum' => false,
		'lgal_addgroupalbum' => false,
		'lgal_addalbum_approve' => false,
		'lgal_approve_album' => false,
		'lgal_edit_album_own' => false,
		'lgal_edit_album_any' => false,
		'lgal_delete_album_own' => false,
		'lgal_delete_album_any' => false,
		'lgal_additem_own' => false,
		'lgal_additem_any' => false,
		'lgal_addbulk' => false,
		'lgal_additem_approve' => false,
		'lgal_approve_item' => false,
		'lgal_edit_item_own' => false,
		'lgal_edit_item_any' => false,
		'lgal_delete_item_own' => false,
		'lgal_delete_item_any' => false,
		'lgal_comment' => false,
		'lgal_comment_appr' => false,
		'lgal_approve_comment' => false,
		'lgal_edit_comment_own' => false,
		'lgal_edit_comment_any' => false,
		'lgal_delete_comment_own' => false,
		'lgal_delete_comment_any' => false,
	);

	call_integration_hook('integrate_lgal_perms_core', array(&$levgal_perms));

	// This stuff is solely so that automated tests don't flag these things unnecessarily. We
	// don't *need* most of this stuff, but it means we can see what is actually important in the report.
	$permissionGroups['membergroup']['simple'][] = 'levgal';
	$permissionGroups['membergroup']['classic'][] = 'levgal';
	$leftPermissionGroups[] = 'levgal';
	$txt['permissiongroup_levgal'] = $txt['levgal_perms'];
	$relabelPermissions['lgal_view'] = 'permissionname_lgal_view';

	foreach ($levgal_perms as $perm_name => $ownany)
	{
		$permissionList['membergroup'][$perm_name] = array($ownany, 'levgal', 'levgal');
		$hiddenPermissions[] = $perm_name;
		if (isset($txt['permissionname_' . $perm_name]))
		{
			$txt['group_perms_name_' . $perm_name] = sprintf($txt['lgal_media_prefix'], $txt['permissionname_' . $perm_name]);
		}
	}
}

function levgal_adminSettings($return_config = false)
{
	global $context, $txt, $scripturl, $modSettings;

	// Things we need.
	loadTemplate('Admin');
	loadTemplate('levgal_tpl/ManageLevGal');

	$context['sub_template'] = 'show_settings';
	$context['page_title'] = $txt['levgal_settings'];
	$context['post_url'] = $scripturl . '?action=admin;area=lgalsettings;save';

	$settingsForm = new Settings_Form(Settings_Form::DB_ADAPTER);

	$config_vars = array(
		array('title', 'levgal_settings'),
		array('desc', 'levgal_settings_desc'),
		array('check', 'lgal_count_author_views'),
		array('check', 'lgal_enable_mature', 'subtext' => $txt['lgal_enable_mature_desc']),
		'',
		array('check', 'lgal_feed_enable_album'),
		array('int', 'lgal_feed_items_album', 'postinput' => $txt['lgal_feed_items_limits']),
		array('check', 'lgal_feed_enable_item'),
		array('int', 'lgal_feed_items_item', 'postinput' => $txt['lgal_feed_items_limits']),
		'',
		array('int', 'lgal_items_per_page', 'postinput' => $txt['lgal_per_page_limits']),
		array('int', 'lgal_comments_per_page', 'postinput' => $txt['lgal_per_page_limits']),
		'',
		array('text', 'lgal_tag_items_list', 80),
		array('check', 'lgal_tag_items_list_more'),
		'',
		'social' => array('callback', 'lgal_social'),
		'metadata' => array('callback', 'lgal_metadata'),
	);

	$context['available_social_icons'] = array('facebook', 'twitter', 'tumblr', 'reddit', 'pinterest');
	$context['enabled_social_icons'] = explode(',', $modSettings['lgal_social']);

	$context['metadata'] = array(
		'images' => array('datetime', 'make', 'flash', 'exposure_time', 'fnumber', 'shutter_speed',
						  'focal_length', 'digitalzoom', 'brightness', 'contrast', 'sharpness',
						  'isospeed', 'lightsource', 'exposure_prog', 'metering_mode', 'sensitivity',
						  'title', 'subject', 'author', 'keywords', 'comment'),
		'audio' => array('title', 'artist', 'album_artist', 'album', 'track_number', 'genre', 'playtime', 'bitrate'),
		'video' => array('title', 'artist', 'album_artist', 'album', 'track_number', 'genre', 'playtime', 'bitrate'),
	);
	$context['selected_metadata'] = unserialize($modSettings['lgal_metadata'], ['allowed_classes' => false]);

	call_integration_hook('integrate_lgal_settings', array(&$config_vars));

	if ($return_config)
	{
		return $config_vars;
	}

	$settingsForm->setConfigVars($config_vars);

	if (isset($_GET['save']))
	{
		checkSession();

		// We can't just do callback items, funnily enough, we need to splice them in.
		$saveSettings = $config_vars;

		// Social icons.
		$saveSettings['social'] = array('text', 'lgal_social');
		$social = isset($_POST['lgal_social']) && is_array($_POST['lgal_social']) ? $_POST['lgal_social'] : array();
		$_POST['lgal_social'] = implode(',', array_intersect($social, $context['available_social_icons']));

		// Limit the feeds to sane values.
		foreach (array('lgal_feed_items_album', 'lgal_feed_items_item') as $type)
		{
			$_POST[$type] = isset($_POST[$type]) ? LevGal_Bootstrap::clamp((int) $_POST[$type], 1, 50) : 10;
		}

		// Limit the items per page
		foreach (array('lgal_items_per_page', 'lgal_comments_per_page') as $type)
		{
			$_POST[$type] = isset($_POST[$type]) ? LevGal_Bootstrap::clamp((int) $_POST[$type], 10, 50) : 24;
		}

		// And metadata.
		$saveSettings['metadata'] = array('text', 'lgal_metadata');
		$metadata = array();
		foreach ($context['metadata'] as $class => $items)
		{
			$metadata[$class] = isset($_POST['metadata_' . $class]) && is_array($_POST['metadata_' . $class]) ? array_intersect($_POST['metadata_' . $class], $items) : array();
		}
		$_POST['lgal_metadata'] = serialize($metadata);

		call_integration_hook('integrate_lgal_settings_save', array(&$saveSettings));

		$settingsForm->setConfigVars($saveSettings);
		$settingsForm->setConfigValues($_POST);
		$settingsForm->save();

		redirectexit('action=admin;area=lgalsettings');
	}

	$settingsForm->prepare();
}
