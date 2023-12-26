<?php
// Version: 1.0; Levertine Gallery stats template

/**
 * This file handles displaying the stats of the gallery.
 *
 * @package levgal
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 * @since 1.0
 *
 * @version 1.2.0 / elkarte
 */

function template_stats()
{
	global $context;

	// Begin the main beast.
	echo '
		<script>
			Chart.defaults.font.family = \'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Droid Sans", "Helvetica Neue", "Trebuchet MS", Arial, sans-serif\';
			Chart.defaults.font.lineHeight = .5;
		</script>
		<div id="statistics" class="forum_category">
			<h2 class="category_header">', $context['page_title'], '</h2>
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
				<h3 class="category_header hdicon cat_img_piechart">', $txt['levgal_stats_general'], '</h3>
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
				<div class="stats floatleft">
					<canvas id="topPosters"></canvas>
				</div>';

	[$data, $labels, $tooltips, $links] = getChartData($context['top_posters'], 'count');
	showChartData("topPosters", $data, $labels, $tooltips, $links);

	echo '
				<h2 class="category_header hdicon cat_img_stats_info">', $txt['levgal_stats_top_albums'], '</h2>
				<div class="stats">
					<canvas id="topAlbums"></canvas>
				</div>';

	[$data, $labels, $tooltips, $links] = getChartData($context['top_albums'], 'count');
	showChartData("topAlbums", $data, $labels, $tooltips, $links);

	echo '
			</li>';
}

function template_top_items_comments_views()
{
	global $txt, $context;

	echo '
			<li class="flow_hidden">
				<h2 class="category_header floatleft hdicon cat_img_write">', $txt['levgal_stats_top_items_comments'], '</h2>
				<div class="stats floatleft">
					<canvas id="topComments"></canvas>
				</div>';

	[$data, $labels, $tooltips, $links] = getChartData($context['top_items_by_comments'], 'count');
	showChartData("topComments", $data, $labels, $tooltips, $links);

	echo '
				<h2 class="category_header hdicon cat_img_eye">', $txt['levgal_stats_top_items_views'], '</h2>
				<div class="stats">
					<canvas id="topItems"></canvas>
				</div>';

	[$data, $labels, $tooltips, $links] = getChartData($context['top_items_by_views'], 'count');
	showChartData("topItems", $data, $labels, $tooltips, $links);

	echo '
			</li>';
}

function getChartData($stats, $num = 'count', $usePercent = false)
{
	// Just so we always have at least 10 bars to plot
	$labels = array_fill(0, 10, "' '");
	$data = array_fill(0, 10, '0');
	$tooltips = array_fill(0, 10, null);
	$links = array_fill(0, 10, "'#'");

	foreach ($stats as $i => $value)
	{
		if ($usePercent)
		{
			$data[$i] = !empty($value['percent']) ? $value['percent'] : '0';
		}
		else
		{
			$data[$i] = !empty($value[$num]) ? removeComma($value[$num]) : '0';
		}

		$labels[$i] = "'" . Util::shorten_text((isset($value['real_name']) ? $value['real_name'] : strip_tags($value['item'])), 26, true, '...', true, 0) . "'";
		$tooltips[$i] = "'" . $value[$num] . "'";
		$links[$i] = $value['link'] ?? '';
	}

	return [$data, $labels, $tooltips, $links];
}

function showChartData($id, $data, $labels, $tooltips, $links)
{
	// The use of var and not let, is intentional as we call this multiple times.
	echo '
	<script>
		var bar_ctx' . $id . ' = document.getElementById("', $id, '").getContext("2d"),
			background = bar_ctx' . $id . '.createLinearGradient(0, 0, 600, 0);
		
		// Right to left fade on canvas
		background.addColorStop(1, "#60BC78");
		background.addColorStop(0, "#27a348");
		
		// Set these vars for easy use in the config object
		var labels = [', implode(',', $labels), '],
			tooltips = [', implode(',', $tooltips), '],
			links = [', implode(',', $links), '],
			bar_data = {
				labels: labels,
				links: links,
				datasets: [{
					data: [', implode(',', $data), '],
					backgroundColor: background,
				}]
			};
		
		const chart_' . $id . ' = new Chart(bar_ctx' . $id . ', barConfig(bar_data, tooltips));
		
		// Click on the bar to navigate function
		document.getElementById("' . $id . '").onclick = function(evt) {
        	let activePoints = chart_' . $id . '.getElementsAtEventForMode(evt, "nearest", { intersect: true }, true);
        	if (activePoints.length) {
      			var firstPoint = activePoints[0],
        			link = chart_' . $id . '.data.links[firstPoint.index];
        			
        		if (firstPoint !== undefined && link !== "#") {
            		window.location = link;
           		}	
   			}
		}
		
	</script>';
}

function removeComma($c)
{
	return preg_replace('~\D~', '', $c);
}
