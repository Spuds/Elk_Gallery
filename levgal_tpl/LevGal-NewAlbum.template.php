<?php
// Version: 1.0; Levertine Gallery new album template

/**
 * This file handles displaying the form for creation new albums.
 *
 * @package levgal
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 * @since 1.0
 *
 * @version 1.2.0 / elkarte
 */

function template_newalbum()
{
	global $context, $txt;

	echo '
		<form action="', $context['destination'], '" method="post" accept-charset="UTF-8" name="postmodify" id="postmodify" onsubmit="submitonce(this);" enctype="multipart/form-data">
			<h2 class="lgal_secondary_header secondary_header">
				', $txt['levgal_newalbum'], '
			</h2>
			<p class="infobox">', $txt['levgal_album_add_description'], '</p>';

	template_newalbum_errors();

	echo '
			<div class="well">';

	template_newalbum_details();

	template_newalbum_ownership();

	template_newalbum_privacy();

	// Now the description box, the save button and end of the form.
	template_newalbum_description();

	echo '
			</div>
		</form>';

	template_newalbum_js();
}

function template_newalbum_description() {
	global $context, $txt;

	echo '
			<dl class="settings">
				<dt>
					<span style="font-weight: 600">', $txt['lgal_album_description'], '</span>
				</dt>
				<dd></dd>
			</dl>';

	/** @var $description_box \LevGal_Helper_Richtext */
	$description_box = $context['description_box'];
	$description_box->displayEditWindow();
	$description_box->displayButtons();

}

function template_newalbum_errors()
{
	global $context, $txt;

	if (!empty($context['errors']))
	{
		template_lgal_error_list($txt['levgal_album_create_error'], $context['errors']);
	}
}

function template_newalbum_details()
{
	global $context, $txt, $scripturl;

	// First, general album details.
	echo '
					<dl class="lgal_settings">
						<dt>', $txt['levgal_album_name'], '</dt>
						<dd>
							<input type="text" id="album_name" name="album_name" tabindex="1" size="80" maxlength="80" class="input_text" value="', $context['album_name'], '" style="width: 95%;" />
						</dd>
						<dt class="clear_left">', $txt['levgal_album_slug'], '</dt>
						<dd>
							<span class="smalltext">', $scripturl, '?media/album/</span>
							<input type="text" id="album_slug" name="album_slug" tabindex="2" size="20" maxlength="40" class="input_text" value="', $context['album_slug'], '" /><span class="smalltext">.x/</span>
						</dd>
					</dl>
					<hr class="clear" />';
}

function template_newalbum_ownership()
{
	global $context, $txt;

	// Now ownership
	echo '
					<dl class="lgal_settings">
						<dt>', $txt['levgal_album_ownership'], '</dt>
						<dd>';

	if (count($context['ownership_opts']) == 1)
	{
		echo '
							<input type="hidden" id="ownership" name="ownership" value="', $context['ownership_opts'][0], '" />
							', $txt['levgal_album_ownership_' . $context['ownership_opts'][0]];
	}
	else
	{
		echo '
							<select name="ownership" id="ownership">';
		foreach ($context['ownership_opts'] as $item)
		{
			echo '
								<option value="', $item, '"', $context['ownership'] == $item ? ' selected="selected"' : '', '>', $txt['levgal_album_ownership_' . $item], '</option>';
		}
		echo '
							</select>';
	}

	if (!empty($context['group_list']))
	{
		echo '
							<fieldset id="ownership_groups">';
		foreach ($context['group_list'] as $id_group => $group)
		{
			echo '
								<label><input type="radio" name="ownership_group" value="', $id_group, '"', $id_group == $context['ownership_group'] ? ' checked="checked"' : '', ' class="input_radio" /> ', $group['color_name'], empty($group['stars']) ? '' : ' ' . $group['stars'], '</label><br />';
		}
		echo '
							</fieldset>';
	}

	echo '
						</dd>
					</dl>
					<hr class="clear" />';
}

function template_newalbum_privacy()
{
	global $context, $txt;

	// Now privacy
	echo '
					<dl class="lgal_settings">
						<dt>', $txt['levgal_album_privacy_title'], '</dt>
						<dd>
							<select name="privacy" id="privacy">';
	foreach (array('guests', 'members', 'justme', 'custom') as $item)
	{
		if ($item === 'custom' && empty($context['group_list']))
		{
			continue;
		}
		echo '
								<option value="', $item, '"', $context['privacy'] == $item ? ' selected="selected"' : '', '>', $txt['levgal_album_privacy_' . $item], '</option>';
	}

	echo '
							</select>';
	if (!empty($context['access_list']))
	{
		echo '
							<fieldset id="privacy_custom">';
		foreach ($context['access_list'] as $id_group => $group)
		{
			echo '
								<label><input type="checkbox" name="privacy_group[]" value="', $id_group, '"', $id_group == 1 ? ' checked="checked" disabled="disabled"' : (in_array($id_group, $context['privacy_group']) ? ' checked="checked"' : ''), ' class="input_check" /> ', $group['color_name'], empty($group['stars']) ? '' : ' ' . $group['stars'], '</label><br />';
		}
		echo '
							</fieldset>';
	}
	echo '
						</dd>
					</dl>
					<hr class="clear" />';
}

function template_newalbum_js()
{
	global $context, $settings;

	echo '
		<script src="' . $settings['default_theme_url'] . '/levgal_res/url_slug.js"></script>
		<script>';

	// Automatically set the thing not to update if we already have one.
	echo '
			let updateSlug = ', empty($context['album_slug']) ? 'true' : 'false', ',
				albumName = document.getElementById("album_name"),
				albumSlug = document.getElementById("album_slug");

			function transLitSlug()
			{
				let mystr;
				if (updateSlug)
				{
					mystr = albumName.value;
					mystr = mystr.replace("\'", "");
					albumSlug.value = url_slug(mystr, {}).substring(0, 50);
				}
			}
			createEventListener(albumName);
			albumName.addEventListener("keyup", transLitSlug, false);
			albumName.addEventListener("change", transLitSlug, false);
			createEventListener(albumSlug);
			albumSlug.addEventListener("keyup", function() { updateSlug = false; }, false);';

	if (!empty($context['group_list']))
	{
		// And something for ownership
		echo '
			function updateOwnership()
			{
				document.getElementById("ownership_groups").style.display = (document.getElementById("ownership").value == "group") ? "" : "none";
			}
			let ownershipSel = document.getElementById("ownership");
			createEventListener(ownershipSel);
			ownershipSel.addEventListener("change", updateOwnership, false);
			updateOwnership();';

		// And then we need something to sort out the privacy stuff.
		echo '
			function updatePrivacy()
			{
				document.getElementById("privacy_custom").style.display = (document.getElementById("privacy").value == "custom") ? "" : "none";
			}
			let privacySel = document.getElementById("privacy");
			createEventListener(privacySel);
			privacySel.addEventListener("change", updatePrivacy, false);
			updatePrivacy();';
	}

	echo '
		</script>';
}
