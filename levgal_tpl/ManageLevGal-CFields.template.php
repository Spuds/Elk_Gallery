<?php
// Version: 1.1.0; Levertine Gallery admin custom fields template

/**
 * This file handles displaying the custom fields configuration.
 *
 * @package levgal
 * @since 1.0
 */

function template_cfields()
{
	global $context, $txt, $scripturl;

	echo '
	<div id="admincenter">
		<h2 class="secondary_header">
			 ', $txt['levgal_cfields'], '
		</h2>
		<p class="information">', $txt['levgal_cfields_desc'], '</p>
		<form action="', $scripturl, '?action=admin;area=lgalcfields" method="post">';

	if (empty($context['custom_fields']))
	{
		echo '
			<div class="content">
				<div class="centertext">', $txt['levgal_cfields_none'], '</div>
			</div>';
	}
	else
	{
		echo '
			<ul class="sortable">';
		foreach ($context['custom_fields'] as $field)
		{
			echo '
				<li>
					<div class="well">
						<input type="hidden" name="field[]" value="', $field['id_field'], '" />
						<div class="field_name floatleft">
							', $field['field_name'], '
						</div>
						<div class="field_type floatleft">
							<span class="lgaladmin ui_', $field['field_type'], '"></span> ', $txt['levgal_cfields_field_type_' . $field['field_type']], '
						</div>
						<div class="field_active floatleft">
							', $field['active'] ? $txt['levgal_cfields_field_active'] : $txt['levgal_cfields_field_inactive'], '
						</div>
						<div class="field_placement floatleft">
							', sprintf($txt['levgal_cfields_placement'], $txt['levgal_cfields_placement_' . $field['placement']]), '
						</div>
						<div class="floatright">
							<a href="', $scripturl, '?action=admin;area=lgalcfields;sa=modify;field=', $field['id_field'], '">', $txt['modify'], '</a>
						</div>
					</div>
				</li>';
		}
		echo '
			</ul>';
	}

	echo '
			<br />
			<div class="submitbutton">
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
				<input type="submit" name="saveorder" id="saveorder" value="', $txt['lgal_cfields_save'], '" style="display:none" />
				<input type="submit" name="addfield" value="', $txt['levgal_cfields_add'], '" />
			</div>
		</form>
	</div>
	<script>
	$(".sortable").sortable({ update: function (event, ui) { $(\'#saveorder\').show(); } }).disableSelection();
	</script>';
}

function template_cfields_modify()
{
	global $context, $txt;

	echo '
	<div id="admincenter">
		<h3 class="secondary_header">
			 ', $context['page_title'], '
		</h3>
		<p class="information">', $txt['levgal_cfields_desc'], '</p>';

	if (!empty($context['errors']))
	{
		template_lgal_error_list($txt['levgal_cfield_could_not_be_saved'], $context['errors']);
	}

	echo '
		<form action="', $context['form_url'], '" method="post" accept-charset="UTF-8">
				<div class="content">';

	// General options, aka display options
	echo '
					<fieldset>
						<legend>', $txt['levgal_cfields_general'], '</legend>
						<dl class="settings">
							<dt>
								<strong>', $txt['levgal_cfields_field_name'], '</strong>
								<div class="smalltext">', $txt['levgal_cfields_field_name_desc'], '</div>
							</dt>
							<dd>
								<input type="text" name="field_name" value="', $context['custom_field']['field_name'], '" class="input_text" size="30" />
							</dd>
							<dt>
								<strong>', $txt['levgal_cfields_field_desc'], '</strong>
								<div class="smalltext">', $txt['levgal_cfields_field_desc_desc'], '</div>
							</dt>
							<dd>
								<textarea name="description" cols="40" rows="3">', $context['custom_field']['description'], '</textarea>
							</dd>
							<dt>
								<strong>', $txt['levgal_cfields_field_placement'], '</strong>
								<div class="smalltext">', $txt['levgal_cfields_field_placement_desc'], '</div>
							</dt>
							<dd>
								<select name="placement">';

	foreach (array(0, 1, 2) as $placement)
	{
		echo '
									<option value="', $placement, '"', $placement == $context['custom_field']['placement'] ? ' selected="selected"' : '', '>', $txt['levgal_cfields_placement_' . $placement], '</option>';
	}

	echo '
								</select>
							</dd>
							<dt>
								<strong>', $txt['levgal_cfields_field_is_active'], '</strong>
								<div class="smalltext">', $txt['levgal_cfields_field_is_active_desc'], '</div>
							</dt>
							<dd>
								<select name="active">
									<option value="1"', empty($context['custom_field']['active']) ? '' : ' selected="selected"', '>', $txt['yes'], '</option>
									<option value="0"', empty($context['custom_field']['active']) ? ' selected="selected"' : '', '>', $txt['no'], '</option>
								</select>
							</dd>
						</dl>
					</fieldset>';

	// Input options
	echo '
					<fieldset>
						<legend>', $txt['levgal_cfields_input'], '</legend>
						<dl class="settings">
							<dt>
								<strong>', $txt['levgal_cfields_field_type'], '</strong>
							</dt>
							<dd>
								<span id="field_type_icon"></span>
								<select id="field_type" name="field_type" onchange="update_field_type()">';

	foreach ($context['valid_field_types'] as $field_type)
	{
		echo '
									<option value="', $field_type, '"', $field_type == $context['custom_field']['field_type'] ? ' selected="selected"' : '', '>', $txt['levgal_cfields_field_type_' . $field_type], '</option>';
	}

	echo '
								</select>
							</dd>
							<dt class="ui_field ui_field_integer ui_field_float">
								<strong>', $txt['levgal_cfields_field_num_limits'], '</strong>
								<div class="smalltext">', $txt['levgal_cfields_field_num_limits_size'], '</div>
							</dt>
							<dd class="ui_field ui_field_integer ui_field_float">
								', sprintf(
		$txt['levgal_cfields_field_num_limits_form'],
		'<input type="text" class="input_text" size="4" name="min" value="' . $context['custom_field']['field_config']['min'] . '" />',
		'<input type="text" class="input_text" size="4" name="max" value="' . $context['custom_field']['field_config']['max'] . '" />'
	), '
							</dd>
							<dt class="ui_field ui_field_text ui_field_largetext">
								<strong>', $txt['levgal_cfields_field_validation'], '</strong>
								<div class="smalltext">', $txt['levgal_cfields_field_validation_desc'], '</div>
							</dt>
							<dd class="ui_field ui_field_text">
								<select id="field_validation" name="field_validation" onchange="updateFieldValidation();">';

	foreach ($context['valid_validation'] as $valid_type)
	{
		echo '
									<option value="', $valid_type, '"', !empty($context['custom_field']['field_config']['validation']) && $valid_type == $context['custom_field']['field_config']['validation'] ? ' selected="selected"' : '', '>', $txt['levgal_cfields_field_validation_' . $valid_type], '</option>';
	}

	echo '
								</select>
								<input type="text" class="input_text" value="', empty($context['custom_field']['field_config']['valid_regex']) ? '' : Util::htmlspecialchars($context['custom_field']['field_config']['valid_regex'], ENT_QUOTES), '" id="valid_regex" name="valid_regex" />
							</dd>
							<dt class="ui_field ui_field_text ui_field_largetext">
								<strong>', $txt['levgal_cfields_field_text_length'], '</strong>
								<div class="smalltext">', $txt['levgal_cfields_field_text_length_desc'], '</div>
							</dt>
							<dd class="ui_field ui_field_text ui_field_largetext">
								<input type="text" name="max_length" value="', $context['custom_field']['field_config']['max_length'], '" class="input_text" size="4" />
							</dd>
							<dt class="ui_field ui_field_largetext">
								<strong>', $txt['levgal_cfields_field_largetext_size'], '</strong>
								<div class="smalltext">', $txt['levgal_cfields_field_largetext_size_desc'], '</div>
							</dt>
							<dd class="ui_field ui_field_largetext">
								', sprintf(
		$txt['levgal_cfields_field_largetext_size_form'],
		'<input type="text" class="input_text" size="4" name="cols" value="' . $context['custom_field']['field_config']['default_size']['columns'] . '" />',
		'<input type="text" class="input_text" size="4" name="rows" value="' . $context['custom_field']['field_config']['default_size']['rows'] . '" />'
	), '
							</dd>
							<dt class="ui_field ui_field_integer ui_field_float ui_field_text">
								<strong>', $txt['levgal_cfields_field_default_val'], '</strong>
								<div class="smalltext">', $txt['levgal_cfields_field_default_val_desc'], '</div>
							</dt>
							<dd class="ui_field ui_field_integer ui_field_float ui_field_text">
								<input type="text" name="default_val" value="', $context['custom_field']['default_val'], '" size="30" class="input_text" />
							</dd>
							<dt class="ui_field ui_field_select ui_field_radio ui_field_multiselect">
								<strong>', $txt['levgal_cfields_field_options'], '</strong>
								<div class="smalltext ui_field ui_field_select ui_field_radio">', $txt['levgal_cfields_field_options_desc'], '</div>
								<div class="smalltext ui_field ui_field_multiselect">', $txt['levgal_cfields_field_options_multi_desc'], '</div>
							</dt>
							<dd class="ui_field ui_field_select ui_field_radio ui_field_multiselect">';
	if (empty($context['custom_field']['field_options']))
	{
		echo '
								<div>
									<div class="ui_field ui_field_select ui_field_radio"><input type="radio" name="default_select" value="0" checked="checked" class="input_radio" /> ', $txt['levgal_cfields_field_options_no_default'], '</div>
									<input type="radio" name="default_select" value="1" class="input_radio ui_field ui_field_select ui_field_field_radio" /><input type="checkbox" name="default_multiselect[1]" value="1" class="input_check ui_field ui_field_multiselect" /><input type="text" name="select_option[1]" value="" class="input_text" />
									<br /><input type="radio" name="default_select" value="2" class="input_radio ui_field ui_field_select ui_field_field_radio" /><input type="checkbox" name="default_multiselect[2]" value="1" class="input_check ui_field ui_field_multiselect" /><input type="text" name="select_option[2]" value="" class="input_text" />
									<span id="addopt"></span>
									[<a href="#" onclick="addOption(); return false;">', $txt['levgal_cfields_field_options_add'], '</a>]
								</div>';
	}
	else
	{
		echo '
								<div>
									<div class="ui_field ui_field_select ui_field_radio"><input type="radio" name="default_select" value="0"', empty($context['custom_field']['default_val']) ? ' checked="checked"' : '', ' class="input_radio" /> ', $txt['levgal_cfields_field_options_no_default'], '</div>';
		if ($context['custom_field']['field_type'] === 'multiselect')
		{
			$first = true;
			$possible = explode(',', $context['custom_field']['default_val']);
			foreach ($context['custom_field']['field_options'] as $k => $v)
			{
				echo '
									', $first ? '' : '<br />', '<input type="checkbox" name="default_selectmulti[', ($k + 1), ']" value="1"', in_array($v, $possible) ? ' checked="checked"' : '', ' class="input_check ui_field ui_field_multiselect" /><input type="text" name="select_option[', ($k + 1), ']" value="', $v, '" class="input_text" />';
				$first = false;
			}
		}
		else
		{
			foreach ($context['custom_field']['field_options'] as $k => $v)
			{
				echo '
									<br />
									<input type="radio" name="default_select" value="', ($k + 1), '"', $context['custom_field']['default_val'] == $v ? ' checked="checked"' : '', ' class="input_radio" /><input type="text" name="select_option[', ($k + 1), ']" value="', $v, '" class="input_text" />';
			}
		}
		echo '
									<span id="addopt"></span>
									[<a href="#" onclick="addOption(); return false;">', $txt['levgal_cfields_field_options_add'], '</a>]
								</div>';
	}

	echo '
							</dd>
							<dt class="ui_field ui_field_text ui_field_largetext ui_field_radio ui_field_multiselect">
								<strong>', $txt['levgal_cfields_field_bbc'], '</strong>
							</dt>
							<dd class="ui_field ui_field_text ui_field_largetext ui_field_radio ui_field_multiselect">
								<input type="checkbox" name="bbc"', empty($context['custom_field']['field_config']['bbc']) ? '' : ' checked="checked"', ' />
							</dd>
							<dt class="ui_field ui_field_integer ui_field_float ui_field_text ui_field_largetext ui_field_select ui_field_radio">
								<strong>', $txt['levgal_cfields_field_is_searchable'], '</strong>
								<div class="smalltext">', $txt['levgal_cfields_field_is_searchable_desc'], '</div>
							</dt>
							<dd class="ui_field ui_field_integer ui_field_float ui_field_text ui_field_largetext ui_field_select ui_field_radio">
								<select name="can_search">
									<option value="1"', empty($context['custom_field']['can_search']) ? '' : ' selected="selected"', '>', $txt['yes'], '</option>
									<option value="0"', empty($context['custom_field']['can_search']) ? ' selected="selected"' : '', '>', $txt['no'], '</option>
								</select>
							</dd>
							<dt>
								<strong>', $txt['levgal_cfields_field_is_required'], '</strong>
								<div class="smalltext">', $txt['levgal_cfields_field_is_required_desc'], '</div>
							</dt>
							<dd>
								<select name="required">
									<option value="1"', empty($context['custom_field']['field_config']['required']) ? '' : ' selected="selected"', '>', $txt['yes'], '</option>
									<option value="0"', empty($context['custom_field']['field_config']['required']) ? ' selected="selected"' : '', '>', $txt['no'], '</option>
								</select>
							</dd>
						</dl>
					</fieldset>';

	// Album selection.
	echo '
					<fieldset>
						<legend>', $txt['levgal_cfields_albums'], '</legend>
						<dl class="settings">
							<dt>
								<strong>', $txt['levgal_cfields_applies_to_album'], '</strong>
								<div class="smalltext">', $txt['levgal_cfields_applies_to_album_desc'], '</div>
							</dt>
							<dd>
								<select id="all_albums" name="all_albums" onchange="switchAlbums();">
									<option value="1"', empty($context['custom_field']['field_config']['all_albums']) ? '' : ' selected="selected"', '>', $txt['levgal_cfields_applies_to_albums_all'], '</option>
									<option value="0"', empty($context['custom_field']['field_config']['all_albums']) ? ' selected="selected"' : '', '>', $txt['levgal_cfields_applies_to_albums_some'], '</option>
								</select>
								<ul id="album_list" class="ignoreboards">';

	if (!empty($context['hierarchies']['site']))
	{
		template_display_albumlist(sprintf($txt['lgal_albums_owned_site'], $context['forum_name']), $context['hierarchies']['site'], 'site');
	}

	if (!empty($context['hierarchies']['member']))
	{
		foreach ($context['hierarchies']['member'] as $id_member => $member)
		{
			template_display_albumlist(sprintf($txt['lgal_albums_owned_someone'], $member['member_name']), $member['albums'], 'member_' . $id_member);
		}
	}

	if (!empty($context['hierarchies']['group']))
	{
		foreach ($context['hierarchies']['group'] as $id_group => $group)
		{
			template_display_albumlist(sprintf($txt['lgal_albums_owned_someone'], $group['group_name']), $group['albums'], 'group_' . $id_group);
		}
	}

	echo '
								</ul>
							</dd>
						</dl>
					</fieldset>';

	// Boring stuff.
	echo '
			</div>
			<br />
			<div class="submitbutton">
				<input type="hidden" name="field" value="', $context['field_id'], '" />
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
				<input type="submit" name="savefield" value="', $txt['save'], '" />';

	if ($context['field_id'] !== 'add')
	{
		echo '
				<input type="submit" name="deletefield" value="', $txt['delete'], '" onclick="return confirm(\'', $txt['custom_edit_delete_sure'], '\');" />';
	}

	echo '
			</div>
		</form>';

	echo '
	</div>
	<script>';

	// When changing the field type, we need to update the field icon and also juggle hiding the various other stuff.
	echo '
	function update_field_type()
	{
		var field_type = document.getElementById("field_type").value;
		document.getElementById("field_type_icon").className = "lgaladmin ui_" + field_type;

		var elements = document.querySelectorAll(".ui_field");
		for (var i = 0, n = elements.length; i < n; i++)
		{
			elements[i].style.display = "none";
		}
		elements = document.querySelectorAll(".ui_field_" + field_type);
		for (i = 0, n = elements.length; i < n; i++)
		{
			elements[i].style.display = "";
		}
	}
	update_field_type();

	function updateFieldValidation()
	{
		document.getElementById("valid_regex").style.display = document.getElementById("field_validation").value == "regex" ? "block" : "none";
	}
	updateFieldValidation();';

	// Adding items to the list for select/radio/multiselect
	echo '
	var startOptID = ', empty($context['custom_field']['field_options']) ? 3 : count($context['custom_field']['field_options']) + 1, ';
	function addOption()
	{
		setOuterHTML(document.getElementById("addopt"), \'<br /><input type="radio" name="default_select" value="\' + startOptID + \'" class="input_radio ui_field ui_field_select ui_field_field_radio" /><input type="checkbox" name="default_multiselect[\' + startOptID + \']" value="1" class="input_check ui_field ui_field_multiselect" /><input type="text" name="select_option[\' + startOptID + \']" value="" class="input_text" /><span id="addopt"></span>\');
		startOptID++;
		update_field_type();
	}';

	// Toggling album behaviours.
	echo '
	function switchAlbums()
	{
		document.getElementById("album_list").style.display = document.getElementById("all_albums").value == "1" ? "none" : "";
	}
	switchAlbums();

	function setAlbums(album, value)
	{
		var elements = document.querySelectorAll("input.album_" + album);
		for (var i = 0, n = elements.length; i < n; i++)
		{
			elements[i].checked = value;
		}
	}

	function selectAlbums(identifier, children)
	{
		var toggle = true;

		var elements = document.querySelectorAll("input." + identifier);
		for (var i = 0, n = elements.length; i < n; i++)
		{
			toggle = toggle & elements[i].checked;
		}

		for (i = 0, n = children.length; i < n; i++)
		{
			setAlbums(children[i], !toggle);
		}
	}';

	echo '
	</script>';
}

function template_display_albumlist($title, $albumlist, $identifier)
{
	global $context;

	echo '					<li class="category">
								<a href="javascript:void(0);" onclick="selectAlbums(', JavaScriptEscape($identifier), ', [', implode(', ', array_keys($albumlist)), ']); return false;">', $title, '</a>
								<ul>';

	foreach ($albumlist as $album)
	{
		$album['selected'] = !empty($context['custom_field']['field_config']['all_albums']) || in_array($album['id_album'], $context['custom_field']['field_config']['albums']);
		echo '
								<li class="board">
									<label style="margin-', $context['right_to_left'] ? 'right' : 'left', ': ', $album['album_level'], 'em;" for="alb', $identifier, '_', $album['id_album'], '"><input type="checkbox" id="alb', $identifier, '_', $album['id_album'], '" name="alb[', $identifier, '_', $album['id_album'], ']" value="', $album['id_album'], '"', empty($album['selected']) ? '' : ' checked="checked"', ' class="input_check album ', $identifier, ' album_', $album['id_album'], '" onchange="setAlbums(', $album['id_album'], ', this.checked);" /> ', $album['album_name'], '</label>
								</li>';
	}

	echo '
								</ul>
							</li>';
}
