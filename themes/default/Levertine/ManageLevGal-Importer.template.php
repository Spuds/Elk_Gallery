<?php

/**
 * This file handles displaying the importers.
 *
 * @package levgal
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 * @since 1.0
 *
 * @version 1.2.0 / elkarte
 */

function template_importer_home()
{
	global $context, $txt, $scripturl;

	echo '
		<div id="admincenter">
			<h2 class="lgal_secondary_header secondary_header">', $txt['levgal_importers'], '</h2>
			<p class="information">', $txt['levgal_importer_desc'], '</p>
			<div class="content">
				<div>', $txt['levgal_import_supports'], '</div>
				<br />
				<dl class="settings">';

	foreach (array_keys($context['possible_importers']) as $import_id)
	{
		echo '
					<dt>', $txt['levgal_importer_' . $import_id], '</dd>';
		if (in_array($import_id, $context['valid_importers']))
		{
			echo '
					<dd>
						<form class="floatleft" action="', $scripturl, '?action=admin;area=lgalimport" method="post" accept-charset="UTF-8">
							<input type="hidden" name="importer" value="', $import_id, '" />
							<input type="hidden" name="step" value="0" />
							<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
							<input type="submit" value="', $txt['levgal_begin_import'], '" class="button_submit" />
						</form>
					</dd>';
		}
		else
		{
			echo '
					<dd>', $txt['levgal_not_found_import'], '</dd>';
		}
	}

	echo '
				</dl>
			</div>
		</div>';
}

function template_no_valid_importers()
{
	global $context, $txt;

	echo '
		<div id="admincenter">
			<h2 class="lgal_secondary_header secondary_header">', $txt['levgal_importers'], '</h2>
			<p class="information">', $txt['levgal_importer_desc'], '</p>
			<div class="content">
				<div>', $txt['levgal_import_supports'], '</div>
				<br />
				<ul class="bbc_list">';

	foreach (array_keys($context['possible_importers']) as $import_id)
	{
		echo '
					<li>', $txt['levgal_importer_' . $import_id], '</li>';
	}

	echo '
				</ul>
				<br />
				<div>', $txt['levgal_no_valid_importer'], '</div>
			</div>
		</div>';
}

function template_importer_pre_import()
{
	global $context, $txt, $scripturl;

	echo '
		<div id="admincenter">
			<h3 class="lgal_secondary_header secondary_header">', $context['page_title'], '</h3>';

	if (!empty($context['import_warning']))
	{
		echo '
			<div id="errors" class="errorbox">
				', $txt['levgal_importer_warning'], '
			</div>';
	}

	echo '
			<div class="content">';

	if (!empty($context['configurables']))
	{
		// As and when an importer needs this, it goes here.
	}
	else
	{
		echo '
				<div>', $txt['levgal_no_additional_information'], '</div>';
	}

	echo '
				<br />
				<div>', $txt['levgal_importer_will_import'], '</div>
				<ul>';

	foreach ($context['importer_supports'] as $importable)
	{
		if (!is_string($importable))
		{
			continue;
		}

		echo '
					<li>', $importable, '</li>';
	}

	echo '
				</ul>
				<div class="submitbutton">
					<form action="', $scripturl, '?action=admin;area=lgalimport" method="post" accept-charset="UTF-8">
						<input type="hidden" name="importer" value="', $context['importer_name'], '" />
						<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
						<input type="submit" value="', $txt['levgal_begin_import'], '" />
						<input type="hidden" name="step" value="1" />
					</form>
				</div>
			</div>
		</div>';
}

function template_importer_done()
{
	global $context, $txt, $scripturl;

	echo '
		<div id="admincenter">
			<h2 class="lgal_secondary_header secondary_header">', $context['page_title'], '</h2>
			<div class="content">
				', $txt['levgal_importer_result'], '
				<ul>';

	foreach ($context['results'] as $result)
	{
		echo '
					<li>', $result, '</li>';
	}

	echo '
				</ul>
				<div class="submitbutton">
					<form action="', $scripturl, '?action=admin;area=lgalimport;done" method="post" accept-charset="UTF-8">
						<input type="submit" name="gallery" value="', $txt['levgal_importer_return_gallery'], '" />
						<input type="submit" name="admin" value="', $txt['levgal_importer_return_admin'], '" />
					</form>
				</div>
			</div>
		</div>';
}
