<?php
// Version: 1.0; Levertine Gallery moderation centre

/**
 * This file handles displaying the moderation area for the gallery.
 *
 * @package levgal
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 * @since 1.0
 */

function template_moderate_main()
{
	global $context;

	echo '
		<div id="admin_content">
			<div id="modcenter">';

	$alternate = true;
	// Show all the blocks they want to see.
	foreach ($context['mod_blocks'] as $block)
	{
		$block_function = 'template_block_' . $block;

		echo '
			<div class="modblock_', $alternate ? 'left' : 'right', '">', function_exists($block_function) ? $block_function() : '', '</div>';

		if (!$alternate)
		{
			echo '
			<br class="clear" />';
		}

		$alternate = !$alternate;
	}

	echo '
			</div>
		</div>';
}

function template_block_unapproved_comments()
{
	global $context, $txt, $scripturl;

	echo '
		<h3 class="category_header hdicon cat_img_talk">
			<a href="', $scripturl, '?media/moderate/unapproved_comments/">', $txt['lgal_recent_unapproved_comments'], '</a>
		</h3>
		<div class="content modbox">
			<ul>';

	foreach ($context['moderation']['unapproved_comments'] as $comment)
	{
		echo '
				<li>
					', sprintf($txt['lgal_mod_comment_text'], $comment['comment_url'], $comment['item_url'], $comment['item_name'], $comment['author'], $comment['time_added_format']), '
				</li>';
	}

	// Don't have any unapproved comments right now?
	if (empty($context['moderation']['unapproved_comments']))
	{
		echo '
				<li class="infobox">
					<strong>', $txt['lgal_recent_unapproved_comments_none'], '</strong>
				</li>';
	}

	echo '
			</ul>
		</div>';
}

function template_block_unapproved_items()
{
	global $context, $txt, $scripturl;

	echo '
		<h3 class="category_header hdicon cat_img_attachments">
			<a href="', $scripturl, '?media/moderate/unapproved_items/">', $txt['lgal_recent_unapproved_items'], '</a>
		</h3>
		<div class="content modbox">
			<ul>';

	foreach ($context['moderation']['unapproved_items'] as $item)
	{
		echo '
				<li>
					<i class="icon i-view"></i>
					', sprintf($txt['lgal_mod_item_text'], $item['item_url'], $item['item_name'], $item['author'], $item['time_added_format']), '
				</li>';
	}

	// Don't have any unapproved users right now?
	if (empty($context['moderation']['unapproved_items']))
	{
		echo '
				<li class="infobox">
					<strong>', $txt['lgal_recent_unapproved_items_none'], '</strong>
				</li>';
	}

	echo '
			</ul>
		</div>';
}

function template_block_unapproved_albums()
{
	global $context, $txt, $scripturl;

	echo '
		<h3 class="category_header hdicon cat_img_database">
			<a href="', $scripturl, '?media/moderate/unapproved_albums/">', $txt['lgal_recent_unapproved_albums'], '</a>
		</h3>
		<div class="content modbox">
			<ul>';

	foreach ($context['moderation']['unapproved_albums'] as $album)
	{
		echo '
				<li>
					<i class="icon i-view"></i>
					', sprintf($txt['lgal_mod_album_text'], $album['album_url'], $album['album_name']), '
				</li>';
	}

	// Don't have any unapproved users right now?
	if (empty($context['moderation']['unapproved_albums']))
	{
		echo '
				<li class="infobox">
					<strong>', $txt['lgal_recent_unapproved_albums_none'], '</strong>
				</li>';
	}

	echo '
			</ul>
		</div>';
}

