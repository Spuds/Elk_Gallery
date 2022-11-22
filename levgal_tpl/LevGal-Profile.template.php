<?php
// Version: 1.0; Levertine Gallery profile template

/**
 * This file handles displaying the information from the gallery in a user's profile.
 *
 * @package levgal
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 * @since 1.0
 *
 * @version 1.2.0 / elkarte
 */

function template_levgal_profile_summary()
{
	global $txt, $context;

	echo '
			<h3 class="lgal_secondary_header secondary_header">
				<span class="lgalicon i-album"></span> ', $txt['levgal_profile_summary'], '
			</h3>
			</div>
			<p class="infobox">', $txt['levgal_profile_summary_desc'], '</p>
			<div class="content lgalprofile_summary">
				<div class="centertext">
					', implode(' &bull; ', $context['summary_items']), '
				</div>
			</div>
			<br />';

	if (!empty($context['latest_items']))
	{
		echo '
			<h3 class="lgal_secondary_header secondary_header">
				', sprintf($txt['levgal_latest_items_user'], $context['member']['name']), '
			</h3>';

		template_item_list('latest_items');
	}

	echo '
			<h3 class="lgal_secondary_header secondary_header">
				', sprintf($txt['levgal_albums_user'], $context['member']['name']), '
			</h3>';

	if (empty($context['hierarchy']))
	{
		echo '
			<div class="content">
				<div class="centertext">', $txt['levgal_no_albums'], '</div>
			</div>';
	}
	else
	{
		template_album_hierarchy($context['hierarchy']);
	}

	echo '
			<br class="clear" />';
}

function template_levgal_profile_items()
{
	global $context;

	if (empty($context['num_items']))
	{
		echo '
			<div class="content">
				<div class="centertext">', $context['no_items_text'], '</div>
			</div>';
	}
	else
	{
		echo '
			<div class="pagesection">
				<ul class="pagelinks">', $context['page_index'], '</ul>
			</div>';

		template_item_list('profile_items');

		echo '
			<div class="pagesection">
				<ul class="pagelinks">', $context['page_index'], '
			</div>';
	}
}

function template_levgal_profile_bookmarks()
{
	global $context, $txt;

	echo '
			<h3 class="lgal_secondary_header secondary_header">
				<span class="lgalicon i-bookmark"></span> ', $txt['levgal_profile_bookmarks'], '
			</h3>
			<p class="infobox">', $context['bookmarks_desc'], '</p>';

	if (empty($context['bookmarks']))
	{
		echo '
			<div class="content">', $context['no_bookmarks_text'], '</div>
			<br class="clear" />';
	}
	else
	{
		foreach ($context['bookmarks'] as $bookmark)
		{
			echo '
				<div class="well">
					<div class="innerframe">
						<div class="lgal_profile_thumbnail floatleft"><img src="', $bookmark['item_thumbnail'], '" /></div>
						<div class="lgal_profile_itemname floatleft"><a href="', $bookmark['item_url'], '">', $bookmark['item_name'], '</a></div>
						<div class="lgal_profile_bookmarked floatright"><span class="lgalicon i-bookmark"></span> ', sprintf($txt['levgal_profile_bookmarks_item_bookmarked'], $bookmark['bookmark_timestamp_format']), '</div>
						<br />
						<div class="lgal_profile_added floatleft"><span class="lgalicon i-additem"></span> ', sprintf($txt['levgal_profile_bookmarks_item_added'], $bookmark['poster_link'], $bookmark['item_added_format']), '</div>
						<div class="lgal_profile_stats floatright clear_right"><span class="lgalicon i-view"></span> ', $bookmark['num_views'], ' <span class="lgalicon i-comments"></span> ', $bookmark['num_comments'], '</div>
						<br class="clear" />
					</div>
				</div>';
		}
	}
}

function template_levgal_profile_notify()
{
	global $context, $txt, $scripturl;

	echo '
			<h3 class="lgal_secondary_header secondary_header">
				<span class="lgalicon i-notify"></span> ', $txt['levgal_profile_notify'], '<span>
			</h3>
			<p class="description">', $context['notify_desc'], '</p>';

	// Option(s)
	if (empty($context['enabled_media_notifications']))
	{
		echo '
			<div class="warningbox">',
				$txt['levgal_profile_notify_none'], '
			</div>';
		return;
	}

	echo '
			<div class="infobox">',
				sprintf($txt['levgal_profile_notify_email'], $scripturl . '?action=profile;area=notification'), '
			</div>';

	// Item List
	if (empty($context['enabled_media_notifications']['lgcomment']))
	{
		echo '
			<div class="infobox">',
				$txt['levgal_profile_notify_comment_none'], '
			</div>';
	}
	else
	{
		template_item_notification_list();
	}

	// Album list
	if (empty($context['enabled_media_notifications']['lgnew']))
	{
		echo '
			<div class="infobox">',
				$txt['levgal_profile_notify_album_none'], '
			</div>';
	}
	else
	{
		template_album_notification_list();
	}
}

