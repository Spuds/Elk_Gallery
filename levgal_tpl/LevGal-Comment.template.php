<?php

/**
 * This file handles displaying comment-related behaviours in the gallery.
 *
 * @package levgal
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 * @since 1.0
 */

function template_editcomment()
{
	global $context, $txt;

	/** @var $comment_box \LevGal_Helper_Richtext */
	$comment_box = $context['comment_box'];

	echo '
		<form action="', $context['form_url'], '" method="post" accept-charset="UTF-8" name="postmodify" id="postmodify" onsubmit="submitonce(this);smc_saveEntities(\'postmodify\', [\'', $comment_box->getId(), '\']);" enctype="multipart/form-data">
			<h3 class="lgal_secondary_header secondary_header">', $context['display_title'], '</h3>
			<div class="well">';

	if (!empty($context['comment_errors']))
	{
		template_lgal_error_list($txt['levgal_comment_edit_error'], $context['comment_errors']);
	}

	if (!empty($context['editing_guest']))
	{
		echo '
				<dl id="post_header">
					<dt>', $txt['name'], ':</dt>
					<dd>
						<input type="text" id="author_name" name="author_name" tabindex="', $context['tabindex']++, '" size="40" maxlength="80" class="input_text" value="', $context['comment_details']['author_name'], '" style="width: 45%;" />
					</dd>
					<dt class="clear_left">', $txt['email'], ':</dt>
					<dd>
						<input type="text" id="author_name" name="author_email" tabindex="', $context['tabindex']++, '" size="40" maxlength="80" class="input_text" value="', $context['comment_details']['author_email'], '" style="width: 45%;" />
					</dd>
				</dl>';
	}

	$comment_box->displayEditWindow();
	$comment_box->displayButtons();

	echo '
			</div>
		</form>';
}

function template_flagcomment()
{
	global $txt, $context, $scripturl;

	echo '
		<form action="', $context['form_url'], '" method="post" accept-charset="UTF-8" name="postmodify" id="postmodify" >
			<h3 class="lgal_secondary_header secondary_header">', $context['page_title'], '</h3>
			<div id="report_topic" class="content">
				<dl class="lgal_settings">
					<dt>', $txt['lgal_mod_comment_on'], '</dt>
					<dd><a href="', $context['item_details']['item_url'], '">', $context['item_details']['item_name'], '</a></dd>
					<dt>', $txt['lgal_comment_by'], '</dt>
					<dd>', empty($context['comment_details']['id_author']) ? $context['comment_details']['author_name'] : '<a href="' . $scripturl . '?action=profile;u=' . $context['comment_details']['id_author'] . '">' . $context['comment_details']['author_name'] . '</a>', '</dd>
					<dt>', $txt['lgal_posted_on'], '</dt>
					<dd>', $context['comment_details']['time_added_format'], '</dd>
					<dt>', $txt['lgal_comment_body'], '</dt>
					<dd><i class="icon i-warning-moderate"></i>', $context['comment_details']['comment_parsed'], '</dd>
				</dl>
				<div class="centertext">
					<p class="infobox">', $txt['lgal_why_reporting_comment'], '</p>
					<textarea class="report_body" name="report_body"></textarea>
				</div>
				<div class="submitbutton">
					<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
					<input type="submit" value="', $context['page_title'], '" />
				</div>
			</div>
		</form>';
}
