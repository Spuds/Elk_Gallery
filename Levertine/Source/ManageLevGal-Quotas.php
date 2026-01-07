<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.2 / elkarte
 */

/**
 * This file deals with the gallery quotas and limits on what file types are allowed.
 */
class ManageLevGalQuotas_Controller extends Action_Controller
{
	public function pre_dispatch()
	{
		Templates::instance()->load('levgal_tpl/ManageLevGal-Quotas');
		Templates::instance()->load('Admin');
		loadLanguage('levgal_lng/ManageLevGal-Quotas');

		parent::pre_dispatch();
	}

	public function action_index()
	{
		global $context, $txt, $scripturl;

		// Just a single action, and we will be great at doing it
		$subActions = array(
			'quota' => [$this, 'action_levgal_adminQuotas', 'permissions' => 'admin_forum']
		);

		$action = new Action();
		$subAction = $action->initialize($subActions, 'quota');

		// Page items for the template
		$context['sub_action'] = $subAction;
		$context['sub_template'] = 'show_settings';
		$context['page_title'] = $txt['levgal_quotas'];
		$context['post_url'] = $scripturl . '?action=admin;area=lgalquotas;save';

		// Set up action/subaction stuff.
		$action->dispatch($subAction);
	}

	public function action_levgal_adminQuotas()
	{
		global $context, $txt;

		// Define the generic master settings.
		$settingsForm = new Settings_Form(Settings_Form::DB_ADAPTER);
		$settingsForm->setConfigVars($this->_settings());

		// Now we need to get the list of groups.
		$context['group_list'] = array(
			-1 => array(
				'group_name' => $txt['levgal_guests'],
				'online_color' => '',
				'stars' => '',
				'color_name' => $txt['levgal_guests'],
			),
		);

		$groupModel = new LevGal_Model_Group();
		$context['group_list'] += $groupModel->getSimpleGroupList();
		$context['managers'] = $groupModel->allowedTo('lgal_manage');

		// Do we have any rogue quotas that are for groups that are now manager groups?
		$this->levgal_fix_managers();

		// We need some JavaScript for the template, but because we want to keep
		// it all together, *fetch* it from the template.
		Template_Layers::instance()->addEnd('quotas_javascript');

		if (isset($this->_req->query->save))
		{
			$this->levgal_save_quotas($this->_settings(), $settingsForm);
			redirectexit('action=admin;area=lgalquotas');
		}

		$settingsForm->prepare();
	}

	private function _settings()
	{
		global $context;

		// Define the generic master settings.
		$config_vars = array(
			array('title', 'levgal_quotas'),
			array('desc', 'levgal_quotas_desc'),
			array('text', 'lgal_max_space'),
			array('message', 'lgal_max_space_note'),
			array('check', 'lgal_enable_resize'),
			array('message', 'lgal_enable_resize_note'),
		);

		$this->levgal_get_filetypes();

		foreach (array_keys($context['file_types']) as $type)
		{
			$context['container_blocks'][] = $type;
			$config_vars[] = array('title', 'levgal_quotas_' . $type . '_title');
			$config_vars[] = array('check', 'lgal_enable_' . $type, 'javascript' => ' onclick="showHide(\'' . $type . '\')"');
			$config_vars[] = array('callback', 'levgal_quotas_' . $type);
		}

		return $config_vars;
	}

	public function levgal_get_filetypes()
	{
		global $context, $modSettings;

		$context['file_types'] = array(
			'image' => array('jpg', 'gif', 'png',  'webp', 'psd', 'tiff', 'mng', 'iff'),
			'audio' => array('mp3', 'm4a', 'oga', 'flac', 'wav'),
			'video' => array('m4v', 'ogv', 'mov', 'webm', 'mkv'),
			'document' => array('doc', 'xls', 'ppt', 'pdf', 'txt', 'html', 'xml'),
			'archive' => array('zip', 'rar', 'targz', '7z', 'dmg', 'sit', 'lz'),
			'generic' => array('exe', 'ttf'),
			'external' => array('youtube', 'vimeo', 'dailymotion', 'metacafe'),
		);
		$context['selected_file_types'] = array();
		$context['quotas'] = array();
		foreach ($context['file_types'] as $type_id => $types)
		{
			$context['selected_file_types'][$type_id] = empty($modSettings['lgal_' . $type_id . '_formats']) ? array() : array_intersect($types, explode(',', $modSettings['lgal_' . $type_id . '_formats']));
			$context['quotas'][$type_id] = !empty($modSettings['lgal_' . $type_id . '_quotas']) ? Util::unserialize($modSettings['lgal_' . $type_id . '_quotas'],  ['allowed_classes' => false]) : array();
		}
	}

	public function levgal_fix_managers()
	{
		global $context;

		$changed = array();
		foreach ($context['quotas'] as $quota_type => $quota)
		{
			if (empty($quota))
			{
				continue;
			}

			foreach ($quota as $k => $quota_rule)
			{
				$quota_groups = $quota_rule[0];
				$manager_groups = array_intersect($quota_groups, $context['managers']);
				if (count($manager_groups) > 0)
				{
					$quota_groups = array_diff($manager_groups, $context['managers']);
					$changed[$quota_type] = true;
					if (empty($quota_groups))
					{
						unset ($context['quotas'][$quota_type][$k]);
					}
					else
					{
						$context['quotas'][$quota_type][$k][0] = $quota_groups;
					}
				}
			}
		}

		// And did we change anything? If so, update it.
		foreach (array_keys($changed) as $quota_type)
		{
			if (empty($context[$quota_type]))
			{
				continue;
			}

			updateSettings(array('lgal_' . $quota_type . '_quotas' => serialize($context[$quota_type])));

		}
	}

