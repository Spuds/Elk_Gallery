<?php
// Version: 1.0.4; Levertine Gallery move album template

/**
 * This file handles displaying the setup for editing album hierarchies.
 *
 * @package levgal
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 * @since 1.0
 *
 * @version 1.2.0 / elkarte
 */

function template_movealbum_header()
{
	global $txt;

	echo '
		<h2 class="lgal_secondary_header secondary_header">', $txt['lgal_arrange_albums'], '</h2>
		<p class="infobox">', $txt['lgal_arrange_albums_desc'], '</p>
		<div class="errorbox" id="errors" style="display:none">
			<div class="error">', $txt['lgal_invalid_saving_order'], '</div>
		</div>';
}

function template_movealbum()
{
	global $context, $txt;

	template_movealbum_header();

	template_album_hierarchy($context['hierarchy']);

	echo '
		<br />
		<div class="submitbutton">
			<input type="button" id="saveorder" value="', $txt['lgal_arrange_albums_save'], '" onclick="return dosave();" />
		</div>
		<br class="clear" />';

	template_movealbum_js();
}

function template_movealbum_js()
{
	global $settings, $context;

	echo '
	<script src="', $settings['default_theme_url'], '/levgal_res/jquery.nestedSortable.js"></script>
	<script>
	$(".album_hierarchy.level_0").nestedSortable({
		handle: "div",
		items: "li",
		listType: "ul",
		errorClass: "sortable_error",
		placeholder: "placeholder",
		forcePlaceholderSize: true,
		maxLevels: 100,
		tabSize: 25,
		revert: 150,
		tolerance: "pointer",
		toleranceElement: "> div",
		update: function (event, ui) { $("#saveorder").show(); },
		rtl: ', $context['right_to_left'] ? 'true' : 'false', '
	});
	$("#saveorder").hide();
	function dosave()
	{
		ajax_indicator(true);
		$("#saveorder").prop(\'disabled\', true);
		$.post(
			elk_prepareScriptUrl(elk_scripturl) + ', JavaScriptEscape($context['form_url']), ',
			"saveorder=1&" + ', JavaScriptEscape($context['session_var']), ' + "=" + ', JavaScriptEscape($context['session_id']), ' + "&" + $(".album_hierarchy.level_0").nestedSortable("serialize"),
			function() {
				ajax_indicator(false);
				$("#saveorder").prop("disabled", false).hide();
				window.location = elk_prepareScriptUrl(elk_scripturl) + ', JavaScriptEscape($context['return_url']), ';
			}
		).fail(function() {
			ajax_indicator(false);
			$("#saveorder").hide();
			$("#errors").show();
		});
	}
	</script>';
}
