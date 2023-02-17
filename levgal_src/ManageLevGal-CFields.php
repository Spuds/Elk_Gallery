<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.0 / elkarte
 */

/**
 * This file deals with the custom fields configuration inside the gallery.
 */
class ManageLevGalCFields_Controller extends Action_Controller
{
	public function pre_dispatch()
	{
		loadLanguage('levgal_lng/ManageLevGal-CFields');
		Templates::instance()->load('levgal_tpl/ManageLevGal-CFields');

		parent::pre_dispatch();
	}

	public function action_index()
	{
		global $context;

		$subActions = array(
			'index' => [$this, 'action_levgal_adminCFields_index'],
			'add' => [$this, 'action_levgal_adminCFields_add'],
			'modify' => [$this, 'action_levgal_adminCFields_modify'],
		);

		$action = new Action();
		$subAction = $action->initialize($subActions, 'index');
		$subAction = isset($_POST['addfield']) ? 'add' : $subAction;

		$cfModel = LevGal_Bootstrap::getModel('LevGal_Model_Custom');

		$context['valid_field_types'] = $cfModel->getValidFieldTypes();
		$context['valid_validation'] = $cfModel->getValidValidationTypes();

		$action->dispatch($subAction);
	}

	public function action_levgal_adminCFields_index()
	{
		global $context, $txt, $modSettings;

		$context['sub_template'] = 'cfields';
		$context['page_title'] = $txt['levgal_cfields'];
		$modSettings['jquery_include_ui'] = true;

		$cfModel = LevGal_Bootstrap::getModel('LevGal_Model_Custom');
		$context['custom_fields'] = $cfModel->getAllCustomFields();

		if (isset($_POST['saveorder']))
		{
			checkSession();

			if (!empty($_POST['field']) && is_array($_POST['field']) && count(array_intersect($_POST['field'], array_keys($context['custom_fields']))) == count($_POST['field']))
			{
				$field_pos = 1;
				foreach ($_POST['field'] as $field_id)
				{
					$cfModel->updateField($field_id, array('field_pos' => $field_pos++));
				}

				redirectexit('action=admin;area=lgalcfields');
			}
		}
	}

	private function lgal_get_default_field()
	{
		return array(
			'field_name' => '',
			'description' => '',
			'field_type' => 'text',
			'active' => 1,
			'can_search' => 0,
			'placement' => 0,
			'default_val' => '',
			'field_options' => array(),
			'field_config' => array(
				'required' => false,
				'bbc' => false,
				'default_size' => array('columns' => 30, 'rows' => 4),
				'max_length' => 255,
				'min' => 0,
				'max' => 0,
				'validation' => 'nohtml',
				'valid_regex' => '',
				'all_albums' => true,
				'albums' => array(),
			),
		);
	}

	public function action_levgal_adminCFields_add()
	{
		global $context, $txt, $scripturl;

		$context['sub_template'] = 'cfields_modify';
		$context['page_title'] = $txt['levgal_cfields_add'];
		$context['form_url'] = $scripturl . '?action=admin;area=lgalcfields;sa=modify';
		$context['field_id'] = 'add';

		$context['custom_field'] = $this->lgal_get_default_field();

		$album_list = LevGal_Bootstrap::getModel('LevGal_Model_AlbumList');
		$context['hierarchies'] = $album_list->getAllHierarchies();
	}

