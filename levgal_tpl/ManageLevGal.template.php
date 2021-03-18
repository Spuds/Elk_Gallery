<?php
// Version: 1.0; Levertine Gallery admin front page template

/**
 * This file handles displaying the front page of the admin.
 *
 * @package levgal
 * @since 1.0
 */

function template_levgal_dash()
{
	global $context, $txt, $settings;

	if (!empty($context['out_of_date']))
	{
		echo '
		<div class="errorbox" id="errors"><span class="lgaladmin notavailable"></span> ', sprintf($txt['levgal_out_of_date'], LEVGAL_VERSION, $context['latest_version']), '</div>';
	}

	// Header area.
	echo '
	<div id="admincenter">
		<div id="statistics">
			<h2 class="secondary_header hdicon cat_img_stats">', $txt['levgal_stats_general'], '</h2>';

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
						<div id="graph_container" class="floatleft"></div>
						<div id="graph_legend_container" class="floatleft">
							<strong>', $txt['levgal_uploaded_items'], '</strong>
							<div id="graph_legend"></div>
						</div>
						<script src="', $settings['default_theme_url'], '/levgal_res/Chart.min.js"></script>
						<script>
							document.getElementById(\'graph_container\').innerHTML = \'<canvas id="lgal_graph" width="200" height="150"></canvas>\';
							var ctx = document.getElementById("lgal_graph").getContext("2d");
							var data = ' . json_encode($context['item_breakdown']) . ';
							var newChart = new Chart(ctx).Pie(data, {
								animateRotate: false
							});
							document.getElementById(\'graph_legend\').innerHTML = newChart.generateLegend();
						</script>
					</div>
				</div>';

	// Row two, left block: support information. But since we're reusing the stats templates we have to use totally inappropriate ids. Oh well.
	echo '
				<div id="stats_left">
					<h2 class="secondary_header hdicon cat_img_helptopics">', $txt['levgal_support_information'], '</h2>
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
					<h2 class="secondary_header hdicon cat_img_address">', $txt['levgal_news_from_home'], '</h2>
					<div class="content modbox">';

	if (empty($context['latest_news']))
	{
		echo '
						', $txt['levgal_news_not_available'];
	}
	else
	{
		echo '
						<dl id="lev_news">';
		foreach ($context['latest_news'] as $item)
		{
			echo '
							<dt>', sprintf($txt['levgal_news_item'], $item['subject'], $item['author'], $item['time']), '</dt>
							<dd>', $item['message'], '</dd>';
		}

		echo '
						</dl>';
	}

	echo '
					</div>
				</div>';

	// And the containers.
	echo '
			</div>
		</div>';
}

function template_levgal_credits()
{
	global $context, $txt;

	echo '
	<div id="admincenter">';

	foreach ($context['levgal_credits'] as $credit_cat => $credits)
	{
		echo '
		<h2 class="secondary_header hdicon cat_img_config">',
			$txt['levgal_credits_' . $credit_cat . '_title'], '
		</h2>
		<div class="roundframe">
			<dl>
				<dt>', $txt['levgal_credits_' . $credit_cat . '_desc'], '</dt>';

		foreach ($credits as $credit)
		{
			echo '
				<dd>', $credit, '</dd>';
		}

		echo '
			</dl>
		</div>';
	}
}

function template_callback_lgal_social()
{
	global $txt, $context;

	echo '
						<dt>', $txt['lgal_settings_social'], '</dt>
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
							', $txt['lgal_settings_metadata'], '<br />
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
