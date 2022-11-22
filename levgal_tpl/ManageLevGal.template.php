<?php
// Version: 1.0; Levertine Gallery admin front page template

/**
 * This file handles displaying the front page of the admin.
 *
 * @package levgal
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 * @since 1.0
 *
 * @version 1.2.0 / elkarte
 */

function template_levgal_dash()
{
	global $context, $txt, $settings;

	// Header area.
	echo '
	<div id="admincenter">
		<div id="statistics">
			<h2 class="lgal_secondary_header secondary_header hdicon cat_img_stats">', $txt['levgal_stats_general'], '</h2>';

	// Left stats block with generic stuff in.
	echo '
				<div id="stats_left">
					<div class="content modbox">
						<dl class="settings">';

	foreach ($context['general_stats'] as $key => $value)
	{
		echo '
							<dt>', $txt['levgal_stats_' . $key], '</dt>
							<dd>', $value, '</dd>';
	}

	echo '
						</dl>
					</div>
				</div>';

	// Right stats block with shiny graph in.
	echo '
				<div id="stats_right">
					<div class="content modbox">
						<div id="pie_container">
							<div id="graph_container" class="floatleft">
								<canvas id="lgal_graph" width="200" height="200"></canvas>
							</div>
							<div id="graph_legend_container" class="floatleft">
								<div id="graph_legend"><ul></ul></div>
							</div>
						</div>
						<script src="', $settings['default_theme_url'], '/levgal_res/chart.min.js"></script>
						<script>
							let ctx = document.getElementById("lgal_graph").getContext("2d"),
								data = {
									labels: ' . json_encode($context['item_breakdown']['labels']) . ',
									datasets: [{
										data: ' . json_encode($context['item_breakdown']['datasets']['data']) . ',
										backgroundColor: ' . json_encode($context['item_breakdown']['datasets']['backgroundColor']) . ',
										hoverOffset: 4
									}]
								};
							
							// Separate the legend from the chart canvas to allow the pie to be full sized / symmetric
							const htmlLegendPlugin = {
								id: "pielegend",
								afterUpdate(chart, args, options) {
									let legendContainer = document.getElementById(options.containerID);
								
									const ul = legendContainer.querySelector("ul")
							
									// Remove old legend items
									while (ul.firstChild) {
										ul.firstChild.remove();
									}
								
									// Reuse the built-in legendItems generator
									const items = chart.options.plugins.legend.labels.generateLabels(chart);
							
									items.forEach(item => {
										const li = document.createElement("li");
								
										li.onclick = () => {
											const {type} = chart.config;

											chart.toggleDataVisibility(item.index);
											chart.update();
										};
						
										// Color box
										const boxSpan = document.createElement("span");
										boxSpan.style.background = item.fillStyle;
										boxSpan.style.borderColor = item.strokeStyle;
										boxSpan.style.borderWidth = item.lineWidth + "px";
										
										// Text
										const textContainer = document.createElement("p");
										textContainer.style.color = item.fontColor;
										textContainer.style.textDecoration = item.hidden ? "line-through" : "";
							
										const text = document.createTextNode(item.text);
										textContainer.appendChild(text);
										
										li.appendChild(boxSpan);
										li.appendChild(textContainer);
										ul.appendChild(li);
									});
								}
							};								
							
							// All that and this for a pie chart !
							newChart = new Chart(ctx, {
								type: "pie",
								data: data,
								options: {
									plugins: {
								      pielegend: {
										containerID: "graph_legend",
									},
									title: {
										display: true,
										font: {
											size: 18
										},
										text: "' . $txt['levgal_uploaded_items'] . '"
									},
									legend: {
										display: false,
									}},
									responsive: true,
								},
								plugins: [htmlLegendPlugin],
							});
						</script>
					</div>
				</div>';

	// Row two, left block: support information. But since we're reusing the stats templates we have to use totally inappropriate ids. Oh well.
	echo '
				<div id="stats_left">
					<h2 class="lgal_secondary_header secondary_header hdicon cat_img_helptopics">', $txt['levgal_support_information'], '</h2>
					<div class="content modbox">
						<dl class="settings">';

	foreach ($context['support'] as $id => $thing)
	{
		if (!is_array($thing))
		{
			echo '
							<dt>', $txt['levgal_versions_' . $id], '</dt>
							<dd>', $thing, '</dd>';
		}
	}

	echo '
						</dl>
					</div>
				</div>';

	// Row two, right block: news from LevGal central.
	echo '
				<div id="stats_right">
					<h2 class="lgal_secondary_header secondary_header hdicon cat_img_address">', $txt['levgal_news_from_home'], '</h2>
					<div class="content modbox">
						<div>
							', $txt['levgal_versions_lgal'], ' ', $txt['support_versions_current'], ' <span id="levgalCurrentVersion" class="bbc_strong">???</span>
 							/ ', $txt['support_versions_forum'], ' <span id="levgalYourVersion" class="bbc_strong">', LEVGAL_VERSION, '</span>
						</div>
						<hr />
						<div id="lev_news">',
							$txt['levgal_news_not_available'], '
						</div>
					</div>
				</div>';

	// And the containers.
	echo '
			</div>
		</div>';

	// Fetch the news
	echo '
	<script>
	addLoadEvent(levgal_currentVersion);
	</script>';
}

