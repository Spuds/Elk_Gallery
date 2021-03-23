<?php
// Version: 1.0; Levertine Gallery admin quotas

/**
 * This file handles displaying additional options within the quotas configuration.
 *
 * @package levgal
 * @since 1.0
 */

// This returns JavaScript to the handler. But since it all relates to these templates,
// might as well leave it that way.
function template_quotas_javascript_above()
{
	global $context, $txt;

	$js = '';

	// Run the toggler for the overall blocks of file types; so we hide the ones not even enabled.
	foreach ($context['container_blocks'] as $type)
	{
		$js .= '
	showHide("' . $type . '");';
	}

	// Now for the meat of the quota code. This will be fun. Not. We need the list of groups.
	$array = array();
	foreach ($context['group_list'] as $id_group => $group)
	{
		$array[$id_group] = $group['color_name'];
	}
	$js .= '

	groupList = ' . json_encode($array) . ';
	managers = ' . json_encode($context['managers']) . ';
	quotas = ' . json_encode($context['quotas']) . ';
	groups_without_quotas = [];';

	$strings = array(
		'modify' => $txt['modify'],
		'add' => $txt['levgal_add_quota'],
		'update' => $txt['levgal_update'],
		'cancel' => $txt['levgal_cancel'],
		'remove' => $txt['levgal_remove'],
		'groups' => $txt['levgal_quota_groups'],
		'managers' => $txt['levgal_gallery_managers'],
		'max_file_size' => $txt['levgal_max_file_size'],
		'max_image_size' => $txt['levgal_max_image_size'],
		'max_image_size_unlimited' => $txt['levgal_max_image_size_unlimited'],
		'max_image_size_defined' => $txt['levgal_max_image_size_defined'],
		'max_image_size_placeholder' => $txt['levgal_max_image_size_placeholder'],
		'no_upload' => $txt['levgal_no_upload'],
		'none' => $txt['levgal_none'],
		'quota_no_groups_selected' => $txt['levgal_quota_no_groups_selected'],
		'quota_invalid_filesize' => $txt['levgal_quota_invalid_filesize'],
		'quota_invalid_imagesize' => $txt['levgal_quota_invalid_imagesize'],
	);

	$js .= '
	langs = ' . json_encode($strings) . ';
	generate_quota_image();';
	foreach ($context['container_blocks'] as $type)
	{
		if ($type !== 'image' && $type !== 'external')
		{
			$js .= '
	generate_quota_generic("' . $type . '");';
		}
	}

	$js .= '
	closeFieldsets();';

	addInlineJavascript($js, true);
}

function template_generic_filetypes($typelist)
{
	global $context, $txt;

	echo '
						<dt class="container_', $typelist, '">
							', $txt['lgal_allowed_types_' . $typelist], '
						</dt>
						<dd class="container_', $typelist, '">
							', $txt['levgal_allowed_file_types'], '
							<fieldset id="quota_', $typelist, '_filetype">
								<legend>
									', $txt['levgal_allowed_file_types'], '
								</legend>
								<ul class="permission_groups">';

	foreach ($context['file_types'][$typelist] as $type)
	{
		echo '
									<li>
										<label>
											<input type="checkbox" name="formats_', $typelist, '[', $type, ']" value="on"', in_array($type, $context['selected_file_types'][$typelist]) ? ' checked="checked"' : '', ' class="input_check" onchange="showChanged(\'', $typelist, '\');" />
											<span>', $txt['lgal_' . $typelist . '_' . $type], '</span>
										</label>
									</li>';
	}

	echo '
								</ul>
							</fieldset>
						</dd>';
}

function template_quota_container($type)
{
	global $txt;

	echo '
						<dd class="container_', $type, ' main_width">
							', $txt['levgal_quota_header'], '
							<fieldset id="quota_', $type, '">
								<legend>
									', $txt['levgal_quota_header'], '
								</legend>
								<div id="', $type, '_quota_container"></div>
							</fieldset>
							<div class="infobox" id="', $type, '_changed">', $txt['levgal_changes_not_saved'], '</div>
						</dd>';
}

function template_callback_levgal_quotas_image()
{
	template_generic_filetypes('image');
	template_quota_container('image');
}

function template_callback_levgal_quotas_audio()
{
	template_generic_filetypes('audio');
	template_quota_container('audio');
}

function template_callback_levgal_quotas_video()
{
	template_generic_filetypes('video');
	template_quota_container('video');
}

function template_callback_levgal_quotas_document()
{
	template_generic_filetypes('document');
	template_quota_container('document');
}

function template_callback_levgal_quotas_archive()
{
	template_generic_filetypes('archive');
	template_quota_container('archive');
}

function template_callback_levgal_quotas_generic()
{
	template_generic_filetypes('generic');
	template_quota_container('generic');
}

function template_callback_levgal_quotas_external()
{
	global $txt;

	template_generic_filetypes('external');

	echo '
						<dd class="container_external main_width">
							<div class="infobox" id="external_changed">', $txt['levgal_changes_not_saved'], '</div>
						</dd>';
}
