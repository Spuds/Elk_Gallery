<?php

/**
 * This file handles displaying the gallery maintenance functions.
 *
 * @package levgal
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 * @since 1.0
 *
 * @version 1.2.0 / elkarte
 */

function template_levgal_maint()
{
	global $context, $txt, $scripturl;

	echo '
		<h2 class="lgal_secondary_header secondary_header">
			', $txt['levgal_maint'], '
		</h2>
		<p class="information">
			', $txt['levgal_maint_desc'], '
		</p>';

	if (isset($context['success']))
	{
		echo '
		<div class="maintenance_finished">
			', $context['success'], '
		</div>';
	}

	foreach (array_keys($context['maint_tasks']) as $task)
	{
		echo '
		<h2 class="lgal_secondary_header secondary_header">
			', $txt['levgal_task_' . $task], '
		</h2>
		<div class="content">
			<form action="', $scripturl, '?action=admin;area=lgalmaint;activity=', $task, '" method="post" accept-charset="UTF-8">
				<p>', $txt['levgal_task_desc_' . $task], '</p>
				<span><input type="submit" value="', $txt['levgal_run_task'], '" class="button_submit"></span>
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
			</form>
		</div>';
	}
}
