<?php
// Version: 1.0; Levertine Gallery unseen template

/**
 * This file handles displaying the unseen items for a user.
 *
 * @package levgal
 * @since 1.0
 */

function template_unseen()
{
	global $context, $txt;

	if (empty($context['unseen_items']))
	{
		echo '
	<h3 class="category_header centertext">
		', $txt['lgal_no_unseen'], '
	</h3>';
	}
	else
	{
		template_unseen_sidebar();
		template_unseen_display();
	}

	echo '
		<br class="clear" />';
}

function template_unseen_sidebar()
{
	global $context, $txt;

	echo '
		<div id="album_sidebar">';

	// Information block
	echo '
			<h3 class="secondary_header">
				', $txt['lgal_unseen_by_album'], '
			</h3>
			<div class="content">
				<dl class="album_details">
					<dt></dt>
					<dd>
						<ul class="sidebar_actions">';
	foreach ($context['unseen_albums'] as $id_album => $album)
	{
		echo '
							<li><a href="', $album['filter_url'], '">', $context['album_filter'] == $id_album ? '<strong>' . $album['album_name'] . '</strong>' : $album['album_name'], '</a> (', comma_format($album['unseen']), ')</li>';
	}
	echo '
						</ul>
					</dd>
				</dl>
			</div>';

	// Actions block
	if (!empty($context['unseen_actions']))
	{
		echo '
			<h3 class="secondary_header">
				', $txt['lgal_album_actions'], '
			</h3>
			<div class="content">
				<dl class="album_details">';

		$display_title = count($context['unseen_actions']) > 1;
		foreach ($context['unseen_actions'] as $action_group => $actions)
		{
			echo '
					<dt>', $display_title ? $txt['lgal_item_actions_' . $action_group] : '', '</dt>
					<dd>
						<ul class="sidebar_actions">';

			foreach ($actions as $id_action => $action)
			{
				echo '
							<li><a href="', $action[1], '"><span class="lgalicon i-', $id_action, '"></span>', $action[0], '</a></li>';
			}

			echo '
						</ul>
					</dd>';
		}

		echo '
				</ul>
			</div>';
	}

	echo '
		</div>';
}

function template_unseen_display()
{
	global $context, $txt;

	echo '
		<div id="item_main">
			<h3 class="secondary_header">', $context['page_title'], '</h3>';

	if (!empty($context['unseen_pageindex']))
	{
		echo '
			<div class="pagesection">', $txt['pages'], ': ', $context['unseen_pageindex'], '</div>';
	}

	template_item_list('unseen_items');

	echo '
		</div>';
}