function template_levgal_credits()
{
	global $context, $txt;

	echo '
	<div id="admincenter">';

	$icon = ['developers' => 'cat_img_config', 'components' => 'cat_img_database', 'images' => 'cat_img_attachments', 'translators' => 'cat_img_write', 'people' => 'cat_img_star'];

	foreach ($context['levgal_credits'] as $credit_cat => $credits)
	{
		echo '
		<h2 class="lgal_secondary_header secondary_header hdicon ' . $icon[$credit_cat] . '">',
			$txt['levgal_credits_' . $credit_cat . '_title'], '
		</h2>
		<div class="well">
			<dl>
				<dt>
					<u>', $txt['levgal_credits_' . $credit_cat . '_desc'], '</u>
				</dt>
				<dd>
					<ul class="bbc_list">';

		foreach ($credits as $credit)
		{
			echo '
						<li>', $credit, '</li>';
		}

		echo '
					</ul>
				</dd>
			</dl>
		</div>';
	}

	echo '
	</div>';
}

function template_callback_lgal_social()
{
	global $txt, $context;

	echo '
						<dt>
							<label>', $txt['lgal_settings_social'], '</label>
						</dt>
						<dd>
							<fieldset id="social_icons">
								<legend>
									', $txt['lgal_settings_select_networks'], '
								</legend>
								<ul class="permission_groups">';
	foreach ($context['available_social_icons'] as $network)
	{
		echo '
									<li>
										<label>
											<input type="checkbox" name="lgal_social[]" value="', $network, '"', in_array($network, $context['enabled_social_icons']) ? ' checked="checked"' : '', ' class="input_check" />
											<span>', $txt['lgal_share_' . $network], '</span>
										</label>
									</li>';
	}
	echo '
								</ul>
							</fieldset>
						</dd>';
}

function template_callback_lgal_metadata()
{
	global $txt, $context;

	echo '
						<dt>
							<label>', $txt['lgal_settings_metadata'], '</label>
							<br />
							<span class="smalltext">', $txt['lgal_settings_metadata_desc'], '</span>
						</dt>
						<dd>';

	foreach ($context['metadata'] as $section => $items)
	{
		echo '
							<fieldset id="settings_', $section, '">
								<legend>
									', $txt['lgal_settings_metadata_' . $section], '
								</legend>
								<ul class="permission_groups">';

		foreach ($items as $item)
		{
			echo '
									<li>
										<label>
											<input type="checkbox" name="metadata_', $section, '[]" value="', $item, '"', in_array($item, $context['selected_metadata'][$section]) ? ' checked="checked"' : '', ' class="input_check" />
											<span>', $txt['lgal_opts_metadata_' . $item], '</span>
										</label>
									</li>';
		}

		echo '
								</ul>
							</fieldset>';
	}

	echo '
						</dd>';
}