	public function action_levgal_adminCFields_modify()
	{
		global $context, $txt, $scripturl;

		loadLanguage('ManageSettings');

		$context['sub_template'] = 'cfields_modify';
		$context['page_title'] = $txt['levgal_cfields_modify'];
		$context['form_url'] = $scripturl . '?action=admin;area=lgalcfields;sa=modify';
		$context['field_id'] = 'add';

		$cfModel = new LevGal_Model_Custom();
		$default_field = $this->lgal_get_default_field();

		if (!empty($_REQUEST['field']))
		{
			$field = $cfModel->getCustomFieldById((int) $_REQUEST['field']);
			if (!empty($field))
			{
				$context['field_id'] = (int) $_REQUEST['field'];
				$context['custom_field'] = array_merge($default_field, $field);
				$context['custom_field']['field_config'] = array_merge($default_field['field_config'], $context['custom_field']['field_config']);
				if (!empty($context['custom_field']['field_config']['albums']))
				{
					$context['custom_field']['field_config']['all_albums'] = false;
				}

				// Certain fields can only be turned in certain directions.
				$conversion = array(
					'integer' => array('integer', 'float'),
					'float' => array('float'),
					'text' => array('text', 'largetext'),
					'largetext' => array('text', 'largetext'),
					'select' => array('select', 'radio', 'multiselect'),
					'multiselect' => array('multiselect'),
					'radio' => array('select', 'radio', 'multiselect'),
					'checkbox' => array('checkbox'),
				);
				$context['valid_field_types'] = $conversion[$context['custom_field']['field_type']];
			}

			$album_list = LevGal_Bootstrap::getModel('LevGal_Model_AlbumList');
			$context['hierarchies'] = $album_list->getAllHierarchies();
		}

		if (isset($_REQUEST['deletefield']) && $context['field_id'] !== 'add')
		{
			checkSession();
			$cfModel->deleteCustomField($context['field_id']);
			redirectexit('action=admin;area=lgalcfields');
		}

		if (isset($_REQUEST['savefield']))
		{
			checkSession();

			// At this stage we just grab everything for template purposes. We'll rebuild field_config before we pass it on however.
			$context['custom_field']['field_name'] = LevGal_Helper_Sanitiser::sanitiseTextFromPost('field_name');
			if (empty($context['custom_field']['field_name']))
			{
				$context['errors'][] = $txt['levgal_cfields_empty_field'];
			}
			$context['custom_field']['description'] = LevGal_Helper_Sanitiser::sanitiseBBCTextFromPost('description');
			$context['custom_field']['placement'] = isset($_POST['placement']) && in_array((int) $_POST['placement'], array(0, 1, 2), true) ? (int) $_POST['placement'] : 0;
			$context['custom_field']['active'] = !empty($_POST['active']) ? 1 : 0;
			$context['custom_field']['can_search'] = !empty($_POST['can_search']) ? 1 : 0;
			$context['custom_field']['field_config']['required'] = !empty($_POST['required']) ? 1 : 0;
			$context['custom_field']['field_config']['bbc'] = !empty($_POST['bbc']) ? 1 : 0;
			$context['custom_field']['field_config']['validation'] = isset($_POST['field_validation']) && in_array($_POST['field_validation'], $context['valid_validation'], true) ? $_POST['field_validation'] : 'nohtml';
			$context['custom_field']['default_val'] = LevGal_Helper_Sanitiser::sanitiseTextFromPost('default_val');
			$context['custom_field']['field_config']['max_length'] = LevGal_Helper_Sanitiser::sanitiseIntFromPost('max_length', 0, 30000);

			if (!empty($_POST['all_albums']))
			{
				$context['custom_field']['field_config']['all_albums'] = true;
				$context['custom_field']['field_config']['albums'] = array();
			}
			else
			{
				$albums = array();
				if (!empty($context['hierarchies']['site']))
				{
					foreach ($context['hierarchies']['site'] as $album)
					{
						if (!empty($_POST['alb']['site_' . $album['id_album']]))
						{
							$albums[] = (int) $album['id_album'];
						}
					}
				}
				foreach (array('member', 'group') as $type)
				{
					if (!empty($context['hierarchies'][$type]))
					{
						foreach ($context['hierarchies'][$type] as $id_owner => $owner)
						{
							foreach ($owner['albums'] as $album)
							{
								if (!empty($_POST['alb'][$type . '_' . $id_owner . '_' . $album['id_album']]))
								{
									$albums[] = (int) $album['id_album'];
								}
							}
						}
					}
				}
				$albums = array_unique($albums);
				$context['custom_field']['field_config']['all_albums'] = false;
				$context['custom_field']['field_config']['albums'] = $albums;
			}
			if ($context['custom_field']['field_config']['validation'] === 'regex')
			{
				if (!empty($_POST['valid_regex']))
				{
					$context['custom_field']['field_config']['valid_regex'] = $_POST['valid_regex']; // This needs to be unsanitised to work correctly.
				}
				else
				{
					$context['custom_field']['field_config']['validation'] = 'nohtml';
				}
			}
			else
			{
				$context['custom_field']['field_config']['valid_regex'] = '';
			}

			if (isset($_POST['field_type']) && in_array($_POST['field_type'], $context['valid_field_types'], true))
			{
				$context['custom_field']['field_type'] = $_POST['field_type'];
				switch ($context['custom_field']['field_type'])
				{
					case 'integer':
					case 'float':
						$context['custom_field']['field_config']['min'] = LevGal_Helper_Sanitiser::sanitiseIntFromPost('min');
						$context['custom_field']['field_config']['max'] = LevGal_Helper_Sanitiser::sanitiseIntFromPost('max');
						if ($context['custom_field']['field_config']['min'] > $context['custom_field']['field_config']['max'])
						{
							$temp = $context['custom_field']['field_config']['min'];
							$context['custom_field']['field_config']['min'] = $context['custom_field']['field_config']['max'];
							$context['custom_field']['field_config']['max'] = $temp;
						}
						if ($context['custom_field']['field_type'] === 'integer')
						{
							$context['custom_field']['default_val'] = (int) $context['custom_field']['default_val'];
						}
						else
						{
							$context['custom_field']['default_val'] = (float) $context['custom_field']['default_val'];
						}
						if (!empty($context['custom_field']['field_config']['min']) && !empty($context['custom_field']['field_config']['max']))
						{
							$context['custom_field']['default_val'] = LevGal_Bootstrap::clamp($context['custom_field']['default_val'], $context['custom_field']['field_config']['min'], $context['custom_field']['field_config']['max']);
						}
						break;
					case 'text':
						// Nothing beyond what we already did above needs doing here.
						break;
					case 'largetext':
						$context['custom_field']['field_config']['validation'] = 'nohtml';
						$context['custom_field']['field_config']['valid_regex'] = '';
						break;
					case 'select':
					case 'radio':
						if (isset($_POST['select_option']) && is_array($_POST['select_option']))
						{
							$items = $_POST['select_option'];
							$_POST['default_select'] = $_POST['default_select'] ?? '';
							$field_options = array();
							$default = '';
							foreach ($items as $k => $v)
							{
								$v = Util::htmltrim($v);
								if ($v === '')
								{
									continue;
								}
								$v = Util::htmlspecialchars($v, ENT_QUOTES);
								$field_options[] = $v;
								if ($_POST['default_select'] == $k)
								{
									$default = $v;
								}
							}
							if (empty($field_options))
							{
								$context['errors'][] = $txt['levgal_cfields_empty_options'];
							}
							else
							{
								$context['custom_field']['field_options'] = $field_options;
								$context['custom_field']['default_val'] = $default;
							}
						}
						break;
					case 'multiselect':
						if (isset($_POST['select_option']) && is_array($_POST['select_option']))
						{
							$items = $_POST['select_option'];
							$_POST['default_selectmulti'] = isset($_POST['default_selectmulti']) ? (array) $_POST['default_selectmulti'] : array();
							if (isset($_POST['default_selectmulti'][0]))
							{
								$_POST['default_selectmulti'] = array();
							}
							$field_options = array();
							$default = array();
							foreach ($items as $k => $v)
							{
								$v = Util::htmltrim($v);
								if ($v === '')
								{
									continue;
								}
								$v = Util::htmlspecialchars($v, ENT_QUOTES);
								$field_options[] = $v;
								if (isset($_POST['default_selectmulti'][$k]))
								{
									$default[] = str_replace(',', '', $v);
								}
							}
							$default = implode(',', $default);
							if (empty($field_options))
							{
								$context['errors'][] = $txt['levgal_cfields_empty_options'];
							}
							else
							{
								$context['custom_field']['field_options'] = $field_options;
								$context['custom_field']['default_val'] = $default;
							}
						}
						break;
					case 'checkbox':
						$context['custom_field']['can_search'] = 0; // Never searchable
						break;
				}
			}

			if (empty($context['errors']))
			{
				if ($context['field_id'] === 'add')
				{
					$cfModel->createField($context['custom_field']);
				}
				else
				{
					$cfModel->updateField($context['field_id'], $context['custom_field']);
				}
				redirectexit('action=admin;area=lgalcfields');
			}
			else
			{
				// We will want this for the error list template.
				Templates::instance()->load('levgal_tpl/LevGal');
			}
		}
	}
}