<?php
// Version: 1.2.0; Levertine Gallery item display template

/**
 * This file handles displaying the item pages.
 *
 * @package levgal
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 * @since 1.0
 *
 * @version 1.2.0 / elkarte
 */

function template_main_item_view()
{
	global $settings, $txt, $context;

	echo '
	<div id="gallery_contain">';

	if (!empty($context['item_actions']['actions']))
	{
		template_album_list_action_tabs($context['item_actions']);
	}

	template_main_item_sidebar();
	template_main_item_display();

	echo '
	</div>';

	// Now we need the JavaScript for our copy buttons.
	echo '
	<script src="', $settings['default_theme_url'], '/levgal_res/clipboard/clipboard.min.js"></script>
	<script>
		let items = ["lgal_share_simple_bbc", "lgal_share_complex_bbc", "lgal_share_page"],
			el;
		
		for (let i = 0, n = items.length; i < n; i++)
		{
			el = document.querySelector("#" + items[i] + "_container span.i-clipboard");
			el.setAttribute("data-clipboard-target", "#" + items[i]);
		}
		
		new ClipboardJS(".i-clipboard", {
   			text: function(trigger) {
        		return trigger.setAttribute("title", "', $txt['lgal_copyied_to_clipboard'], '");
    		}
		});
	</script>';
}

function template_main_item_display()
{
	global $context, $txt;

	echo '
		<div id="item_main">
			<h3 class="secondary_header">', $context['item_details']['item_name'], '</h3>
			<div class="', $context['item_details']['approved'] ? 'content' : 'approvebg', '">';

	if (isset($context['item_reported']))
	{
		echo '
				<div class="centertext"', $txt['lgal_item_was_reported'], '</div>
				<br />';
	}

	if (!$context['item_details']['approved'])
	{
		echo '
				<div class="centertext">', $txt['lgal_unapproved_item'], '</div>
				<br />';
	}

	$func = 'template_item_' . $context['item_display']['display_template'];
	if (function_exists($func))
	{
		$func();
	}
	else
	{
		template_item_generic();
	}

	if (!empty($context['item_details']['description']))
	{
		echo '
				<div id="item_desc" class="well">', $context['item_details']['description'], '</div>';
	}

	if (!empty($context['item_display']['custom_fields']['desc']))
	{
		echo '
				<div class="settings" id="lgal_cf_desc">';
		template_display_custom_fields('desc');
		echo '
				</div>';
	}

	echo '
			</div>';

	if (!empty($context['likes']) || $context['allowed_like'] || !empty($context['item_display']['tags']))
	{
		template_main_item_tags_likes();
	}

	if (!empty($context['prev_next']))
	{
		template_main_item_navigation();
	}

	if ($context['can_comment'] !== 'disabled')
	{
		template_main_item_comments();
	}

	echo '
		</div>';
}

function template_main_item_tags_likes()
{
	global $context, $txt;

	echo '
			<div class="content">';

	// First of all, are we doing likes?
	if (!empty($context['likes']) || $context['allowed_like'])
	{
		echo '
				<div class="item_likes" id="item_likes">', template_return_item_likers(), '</div>';
	}

	if (!empty($context['item_display']['tags']))
	{
		$tags = array();
		foreach ($context['item_display']['tags'] as $tag)
		{
			$tags[] = '<a href="' . $tag['url'] . '">' . $tag['name'] . '</a>';
		}

		echo '
				<div class="item_tags">
					<span class="lgalicon i-tag"></span> ', $txt['lgal_tagged_as'], '
					', implode(', ', $tags), '
				</div>';
	}

	echo '
		</div>';
}

function template_return_item_likers()
{
	global $context, $txt;

	$result = '<span class="lgalicon i-thumbup"></span> ' . $txt['lgal_likes'];

	// Then the people.
	if ($context['currently_liking'])
	{
		$likers = array_merge(array($txt['lgal_liked_you']), array_values($context['likes']));
	}
	else
	{
		$likers = array_values($context['likes']);
	}

	if (!empty($likers))
	{
		$result .= ' ' . implode(', ', $likers);
	}
	else
	{
		$result .= ' ' . $txt['lgal_liked_none'];
	}

	// Then the actual like button.
	if ($context['allowed_like'])
	{
		$string = $context['currently_liking'] ? $txt['lgal_unlike_this'] : $txt['lgal_like_this'];
		$result .= ' &mdash; <a class="linkbutton" href="' . $context['item_details']['item_url'] . 'like/' . $context['session_var'] . '=' . $context['session_id'] . '/" onclick="return handleLike(this);">' . $string . '</a>';
	}

	return $result;
}

