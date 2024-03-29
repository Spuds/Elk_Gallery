<?php

/**
 * This file handles displaying the unseen items for a user.
 *
 * @package levgal
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 * @since 1.0
 *
 * @version 1.2.1 / elkarte
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
		if (!empty($context['unseen_actions']))
		{
			template_album_list_action_tabs($context['unseen_actions']);
		}

		template_unseen_sidebar();
		template_unseen_display();
	}
}

function template_unseen_sidebar()
{
	global $context, $txt;

	echo '
		<div id="album_sidebar">';

	// Information block
	echo '
			<h3 class="lgal_secondary_header secondary_header">
				', $txt['lgal_unseen_by_album'], '
			</h3>
			<div class="content">
				<dl class="album_details">
					<dt></dt>
					<dd>
						<ul class="sidebar_actions columns">';
	foreach ($context['unseen_albums'] as $id_album => $album)
	{
		echo '
							<li>
								<a href="', $album['filter_url'], '">', $context['album_filter'] == $id_album ? '<strong>' . $album['album_name'] . '</strong>' : $album['album_name'], '</a> (', comma_format($album['unseen']), ')
							</li>';
	}
	echo '
						</ul>
					</dd>
				</dl>
			</div>';

	// Actions block
	if (!empty($context['unseen_actions']))
	{
		$context['unseen_actions'] = array_filter($context['unseen_actions'], static function($e) {
			$check = reset($e);
			return (!isset($check['sidebar']) || $check['sidebar'] !== false);
		});
	}
	if (!empty($context['unseen_actions']))
	{
		echo '
			<h3 class="lgal_secondary_header secondary_header">
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
							<li>
								<a href="', $action[1], '"><span class="lgalicon i-', $id_action, '"></span>', $action[0], '</a>
							</li>';
			}

			echo '
						</ul>
					</dd>';
		}

		echo '
				</dl>
			</div>';
	}

	echo '
		</div>';
}

function template_unseen_display()
{
	global $context;

	echo '
		<div id="item_main">
			<h3 class="lgal_secondary_header secondary_header">', $context['page_title'], '</h3>';

	if (!empty($context['unseen_pageindex']))
	{
		echo '
			<div class="pagesection">', $context['unseen_pageindex'], '</div>';
	}

	template_item_list('unseen_items');

	echo '
		</div>';
}
