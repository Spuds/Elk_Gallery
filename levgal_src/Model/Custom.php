<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license proprietary
 *
 * @version 1.1.1 / elkarte
 */

/**
 * This file deals with custom fields.
 */
class LevGal_Model_Custom
{
	/** @var array  */
	private $cache;

	public function __construct()
	{
		$this->cache = array();
	}

	public function getValidFieldTypes()
	{
		return array('integer', 'float', 'text', 'largetext', 'select', 'multiselect', 'radio', 'checkbox');
	}

	public function getValidValidationTypes()
	{
		return array('nohtml', 'email', 'numbers', 'regex');
	}

	public function getCustomFieldsByAlbum($album)
	{
		$db = database();

		if (!empty($this->cache['albums'][$album]))
		{
			return $this->cache['albums'][$album];
		}

		$cache_key = 'lgal_cfield_a' . $album;
		$cache_ttl = 150;

		if (($temp = cache_get_data($cache_key, $cache_ttl)) === null)
		{
			$temp = array();
			$request = $db->query('', '
				SELECT 
				    id_field, field_name, description, field_type, field_options,
					field_config, field_pos, active, can_search, default_val, placement
				FROM {db_prefix}lgal_custom_field
				WHERE active = 1
				ORDER BY field_pos, id_field');
			while ($row = $db->fetch_assoc($request))
			{
				$row['field_options'] = !empty($row['field_options']) ? explode(',', $row['field_options']) : array();
				$row['field_config'] = !empty($row['field_config']) ? unserialize($row['field_config']) : array();
				$row['description_raw'] = $row['description'];
				$row['description'] = parse_bbc($row['description']);
				if (!empty($row['field_config']['all_albums']) || (!empty($row['field_config']['albums']) && in_array($album, $row['field_config']['albums'])))
				{
					$temp[$row['id_field']] = $row;
				}
			}
			$db->free_result($request);

			cache_put_data($cache_key, $temp, $cache_ttl);
		}

		$this->cache['albums'][$album] = $temp;

		return $temp;
	}

	public function getCustomFieldValues($item, $album)
	{
		$db = database();

		$fields = $this->getCustomFieldsByAlbum($album);
		$values = array();

		if (empty($fields))
		{
			return array();
		}

		$request = $db->query('', '
			SELECT 
				id_field, value
			FROM {db_prefix}lgal_custom_field_data
			WHERE id_item = {int:item}
				AND id_field IN ({array_int:fields})',
			array(
				'item' => $item,
				'fields' => array_keys($fields),
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$values[$row['id_field']]['raw'] = $row['value'];
			if ($fields[$row['id_field']]['field_type'] === 'multiselect')
			{
				$row['value'] = explode(',', $row['value']);
				if (!empty($fields[$row['id_field']]['field_config']['bbc']))
				{
					foreach ($row['value'] as $k => $v)
					{
						$row['value'][$k] = parse_bbc($v);
					}
				}
				$values[$row['id_field']]['display'] = implode(', ', $row['value']);
			}
			else
			{
				$values[$row['id_field']]['display'] = !empty($fields[$row['id_field']]['field_config']['bbc']) ? parse_bbc($row['value']) : $row['value'];
			}
		}
		$db->free_result($request);

		return $values;
	}

	public function deleteCustomField($id_field)
	{
		$db = database();

		// 1. Get all the items with this field
		$items = array();
		$request = $db->query('', '
			SELECT 
				id_item
			FROM {db_prefix}lgal_custom_field_data
			WHERE id_field = {int:field}',
			array(
				'field' => $id_field,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$items[] = $row['id_item'];
		}
		$db->free_result($request);

		// 2. Delete all the entries for this field from data table
		if (!empty($items))
		{
			$db->query('', '
				DELETE FROM {db_prefix}lgal_custom_field_data
				WHERE id_item IN ({array_int:items})
					AND id_field = {int:field}',
				array(
					'items' => $items,
					'field' => $id_field,
				)
			);
		}

		// 3. Delete entry from main CF table
		$db->query('', '
			DELETE FROM {db_prefix}lgal_custom_field
			WHERE id_field = {int:field}',
			array(
				'field' => $id_field,
			)
		);

		// 4. Assess if, for those items we fetched in step 1, if any of them no longer have any fields - if not, update has_custom for those items.
		$items_without_fields = array();

		if (!empty($items))
		{
			$request = $db->query('', '
				SELECT 
					li.id_item, COUNT(lcfd.id_field) AS num_fields
				FROM {db_prefix}lgal_items AS li
					LEFT JOIN {db_prefix}lgal_custom_field_data AS lcfd ON (li.id_item = lcfd.id_item)
				WHERE li.id_item IN ({array_int:items})
				GROUP BY li.id_item',
				array(
					'items' => $items,
				)
			);
			while ($row = $db->fetch_assoc($request))
			{
				if (empty($row['num_fields']))
				{
					$items_without_fields[] = $row['id_item'];
				}
			}
			$db->free_result($request);

			if (!empty($items_without_fields))
			{
				$db->query('', '
					UPDATE {db_prefix}lgal_items
					SET has_custom = 0
					WHERE id_item IN ({array_int:items})',
					array(
						'items' => $items_without_fields,
					)
				);
			}
		}

		$this->recacheFields();
	}

	public function getCustomFieldById($id)
	{
		$fields = $this->getAllCustomFields();

		return $fields[$id] ?? array();
	}

	public function getAllCustomFields()
	{
		$db = database();

		$cache_key = 'lgal_custom_fields';
		$cache_ttl = 120;
		if (($fields = cache_get_data($cache_key, $cache_ttl)) === null)
		{
			$fields = array();
			$request = $db->query('', '
				SELECT 
				    id_field, field_name, description, field_type, field_options,
					field_config, field_pos, active, can_search, default_val, placement
				FROM {db_prefix}lgal_custom_field
				ORDER BY field_pos, id_field');
			while ($row = $db->fetch_assoc($request))
			{
				$row['field_options'] = !empty($row['field_options']) ? explode(',', $row['field_options']) : array();
				$row['field_config'] = !empty($row['field_config']) ? unserialize($row['field_config']) : array();
				$fields[$row['id_field']] = $row;
			}
			$db->free_result($request);

			cache_put_data($cache_key, $fields, $cache_ttl);
		}

		return $fields;
	}

	public function getSearchableFields()
	{
		$fields = $this->getAllCustomFields();
		$searchable_fields = array();
		foreach ($fields as $id_field => $field)
		{
			if (!empty($field['active']) && !empty($field['can_search']))
			{
				$searchable_fields[$id_field] = $field;
			}
		}

		return $searchable_fields;
	}

	public function deleteFieldsByItems($items)
	{
		$db = database();

		$items = (array) $items;
		if (!empty($items))
		{
			$db->query('', '
				DELETE FROM {db_prefix}lgal_custom_field_data
				WHERE id_item IN ({array_int:items})',
				array(
					'items' => $items,
				)
			);
		}
	}

	public function createField($opts)
	{
		$db = database();

		if (empty($opts['field_type']) || !in_array($opts['field_type'], $this->getValidFieldTypes()))
		{
			$opts['field_type'] = 'text';
		}

		list ($field_options, $field_config) = $this->buildConfig($opts);

		$row = array(
			'field_name' => $opts['field_name'],
			'description' => !empty($opts['description']) ? $opts['description'] : '',
			'field_type' => $opts['field_type'],
			'field_options' => $field_options,
			'field_config' => !empty($field_config) ? serialize($field_config) : '',
			'field_pos' => 1,
			'active' => !empty($opts['active']) ? 1 : 0,
			'can_search' => !empty($opts['can_search']) ? 1 : 0,
			'default_val' => !empty($opts['default_val']) ? $opts['default_val'] : '',
			'placement' => !empty($opts['placement']) ? $opts['placement'] : 0,
		);

		// Make some room.
		$db->query('', '
			UPDATE {db_prefix}lgal_custom_field
			SET field_pos = field_pos + 1');

		// And... tada.
		$db->insert('replace',
			'{db_prefix}lgal_custom_field',
			array('field_name' => 'string', 'description' => 'string', 'field_type' => 'string', 'field_options' => 'string',
				  'field_config' => 'string', 'field_pos' => 'int', 'active' => 'int', 'can_search' => 'int',
				  'default_val' => 'string', 'placement' => 'int'),
			$row,
			array('id_field')
		);

		$this->recacheFields();

		return $db->insert_id('{db_prefix}lgal_custom_field');
	}

	protected function buildConfig($opts)
	{
		$field_config = array();
		$field_options = '';
		switch ($opts['field_type'])
		{
			case 'text':
				$field_config['max_length'] = isset($opts['field_config']['max_length']) ? (int) $opts['field_config']['max_length'] : '';
				$field_config['validation'] = isset($opts['field_config']['validation']) && in_array($opts['field_config']['validation'], $this->getValidValidationTypes()) ? $opts['field_config']['validation'] : 'nohtml';
				if ($field_config['validation'] === 'regex')
				{
					$field_config['valid_regex'] = $opts['field_config']['valid_regex'] ?? '';
				}
				break;
			case 'largetext':
				$field_config['max_length'] = isset($opts['field_config']['max_length']) ? (int) $opts['field_config']['max_length'] : '';
				$field_config['default_size'] = array(
					'columns' => isset($opts['field_config']['default_size']['columns']) ? (int) $opts['field_config']['default_size']['columns'] : 30,
					'rows' => isset($opts['field_config']['default_size']['rows']) ? (int) $opts['field_config']['default_size']['rows'] : 4,
				);
				break;
			case 'integer':
				if (isset($opts['field_config']['min'], $opts['field_config']['max']))
				{
					$field_config['min'] = (int) $opts['field_config']['min'];
					$field_config['max'] = (int) $opts['field_config']['max'];
				}
				break;
			case 'float':
				if (isset($opts['field_config']['min'], $opts['field_config']['max']))
				{
					$field_config['min'] = (float) $opts['field_config']['min'];
					$field_config['max'] = (float) $opts['field_config']['max'];
				}
				break;
			case 'select':
			case 'multiselect':
			case 'radio':
				foreach ($opts['field_options'] as $k => $v)
				{
					$opts['field_options'][$k] = trim(str_replace(',', '', $v));
				}
				$field_options = implode(',', $opts['field_options']);
				break;
			case 'checkbox':
				break;
		}

		if (!empty($opts['field_config']['bbc']) && in_array($opts['field_type'], array('text', 'largetext', 'radio', 'multiselect')))
		{
			$field_config['bbc'] = true;
		}
		if (!empty($opts['field_config']['required']))
		{
			$field_config['required'] = true;
		}

		if (!empty($opts['field_config']['all_albums']))
		{
			$field_config['all_albums'] = true;
		}
		else
		{
			$field_config['albums'] = !empty($opts['field_config']['albums']) ? $opts['field_config']['albums'] : array();
		}

		return array($field_options, $field_config);
	}

	public function updateField($field, $opts)
	{
		$db = database();

		$criteria = array();
		$values = array();

		// Strings.
		foreach (array('field_name', 'description', 'default_val', 'field_type') as $opt)
		{
			if (isset($opts[$opt]))
			{
				$criteria[] = $opt . ' = {string:' . $opt . '}';
				$values[$opt] = !empty($opts[$opt]) ? $opts[$opt] : '';
			}
		}

		// Special strings
		if (isset($opts['field_options'], $opts['field_config']))
		{
			list ($opts['field_options'], $opts['field_config']) = $this->buildConfig($opts);
			$criteria[] = 'field_options = {string:field_options}';
			$criteria[] = 'field_config = {string:field_config}';
			$values['field_options'] = $opts['field_options'];
			$values['field_config'] = serialize($opts['field_config']);
		}

		// Special integers we know about.
		if (isset($opts['placement']) && in_array($opts['placement'], array(0, 1, 2)))
		{
			$criteria[] = 'placement = {int:placement}';
			$values['placement'] = $opts['placement'];
		}

		// Regular ints.
		foreach (array('field_pos') as $opt)
		{
			if (isset($opts[$opt]))
			{
				$criteria[] = $opt . ' = {int:' . $opt . '}';
				$values[$opt] = !empty($opts[$opt]) ? (int) $opts[$opt] : '';
			}
		}

		// Pseudobools.
		foreach (array('can_search', 'active') as $opt)
		{
			if (isset($opts[$opt]))
			{
				$criteria[] = $opt . ' = {int:' . $opt . '}';
				$values[$opt] = !empty($opts[$opt]) ? 1 : 0;
			}
		}

		if (!empty($criteria))
		{
			$values['id_field'] = $field;

			$db->query('', '
				UPDATE {db_prefix}lgal_custom_field
				SET ' . implode(', ', $criteria) . '
				WHERE id_field = {int:id_field}',
				$values
			);
		}

		$this->recacheFields();
	}

	protected function recacheFields()
	{
		cache_put_data('lgal_custom_fields', null);
	}

	public function prepareFieldInputs($album, $item = null)
	{
		$fields = $this->getCustomFieldsByAlbum($album);
		if (!empty($fields))
		{
			// If we have an item, use that, otherwise apply default values.
			if (!empty($item))
			{
				$values = $this->getCustomFieldValues($item, $album);
				foreach (array_keys($fields) as $id_field)
				{
					$fields[$id_field]['value'] = isset($values[$id_field]) ? $values[$id_field]['raw'] : '';
				}
			}
			else
			{
				foreach ($fields as $id_field => $field)
				{
					$fields[$id_field]['value'] = $field['default_val'];
				}
			}
		}

		return $fields;
	}

	public function getFieldValuesFromPost($fields)
	{
		global $txt;

		loadLanguage('levgal_lng/LevGal-Errors');
		$values = array();
		$errors = array();

		foreach ($fields as $id_field => $field)
		{
			// Each type of field has its own quirks of validation.
			switch ($field['field_type'])
			{
				case 'integer':
				case 'float':
					if (isset($_POST['field_' . $id_field]) && is_numeric($_POST['field_' . $id_field]))
					{
						$values[$id_field] = $field['field_type'] === 'int' ? (int) $_POST['field_' . $id_field] : (float) $_POST['field_' . $id_field];
						if (!empty($field['field_config']['min']) && !empty($field['field_config']['max']) && ($values[$id_field] < $field['field_config']['min'] || $values[$id_field] > $field['field_config']['max']))
						{
							$errors[] = sprintf($txt['lgal_invalid_number_range'], $field['field_name'], $field['field_config']['min'], $field['field_config']['max']);
						}
					}
					elseif (!empty($field['field_config']['required']))
					{
						$values[$id_field] = '';
						if (!empty($field['field_config']['min']) && !empty($field['field_config']['max']))
						{
							$errors[] = sprintf($txt['lgal_invalid_number_range'], $field['field_name'], $field['field_config']['min'], $field['field_config']['max']);
						}
						else
						{
							$errors[] = sprintf($txt['lgal_missing_required_field'], $field['field_name']);
						}
					}
					else
					{
						$values[$id_field] = 0;
					}
					break;
				case 'text':
				case 'largetext':
					// Text is the most complex. Start by getting the field and applying an immediate nohtml validation.
					$values[$id_field] = LevGal_Helper_Sanitiser::sanitiseTextFromPost('field_' . $id_field);
					// First, check the length.
					if (!empty($field['field_config']['max_length']))
					{
						if (Util::strlen($values[$id_field]) > $field['field_config']['max_length'])
						{
							$errors[] = sprintf($txt['lgal_string_too_long'], $field['field_name'], $field['field_config']['max_length']);
							continue 2;
						}
					}
					elseif (!empty($field['field_config']['required']) && $values[$id_field] === '')
					{
						$errors[] = sprintf($txt['lgal_missing_required_field'], $field['field_name']);
						continue 2;
					}

					if ($field['field_type'] === 'text')
					{
						// Now do the rest of the validation on it. nohtml was already done, though, so that just leaves the rest.
						switch ($field['field_config']['validation'])
						{
							case 'email':
								$email = filter_var($values[$id_field], FILTER_VALIDATE_EMAIL);
								if (empty($email))
								{
									$errors[] = sprintf($txt['lgal_invalid_email_field'], $field['field_name']);
									continue 3;
								}
								break;
							case 'numbers':
								if (!preg_match('~^\d+$~', $values[$id_field]))
								{
									$errors[] = sprintf($txt['lgal_invalid_numbers_field'], $field['field_name']);
									continue 3;
								}
								break;
							case 'regex':
								if (!preg_match($field['field_config']['valid_regex'], $values[$id_field]))
								{
									$errors[] = sprintf($txt['lgal_invalid_field'], $field['field_name']);
									continue 3;
								}
								break;
						}
					}

					break;
				case 'select':
				case 'radio':
					// We have an array of 0..(n-1) index items, but we display them with values of 1..n in the HTML
					// so that we can conveniently do sanitisation.
					if (isset($_POST['field_' . $id_field]) && is_numeric($_POST['field_' . $id_field]))
					{
						$val = (int) $_POST['field_' . $id_field];
						if ($val >= 1 && $val <= count($field['field_options']))
						{
							$values[$id_field] = $field['field_options'][$val - 1];
						}
						else
						{
							$values[$id_field] = '';
						}
					}

					if (empty($values[$id_field]) && !empty($field['field_config']['required']))
					{
						$values[$id_field] = '';
						$errors[] = sprintf($txt['lgal_missing_required_field'], $field['field_name']);
					}
					break;
				case 'multiselect':
					// Like select/radio, we have a zero based array of items but a 1-based array in the HTML.
					if (isset($_POST['field_' . $id_field]))
					{
						$raw = (array) $_POST['field_' . $id_field];
						$possible = array();
						$count = count($field['field_options']);
						foreach ($raw as $val)
						{
							$val = (int) $val;

							if ($val >= 1 && $val <= $count)
							{
								$possible[] = $field['field_options'][$val - 1];
							}
						}

						$values[$id_field] = implode(',', $possible);
					}
					else
					{
						$values[$id_field] = '';
					}

					if (empty($values[$id_field]) && !empty($field['field_config']['required']))
					{
						$values[$id_field] = '';
						$errors[] = sprintf($txt['lgal_missing_required_field'], $field['field_name']);
					}
					break;
				case 'checkbox':
					$values[$id_field] = !empty($_POST['field_' . $id_field]);
					break;
			}
		}

		return array('values' => $values, 'errors' => $errors);
	}

	public function displayFieldInputs($fields)
	{
		global $txt, $context;

		if (empty($fields))
		{
			return;
		}

		echo '
						<dl class="settings">';

		foreach ($fields as $id_field => $field)
		{
			$bbc = !empty($field['field_config']['bbc']);

			echo '
							<dt>
								<strong>', $field['field_name'], ':</strong>', !empty($field['field_config']['required']) ? $txt['field_required'] : '';
			if (!empty($field['description']))
			{
				echo '
								<div class="smalltext">', $field['description'], '</div>';
			}
			echo '
							</dt>
							<dd>';
			switch ($field['field_type'])
			{
				case 'integer':
				case 'float':
				case 'text':
					echo '
								<input type="text" name="field_', $id_field, '" tabindex="', $context['tabindex']++, '" class="input_text" value="', $field['value'], '" />';
					break;
				case 'largetext':
					echo '
								<textarea name="field_', $id_field, '"', !empty($field['field_config']['default_size']['columns']) && !empty($field['field_config']['default_size']['rows']) ? ' cols="' . $field['field_config']['columns'] . '" rows="' . $field['field_config']['rows'] . '"' : '', ' tabindex="', $context['tabindex']++, '">', $field['value'], '</textarea>';
					break;
				case 'select':
					echo '
								<select name="field_', $id_field, '" tabindex="', $context['tabindex']++, '">
									<option value="0">', $txt['field_select'], '</option>';
					foreach ($field['field_options'] as $key => $option)
					{
						// We offset the value by one so that we can also have the setup where a user has to explicitly choose something.
						echo '
									<option value="', ($key + 1), '"', $option == $field['value'] ? ' selected="selected"' : '', '>', $option, '</option>';
					}
					echo '
								</select>';
					break;
				case 'multiselect':
					$values = explode(',', $field['value']); // Because we reformatted it elsewhere.
					foreach ($values as $k => $v)
					{
						$values[$k] = trim($v);
					}
					foreach ($field['field_options'] as $key => $option)
					{
						echo '
								<label><input type="checkbox" class="input_check" tabindex="', $context['tabindex'], '" name="field_', $id_field, '[]" value="', ($key + 1), '"', in_array($option, $values) ? ' checked="checked"' : '', ' /> ', $bbc ? parse_bbc($option) : $option, '</label><br />';
					}
					break;
				case 'radio':
					foreach ($field['field_options'] as $key => $option)
					{
						echo '
								<label><input type="radio" class="input_radio" tabindex="', $context['tabindex'], '" name="field_', $id_field, '" value="', ($key + 1), '"', $option == $field['value'] ? ' checked="checked"' : '', ' /> ', $bbc ? parse_bbc($option) : $option, '</label><br />';
					}
					break;
				case 'checkbox':
					echo '
								<input type="checkbox" class="input_check" tabindex="', $context['tabindex'], '" name="field_', $id_field, '" value="1"', !empty($field['value']) ? ' checked="checked"' : '', ' />';
					break;
			}
			echo '
							</dd>';
		}

		echo '
						</dl>';
	}

	public function setCustomValues($item, $fields)
	{
		$db = database();

		$db->query('', '
			DELETE FROM {db_prefix}lgal_custom_field_data
			WHERE id_item = {int:item}',
			array(
				'item' => $item,
			)
		);
		$rows = array();
		foreach ($fields as $id_field => $field)
		{
			if (!empty($field['value']))
			{
				$rows[] = array($item, $id_field, $field['value']);
			}
		}
		if (!empty($rows))
		{
			$db->insert('',
				'{db_prefix}lgal_custom_field_data',
				array('id_item' => 'int', 'id_field' => 'int', 'value' => 'text'),
				$rows,
				array('id_item', 'id_field')
			);
			$db->query('', '
				UPDATE {db_prefix}lgal_items
				SET has_custom = 1
				WHERE id_item = {int:item}',
				array(
					'item' => $item,
				)
			);
		}
	}
}