function template_main_item_navigation()
{
	global $txt, $context;

	echo '
			<h2 class="secondary_header">';

	if (!empty($context['prev_next']['previous']))
	{
		echo '
				<span class="lefttext" style="width: 50%">
					<a href="', $context['prev_next']['previous']['item_url'], '#item_main" title="', $context['prev_next']['previous']['item_name'], '"><i class="icon i-chevron-circle-left"></i>', $txt['lgal_previous'], '</a>
				</span>';
	}
	if (!empty($context['prev_next']['next']))
	{
		echo '
				<span class="righttext" style="width: 50%">
					<a href="', $context['prev_next']['next']['item_url'], '#item_main" title="', $context['prev_next']['next']['item_name'], '">', $txt['lgal_next'], '<i class="icon i-chevron-circle-right"></i></a>
				</span>';
	}

	echo '
			</h2>';
}

function template_main_item_sidebar()
{
	global $context, $txt, $memberContext, $scripturl;

	echo '
		<div id="album_sidebar">
			<h3 class="secondary_header">
				', $txt['lgal_item_info'], '
			</h3>
			<div class="content">';
	if (!empty($context['item_owner']) && is_int($context['item_owner']))
	{
		echo '
				<div class="posted_by">', $txt['lgal_posted_by'], '</div>
				<div class="album_owner">
					<span class="user_avatar">', $memberContext[$context['item_owner']]['avatar']['image'], '</span>
					<span class="user">', $memberContext[$context['item_owner']]['link'], '</span>';
		if (!empty($context['item_owner_link']))
		{
			echo '
					<br />
					<span class="user">', sprintf($txt['lgal_see_more'], $scripturl . '?action=profile;area=mediasummary;u=' . $context['item_owner']), '</span>';
		}
		echo '
				</div>';
	}
	elseif (!empty($context['item_owner']))
	{
		echo '
				<div class="posted_by">', $txt['lgal_posted_by'], '</div><br />
				<div class="album_owner">
					<span class="user_avatar"></span>
					<span class="user">', $context['item_owner'], '</span>
					<br class="clear" />
				</div>';
	}

	echo '
				<hr />
				<div class="album_details">';

	if (!empty($context['item_details']['time_added']))
	{
		echo '
					<div><strong>', $txt['lgal_time_added'], '</strong><br>
					', $context['item_details']['time_added_format'], '</div>';
	}

	if ($context['item_details']['time_added'] < $context['item_details']['time_updated'])
	{
		echo '
					<div><strong>', $txt['lgal_time_updated'], '</strong><br>
					', $context['item_details']['time_updated_format'], '</div>';
	}

	if (!empty($context['item_display']['display_string']) && !empty($txt[$context['item_display']['display_string']]))
	{
		echo '
					<div><strong>', $txt[$context['item_display']['display_string']], '</strong>
					', $context['item_display']['display_value'], '</div>';
	}

	if (!empty($context['item_display']['display_size']))
	{
		echo '
					<div><strong>', $txt['lgal_file_size'], '</strong>
					', $context['item_display']['display_size'], '</div>';
	}

	if (!empty($context['item_details']['extension']))
	{
		echo '
					<div><strong>', $txt['lgal_file_type'], '</strong>
					', strtoupper($context['item_details']['extension']), '</div>';
	}

	template_display_custom_fields('main');

	echo '
				</div>
			</div>';

	if (!empty($context['item_display']['meta']) || !empty($context['item_display']['custom_fields']['meta']))
	{
		loadLanguage('Admin');
		template_main_item_sidebar_meta();
	}

	if (!empty($context['item_actions']))
	{
		template_sidebar_action_list($txt['lgal_item_actions'], $context['item_actions']);
	}

	template_sidebar_share();

	echo '
		</div>';
}