function template_levgal_profile_prefs()
{
	global $context, $txt, $options, $scripturl;

	echo '
			<h3 class="lgal_secondary_header secondary_header">
				<span class="lgalicon i-options"></span> ', $txt['levgal_profile_prefs'], '
			</h3>
			<p class="infobox">', $txt['levgal_profile_prefs_desc'], '</p>';

	echo '
			<form action="', $scripturl, '?action=profile;u=', $context['user']['id'], ';area=mediaprefs" method="post" accept-charset="UTF-8">
				<div class="content">
					<dl class="settings">';

	foreach ($context['preferences'] as $pref)
	{
		echo '
						<dt>', $txt['levgal_pref_' . $pref[1]], empty($txt['levgal_pref_' . $pref[1] . '_note']) ? '' : '<div class="smalltext">' . $txt['levgal_pref_' . $pref[1] . '_note'] . '</div>', '</dt>
						<dd>';
		switch ($pref[0])
		{
			case 'check':
				echo '
							<input type="checkbox" name="', $pref[1], '" value="1"', empty($options[$pref[1]]) ? '' : ' checked="checked"', ' class="input_check" />';
				break;
			case 'select':
				break;
		}
		echo '
						</dd>';
	}

	echo '
					</dl>
					<hr class="clear">
					<div class="submitbutton">
						<input type="submit" name="save" value="', $txt['levgal_profile_prefs_update'], '" class="button_submit">
						<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
					</div>
				</div>
			</form>';
}

function template_item_notification_list()
{
	global $context, $txt, $scripturl;

	// Item notifications
	echo '
			<br />
			<form action="', $scripturl, '?action=profile;area=medianotify;u=', $context['id_member'], ';save" method="post" accept-charset="UTF-8">
				<table class="table_grid">
					<thead>
						<tr class="table_head">
							<th scope="col" class="lefttext">
								', $txt['levgal_profile_notify_items'], '
							</th>
							<th scope="col" class="lefttext">
								', $txt['lgal_posted_by'], '
							</th>
							<th scope="col" class="lefttext">
								', $txt['lgal_posted_in'], '
							</th>
							<th scope="col" class="centertext" style="width: 4%;">
								<input type="checkbox" class="input_check" onclick="invertAll(this, this.form);">
							</th>
						</tr>
					</thead>
					<tbody>';

	if (empty($context['item_notifications']))
	{
		echo '
						<tr class="content">
							<td colspan="4">
								<div class="centertext">', $txt['levgal_profile_notify_items_none'], '</div>
							</td>
						</tr>';
	}
	else
	{
		foreach ($context['item_notifications'] as $id_item => $item)
		{
			echo '
						<tr class="content">
							<td>
								<a href="', $item['item_url'], '">', $item['item_name'], '</a>
							</td>
							<td></td>
							<td>
								<a href="', $item['album_url'], '">', $item['album_name'], '</a>
							</td>
							<td class="centertext">
								<input type="checkbox" name="notify_items[]" value="', $id_item, '" class="input_check" />
							</td>
						</tr>';
		}
	}

	echo '
					</tbody>
				</table>';
	if (!empty($context['item_notifications']))
	{
		echo '
				<div class="submitbutton">
					<div class="additional_row">
						<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
						<input type="submit" name="edit_notify_item" value="', $txt['lgal_unnotify'], '" />
					</div>
				</div>';
	}
	echo '
			</form>';
}

function template_album_notification_list()
{
	global $context, $txt, $scripturl;

	// And now album notifications
	echo '
			<br />
			<form action="', $scripturl, '?action=profile;area=medianotify;u=', $context['id_member'], ';save" method="post" accept-charset="UTF-8">
				<table class="table_grid">
					<thead>
						<tr class="table_head">
							<th scope="col" class="lefttext">
								', $txt['levgal_profile_notify_albums'], '
							</th>
							<th scope="col" class="centertext" style="width: 4%;">
								<input type="checkbox" class="input_check" onclick="invertAll(this, this.form);">
							</th>
						</tr>
					</thead>
					<tbody>';

	if (empty($context['album_notifications']))
	{
		echo '
						<tr class="content">
							<td colspan="2">
								<div class="centertext">', $txt['levgal_profile_notify_albums_none'], '</div>
							</td>
						</tr>';
	}
	else
	{
		foreach ($context['album_notifications'] as $id_album => $album)
		{
			echo '
						<tr class="content">
							<td><a href="', $album['url'], '">', $album['name'], '</a></td>
							<td class="centertext">
								<input type="checkbox" name="notify_albums[]" value="', $id_album, '" class="input_check" />
							</td>
						</tr>';
		}
	}

	echo '
					</tbody>
				</table>';

	if (!empty($context['album_notifications']))
	{
		echo '
				<div class="submitbutton">
					<div class="additional_row">
						<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
						<input type="submit" name="edit_notify_album" value="', $txt['lgal_unnotify'], '" />
					</div>
				</div>';
	}

	echo '
			</form>';
}