function template_block_reported_comments()
{
	global $context, $txt, $scripturl;

	echo '
		<h3 class="category_header hdicon cat_img_moderation">
			<a href="', $scripturl, '?media/moderate/reported_comments/">', $txt['lgal_recent_reported_comments'], '</a>
		</h3>
		<div class="content modbox">
			<ul>';

	foreach ($context['moderation']['reported_comments'] as $comment)
	{
		echo '
				<li>
					<i class="icon i-flag"></i>
					', sprintf($txt['lgal_mod_comment_text'], $comment['report_url'], $comment['item_url'], $comment['item_name'], $comment['author'], $comment['time_started_format']), '
				</li>';
	}

	// Don't have any reported comments right now?
	if (empty($context['moderation']['reported_comments']))
	{
		echo '
				<li class="infobox">
					<strong>', $txt['lgal_recent_reported_comments_none'], '</strong>
				</li>';
	}

	echo '
			</ul>
		</div>';
}

function template_block_reported_items()
{
	global $context, $txt, $scripturl;

	echo '
		<h3 class="category_header hdicon cat_img_moderation">
			<a href="', $scripturl, '?media/moderate/reported_items/">', $txt['lgal_recent_reported_items'], '</a>
		</h3>
		<div class="content modbox">
			<ul>';

	foreach ($context['moderation']['reported_items'] as $item)
	{
		echo '
				<li>
					<i class="icon i-flag"></i>
					', sprintf($txt['lgal_mod_item_text'], $item['report_url'], $item['item_name'], $item['author'], $item['time_added_format']), '
				</li>';
	}

	// Don't have any reported comments right now?
	if (empty($context['moderation']['reported_items']))
	{
		echo '
				<li class="infobox">
					<strong>', $txt['lgal_recent_reported_items_none'], '</strong>
				</li>';
	}

	echo '
			</ul>
		</div>';
}

function template_moderate_unapproved_comments()
{
	global $context, $txt;

	echo '
		<h3 class="secondary_header">', $txt['levgal_unapproved_comments'], '</h3>';

	echo '
		<div class="pagesection">', $context['pageindex'], '</div>';

	if (empty($context['comments']))
	{
		echo '
		<div class="content">', $txt['lgal_recent_unapproved_comments_none'], '</div>';
	}
	else
	{
		$alternate = false;
		foreach ($context['comments'] as $comment)
		{
			echo '
		<h4 class="secondary_header">
			<span class="smalltext floatleft">
				', $txt['lgal_mod_comment_on'], '
				<a href="', $comment['album_url'], '">', $comment['album_name'], '</a> /
				<a href="', $comment['item_url'], '">', $comment['item_name'], '</a>
			</span>
			<span class="smalltext floatright">
				', sprintf($txt['lgal_moderate_by_on'], $comment['author'], $comment['time_added_format']), '
			</span>
		</h4>
		<div class="content">
			<div class="content">
				<div class="post">', $comment['comment_body'], '</div>';

			template_action_strip($comment['actions']);

			echo '
				<br class="clear" />
			</div>
		</div>';

			$alternate = !$alternate;
		}
	}

	echo '
		<div class="pagesection">', $context['pageindex'], '</div>';

	echo '
		<br class="clear" />';
}

function template_moderate_unapproved_items()
{
	global $context, $txt;

	echo '
		<h3 class="secon">', $txt['levgal_unapproved_items'], '</h3>';

	echo '
		<div class="pagesection">', $context['pageindex'], '</div>';

	echo '
		<table class="table_grid" style="width: 100%;">
			<thead>
				<tr class="catbg">
					<th>', $txt['lgal_item_name'], '</th>
					<th>', $txt['lgal_posted_in'], '</th>
					<th>', $txt['lgal_uploaded_by'], '</th>
					<th>', $txt['lgal_uploaded_on'], '</th>
					<th></th>
				</tr>
			</thead>
			<tbody>';

	if (empty($context['items']))
	{
		echo '
			<tr class="content">
				<td colspan="5">
					<div class="centertext">', $txt['lgal_recent_unapproved_items_none'], '</div>
				</td>
			</tr>';
	}
	else
	{
		$alternate = false;
		foreach ($context['items'] as $item)
		{
			echo '
			<tr class="content">
				<td><a href="', $item['item_url'], '">', $item['item_name'], '</a></td>
				<td><a href="', $item['album_url'], '">', $item['album_name'], '</a></td>
				<td>', $item['author'], '</td>
				<td>', $item['time_added_format'], '</td>
				<td>';

			template_action_strip($item['actions']);

			echo '
					<br class="clear" />
				</td>
			</tr>';
			$alternate = !$alternate;
		}
	}

	echo '
			</tbody>
		</table>';

	echo '
		<div class="pagesection">', $context['pageindex'], '</div>';
}

