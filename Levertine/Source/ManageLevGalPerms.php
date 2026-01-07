<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0 / elkarte
 */

namespace Addons\Levertine\Source;

use ElkArte\AbstractController;
use ElkArte\Action;
use ElkArte\SettingsForm\SettingsForm;

/**
 * This file deals with the gallery permissions.
 */
class ManageLevGalPerms extends AbstractController
{
	public function pre_dispatch()
	{
		theme()->getTemplates()->load('Admin');

		parent::pre_dispatch();
	}

	public function action_index()
	{
		global $context, $txt, $scripturl;

		// Control for a single action, why not!
		$subActions = [
			'perms' => [
				'controller' => $this,
				'function' => 'action_levgal_adminPerms',
				'permission' => 'admin_forum']
		];

		$action = new Action();
		$subAction = $action->initialize($subActions, 'perms');

		// Page items for the template
		$context['sub_action'] = $subAction;
		$context['sub_template'] = 'show_settings';
		$context['page_title'] = $txt['levgal_perms'];
		$context['post_url'] = $scripturl . '?action=admin;area=lgalperms;save';

		// Set up action/subaction stuff.
		$action->dispatch($subAction);
	}

	public function action_levgal_adminPerms()
	{
		global $context;

		// Things we need.
		$settingsForm = new SettingsForm(SettingsForm::DB_ADAPTER);

		// Initialize it with our settings
		$settingsForm->setConfigVars($this->_settings());

		// Permissions we don't want users to have.
		$denied_permissions = [
			// Group -1: guests, lots of things guests shouldn't have.
			-1 => [
				'lgal_manage', 'lgal_adduseralbum', 'lgal_addgroupalbum', 'lgal_addalbum_approve', 'lgal_approve_album',
				'lgal_edit_album_own', 'lgal_edit_album_any', 'lgal_delete_album_own', 'lgal_delete_album_any',
				'lgal_additem_own', 'lgal_addbulk', 'lgal_approve_item', 'lgal_edit_item_own', 'lgal_edit_item_any',
				'lgal_delete_item_own', 'lgal_delete_item_any', 'lgal_approve_comment', 'lgal_edit_comment_own',
				'lgal_edit_comment_any', 'lgal_delete_comment_own', 'lgal_delete_comment_any'
			],
			// Group 0: regular members, there are things they should not have.
			0 => [
				'lgal_addgroupalbum',
			],
		];

		call_integration_hook('integrate_lgal_perms_denied', [&$denied_permissions]);

		if (isset($_GET['save']))
		{
			checkSession();

			// Since we're saving, we need to make sure that a certain permission never ever gets saved.
			// Guests should never be able to manage the gallery.
			foreach ($denied_permissions as $group => $perm_list)
			{
				foreach ($perm_list as $perm)
				{
					$_POST[$perm][$group] = 'off';
				}
			}

			$settingsForm->setConfigValues($_POST);
			$settingsForm->save();

			redirectexit('action=admin;area=lgalperms');
		}

		theme()->addInlineJavascript('closeFieldsets();', true);
		$settingsForm->prepare();

		// There are certain permissions we do not want giving out. For example, admin rights!
		foreach ($denied_permissions as $group => $perm_list)
		{
			foreach ($perm_list as $perm)
			{
				unset ($context[$perm][$group]);
			}
		}
	}

	/**
	 * The big ol array of permission love
	 *
	 * @return array
	 */
	private function _settings()
	{
		global $txt;

		$config_vars = [
			['title', 'levgal_perms_general'],
			['permissions', 'lgal_view'],
			['permissions', 'lgal_manage', 'collapsed' => true, 'subtext' => $txt['lgal_manage_note']],
			['title', 'levgal_perms_album'],
			['permissions', 'lgal_adduseralbum'],
			['permissions', 'lgal_addgroupalbum'],
			['permissions', 'lgal_addalbum_approve'],
			['permissions', 'lgal_approve_album'],
			['permissions', 'lgal_edit_album_own'],
			['permissions', 'lgal_edit_album_any'],
			['permissions', 'lgal_delete_album_own'],
			['permissions', 'lgal_delete_album_any'],
			['title', 'levgal_perms_item'],
			['permissions', 'lgal_additem_own'],
			['permissions', 'lgal_additem_any'],
			['permissions', 'lgal_addbulk'],
			['permissions', 'lgal_additem_approve'],
			['permissions', 'lgal_approve_item'],
			['permissions', 'lgal_edit_item_own'],
			['permissions', 'lgal_edit_item_any'],
			['permissions', 'lgal_delete_item_own'],
			['permissions', 'lgal_delete_item_any'],
			['title', 'levgal_perms_comments'],
			['permissions', 'lgal_comment'],
			['permissions', 'lgal_comment_appr'],
			['permissions', 'lgal_approve_comment'],
			['permissions', 'lgal_edit_comment_own'],
			['permissions', 'lgal_edit_comment_any'],
			['permissions', 'lgal_delete_comment_own'],
			['permissions', 'lgal_delete_comment_any'],
			['title', 'levgal_perms_moderation'],
			['desc', 'levgal_perms_moderation_desc'],
			['check', 'lgal_selfmod_approve_item'],
			'',
			['check', 'lgal_selfmod_approve_comment'],
			['check', 'lgal_selfmod_edit_comment'],
			['check', 'lgal_selfmod_delete_comment'],
			['check', 'lgal_selfmod_lock_comment'],
		];

		call_integration_hook('integrate_lgal_perms_config', [&$config_vars]);

		// While permissionname_* is defined, ElkArte's expects groups_* for permissions-as-settings.
		// We are not exposing the permissionname strings to the permissions area anyway, so
		// that's a non-issue. But we have to have the strings defined there regardless, so
		// let's just quickly clone them.
		foreach ($config_vars as $var)
		{
			if (!empty($var) && $var[0] === 'permissions')
			{
				$txt['groups_' . $var[1]] = $txt['permissionname_' . $var[1]];
			}
		}

		return $config_vars;
	}

	/**
	 * Return the LevGal perm settings for use in admin search
	 */
	public function settings_search()
	{
		return $this->_settings();
	}
}