	public function levgal_save_quotas($config_vars, $settingsForm)
	{
		global $context;

		checkSession();

		// First we save the enabled/disabled sections and size
		$settingsForm->setConfigValues($_POST);
		$settingsForm->save();

		// Now to build/save the selected types of magic. This is rather cheeky actually.
		foreach ($context['file_types'] as $type_id => $known_types)
		{
			$config_vars[] = array('text', 'lgal_' . $type_id . '_formats');
			$selected = isset($_POST['formats_' . $type_id]) && is_array($_POST['formats_' . $type_id])
				? array_keys($_POST['formats_' . $type_id])
				: array();
			$_POST['lgal_' . $type_id . '_formats'] = implode(',', array_intersect($selected, $known_types));
		}

		// Now it *really* gets icky. Images first.
		if (isset($_POST['image_quota_groups'], $_POST['image_quota_imagesize'], $_POST['image_quota_filesize']))
		{
			$image_quota = array();
			// Second test. That we have arrays and all are the same size.
			if (is_array($_POST['image_quota_groups']) && is_array($_POST['image_quota_imagesize']) && is_array($_POST['image_quota_filesize']))
			{
				$keys = array_keys($_POST['image_quota_groups']);
				foreach ($keys as $key)
				{
					// If something went wrong with the array, abort, abort, abort!
					if (!isset($_POST['image_quota_groups'][$key], $_POST['image_quota_imagesize'][$key], $_POST['image_quota_filesize'][$key]))
					{
						break;
					}

					// Do *some* semblance of sanity.
					$groups = explode(',', $_POST['image_quota_groups'][$key]);
					foreach ($groups as $k => $v)
					{
						$groups[$k] = (int) $v;
						if ($groups[$k] < -1)
						{
							break 2;
						}
					}

					// Managers can be dispensed with and if there's no quotas left, save.
					$groups = array_diff($groups, $context['managers']);
					if (empty($groups))
					{
						continue;
					}

					// Now image size
					$size = explode('x', $_POST['image_quota_imagesize'][$key]);
					if (count($size) !== 2)
					{
						continue;
					}
					$width = LevGal_Bootstrap::clamp($size[0], 0, 9999);
					$height = LevGal_Bootstrap::clamp($size[1], 0, 9999);
					// If both are empty, it's allowed. Otherwise, it's not.
					if (($width === 0 || $height === 0) && ($width !== $height))
					{
						continue;
					}
					$imagesize = $width . 'x' . $height;

					// Now file size
					$filesize = trim($_POST['image_quota_filesize'][$key]);
					if (!preg_match('~^[\d]+[KMG]$~i', $filesize))
					{
						continue;
					}

					$image_quota[] = array($groups, $imagesize, $filesize);
				}

				if (!empty($image_quota))
				{
					$config_vars[] = array('text', 'lgal_image_quotas');
					$_POST['lgal_image_quotas'] = serialize($image_quota);
				}
			}
		}
		else
		{
			$config_vars[] = array('text', 'lgal_image_quotas');
			$_POST['lgal_image_quotas'] = serialize(array());
		}

		// Everything else, fortunately, is pretty generic. Still icky.
		foreach (array_keys($context['file_types']) as $type_id)
		{
			if ($type_id !== 'image' && $type_id !== 'external')
			{
				if (isset($_POST[$type_id . '_quota_groups'], $_POST[$type_id . '_quota_filesize']))
				{
					$quota = array();
					// Second test. That we have arrays and all are the same size.
					if (is_array($_POST[$type_id . '_quota_groups']) && is_array($_POST[$type_id . '_quota_filesize']))
					{
						$keys = array_keys($_POST[$type_id . '_quota_groups']);
						foreach ($keys as $key)
						{
							// If something went wrong with the array, abort, abort, abort!
							if (!isset($_POST[$type_id . '_quota_groups'][$key], $_POST[$type_id . '_quota_filesize'][$key]))
							{
								break;
							}

							// Do *some* semblance of sanity.
							$groups = explode(',', $_POST[$type_id . '_quota_groups'][$key]);
							foreach ($groups as $k => $v)
							{
								$groups[$k] = (int) $v;
								if ($groups[$k] < -1)
								{
									break 2;
								}
							}

							// Managers can be dispensed with and if there's no quotas left, save.
							$groups = array_diff($groups, $context['managers']);
							if (empty($groups))
							{
								continue;
							}

							// Now file size
							$filesize = trim($_POST[$type_id . '_quota_filesize'][$key]);
							if (!preg_match('~^[\d]+[KMG]$~i', $filesize))
							{
								continue;
							}

							$quota[] = array($groups, $filesize);
						}

						if (!empty($quota))
						{
							$config_vars[] = array('text', 'lgal_' . $type_id . '_quotas');
							$_POST['lgal_' . $type_id . '_quotas'] = serialize($quota);
						}
					}
				}
				else
				{
					$config_vars[] = array('text', 'lgal_' . $type_id . '_quotas');
					$_POST['lgal_' . $type_id . '_quotas'] = serialize(array());
				}
			}
		}

		$settingsForm->setConfigVars($config_vars);
		$settingsForm->setConfigValues($_POST);
		$settingsForm->save();
	}

	/**
	 * Return the LevGal quota settings for use in admin search
	 */
	public function settings_search()
	{
		return $this->_settings();
	}
}