function template_moderate_unapproved_albums()
{
	global $context, $txt;

	echo '
		<h3 class="secondary_header">', $txt['levgal_unapproved_albums'], '</h3>';

	echo '
		<div class="pagesection">', $context['pageindex'], '</div>';

	echo '
		<table class="table_grid" style="width: 100%;">
			<thead>
				<tr class="catbg">
					<th>', $txt['levgal_album_name'], '</th>
					<th>', $txt['lgal_album_owned_by'], '</th>
					<th></th>
				</tr>
			</thead>
			<tbody>';

	if (empty($context['albums']))
	{
		echo '
			<tr class="content">
				<td colspan="5">
					<div class="centertext">', $txt['lgal_recent_unapproved_albums_none'], '</div>
				</td>
			</tr>';
	}
	else
	{
		$alternate = false;
		foreach ($context['albums'] as $album)
		{
			echo '
			<tr class="content">
				<td><a href="', $album['album_url'], '">', $album['album_name'], '</a></td>
				<td>', implode($album['owner']), '</td>
				<td>';

			template_action_strip($album['actions']);

			echo '
					<br class="clear" />
				</td>
			</tr>';
			$alternate = !$alternate;
		}
	}

	echo '
			</tbody>
		</table>';

	echo '
		<div class="pagesection">', $context['pageindex'], '</div>';
}

function template_moderate_reported_comments()
{
	global $context, $txt;

	echo '
		<h3 class="secondary_header">', $txt['levgal_reported_comments'], '</h3>
		<p class="information">', $txt['lgal_reported_comments_desc'], '</p>';

	echo '
		<ul id="adm_submenus">';

	foreach ($context['tabs'] as $tab)
	{
		echo '
			<li class="listlevel1">
				<a class="linklevel1', $tab['active'] ? ' active' : '', '" href="', $tab['url'], '">', $tab['title'], '</a>
			</li>';
	}

	echo '
		</ul>
		<br class="clear" />';

	echo '
		<h3 class="secondary_header">', $context['page_title'], '</h3>';

	echo '
		<div class="pagesection">', $context['pageindex'], '</div>';

	foreach ($context['comments'] as $comment)
	{
		echo '
		<div class="content">
			<div class="floatleft">
				', sprintf($txt['lgal_mod_comment_text'], $comment['comment_url'], $comment['item_url'], $comment['item_name'], $comment['author'], $comment['comment_time_format']), '<br />
			</div>';

		template_action_strip($comment['actions']);

		echo '
			<br />
			<div class="smalltext">
				', $txt['lgal_last_report'], ' ', $comment['time_updated_format'], '<br />
				', $txt['lgal_comment_reported_by'], ' ', empty($comment['reporters']) ? $txt['not_applicable'] : implode(', ', $comment['reporters']), '
			</div>
			<hr />
			', $comment['comment'], '
		</div>';
	}

	if (empty($context['comments']))
	{
		echo '
		<div class="content">
			<div class="centertext">', $context['open_reports'] ? $txt['lgal_recent_reported_comments_none'] : $txt['lgal_recent_reported_comments_closed_none'], '</div>
		</div>';
	}

	echo '
		<div class="pagesection">', $context['pageindex'], '</div>
		<br class="clear" />';
}

