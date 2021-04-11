<?php
// Version: 1.0; Levertine Gallery stats template

/**
 * This file handles displaying the stats of the gallery.
 *
 * @package levgal
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 * @since 1.0
 */

function template_stats()
{
	global $context;

	// Begin the main beast.
	echo '
		<div id="statistics" class="forum_category">
			<h3 class="category_header">', $context['page_title'], '</h3>
			<ul class="statistics">';

	template_general_statistics();
	template_top_posters_albums();
	template_top_items_comments_views();

	// End the main beast.
	echo '
			</ul>
		</div>';
}

function template_general_statistics()
{
	global $txt, $context;

	echo '
			<li class="flow_hidden" id="top_row">
				<h4 class="category_header hdicon cat_img_piechart">', $txt['levgal_stats_general'], '</h4>
				<dl class="stats floatleft">';

	foreach ($context['general_stats']['left'] as $key => $value)
	{
		echo '
					<dt>', $txt['levgal_stats_' . $key], '</dt>
					<dd>', $value, '</dd>';
	}

	echo '
				</dl>
				<dl class="stats">';

	foreach ($context['general_stats']['right'] as $key => $value)
	{
		echo '
					<dt>', $txt['levgal_stats_' . $key], '</dt>
					<dd>', $value, '</dd>';
	}

	echo '
				</dl>
			</li>';
}

function template_top_posters_albums()
{
	global $txt, $context;

	echo '
			<li class="flow_hidden">
				<h2 class="category_header floatleft hdicon cat_img_star">', $txt['levgal_stats_top_uploaders'], '</h2>
				<dl class="stats floatleft">';

	template_username_bar_list($context['top_posters']);

	echo '
				</dl>
				<h2 class="category_header hdicon cat_img_stats_info">', $txt['levgal_stats_top_albums'], '</h2>
				<dl class="stats">';

	template_username_bar_list($context['top_albums']);

	echo '
				</dl>
			</li>';
}

function template_top_items_comments_views()
{
	global $txt, $context;

	echo '
			<li class="flow_hidden">
				<h2 class="category_header floatleft hdicon cat_img_write">', $txt['levgal_stats_top_items_comments'], '</h2>
				<dl class="stats floatleft">';

	template_username_bar_list($context['top_items_by_comments']);

	echo '
				</dl>
				<h2 class="category_header hdicon cat_img_eye">', $txt['levgal_stats_top_items_views'], '</h2>
				<dl class="stats">';

	template_username_bar_list($context['top_items_by_views']);

	echo '
				</dl>
			</li>';
}

function template_username_bar_list($list)
{
	foreach ($list as $list_item)
	{
		echo '
						<dt>', $list_item['item'], '</dt>
						<dd class="statsbar">
						
						<div class="bar" style="width: ', empty($list_item['percent']) ? '0' : $list_item['percent'], 'px;"></div>
							<span class="righttext">' . $list_item['count_format'] . '</span>
						</dd>';
	}
}