function template_main_item_sidebar_meta()
{
	global $context, $txt;

	echo '
			<h3 class="secondary_header panel_toggle">
				<span>
					<span id="sidebar_meta_toggle" class="chevricon i-chevron-up hide" title="', $txt['hide'], '"></span>
				</span>
				<a href="#" id="sidebar_meta_toggle_link" >', $txt['exclude_these'], '</a>
			</h3>
			<div class="content hide" id="sidebar_meta_container">
				<div class="album_details">';

	if (!empty($context['item_display']['meta']))
	{
		foreach ($context['item_display']['meta'] as $key => $value)
		{
			if (isset($txt['lgal_metadata_' . $key]))
			{
				echo '
					<div><strong>', $txt['lgal_metadata_' . $key], '</strong>
					', $value, '</div>';
			}
		}
	}

	template_display_custom_fields('meta');

	echo '
				</div>
			</div>
			<script>
			var oMetaToggle = new elk_Toggle({
				bToggleEnabled: true,
				bCurrentlyCollapsed: true,
				aSwappableContainers: [\'sidebar_meta_container\'],
				aSwapClasses: [
					{
						sId: \'sidebar_meta_toggle\',
						classExpanded: \'chevricon i-chevron-up\',
						titleExpanded: ' . JavaScriptEscape($txt['hide']) . ',
						classCollapsed: \'chevricon i-chevron-down\',
						titleCollapsed: ' . JavaScriptEscape($txt['show']) . '
					}
				],
				aSwapLinks: [
					{
						sId: \'sidebar_meta_toggle_link\',
						msgExpanded: ', JavaScriptEscape($txt['lgal_additional_information']), ',
						msgCollapsed: ', JavaScriptEscape($txt['lgal_additional_information']), '
					}
				]
			});
			</script>';
}

function template_display_custom_fields($area)
{
	global $context;
	if (!empty($context['item_display']['custom_fields'][$area]))
	{
		foreach ($context['item_display']['custom_fields'][$area] as $field)
		{
			echo '
						<div><strong>', $field['field_name'], ':</strong>
						', $field['value'], '</div>';
		}
	}
}

function template_sidebar_share()
{
	global $context, $txt, $memberContext;

	$poster_name = empty($memberContext[$context['item_owner']]['name']) ? empty($context['item_owner']) ? $txt['not_applicable'] : $context['item_owner'] : ($memberContext[$context['item_owner']]['name']);
	echo '
			<h3 class="secondary_header">
				', $txt['lgal_share'], '
			</h3>
			<div class="content">
				<dl class="album_details">
					<dt>', $txt['lgal_share_simple_bbc'], '</dt>
					<dd id="lgal_share_simple_bbc_container" class="lgal_share">
						<input type="text" class="input_text" id="lgal_share_simple_bbc" value="[media]', $context['item_details']['id_item'], '[/media]" readonly="readonly" />
						<span class="lgalicon i-clipboard" title="', $txt['lgal_copy_to_clipboard'], '"></span>
					</dd>
					<dt>', $txt['lgal_share_complex_bbc'], '</dt>
					<dd id="lgal_share_complex_bbc_container" class="lgal_share">
						<input type="text" class="input_text" id="lgal_share_complex_bbc" value="', sprintf($txt['lgal_share_complex_bbc_entry'], $context['item_details']['id_item'], $context['item_details']['item_name'], $poster_name, $context['item_details']['time_added_format']), '" readonly="readonly" />
						<span class="lgalicon i-clipboard" title="', $txt['lgal_copy_to_clipboard'], '"></span>
					</dd>
					<dt>', $txt['lgal_share_page'], '</dt>
					<dd id="lgal_share_page_container" class="lgal_share">
						<input type="text" class="input_text" id="lgal_share_page" value="', $context['item_details']['item_url'], '" readonly="readonly" />
						<span class="lgalicon i-clipboard" title="', $txt['lgal_copy_to_clipboard'], '"></span>
					</dd>';

	if (!empty($context['social_icons']))
	{
		foreach ($context['social_icons'] as $actions)
		{
			echo '
					<dt>', $txt['lgal_share_social_media'], '</dt>
					<dd>
						<ul class="sidebar_actions">';

			foreach ($actions as $id_action => $action)
			{
				echo '
							<li>
								<a href="', $action[1], '"', empty($action[2]) ? '' : ' class="new_win" target="_blank"', empty($action['title']) ? '' : ' title="' . $action['title'] . '"', '>
									<span class="lgalicon i-', $id_action, '"></span>', $action[0], '
								</a>
							</li>';
			}

			echo '
						</ul>
					</dd>';
		}
	}

	echo '
				</dl>
			</div>';
}

function template_main_item_comments()
{
	global $context, $txt, $modSettings;

	echo '
			<div id="item_comments">
				<h2 class="secondary_header">', empty($modSettings['lgal_feed_enable_item'])
					? ''
					: '<a href="' . $context['item_details']['item_url'] . 'feed/"><span class="lgalicon i-rss"></span></a> ',
					sprintf($txt['lgal_comments'], comma_format($context['num_comments'])), '
				</h2>';
	if (!empty($context['item_pageindex']))
	{
		echo '
				<div class="pagesection">', $context['item_pageindex'], '</div>';
	}

	foreach ($context['item_comments'] as $id_comment => $comment)
	{
		echo '
				<div class="', $comment['approved'] ? 'content' : 'approvebg', ' comment">
					<div id="comment-', $id_comment, '">';

		if (!empty($comment['reported']))
		{
			echo '
						<div class="centertext">', $txt['lgal_comment_was_reported'], '</div>';
		}

		if (!$comment['approved'])
		{
			echo '
						<div class="centertext">', $txt['lgal_unapproved_comment'], '</div>';
		}

		echo '
						<div class="comment_info">';
		if (!empty($comment['avatar']))
		{
			echo '
							<div class="comment_avatar">', $comment['avatar'], '</div>';
		}

		echo '
							<div class="comment_poster">', $comment['author_link'], '</div>
						</div>';
		echo '
						<div class="comment_body">
							<div class="comment_time">
								<a href="', $context['item_details']['item_url'], $context['this_page'] != 1 ? 'page-' . $context['this_page'] . '/' : '', '#comment-', $id_comment, '">', $comment['time_added_format'], '</a>';
		if (!empty($comment['modified_name']) && !empty($comment['modified_time']))
		{
			echo '
								<br />
								', sprintf($txt['levgal_last_edit'], $comment['modified_time_format'], $comment['modified_name']);
		}

		echo '
							</div>
							', $comment['comment'], '
						</div>';

		if (!empty($comment['options']))
		{
			template_action_strip($comment['options']);
		}

		echo '		</div>
					<br class="clear" />
				</div>';
	}

	if (!empty($context['display_comment_reply']) && $context['display_comment_reply'] !== 'no')
	{
		template_main_item_commentbox();
	}

	echo '
			</div>';
}

function template_main_item_commentbox()
{
	global $context, $txt;

	echo '
				<div class="editor_wrapper">
					<form action="', $context['form_url'], '#postmodify" method="post" accept-charset="UTF-8" name="postmodify" id="postmodify" onsubmit="submitonce(this);smc_saveEntities(\'postmodify\', [\'', $context['comment_box']->getId(), '\']);" enctype="multipart/form-data">';

	if (!empty($context['comment_errors']))
	{
		template_lgal_error_list($txt['levgal_comment_error'], $context['comment_errors']);
	}

	if ($context['display_comment_reply'] === 'approval')
	{
		echo '
						<div class="centertext">', $txt['levgal_comment_waiting_approval'], '</div>';
	}

	$context['comment_box']->displayEditWindow();

	if ($context['user']['is_guest'])
	{
		echo '
						<strong>', $txt['name'], ':</strong> <input type="text" name="guest_username" value="', $context['comment_user_name'], '" size="25" class="input_text" tabindex="', $context['tabindex']++, '" />
						<strong>', $txt['email'], ':</strong> <input type="text" name="guest_email" value="', $context['comment_user_email'], '" size="25" class="input_text" tabindex="', $context['tabindex']++, '" /><br />';
	}

	if (!empty($context['verification']))
	{
		$context['verification']->output();
	}

	$context['comment_box']->displayButtons();

	echo '
					</form>
				</div>';
}

function template_mature_item()
{
	global $context, $txt, $settings;

	echo '
		<h3 class="secondary_header">', $context['page_title'], '</h3>
		<form action="', $context['form_url'], '" method="post" accept-charset="UTF-8">
			<div class="content">
				<div class="centertext">
					<img src="', $settings['default_theme_url'], '/levgal_res/icons/_mature.png" />
					<br />
					', $txt['lgal_item_is_mature'], '
					<br /><br />
					', $txt['lgal_item_is_mature_continue'], '
					<br /><br />
					<input type="submit" name="yes" value="', $txt['lgal_item_mature_accept'], '" class="button_submit" />
					<input type="submit" name="no" value="', $txt['lgal_item_mature_cancel'], '" class="button_submit" />
					<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
				</div>
			</div>
		</form>';
}

function template_notify_item()
{
	global $context, $txt;

	echo '
		<h3 class="secondary_header">', $context['page_title'], '</h3>
		<form action="', $context['form_url'], '" method="post" accept-charset="UTF-8">
			<div class="content">
				<div class="centertext">';
	if (!empty($context['item_urls']['thumb']))
	{
		echo '
					<img src="', $context['item_urls']['thumb'], '" alt="*" />
					<br />';
	}

	echo '
					', $txt['lgal_notify_item_desc'], '
					<br /><br />
					', $txt['lgal_notify_are_you_sure'], '
					<br /><br />
					<input type="submit" name="notify_yes" value="', $txt['lgal_notify'], '" class="button_submit" />
					<input type="submit" name="notify_no" value="', $txt['lgal_unnotify'], '" class="button_submit" />
					<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
				</div>
			</div>
		</form>';
}

function template_item_picture()
{
	global $context, $txt;

	// Maybe we're showing a preview of the thing, or maybe we're just inlining the thing.
	$using = empty($context['item_display']['urls']['preview']) ? 'raw' : 'preview';
	if (!empty($context['item_display']['needs_lightbox']))
	{
		echo '
					<div id="item_picture_container">
						<a class="glightbox" href="', $context['item_display']['urls']['raw'], '" data-glightbox="type: image" data-title="', $context['item_details']['item_name'], '" data-description=".custom-desc">
							<img id="item_picture_contained" class="item_', $using, ' has_lightbox" src="', $context['item_display']['urls'][$using], '" alt="" />
						</a>
					</div>
					<div class="centertext smalltext">', $txt['lgal_click_to_expand'], '</div>
					<div class="custom-desc hide">',
						$context['item_details']['description'], '
					</div>';
	}
	else
	{
		// If it's not big enough to need a preview, display the actual GIF in case it has animation.
		if ($context['item_details']['mime_type'] === 'image/gif')
		{
			$using = 'raw';
		}
		echo '
					<img id="item_picture" class="item_', $using, '" src="', $context['item_display']['urls'][$using], '" alt="" />';
	}
}

function template_item_audio()
{
	global $context;

	// So, there might be a preview image, or there might only be a thumbnail.
	// But one or other might be a generic icon fallback.
	// If there's a preview and it's not generic (or it is generic but so is the thumbnail one), use that.
	if (!empty($context['item_display']['urls']['preview']) && (empty($context['item_display']['urls']['generic']['preview']) || !empty($context['item_display']['urls']['generic']['thumb'])))
	{
		echo '
					<div id="item_audio_container">
						<img id="item_audio" class="audio_preview" src="', $context['item_display']['urls']['preview'], '" alt="" />
					</div>';
	}
	else
	{
		echo '
					<div id="item_audio_container">
						<img id="item_audio" class="audio_thumb" src="', $context['item_display']['urls']['thumb'], '" alt="" />
					</div>';
	}

	// Now for the music player.
	echo '
	<audio id="lgal_audio_player" controls preload="metadata">
		<source src="', $context['item_display']['urls']['raw'], '" type="', $context['item_details']['mime_type'], '">,
		Sorry, your browser doesn\'t support embedded audio
	</audio>';
}

function template_item_video()
{
	global $context;

	// We want to try to offer up some kind of poster if we can.
	$poster = $context['item_display']['urls']['thumb'];
	if (!empty($context['item_display']['urls']['preview']) && (empty($context['item_display']['urls']['generic']['preview']) || !empty($context['item_display']['urls']['generic']['thumb'])))
	{
		$poster = $context['item_display']['urls']['preview'];
	}

	// Now for the video player.
	echo '
	<video id="lgal_video_player" controls preload="metadata" poster="', $poster, '">
		<source src="', $context['item_display']['urls']['raw'], '" type="', $context['item_details']['mime_type'], '">,
		Sorry, your browser doesn\'t support embedded videos
	</video>';
}

function template_item_generic()
{
	global $context, $txt;

	$viewInline = isset($context['item_actions']['actions']['download'][1]) ?? '';

	// So, there might be a preview image, or there might only be a thumbnail.
	// But one or other might be a generic icon fallback.
	if (!empty($context['item_display']['urls']['preview']) && (empty($context['item_display']['urls']['generic']['preview']) || !empty($context['item_display']['urls']['generic']['thumb'])))
	{
		echo '
					<div>
						<a href="' . (!empty($viewInline) ? substr($context['item_actions']['actions']['download'][1], 0, -10) : '') . '">
							<img id="item_generic" class="generic_preview" src="', $context['item_display']['urls']['preview'], '" alt="" />
						</a>
					</div>
					<div class="centertext smalltext">', $txt['lgal_click_to_view'], '</div>';
	}
	else
	{
		echo '
					<div>
						<a href="' . (!empty($viewInline) ? substr($context['item_actions']['actions']['download'][1], 0, -10) : '') . '">
							<img id="item_generic" class="generic_thumb" src="', $context['item_display']['urls']['thumb'], '" alt="" />
						</a>
					</div>';
	}
}

function template_item_external()
{
	global $context;
	echo $context['item_display']['markup'];
}

function template_move_item()
{
	global $context, $txt;

	echo '
			<h3 class="secondary_header">', $context['page_title'], '</h3>
			<div class="content">
				<form action="', $context['form_url'], '" method="post" accept-charset="UTF-8">
					<div class="centertext">
						<img id="item_generic" class="generic_thumb" src="', $context['item_urls']['thumb'], '" alt="" />
						<div class="move_desc">
							', $txt['lgal_move_item_album'];
	template_display_hierarchy_dropdown();
	echo '
						</div>
						<div class="move_button">
							<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
							<input type="submit" value="', $txt['lgal_move_item_title'], '" class="button_submit" />
						</div>
					</div>
				</form>
			</div>';
}

function template_display_hierarchy_dropdown()
{
	global $context, $txt;

	echo '
								<select name="destalbum" tabindex="', $context['tabindex']++, '">';
	if (!empty($context['hierarchies']['site']))
	{
		echo '
									<optgroup label="', sprintf($txt['lgal_albums_owned_site'], $context['forum_name']), '">';
		foreach ($context['hierarchies']['site'] as $album)
		{
			$indent = $album['album_level'] == 0 ? '' : str_repeat('&nbsp; ', $album['album_level']);
			echo '
										<option value="', $album['id_album'], '"', $album['id_album'] == $context['item_details']['id_album'] ? ' selected="selected"' : '', '>', $indent, $album['album_name'], '</option>';
		}
		echo '
									</optgroup>';
	}

	if (!empty($context['hierarchies']['member']))
	{
		foreach ($context['hierarchies']['member'] as $member)
		{
			echo '
									<optgroup label="', sprintf($txt['lgal_albums_owned_someone'], $member['member_name']), '">';
			foreach ($member['albums'] as $album)
			{
				$indent = $album['album_level'] == 0 ? '' : str_repeat('&nbsp; ', $album['album_level']);
				echo '
										<option value="', $album['id_album'], '"', $album['id_album'] == $context['item_details']['id_album'] ? ' selected="selected"' : '', '>', $indent, $album['album_name'], '</option>';
			}
			echo '
									</optgroup>';
		}
	}

	if (!empty($context['hierarchies']['group']))
	{
		foreach ($context['hierarchies']['group'] as $group)
		{
			echo '
									<optgroup label="', sprintf($txt['lgal_albums_owned_someone'], $group['group_name']), '">';
			foreach ($group['albums'] as $album)
			{
				$indent = $album['album_level'] == 0 ? '' : str_repeat('&nbsp; ', $album['album_level']);
				echo '
										<option value="', $album['id_album'], '"', $album['id_album'] == $context['item_details']['id_album'] ? ' selected="selected"' : '', '>', $indent, $album['album_name'], '</option>';
			}
			echo '
									</optgroup>';
		}
	}

	echo '
								</select>';
}

function template_delete_item()
{
	global $context, $txt;

	echo '
			<h3 class="secondary_header">', $context['page_title'], '</h3>
			<div class="content">
				<form action="', $context['form_url'], '" method="post" accept-charset="UTF-8">
					<div class="centertext">
						<div class="delete_desc">', $txt['lgal_delete_item_desc'], '</div>
						<img id="item_generic" class="generic_thumb" src="', $context['item_urls']['thumb'], '" alt="" />
						<div class="delete_ays"><i class="icon i-warning"></i>', $txt['lgal_delete_item_are_you_sure'], '</div>
						<div class="delete_buttons">
							<input type="submit" name="delete" value="', $txt['lgal_delete_item_delete'], '" class="button_submit" />
							<input type="submit" name="cancel" value="', $txt['lgal_delete_item_cancel'], '" class="button_submit" />
							<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
						</div>
					</div>
				</form>
			</div>';
}

function template_flagitem()
{
	global $txt, $context, $scripturl;

	echo '
		<form action="', $context['form_url'], '" method="post" accept-charset="UTF-8" name="postmodify" id="postmodify" >
			<h3 class="secondary_header">', $context['page_title'], '</h3>
			<div class="well">
				<dl class="settings">
					<dt>', $txt['lgal_item_name'], '</dt>
					<dd><a href="', $context['item_details']['item_url'], '">', $context['item_details']['item_name'], '</a></dd>
					<dt>', $txt['lgal_posted_by'], '</dt>
					<dd>', empty($context['item_details']['id_member']) ? $context['item_details']['poster_name'] : '<a href="' . $scripturl . '?action=profile;u=' . $context['item_details']['id_member'] . '">' . $context['item_details']['poster_name'] . '</a>', '</dd>
					<dt>', $txt['lgal_time_added'], '</dt>
					<dd>', $context['item_details']['time_added_format'], '</dd>
				</dl>
				<br />
				<div class="centertext">
					', $txt['lgal_why_reporting_item'], '<br /><br />
					<textarea class="report_body" name="report_body"></textarea>
				</div>
				<br />
				<hr class="hrcolor clear" />
				<div class="righttext">
					<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
					<input type="submit" value="', $context['page_title'], '" class="button_submit" />
				</div>
			</div>
		</form>';
}

function template_edit_tag_list()
{
	global $context, $txt;

	// None defined and not allowed to add
	if (empty($context['can_add_tags']) && empty($context['tags']))
	{
		return;
	}

	echo '
		<dt class="clear_left">', $txt['lgal_tagged_as'], '</dt>
		<dd>
			<input type="text" placeholder="', $txt['lgal_item_tag_input'], '" class="flexdatalist" data-min-length="0" multiple="multiple" list="tags" id="tag" name="tag" />
			<span class="smalltext">', $txt['lgal_item_tag_description'], '</span>
			<datalist id="tags">';

	if (!empty($context['tags']))
	{
		foreach($context['tags'] as $tag)
		{
			echo '
				<option value="', strtolower($tag), '">', $tag, '</option>';
		}
	}

	echo '
			</datalist>
		</dd>';
}

function template_edit_item()
{
	global $context, $txt, $scripturl, $settings;

	echo '
			<h3 class="secondary_header">', $context['page_title'], '</h3>
			<form action="', $context['form_url'], '" method="post" accept-charset="UTF-8" name="postmodify" id="postmodify" onsubmit="submitonce(this);smc_saveEntities(\'postmodify\', [\'item_name\', \'item_slug\', \'', $context['description_box']->getId(), '\', \'guest_username\'], \'options\');" enctype="multipart/form-data">
				<div class="well">';

	// If an error occurred, explain what happened.
	template_lgal_error_list($txt['lgal_upload_failed_reason'], empty($context['errors']) ? array() : $context['errors']);

	echo '
					<dl id="post_header">
						<dt>', $txt['lgal_item_name'], '</dt>
						<dd>
							<input type="text" id="item_name" name="item_name" tabindex="', $context['tabindex']++, '" size="80" maxlength="80" class="input_text" value="', $context['item_name'], '" style="width: 95%;" />
						</dd>
						<dt class="clear_left">', $txt['lgal_item_slug'], '</dt>
						<dd>
							<span class="smalltext">', $scripturl, '?media/item/</span><input type="text" id="item_slug" name="item_slug" tabindex="', $context['tabindex']++, '" size="20" maxlength="40" class="input_text" value="', $context['item_slug'], '" /><span class="smalltext">.', $context['item_details']['id_item'], '/</span>
						</dd>';
	if (isset($context['poster_name']))
	{
		echo '
						<dt class="clear_left">', $txt['lgal_item_poster_name'], '</dt>
						<dd>
							<input type="text" name="guest_username" value="', $context['poster_name'], '" size="25" class="input_text" tabindex="', $context['tabindex']++, '" />
						</dd>';
	}

	template_edit_tag_list();

	if (!empty($context['hierarchies']))
	{
		echo '
						<dt class="clear_left">', $txt['lgal_posted_in'], '</dt>
						<dd>';
		template_display_hierarchy_dropdown();
		echo '
						</dd>';
	}
	echo '
					</dl>';

	if (!empty($context['custom_fields']))
	{
		echo '
					<hr />';
		$context['custom_field_model']->displayFieldInputs($context['custom_fields']);
	}

	echo '
					<hr id="upload_divider" />';

	if ($context['editing'] === 'file')
	{
		echo '
					<dl class="settings" style="min-height: 225px;">
						<dt>
							<div>', $txt['lgal_item_want_to_edit_file'], ':</div>
							<div id="allowed_type_file">';
		$display = array();
		foreach ($context['allowed_formats'] as $type => $formats)
		{
			$size_format = '';
			if (!empty($context['quota_data']['quotas'][$type]) && is_array($context['quota_data']['quotas'][$type]) && isset($context['quota_data']['quotas'][$type]['file']))
			{
				$size_format = ' (' . LevGal_Helper_Format::filesize($context['quota_data']['quotas'][$type]['file']) . ')';
			}
			$display[$type] = sprintf($txt['lgal_allowed_format_' . $type], implode('; ', $formats)) . $size_format;
		}
		echo '
								<div>', $txt['lgal_allowed_formats'], '</div>
								<div>', implode('<br />', $display), '</div>
							</div>
						</dt>
						<dd>
							<div id="display_container"></div>
							<div id="upload_type_file">';

		if (!empty($context['existing_upload']))
		{
			echo '
								', sprintf($txt['lgal_upload_already_uploaded'], $context['filename_display']), '
								<input type="hidden" name="async_filename" value="', $context['filename_post'], '" />
								<input type="hidden" name="async" value="', $context['async_id'], '" />
								<input type="hidden" name="async_size" value="', $context['async_size'], '" />';
		}
		else
		{
			echo '
								<div id="dragdropcontainer" class="dropzone"></div>
								<br class="clear" />';
		}

		echo '
							</div>
							<input type="hidden" id="upload_type" name="upload_type" value="file" />
						</dd>
					</dl>
					<hr />';

		// We don't want to tuft out all the JS if we're not using it so we do it here.
		$lang = array(
			'upload_failed' => $txt['lgal_upload_failed'] . ':: {{statusCode}}',
			'browser_not_supported' => $txt['lgal_browser_not_supported'],
			'not_allowed' => $txt['lgal_async_not_allowed'],
			'upload_no_title' => $txt['lgal_upload_no_title'],
			'upload_no_file' => $txt['lgal_upload_no_file'],
			'upload_no_link' => $txt['lgal_upload_no_link'],
			'upload_too_large' => $txt['lgal_upload_too_large'],
			'upload_image_too_big' => $txt['lgal_upload_image_too_big'],
			'uploading' => $txt['lgal_uploading'],
			'upload_complete' => $txt['lgal_upload_complete'],
			'item_drag_here' => $txt['lgal_item_drag_here'],
		);
		echo '
	<script>
		let txt = ' . json_encode($lang) . ',
			submittable = true,
			defaults = get_upload_defaults();
			
		let uploader = new Dropzone("#dragdropcontainer", {
			url: "' . $context['album_details']['album_url'] . 'async/",
			lgal_quota: ' . (empty($context['quota_data']) ? '{}' : json_encode($context['quota_data'])) . ',
			lgal_enable_resize: ' . (empty($context['lgal_enable_resize']) ? 'false' : 'true') . ',
			maxFiles: 1,
			paramName: defaults.paramName,
			chunking: defaults.chunking,
			retryChunks: defaults.retryChunks,
			parallelUploads: defaults.parallelUploads,
			parallelChunkUploads: defaults.parallelChunkUploads,
			chunkSize: defaults.chunkSize,
		 	dictDefaultMessage: txt.item_drag_here,
		  	dictFallbackMessage: txt.browser_not_supported,
			dictResponseError: txt.upload_failed,
			thumbnailMethod: "contain",	
			init: function() 
			{
				this.on("addedfile", function(file)
				{
					document.getElementById(\'errors\').style.display = "none";
					document.getElementById(\'display_container\').innerHTML = file.name;
				});
				this.on("success", function (file, response)
				{
					let container = document.getElementById("display_container"),
						conhtml = container.innerHTML;
					conhtml += \'<input type="hidden" name="async" value="\' + response.async + \'" />\';
					conhtml += \'<input type="hidden" name="async_size" value="\' + file.size + \'" />\';
					conhtml += \'<input type="hidden" name="async_filename" value="\' + encodeURIComponent(file.name) + \'" />\';
					container.innerHTML = conhtml;
					submittable = true;
				});
				this.on("sending", function(file, xhr, formData) 
      			{
      				formData.append("' . $context['session_var'] . '", "' . $context['session_id'] . '");
      				formData.append("async", file.upload.uuid);
				});
				this.on("error", function (file, msg, xhr)
				{
					submittable = false;
					msg = typeof msg !== "undefined" ? msg : txt.upload_failed;
					let response = {"error": msg, "fatal": false};
					if (typeof xhr !== "undefined")
					{
						response = JSON.parse(xhr.response);
						display_error(response.error, true);
					}
					if (response.fatal)
					{
						document.getElementById("upload_container").style.display = "none";
						document.getElementById("upload_divider").style.display = "none";
						uploader.disable();
					}
				});
			},
			accept: function(file, done) {
				this.on("thumbnail", function(file) {
					let result = addFileFilter(file, this.options.lgal_quota, this.options.lgal_enable_resize);
					if (result !== true)
					{
						done(result);
						display_error(result, true);
						setTimeout(() => {this.removeFile(file);}, 7000);
					}
					else
					{
						done();
					}
				});
				
				// If its not an image, trigger thumbnail manually so the accept checks run
				let ext = file.name.split(".").pop().toLowerCase();
				if (ext !== \'png\' || ext !== \'jpeg\' || ext !== \'jpg\')
				{
					let dataURL = get_upload_generic_thumbnail(file, this.options.lgal_quota);
					this.emit("thumbnail", file, dataURL);
				}
			},
			chunksUploaded: function(file, done)
			{
				// All chunks have been uploaded, now merge them for the file
				$.ajax({
					type: "POST",
					url: elk_prepareScriptUrl(elk_scripturl) + ' . JavaScriptEscape(str_replace($scripturl . '?', '', $context['album_details']['album_url']) . 'chunked/') . ',
					data: {
						async_chunks: file.upload.chunks.length,
						async_filename: file.name.php_urlencode(),
						async: file.upload.uuid,
						' . $context['session_var'] . ': "' . $context['session_id'] . '"
					},
					success: function () {
						done();
					},
					error: function (xhr) {
						data = xhr.responseJSON;
						file.accepted = false;
						submittable = false;
						
						display_error(data.error, true);
						document.querySelector("[data-dz-errormessage]").innerHTML = data.error;
						document.getElementsByClassName("dz-preview")[0].classList.add("dz-error");
						document.getElementsByClassName("dz-progress")[0].classList.add("hide");
						
						uploader.disable();
					}
    			});
			}
		})
		</script>';
	}
	elseif ($context['editing'] === 'link')
	{
		echo '
						<dl class="settings">
							<dt>
								<div>', $txt['lgal_item_want_to_add_link'], ':</div>
								<div id="allowed_type_link">
									', sprintf($txt['lgal_allowed_external'], implode(', ', $context['external_formats'])), '
								</div>
							</dt>
							<dd>
								<div id="upload_type_link">
									<input type="text" name="upload_url" id="upload_url" class="input_text" value="', $context['edit_url'], '" style="width: 95%;" />
								</div>
								<input type="hidden" id="upload_type" name="upload_type" value="link" />
							</dd>
						</dl>
						<hr />';
	}

	echo '
						<div class="upload_desc">', $txt['lgal_item_description'], '</div>';

	$context['description_box']->displayEditWindow();

	if (!empty($context['new_options']))
	{
		echo '
						<div id="postAdditionalOptionsHeader">
							', $txt['lgal_additional_options'], '
						</div>
						<div id="postMoreOptions" class="smalltext">
							<ul class="post_options">';
		foreach ($context['new_options'] as $opt_id => $option)
		{
			if ($option['type'] === 'checkbox')
			{
				echo '
								<li><label><input type="checkbox" name="', $opt_id, '" value="1"', empty($option['value']) ? '' : ' checked="checked"', ' class="input_check" />', $option['label'], '</label></li>';
			}
			elseif ($option['type'] === 'select')
			{
				echo '
								<li style="padding: 2px">
									<label>', $option['label'], '
									<select name="', $opt_id, '">';
				foreach ($option['opts'] as $k => $v)
				{
					echo '
										<option value="', $k, '"', $option['value'] == $k ? ' selected="selected"' : '', '>', $v, '</option>';
				}
				echo '
									</select></label>
								</li>';
			}
		}
		echo '
							</ul>
						</div>';
	}

	// Output buttons and session value.
	$context['description_box']->displayButtons();

	echo '
				</div>
				<input type="hidden" name="save" value="1" />
			</form>';
}