function template_moderate_reported_items()
{
	global $context, $txt;

	echo '
		<h3 class="secondary_header">', $txt['levgal_reported_items'], '</h3>
		<p class="description">', $txt['lgal_reported_items_desc'], '</p>';

	echo '
		<ul id="adm_submenus">';

	foreach ($context['tabs'] as $tab)
	{
		echo '
				<li class="listlevel1">
					<a class="linklevel1', $tab['active'] ? ' active' : '', '" href="', $tab['url'], '">', $tab['title'], '</a>
				</li>';
	}

	echo '
		</ul>
		<br class="clear" />';

	echo '
		<h3 class="secondary_header">', $context['page_title'], '</h3>';

	echo '
		<div class="pagesection">', $context['pageindex'], '</div>';

	$alternate = true;
	foreach ($context['items'] as $item)
	{
		echo '
		<div class="content">
			<div class="floatleft">
				', sprintf($txt['lgal_mod_item_text'], $item['item_url'], $item['item_name'], $item['author'], $item['time_added_format']), '<br />
			</div>';

		template_action_strip($item['actions']);

		echo '
			<br />
			<div class="smalltext">
				&laquo; ', $txt['lgal_last_report'], ' ', $item['time_updated_format'], ' &raquo;<br />
				&laquo; ', $txt['lgal_item_reported_by'], ' ', empty($item['reporters']) ? $txt['not_applicable'] : implode(', ', $item['reporters']), ' &raquo;
			</div>
			<hr />
			<div class="mod_report_thumbnail floatleft">', empty($item['thumbnail']) ? '' : '<img src="' . $item['thumbnail'] . '" alt="" />', '</div>
			<div class="mod_report_desc floatleft">', empty($item['description']) ? '' : $item['description'], '</div>
			<br class="clear" />
		</div>';
	}

	if (empty($context['items']))
	{
		echo '
			<div class="content">
				<div class="centertext">', $context['open_reports'] ? $txt['lgal_recent_reported_items_none'] : $txt['lgal_recent_reported_items_closed_none'], '</div>
			</div>';
	}

	echo '
		<div class="pagesection">', $context['pageindex'], '</div>
		<br class="clear" />';
}

function template_showreport()
{
	global $context, $txt, $scripturl;

	echo '
		<h3 class="secondary_header">', $context['section_title'], '</h3>
		<p class="description">', $context['section_desc'], '</p>';

	echo '
		<ul id="adm_submenus">';

	foreach ($context['tabs'] as $tab)
	{
		echo '
				<li class="listlevel1">
					<a class="linklevel1', $tab['active'] ? ' active' : '', '" href="', $tab['url'], '">', $tab['title'], '</a>
				</li>';
	}

	echo '
		</ul>
		<br class="clear" />';

	echo '
		<h3 class="secondary_header">', $context['report_title'], '</h3>';

	echo '
		<h3 class="secondary_header">', sprintf($txt['lgal_reports_received'], comma_format($context['report_details']['num_reports']), $context['report_details']['time_updated_format']), '</h3>';

	// If this is a comment, show the comment being reported.
	if (!empty($context['report_details']['body']))
	{
		echo '
		<div class="content">';

		template_action_strip($context['report_actions']);

		echo $context['report_details']['body'], '
		</div>
		<br />';
	}

	echo '
		<h3 class="secondary_header">', $txt['lgal_reports_by_members'], '</h3>';

	$alternate = false;
	foreach ($context['report_bodies'] as $body)
	{
		echo '
		<div class="content">
			<p class="smalltext">', sprintf($txt['lgal_report_by_member'], $body['author'], $body['time_sent_format']), '</p>
			<p>', $body['body'], '</p>
		</div>';
	}

	echo '
		<br />
		<h3 class="secondary_header">', $txt['lgal_moderator_comments'], '</h3>
		<div class="content">';

	foreach ($context['report_comments'] as $comment)
	{
		echo '
			<p>', $comment['author'], ': ', $comment['comment'], ' (' . $comment['log_time_format'], ')</p>';
	}

	if ($context['can_comment'])
	{
		echo '
			<form action="', $scripturl, '?media/moderate/', $context['report_details']['id_report'], '/comment/" method="post" accept-charset="UTF-8">
				<textarea class="mod_comment" name="mod_comment"></textarea>
				<div>
					<input type="submit" value="', $txt['levgal_add_comment'], '" class="button_submit" />
				</div>
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
			</form>';
	}
	else
	{
		echo '
			<p>', $txt['lgal_cannot_comment'], '</p>';
	}

	echo '
		</div>
		<br class="clear" />';
}
