<?php
// Version: 1.2.0; Levertine Gallery album template

/**
 * This file handles displaying the album pages.
 *
 * @package levgal
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 * @since 1.0
 *
 * @version 1.2.1 / elkarte
 */

function template_main_album_view()
{
	global $context;

	echo '
	<div id="gallery_contain">';

	if (!empty($context['album_actions']['actions']))
	{
		template_album_list_action_tabs($context['album_actions']);
	}

	template_main_album_sidebar();
	template_main_album_display();

	echo '
	</div>';
}

function template_main_tag_list()
{
	global $context, $txt;

	// None defined and not allowed to add
	if (empty($context['can_add_tags']) && empty($context['tags']))
	{
		return;
	}

	echo '
		<dt class="clear_left">', $txt['lgal_item_tag'], '<br /><span class="smalltext">', $txt['lgal_item_tag_description'], '</span></dt>
		<dd>
			<input type="text" placeholder="', $txt['lgal_item_tag_input'], '" class="flexdatalist" data-min-length="0" multiple="multiple" list="tags" id="tag" name="tag" />
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

function template_base_album_display()
{
	global $context, $txt, $scripturl;

	// Determine the total number of child albums for all owners of this album
	$child_album_counts = get_child_count($context['album_details']);

	echo '
			<div class="album_block content">
				<div class="album_thumbnail">
					<img src="', $context['album_details']['thumbnail_url'], '" alt="" />
				</div>
				<div class="album_block_details">
					<span class="largetext bbc_strong">', $context['album_details']['album_name'], '</span>
					<div>
						<span class="lgalicon i-album"></span> ', LevGal_Helper_Format::numstring('lgal_items', $context['album_details']['num_items']), ' / ', LevGal_Helper_Format::numstring('lgal_albums', $child_album_counts), '
					</div>
					<div class="album_block_description">', $context['album_details']['description'], '</div>
				</div>';

	// Provide some "back" navigation points
	$back = get_back_navigation();
	$parent = get_album_parent();
	echo '
				<span class="album_block_nav">';
	if ($back)
	{
		echo
					$txt['lgal_back_to_album'], ': <a class="linkbutton"  href="', $back['album_url'], '">', $back['album_name'], '</a> / ';
	}

	if ($parent && (!$back || $parent['album_url'] !== $back['album_url']))
	{
		echo
					$txt['lgal_go_to_parent_album'], ': <a class="linkbutton"  href="', $parent['album_url'], '">', $parent['album_name'], '</a>';
	}
	else
	{
		$title = $txt['lgal_albums_member'];
		$link = 'member';
		if (empty($context['album_owner']))
		{
			$link = 'site';
			$title = $txt['lgal_albums_site'];
		}
		elseif (!empty($context['album_owner']['group_details']))
		{
			$link = 'group';
			$title = $txt['lgal_albums_group'];
		}
		echo
					$txt['lgal_go_to_album'], ': <a class="linkbutton"  href="', $scripturl . '?media/albumlist/', $link, '">', $title, '</a>';
	}

	echo '
				</span>
			</div>';
}

function get_back_navigation()
{
	global $scripturl, $context;

	static $back;

	if (isset($back))
	{
		return $back;
	}

	if (empty($_SESSION['levgal_breadcrumbs']))
	{
		return null;
	}

	$id_album = (int) $context['album_details']['id_album'];

	// A previous known navigation point
	list($prev_slug, $prev_name) = current($_SESSION['levgal_breadcrumbs']);
	if (count($_SESSION['levgal_breadcrumbs']) > 1)
	{
		end($_SESSION['levgal_breadcrumbs']);
		list($prev_slug, $prev_name) = prev($_SESSION['levgal_breadcrumbs']);
	}
	$prev_album = (int) key($_SESSION['levgal_breadcrumbs']);
	if ($prev_album === $id_album)
	{
		return null;
	}

	$back['album_url'] = $scripturl . '?media/album/' . $prev_slug . '.' . $prev_album . '/';
	$back['album_name'] = $prev_name;
	$back['id_album'] = $prev_album;

	return $back;
}

function get_album_parent()
{
	global $context;

	static $parent;

	if (isset($parent))
	{
		return $parent;
	}

	// Determine the nearest parent of an album
	$hierarchy = $context['hierarchy'] ?? $context['album_family']['member'][0] ?? [];
	if (empty($hierarchy))
		return null;

	// Current album and its level
	$id_album = (int) $context['album_details']['id_album'];
	$current_level = (int) $hierarchy[$id_album]['album_level'];

	$parent = null;
	if ($current_level > 0)
	{
		// Find the closest parent in this tree
		if (empty($parent))
		{
			$key = array_search($id_album, array_keys($hierarchy), true);
			$slice = array_reverse(array_slice($hierarchy, 0, $key));
			foreach ($slice as $next)
			{
				if ($next['album_level'] < $current_level)
				{
					$parent = $next;
					break;
				}
			}
		}
	}

	return $parent;
}

function template_album_navigation()
{
	global $context, $txt, $memberContext, $scripturl;

	$id_album = (int) $context['album_details']['id_album'];
	$album_level = (int) ($context['hierarchy'][$id_album]['album_level'] ?? 0);
	$has_multiple_owners = false;

	foreach ($context['album_family'] as $owners)
	{
		if (empty($owners))
		{
			continue;
		}

		$has_multiple_owners = count($owners) > 1;
		if ($has_multiple_owners)
		{
			break;
		}
	}

	// Children of an album, remember each album owner may have their own album children
	echo '
		<h3 class="secondary_header">', $txt['lgal_album_family'], '</h3>
		<div class="child_container content">';

	foreach ($context['album_family'] as $owner_type => $owners)
	{
		if (empty($owners))
		{
			continue;
		}

		$owners = remove_duplicates($owners);

		foreach ($owners as $owner => $albums)
		{
			$title = '';
			$link = '';

			if ($has_multiple_owners)
			{
				if ($owner_type === 'member' && $owner === 0)
				{
					$title = sprintf($txt['lgal_albums_owned_site'], $context['forum_name']);
					$link = '?media/albumlist/site';
				}
				elseif ($owner_type === 'member')
				{
					$title = sprintf($txt['lgal_albums_owned_someone'], $memberContext[$owner]['link']);
					$link = '?media/albumlist/' . $owner . '/member/';
				}
				elseif ($owner_type === 'group')
				{
					$title = sprintf($txt['lgal_albums_owned_someone'], $context['album_owner']['group_details'][$owner]['color_name']);
					$link = '?media/albumlist/' . $owner . '/group/';
				}
			}

			echo '
			<strong>', $title, '</strong>
			<div class="album_family">';

			foreach ($albums as $album)
			{
				// Show the child albums
				if ($album['album_level'] > $album_level)
				{
					echo '
					<a class="album_child well" href="', $album['album_url'], '">
						<div>
							<img style="float: left;" src="', $album['thumbnail_url'], '" alt="cover" loading="lazy" />
							<p class="album_child_details">
								<span class="album_name">', $album['album_name'], '</span><br />
								<span class="album_block_description">', $album['description_short'],
								!empty($album['description_short']) ? '<br />' : '', '
									<span class="lgalicon i-album"></span> ', LevGal_Helper_Format::numstring('lgal_items', $album['num_items']),
									empty($album['sub_albums']) ? '' : ' / ' . LevGal_Helper_Format::numstring('lgal_albums', $album['sub_albums']), '
								</span>
							</p>
						</div>
					</a>';
				}
			}

			// If we have multiple owners, show a link back to that owners collection
			if ($has_multiple_owners)
			{
				echo '
				<div class="righttext" style="width:100%">', sprintf($txt['lgal_see_more'], $scripturl . $link), '</div>';
			}

			echo '
			</div>';
		}
	}

	echo '
		</div>';
}

function remove_duplicates($owners)
{
	// Experimental.
	// Shared ownership can show the same child album under different users
	// this collapses to only unique nav points.  Perhaps a bad idea?
	$back = get_back_navigation();
	$parent = get_album_parent();
	$extra_ids = array();
	$primary = null;
	$extra_ids[] = $back['id_album'] ?? 'xyz';
	$extra_ids[] = isset($parent['id_album']) ? (int) $parent['id_album'] : 'xyz';

	// Prefer the child albums of the navigation point
	if (isset($back['id_album']))
	{
		foreach ($owners as $id => $album)
		{
			if (isset($album[$back['id_album']]))
			{
				$primary = $id;
				$keys = array_keys($album);
				break;
			}
		}
	}

	// Otherwise, use the first owner
	if (empty($keys))
	{
		$keys = array_keys($owners);
		$primary = $keys[0];
		$keys = array_keys($owners[$primary]);
	}

	$keys = array_unique(array_merge($keys, $extra_ids), SORT_REGULAR);
	foreach ($owners as $id => $album)
	{
		if ($id === $primary)
		{
			continue;
		}

		foreach ($keys as $key)
		{
			foreach ($album as $ignored)
			{
				unset($owners[$id][$key]);
			}
		}
	}

	return array_filter($owners);
}

function template_main_album_display()
{
	global $context, $txt;

	echo '
		<div id="item_main">
			<h3 class="lgal_secondary_header secondary_header">', sprintf($txt['lgal_viewing_album'], '&nbsp;<strong>' . $context['album_details']['album_name'] . '</strong>'), '</h3>';

	// The album art cover, name, description
	template_base_album_display();

	if (!empty(get_child_count($context['album_details'])))
	{
		// Children of an album, remember each owner can have their own arrangement
		template_album_navigation();
	}

	// Pages and slideshow option
	if (!empty($context['album_items']) || !empty($context['album_pageindex']))
	{
		echo '
		<div class="levgal_navigation">';

		if (!empty($context['album_pageindex']))
		{
			echo '
			<div class="pagesection">', $context['album_pageindex'], '</div>';
		}

		if (!empty($context['album_items']))
		{
			echo '
			<a class="linkbutton" style="margin-left: auto" href="#" onclick="myGallery.open();">', $txt['lgal_click_to_slideshow'], '</a>';
		}

		echo '
		</div>';
	}

	if (empty($context['album_details']['approved']))
	{
		echo '
			<div class="approvebg">
				<div class="content">
					<div class="centertext">', $txt['lgal_unapproved_album'], '</div>
				</div>
			</div>
			<br />';
	}

	if (empty($context['album_items']))
	{
		if (!empty($context['album_family']) && isset($context['album_family']['album_count']))
		{
			echo '
			<h4 class="lgal_secondary_header secondary_header centertext">
				', $txt['lgal_see_albums'], '
			</h4>';

			foreach ($context['album_family'] as $owners)
			{
				if (empty($owners))
				{
					continue;
				}

				foreach ($owners as $albums)
				{
					array_shift($albums);
					$context['xyz'] = $albums;

					template_display_album_list('xyz');
				}
			}
		}
		else
		{
			echo '
			<h4 class="lgal_secondary_header secondary_header centertext">
				', $txt['lgal_empty_album'], '
			</h4>';
		}
	}
	else
	{
		template_item_list('album_items');
	}

	if (!empty($context['album_pageindex']))
	{
		echo '
			<div class="pagesection">', $context['album_pageindex'], '</div>';
	}

	echo '
		</div>';
}

function template_main_album_sidebar()
{
	global $context, $txt, $modSettings, $memberContext, $scripturl, $settings;

	// Start the sidebar container.
	echo '
		<div id="album_sidebar">';

	// Information block.
	echo '
			<h3 class="lgal_secondary_header secondary_header">
				', $txt['lgal_album_info'], empty($modSettings['lgal_feed_enable_album']) ? '' : ' <a href="' . $context['album_details']['album_url'] . 'feed/"><span class="lgalicon i-rss"></span></a>', '
			</h3>
			<div class="content">';

	if (!empty($context['album_owner']['member']))
	{
		echo '
				<div class="posted_by">', $txt['lgal_owned_by'], '</div>';
		foreach ($context['album_owner']['member'] as $user)
		{
			echo '
				<div class="album_owner">
					<span class="user_avatar">', $memberContext[$user]['avatar']['image'], '</span>
					<span class="user">', $memberContext[$user]['link'], '</span><br />
					<span class="user smalltext">', sprintf($txt['lgal_see_more'], $scripturl . '?media/albumlist/' . $user . '/member/'), '</span>
				</div>';
		}
	}
	elseif (!empty($context['album_owner']['group']))
	{
		echo '
				<div class="posted_by">', $txt['lgal_owned_by'], '</div>';
		foreach ($context['album_owner']['group_details'] as $group_id => $group)
		{
			echo '
				<div class="album_group">
					<div class="group_name">', $group['color_name'], '</div>
					<div class="group_stars">', $group['stars'], '</div>',
			sprintf($txt['lgal_see_more'], $scripturl . '?media/albumlist/' . $group_id . '/group/'), '
					<br class="clear" />
				</div>';
		}
	}
	// a site owned album, use site logo if available.
	elseif(!empty($context['header_logo_url_html_safe']))
	{
		echo '
				<div class="posted_by">', $txt['lgal_owned_by'], '</div>
				<div class="album_owner">
					<span class="user_avatar">
						<img class="avatar avatarresize" src="', $context['header_logo_url_html_safe'], '" alt="', $context['forum_name_html_safe'], '" />', '
					</span>
					<div class="">', $context['forum_name_html_safe'], '</div>
					<div class="user smalltext">', sprintf($txt['lgal_see_more'], $scripturl . '?media/albumlist/site/'), '</div>
				</div>
				<br />';
	}

	echo '
				<hr />
					', LevGal_Helper_Format::numstring('lgal_items', $context['album_details']['num_items']), ', ', LevGal_Helper_Format::numstring('lgal_comments', $context['album_details']['num_comments']);

	// If we're doing both, we need slightly different formatting.
	if (!empty($context['can_see_unapproved']['items']) && !empty($context['can_see_unapproved']['comments']))
	{
		echo '
				<br /><br />
				<div class="errorbox">
					', $txt['lgal_pending_approval'], '<br />
					', LevGal_Helper_Format::numstring('lgal_items', $context['can_see_unapproved']['items']), ', ', LevGal_Helper_Format::numstring('lgal_comments', $context['can_see_unapproved']['comments']), '
				</div>';
	}
	elseif (!empty($context['can_see_unapproved']['items']))
	{
		echo '
				<br /><br />
				<div class="errorbox">
					', $txt['lgal_pending_approval'], '<br />
					', LevGal_Helper_Format::numstring('lgal_items', $context['can_see_unapproved']['items']), '
				</div>';
	}
	elseif (!empty($context['can_see_unapproved']['comments']))
	{
		echo '
				<br /><br />
				<div class="errorbox">
					', $txt['lgal_pending_approval'], '<br />
					', LevGal_Helper_Format::numstring('lgal_comments', $context['can_see_unapproved']['comments']), '
				</div>';
	}

	echo '
			</div>';

	// Sidebar actions.
	if (!empty($context['album_actions']))
	{
		template_sidebar_action_list($txt['lgal_album_actions'], $context['album_actions']);
	}

	// Sidebar Sharing.
	template_main_item_sidebar_share();

	// Sorting albums.
	template_main_item_sidebar_sorting();

	// And end the sidebar container.
	echo '
		</div>';

	// Now we need the JavaScript for our copy to clipboard buttons.
	echo '
	<script src="', $settings['default_theme_url'], '/levgal_res/clipboard/clipboard.min.js"></script>
	<script>
		let items = ["lgal_share_simple_bbc"],
			parentEl,
			el;
		
		for (let i = 0, n = items.length; i < n; i++)
		{
			parentEl = document.querySelector("#" + items[i]  + "_container");
			el = document.querySelector("#" + items[i] + "_container span.i-clipboard");
			el.setAttribute("data-clipboard-target", "#" + items[i]);
			parentEl.addEventListener("mouseleave", lgalClearTooltip);
			parentEl.addEventListener("blur", lgalClearTooltip);
		}
		
		let clipboardSnippets = new ClipboardJS(".i-clipboard", {});
		
		clipboardSnippets.on("success", function(e) {
			let clip = e.trigger.parentElement;
			lgalShowTooltip(clip, "', $txt['lgal_copyied_to_clipboard'], '");
		});
	</script>';
}

function template_main_item_sidebar_share()
{
	global $context, $txt, $scripturl;

	echo '
			<h3 class="lgal_secondary_header secondary_header">
				', $txt['lgal_share'], '
			</h3>
			<div class="content" style="overflow: visible;">
				<dl class="album_details" style="overflow: visible;">
					<dt>', $txt['lgal_share_simple_bbc'], '
						<a href="', $scripturl, '?action=quickhelp;help=media_bbc" onclick="return reqOverlayDiv(this.href);" class="helpicon i-help"><s>', $txt['help'], '</s></a>
					</dt>
					<dd id="lgal_share_simple_bbc_container" class="lgal_share">
						<input type="text" class="input_text" id="lgal_share_simple_bbc" value="[media type=album]', $context['album_details']['id_album'], '[/media]" readonly="readonly" />
						<span class="lgalicon i-clipboard" title="', $txt['lgal_copy_to_clipboard'], '"></span>
					</dd>
				</dl>
			</div>';
}

function template_main_item_sidebar_sorting()
{
	global $context, $txt;

	echo '
			<h2 class="lgal_secondary_header secondary_header">
				', $txt['lgal_sorting_album'], '
			</h2>
			<div class="content">
				<dl class="album_details">
					<dt>', $txt['lgal_sort_by'], '</dt>
					<dd>';

	foreach ($context['sort_options'] as $sort)
	{
		$link = $context['album_details']['album_url'] . ($sort !== 'date' || $context['sort_criteria']['order'] !== 'desc' ? 'view_' . $sort . '_' . $context['sort_criteria']['order'] . '/' : '');
		echo '
						<span>&#9659;</span><a href="', $link, '">', $context['sort_criteria']['order_by'] === $sort ? '<strong>' . $txt['lgal_sort_by_' . $sort] . '</strong>' : $txt['lgal_sort_by_' . $sort], '</a><br />';
	}
	echo '
					</dd>
					<dt>', $txt['lgal_sort_direction'], '</dt>
					<dd>';
	foreach (array('asc', 'desc') as $order)
	{
		$link = $context['album_details']['album_url'] . ($context['sort_criteria']['order_by'] !== 'date' || $order !== 'desc' ? 'view_' . $context['sort_criteria']['order_by'] . '_' . $order . '/' : '');
		echo '
						<a href="', $link, '">', $order == $context['sort_criteria']['order'] ? '<strong>' . $txt['lgal_sort_direction_' . $order] . '</strong>' : $txt['lgal_sort_direction_' . $order], '</a><br />';
	}
	echo '
					</dd>
				</dl>
			</div>';
}

function template_add_single_item()
{
	global $context, $txt, $scripturl;

	/** @var $description_box \LevGal_Helper_Richtext */
	$description_box = $context['description_box'];

	echo '
			<h3 class="lgal_secondary_header secondary_header">
				', $context['page_title'], '
			</h3>
			<form action="', $context['album_details']['album_url'], 'add/" method="post" accept-charset="UTF-8" name="postmodify" id="postmodify" onsubmit="submitonce(this);smc_saveEntities(\'postmodify\', [\'item_name\', \'item_slug\', \'', $description_box->getId(), '\', \'guest_username\'], \'options\');" enctype="multipart/form-data">
			<div>
				<div class="well">';

	// If an error occurred, explain what happened.
	template_lgal_error_list($txt['lgal_upload_failed_reason'], empty($context['errors']) ? array() : $context['errors']);

	if (!empty($context['requires_approval']))
	{
		echo '
					<p class="information">', $txt['levgal_item_waiting_approval'], '</p>';
	}

	echo '
					<div class="infobox">', $txt['lgal_item_name_and_slug_auto'], '</div>

					<dl id="lgal_settings">
						<dt>', $txt['lgal_item_name'], '</dt>
						<dd>
							<input type="text" id="item_name" name="item_name" tabindex="', $context['tabindex']++, '" size="80" maxlength="80" class="input_text" value="', $context['item_name'], '" style="width: 95%;" />
						</dd>
						<dt class="clear_left">', $txt['lgal_item_slug'], '<br /><span class="smalltext">', $txt['lgal_item_slug_note'], '</span></dt>
						<dd>
							<span class="smalltext">', $scripturl, '?media/item/</span>
							<input type="text" id="item_slug" name="item_slug" tabindex="', $context['tabindex']++, '" size="20" maxlength="40" class="input_text" value="', $context['item_slug'], '" />
							<span class="smalltext">.x/</span>
						</dd>';

	template_main_tag_list();

	if ($context['user']['is_guest'])
	{
		echo '
						<dt class="clear_left">', $txt['lgal_item_poster_name'], '</dt>
						<dd>
							<input type="text" name="guest_username" value="', $context['item_posted_by'], '" size="25" class="input_text" tabindex="', $context['tabindex']++, '" />
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
					<hr id="upload_divider" />
					<dl class="settings" id="upload_container">
						<dt>';

	// Only show the option for both if we can select both.
	if (!empty($context['allowed_formats']) && !empty($context['external_formats']) && empty($context['existing_upload']))
	{
		echo '
							<select id="upload_type" name="upload_type" tabindex="', $context['tabindex']++, '" onchange="return switchUploadType(this.value);">
								<option value="file"', $context['upload_type'] === 'file' ? ' selected="selected"' : '', '>', $txt['lgal_item_want_to_add_file'], '</option>
								<option value="link"', $context['upload_type'] === 'link' ? ' selected="selected"' : '', '>', $txt['lgal_item_want_to_add_link'], '</option>
							</select>';
	}
	elseif (!empty($context['allowed_formats']) || !empty($context['existing_upload']))
	{
		echo '
							<div>', $txt['lgal_item_want_to_add_file'], '</div>
							<input type="hidden" id="upload_type" name="upload_type" value="file" />';
	}
	else
	{
		echo '
							<div>', $txt['lgal_item_want_to_add_link'], '</div>
							<input type="hidden" id="upload_type" name="upload_type" value="link" />';
	}

	// And now the listing of what is on offer.
	if (!empty($context['allowed_formats']))
	{
		echo '
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
							</div>';
	}
	else
	{
		echo '
							<div id="allowed_type_file"></div>';
	}

	if (!empty($context['external_formats']))
	{
		echo '
							<div id="allowed_type_link">
								', sprintf($txt['lgal_allowed_external'], implode(', ', $context['external_formats'])), '
							</div>';
	}
	else
	{
		echo '
							<div id="allowed_type_link"></div>';
	}

	echo '
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
								<input class="begin_button full" type="submit" value="', $txt['lgal_add_item'], '" onclick="return is_submittable() && submitThisOnce(this);" />';
	}

	echo '
							</div>
							<div id="upload_type_link">
								<input type="text" name="upload_url" id="upload_url" class="input_text" value="', $context['upload_url'], '" style="width: 95%;" />
							</div>
						</dd>
					</dl>
					<hr />
					<div class="upload_desc">', $txt['lgal_item_description'], '</div>';

	// Description box, Output buttons and session value.
	$description_box->displayEditWindow();
	$description_box->displayButtons();

	if (!empty($context['new_options']))
	{
		echo '
					<h3 class="lgal_secondary_header secondary_header" id="postAdditionalOptionsHeader">
						', $txt['lgal_additional_options'], '
					</h3>
					<div id="postMoreOptions" class="content">
						<ul class="post_options">';
		foreach ($context['new_options'] as $opt_id => $option)
		{
			echo '
							<li>
								<label>
									<input type="checkbox" name="', $opt_id, '" value="1"', empty($option['value']) ? '' : ' checked="checked"', ' class="input_check" />', $option['label'], '
								</label>
							</li>';
		}
		echo '
						</ul>
					</div>';
	}

	echo '
				</div>
			</div>
			<input type="hidden" name="save" value="1" />
		</form>';

	// Now for the fun stuff.
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
		'processing' => $txt['lgal_processing'],
		'processing_message' => $txt['lgal_processing_message'],
	);

	echo '
	<script>
		switchUploadType(document.getElementById("upload_type").value);
		var updateSlug = true;
		let submittable = ' . (empty($context['existing_upload']) ? 'false' : 'true') . ',
			defaults = get_upload_defaults(),
			itemName = document.getElementById("item_name"),
			itemSlug = document.getElementById("item_slug"),
			txt = ' . json_encode($lang) . ';
					
		function transLitSlug()
		{
			if (updateSlug)
			{
				let mystr = itemName.value;

				mystr = mystr.replace(/\'/g, "");
				itemSlug.value = url_slug(mystr, {}).substring(0, 50);
			}
		}
		
		createEventListener(itemName);
		itemName.addEventListener("keyup", transLitSlug, false);
		itemName.addEventListener("change", transLitSlug, false);
		createEventListener(itemSlug);
		itemSlug.addEventListener("keyup", function() { updateSlug = false; }, false);
		
		let uploader = new Dropzone("#dragdropcontainer", {
			url: "' . $context['album_details']['album_url'] . 'async/",
			lgal_quota: ' . (empty($context['quota_data']) ? '{}' : json_encode($context['quota_data'])) . ',
			lgal_enable_resize: ' . (empty($context['lgal_enable_resize']) ? 'false' : 'true') . ',
			maxFiles: 1,
			maxThumbnailFilesize: defaults.maxThumbnailFilesize,
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
					document.getElementById(\'upload_type\').value = "file";
					document.getElementById(\'upload_type\').disabled = true;
					document.querySelectorAll(".begin_button").forEach((elem) => {elem.style.display = "block"});

					if (itemName.value === \'\')
					{
						let mystr = file.name.replace(/_/g, \' \');
						itemName.value = mystr.replace(/\.[^/.]+$/, \'\');
						transLitSlug();
					}
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
					else
					{
						itemName.value = \'\';
						itemSlug.value = \'\';
						updateSlug = true;
					}
				});
			},
			accept: function(file, done) {
				// We do not have the width / height until thumbnail completes
				this.on("thumbnail", function(file, dataURL) {
					let result = addFileFilter(file, this.options.lgal_quota, this.options.lgal_enable_resize);
					if (result !== true)
					{
						done(result);
						display_error(result, true);
						setTimeout(() => {this.removeFile(file);}, 7000);
					}
					else
					{
						// This is for tiff files, maybe others, which fail on creating a client side thumbnail
						if (typeof dataURL === "object" && dataURL instanceof Event)
						{
							let dataURL = get_upload_generic_thumbnail(file, this.options.lgal_quota);
							file.previewElement.querySelector("img[data-dz-thumbnail]").src = dataURL;
						}
						
						done();
					}
				});
				
				// If not an image then trigger thumbnail manually, so accept checks run
				if (!file.type.match(/image.*/))
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
					}
				});
			}
		})
		</script>';
}

function template_add_bulk_items()
{
	global $context, $txt, $scripturl;

	echo '
			<h3 class="lgal_secondary_header secondary_header">
				', $context['page_title'], '
			</h3>							
			<form action="#" method="post" name="postmodify" id="postmodify" accept-charset="UTF-8">
				<div class="well">	
					<dl class="settings" id="upload_container">
						<dt>
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
							<div id="upload_type_file">
								<div id="dragdropcontainer" class="dropzone"></div>
								<input type="button" value="' . $txt['lgal_begin_upload'] . '" class="begin_button full" onclick="return beginUpload();" />
								<div id="levgal_preloader"><i class="levgal_preloader_icon"></i><div>
							</div>
						</dd>
					</dl>';

	// If an error occurred, we need somewhere for it to go.
	template_lgal_error_list($txt['lgal_upload_failed_reason'], empty($context['errors']) ? array() : $context['errors']);

	// This is the template for bulk uploads
	echo '
					<div class="dz-table files" id="previews">
						<div id="file_queue" class="file-row">
							<div class="dz-thumb">
								<span class="preview">
									<img data-dz-thumbnail />
								</span>
							</div>
							<div class="dz-name">
								<p class="name" data-dz-name></p>
								<strong class="error" data-dz-errormessage></strong>
							</div>
							<div class="dz-size">
								<p class="size" data-dz-size></p>
								<div class="progress active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
									<div class="progress-bar progress-bar-success" style="width:0%;" data-dz-uploadprogress></div>
								</div>
							</div>
							<div class="dz-remove">
								<p>
									<button data-dz-remove class="button_submit">
										<i class="icon i-delete"></i>
										<span>', $txt['lgal_item_remove_from_queue'], '</span>
									</button>
								</p>
							</div>
						</div>
					</div>
					<div>
						<div id="total-progress" class="progress active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
							<div class="progress-bar progress-bar-success" style="width:0%;" data-dz-uploadprogress></div>
						</div>
					</div>					
					<input type="button" value="' . $txt['lgal_begin_upload'] . '" style="display: none" class="right_submit begin_button" onclick="return beginUpload();" />
				</div>			
			</form>';

	$lang = array(
		'upload_failed' => $txt['lgal_upload_failed'],
		'browser_not_supported' => $txt['lgal_browser_not_supported'],
		'not_allowed' => $txt['lgal_async_not_allowed'],
		'upload_no_title' => $txt['lgal_upload_no_title'],
		'upload_no_file' => $txt['lgal_upload_no_file'],
		'upload_no_link' => $txt['lgal_upload_no_link'],
		'upload_too_large' => $txt['lgal_upload_too_large'],
		'upload_image_too_big' => $txt['lgal_upload_image_too_big'],
		'uploading' => $txt['lgal_uploading'],
		'upload_complete' => $txt['lgal_upload_complete'],
		'file_name' => $txt['lgal_file_name'],
		'file_size' => $txt['lgal_file_size'],
		'queue_empty' => $txt['lgal_queue_empty'],
		'size_kb' => $txt['lgal_size_kb'],
		'size_mb' => $txt['lgal_size_mb'],
		'size_gb' => $txt['lgal_size_gb'],
		'remove_from_queue' => $txt['lgal_item_remove_from_queue'],
		'begin_upload' => $txt['lgal_begin_upload'],
		'processing' => $txt['lgal_processing'],
		'view_item' => $txt['levgal_view_item'],
		'error_occurred' => $txt['lgal_upload_error_occurred'],
		'item_drag_here' => $txt['lgal_item_drag_here_multiple'],
	);

	echo '
	<script>
		// Fetch the template from, and then remove it from, the document
		let previewNode = document.querySelector("#file_queue");
		previewNode.id = "";
		let previewTemplate = previewNode.parentNode.innerHTML;
		previewNode.parentNode.removeChild(previewNode);
		
		let txt = ' . json_encode($lang) . ',
			urls = {},
			fileCount = 0,
			processedFiles = 0,
			defaults = get_upload_defaults();

		let uploader = new Dropzone("#dragdropcontainer", {
			url: "' . $context['album_details']['album_url'] . 'async/",
			lgal_quota: ' . (empty($context['quota_data']) ? '{}' : json_encode($context['quota_data'])) . ',
			lgal_enable_resize: ' . (empty($context['lgal_enable_resize']) ? 'false' : 'true') . ',
			maxFiles: 250,
			maxThumbnailFilesize: defaults.maxThumbnailFilesize,
			paramName: defaults.paramName,
			chunking: defaults.chunking,
			retryChunks: defaults.retryChunks,
			parallelUploads: defaults.parallelUploads,
			parallelChunkUploads: defaults.parallelChunkUploads,
			chunkSize: defaults.chunkSize,
			dictDefaultMessage: txt.item_drag_here,
			dictFallbackMessage: txt.browser_not_supported,
			dictResponseError: txt.upload_failed,
			previewTemplate: previewTemplate,
			autoProcessQueue: false,
			thumbnailWidth: 60,
			thumbnailHeight: 60,
			previewsContainer: "#previews",
			init: function()
			{
				this.on("addedfiles", function(files)
				{
					fileCount = fileCount + files.length;
					if (fileCount == processedFiles)
					{
						document.querySelectorAll(".begin_button").forEach((elem) => {elem.style.display = "block"});
					}
				});
				this.on("removedfile", function(file)
				{
					fileCount = fileCount - 1;
					processedFiles = processedFiles -1;
					sessionStorage.setItem(file.upload.uuid, file.name);
					if (fileCount === 0)
					{
						hide_levgal_preloader();
						document.querySelectorAll(".begin_button").forEach((elem) => {elem.style.display = "none"});
					}
				});
				this.on("totaluploadprogress", function(progress, totalBytes, totalBytesSent)
				{
					let byteProgress = "<span>(" + get_human_size(totalBytesSent) + " / " + get_human_size(totalBytes) + ")</span>",
						current = document.querySelector("#total-progress .progress-bar").style.width;

					current = parseInt(current.replace("%", ""));
					if (progress > current)
					{
						document.querySelector("#total-progress .progress-bar").innerHTML = byteProgress;
						document.querySelector("#total-progress .progress-bar").style.width = progress + "%";
					}
				});
				this.on("sending", function(file, xhr, formData)
				{
					if (sessionStorage.getItem(file.upload.uuid) === file.name)
					{
						formData.append("' . $context['session_var'] . '", "' . $context['session_id'] . '");
						formData.append("async", file.upload.uuid);
						formData.append("async_filename", file.name.php_urlencode());
					}

					document.getElementById("total-progress").style.opacity = "1";
				});
				this.on("success", function(file, response)
				{
					$.ajax({
						type: "POST",
						url: elk_prepareScriptUrl(elk_scripturl) + ' . JavaScriptEscape(str_replace($scripturl . '?', '', $context['album_details']['album_url']) . 'addbulk/') . ',
						data: {
							save: 1,
							async_filename: file.name.php_urlencode(),
							async: response.async,
							async_size: file.size,
							' . $context['session_var'] . ': "' . $context['session_id'] . '"
						},
						beforeSend: function() {
							let el = file.previewElement.querySelectorAll(".button_submit"),
								spanProgress = "";

							if (el.length)
							{
								spanProgress = "<span id=\"async_" + response.async + "\"><i class=\"icon icon-spin i-spinner\"></i>" + txt.processing + "</span>";
								el[0].parentElement.innerHTML = spanProgress;
							}
						},
						complete: function (xhr) {
							urls[file.upload.uuid] = {async: response.async, url: ""};
							onFileSend(xhr.responseJSON);
						},
						error: function () {
							file.accepted = false;
						}
					})
				});
				this.on("complete", function(file)
				{
					uploader.processQueue();
					sessionStorage.removeItem(file.upload.uuid);
				});
				this.on("error", function (file, msg, xhr)
				{
					msg = typeof msg !== "undefined" ? msg : txt.upload_failed;
					let response = {"error": msg, "fatal": false};
					if (typeof xhr !== "undefined")
					{
						response = JSON.parse(xhr.response);
					}
					if (response.fatal)
					{
						document.querySelector("#total-progress .progress-bar").innerHTML = txt.error_occurred;
						uploader.disable();
					}
				});
				this.on("thumbnail", function(file, dataURL) {
					processedFiles = processedFiles + 1;
					if (processedFiles >= fileCount)
					{
						document.querySelectorAll(".begin_button").forEach((elem) => {elem.style.display = "block"});
						hide_levgal_preloader();
					}
					
					let result = addFileFilter(file, this.options.lgal_quota, this.options.lgal_enable_resize);
					if (result !== true)
					{
						display_error(result, true);
						file.rejectDimensions(result);
					}
					else
					{
						// This is for tiff files, maybe others, which fail on creating a client side thumbnail
						if (typeof dataURL === "object" && dataURL instanceof Event)
						{
							let dataURL = get_upload_generic_thumbnail(file, this.options.lgal_quota);
							file.previewElement.querySelector("img[data-dz-thumbnail]").src = dataURL;
						}
						sessionStorage.setItem(file.upload.uuid, file.name);
						file.acceptDimensions();
					}
				});
			},
			accept: function(file, done)
			{
				// We do not have the width / height until the thumbnail is done, so set up
				// callbacks using the passed done function
				file.acceptDimensions = done;
				file.rejectDimensions = function(error) {done(error);};

				// If its not an image, trigger thumbnail manually so the accept checks run
				if (!file.type.match(/image.*/))
				{
					let dataURL = get_upload_generic_thumbnail(file, this.options.lgal_quota);
					this.emit("thumbnail", file, dataURL);
				}

				show_levgal_preloader();
			},
			chunksUploaded: function(file, done)
			{
				// All chunks have been uploaded, now merge all chunks for the currentFile
				$.ajax({
					type: "POST",
					url: elk_prepareScriptUrl(elk_scripturl) + ' . JavaScriptEscape(str_replace($scripturl . '?', '', $context['album_details']['album_url']) . 'chunked/') . ',
					data: {
						async_chunks: file.upload.chunks.length,
						async_filename: file.name.php_urlencode(),
						async: file.upload.uuid,
						' . $context['session_var'] . ': "' . $context['session_id'] . '"
					},
					success: function (dummy, dummy, xhr) {
						onChunkComplete(xhr);
						done();
					},
					error: function (xhr) {
						file.accepted = false;
						onChunkComplete(xhr);
						done();
					}
				});
			}
		})
	</script>';
}

function template_delete_album()
{
	global $context, $txt;

	echo '
			<h3 class="lgal_secondary_header secondary_header">', $context['page_title'], '</h3>
			<div class="content">
				<form action="', $context['form_url'], '" method="post" accept-charset="UTF-8">
					<div class="centertext">
						<div class="delete_desc">', $txt['lgal_delete_album_desc'], '</div>';

	if (!empty($context['album_details']['thumbnail_url']))
	{
		echo '
						<img id="item_generic" class="generic_thumb" src="', $context['album_details']['thumbnail_url'], '" alt="" />';
	}

	echo '
						<div class="delete_ays"><i class="icon i-warning"></i>', $txt['lgal_delete_album_are_you_sure'], '</div>
						<div>
							<input type="submit" name="delete" value="', $txt['lgal_delete_album_delete'], '" />
							<input type="submit" name="cancel" value="', $txt['lgal_delete_album_cancel'], '" />
							<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
						</div>
					</div>
				</form>
			</div>';
}

function template_notify_album()
{
	global $context, $txt;

	echo '
		<h3 class="lgal_secondary_header secondary_header">', $context['page_title'], '</h3>
		<form action="', $context['form_url'], '" method="post" accept-charset="UTF-8">
			<div class="content">
				<div class="centertext">';

	echo '
					', $txt['lgal_notify_album_desc'];

	if (!empty($context['album_details']['thumbnail_url']))
	{
		echo '
					<img id="item_generic" class="generic_thumb" src="', $context['album_details']['thumbnail_url'], '" alt="" />';
	}

	echo '
					', $txt['lgal_notify_are_you_sure'], '
					<br /><br />
					<input type="submit" name="notify_yes" value="', $txt['lgal_notify'], '" class="button_submit" />
					<input type="submit" name="notify_no" value="', $txt['lgal_unnotify'], '" class="button_submit" />
					<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
				</div>
			</div>
		</form>';
}

function template_edit_album()
{
	global $context, $txt, $scripturl;

	echo '
		<form action="', $context['destination'], '" method="post" accept-charset="UTF-8" name="postmodify" id="postmodify" onsubmit="submitonce(this);" enctype="multipart/form-data">
			<h2 class="lgal_secondary_header secondary_header">
				', $context['page_title'], '
			</h2>
			<p class="infobox">', $txt['lgal_edit_album_description'], '</p>';

	// Any errors?
	if (!empty($context['errors']))
	{
		template_lgal_error_list($txt['levgal_album_edit_error'], $context['errors']);
	}

	echo '
				<div class="well">';

	// First, general album details.
	echo '
				<dl class="lgal_settings">
					<dt>', $txt['levgal_album_name'], '</dt>
					<dd>
						<input type="text" id="album_name" name="album_name" tabindex="1" size="80" maxlength="80" class="input_text" value="', $context['album_details']['album_name'], '" style="width: 95%;" />
					</dd>
					<dt class="clear_left">', $txt['levgal_album_slug'], '<br /><span class="smalltext">', $txt['levgal_album_slug_note'], '</span></dt>
					<dd>
						<span class="smalltext">', $scripturl, '?media/album/</span>
						<input type="text" id="album_slug" name="album_slug" tabindex="2" size="20" maxlength="40" class="input_text" value="', $context['album_details']['album_slug'], '" />
						<span class="smalltext">.', $context['album_details']['id_album'], '/</span>
					</dd>';
	if ($context['display_featured'])
	{
		echo '
					<dt>', $txt['levgal_set_album_featured'], '</dt>
					<dd>
						<select name="feature">
							<option value="1"', $context['is_featured'] ? ' selected="selected"' : '', '>', $txt['yes'], '</option>
							<option value="0"', $context['is_featured'] ? '' : ' selected="selected"', '>', $txt['no'], '</option>
						</select>
					</dd>';
	}

	echo '
					<dt>', $txt['levgal_album_lock_for_items'], '</dt>
					<dd>
						<select name="lock_items">
							<option value="0"', $context['locked_for_items'] ? '' : ' selected="selected"', '>', $txt['yes'], '</option>
							<option value="1"', $context['locked_for_items'] ? ' selected="selected"' : '', '>', $txt['no'], '</option>
						</select>
					</dd>
					<dt>', $txt['levgal_album_lock_for_comments'], '</dt>
					<dd>
						<select name="lock_comments">
							<option value="0"', $context['locked_for_comments'] ? '' : ' selected="selected"', '>', $txt['yes'], '</option>
							<option value="1"', $context['locked_for_comments'] ? ' selected="selected"' : '', '>', $txt['no'], '</option>
						</select>
					</dd>
					<dt>', $txt['levgal_album_default_sort'], '</dt>
					<dd>
						<select name="default_sort" id="default_sort">';

	foreach ($context['sort_options'] as $sort)
	{
		echo '
							<option value="', $sort, '"', $context['order_by'] === $sort ? ' selected="selected"' : '', '>', $txt['lgal_sort_by_' . $sort], '</option>';
	}
	echo '
						</select>
						<select name="default_sort_direction">
							<option value="desc"', $context['order'] === 'desc' ? ' selected="selected"' : '', '>', $txt['lgal_sort_direction_desc'], '</option>
							<option value="asc"', $context['order'] === 'asc' ? ' selected="selected"' : '', '>', $txt['lgal_sort_direction_asc'], '</option>
						</select>
					</dd>
					<dt>', $txt['lgal_album_thumbnail'], '</dt>
					<dd>
						<select name="thumbnail_selector" id="thumbnail_selector" class="floatleft" onchange="update_thumbnail()">';
	foreach ($context['thumbnail_list'] as $thumbnail_group => $thumbnail)
	{
		if (is_array($thumbnail))
		{
			echo '
							<optgroup label="', $txt['lgal_thumbnail_' . $thumbnail_group], '">';
			foreach ($thumbnail as $id => $label)
			{
				echo '
								<option value="', $id, '">', $txt['lgal_thumbnail_icon_' . $label], '</option>';
			}
			echo '
							</optgroup>';
		}
		else
		{
			echo '
							<option value="', $thumbnail, '">', $txt['lgal_thumbnail_' . $thumbnail], '</option>';
		}
	}

	echo '
						</select>
						<div class="floatleft" id="thumbs_container">
							<img id="current_thumbnail" src="', $context['album_details']['thumbnail_url'], '" alt="current" />
							<span id="new_thumbnail" style="display:none"></span>
							<span id="upload_thumbnail" style="display:none">
								<input type="file" size="30" name="thumbnail" class="input_file" />
							</span>
						</div>
					</dd>
				</dl>
				<hr class="clear" />';

	// Ownership is crazy complicated. So complicated we don't even do it here.
	foreach ($context['ownership_blocks'] as $block)
	{
		$func = 'template_' . $block;
		$func();
	}

	// Privacy is complicated so we reuse the new-album version.
	template_newalbum_privacy();

	// Album description box & submit button
	template_newalbum_description();

	// Now the end of the form and the save button.
	echo '
			</div>
		</form>
		<script>
		function update_thumbnail()
		{
			let value = document.getElementById("thumbnail_selector").value,
				current_thumbnail = document.getElementById("current_thumbnail"),
				new_thumbnail_span = document.getElementById("new_thumbnail"),
				upload_span = document.getElementById("upload_thumbnail");
			if (value === "no_change")
			{
				current_thumbnail.style.display = "";
				new_thumbnail_span.style.display = "none";
				upload_span.style.display = "none";
			}
			else if (value === "upload")
			{
				current_thumbnail.style.display = "none";
				new_thumbnail_span.style.display = "none";
				upload_span.style.display = "";
			}
			else if (value.slice(0, 6) === "folder")
			{
				current_thumbnail.style.display = "none";
				new_thumbnail_span.style.display = "";
				new_thumbnail_span.innerHTML = \'<img src="\' + elk_default_theme_url + \'/levgal_res/albums/\' + value + \'?"\' + performance.now() + \' alt="" />\';
				upload_span.style.display = "none";
			}
		}

		function updatePrivacy()
		{
			document.getElementById("privacy_custom").style.display = (document.getElementById("privacy").value == "custom") ? "" : "none";
		}
		var privacySel = document.getElementById("privacy");
		createEventListener(privacySel);
		privacySel.addEventListener("change", updatePrivacy, false);
		updatePrivacy();';
	if (!empty($context['ownership']) && in_array('change_type', $context['ownership_blocks'], true))
	{
		echo '
	function updateOwnership()
	{
		var base_value = ', JavaScriptEscape($context['ownership_original']), ';
		var new_value = document.getElementById("ownership").value;

		var el = document.querySelectorAll(".ownership_member, .ownership_group");
		for (var i = 0, n = el.length; i < n; i++)
		{
			el[i].style.display = "none";
		}

		if (base_value == new_value)
		{
			var el = document.querySelectorAll(".ownership_" + new_value);
			for (var i = 0, n = el.length; i < n; i++)
			{
				el[i].style.display = "block";
			}
			document.querySelector("#configure_owner_member").style.display = "none";
			document.querySelector("#configure_owner_group").style.display = "none";
		}
		else
		{
			document.querySelector("#configure_owner_member").style.display = new_value == "member" ? "block" : "none";
			document.querySelector("#configure_owner_group").style.display = new_value == "group" ? "block" : "none";
		}
	}
	updateOwnership();';
	}

	echo '
		</script>';
}

function template_current_owners()
{
	global $context, $txt, $memberContext;

	if (!empty($context['album_owner']['group']))
	{
		$groups = array();
		foreach ($context['album_owner']['group'] as $group)
		{
			$groups[] = $context['album_owner']['group_details'][$group]['color_name'];
		}

		echo $txt['levgal_album_current_owners'], ' <strong>', implode(', ', $groups), '</strong>';
	}
	elseif (!empty($context['album_owner']['member']) && !in_array(0, $context['album_owner']['member']))
	{
		$members = array();
		$profile = allowedTo('profile_view_any');
		foreach ($context['album_owner']['member'] as $member)
		{
			if (!empty($memberContext[$member]))
			{
				$members[] = $profile ? $memberContext[$member]['link'] : $memberContext[$member]['name'];
			}
		}
		echo $txt['levgal_album_current_owners'], ' ', implode(', ', $members);
	}
	else
	{
		echo $txt['levgal_album_current_owners'], ' ', $txt['levgal_album_edit_ownership_site'];
	}
}

function template_add_owner_member()
{
	global $txt, $context, $settings;

	echo '
					<dl class="lgal_settings ownership_member">
						<dt>
							', $txt['levgal_album_add_owner'], '
							<div>';

	template_current_owners();

	echo '
							</div>
						</dt>
						<dd>
							<div>
								', $txt['levgal_album_member_to_add'], '
								<input id="add_member" type="text" name="add_member" value="', empty($context['add_member_display']) ? '' : '&quot;' . implode('&quot;, &quot;', $context['add_member_display']) . '&quot;', '" size="20" class="input_text" />
								<div id="add_member_container"></div>
							</div>
						</dd>
					</dl>
					<hr class="clear ownership_member" />';

	// We need some fancy JS for this.
	if (empty($context['loaded_autosuggest']))
	{
		$context['loaded_autosuggest'] = true;
		echo '
	<script type="text/javascript" src="', $settings['default_theme_url'], '/scripts/suggest.js?fin20"></script>';
	}

	echo '
	<script>
		var oAddMemberSuggest = new smc_AutoSuggest({
		sSelf: \'oAddMemberSuggest\',
		sSessionId: \'', $context['session_id'], '\',
		sSessionVar: \'', $context['session_var'], '\',
		sSuggestId: \'add_member\',
		sControlId: \'add_member\',
		sSearchType: \'member\',
		bItemList: true,
		sPostName: \'add_member_list\',
		sURLMask: \'action=profile;u=%item_id%\',
		sTextDeleteItem: \'', $txt['autosuggest_delete_item'], '\',
		sItemListContainerId: \'add_member_container\',
		aListItems: [';

	if (!empty($context['add_member_display']))
	{
		$i = 0;
		$count = count($context['add_member_display']);
		foreach ($context['add_member_display'] as $id_member => $member_name)
		{
			$i++;
			echo '
					{
						sItemId: ', JavaScriptEscape($id_member), ',
						sItemName: ', JavaScriptEscape($member_name), '
					}', $i == $count ? '' : ',';
		}
	}

	echo '
		]
	});
	</script>';
}

function template_remove_owner_member()
{
	global $context, $txt, $memberContext;

	echo '
					<dl class="lgal_settings ownership_member">
						<dt>', $txt['levgal_album_remove_owner'], '</dt>
						<dd>';
	foreach ($context['album_owner']['member'] as $member)
	{
		if (empty($memberContext[$member]))
		{
			continue;
		}

		echo '
							<label>
								<input type="checkbox" class="input_check" name="remove_member[', $member, ']" value="', $member, '"', in_array($member, $context['remove_member']) ? ' checked="checked"' : '', ' />
								', $memberContext[$member]['name'], '
							</label>
							<br />';
	}
	echo '
						</dd>
					</dl>
					<hr class="clear ownership_member" />';
}

function template_add_owner_group()
{
	global $context, $txt;

	$group_list = $context['group_list'];
	foreach ($context['album_owner']['group'] as $id_group)
	{
		unset ($group_list[$id_group]);
	}
	if (empty($group_list))
	{
		return;
	}

	echo '
					<dl class="lgal_settings ownership_group">
						<dt>
							', $txt['levgal_album_add_owner'], '
							<div>';

	template_current_owners();

	echo '
							</div>
						</dt>
						<dd>';
	foreach ($group_list as $id_group => $group)
	{
		echo '
							<label>
								<input type="checkbox" class="input_check" name="add_group[', $id_group, ']" value="', $id_group, '"', in_array($id_group, $context['add_group']) ? ' checked="checked"' : '', ' />
								', $group['color_name'], empty($group['stars']) ? '' : ' ' . $group['stars'], '
							</label>
							<br />';
	}
	echo '
						</dd>
					</dl>
					<hr class="clear ownership_group" />';
}

function template_remove_owner_group()
{
	global $context, $txt;

	echo '
					<dl class="lgal_settings ownership_group">
						<dt>', $txt['levgal_album_remove_owner'], '</dt>
						<dd>';
	foreach ($context['album_owner']['group'] as $group)
	{
		if (empty($context['album_owner']['group_details'][$group]))
		{
			continue;
		}

		echo '
							<label>
								<input type="checkbox" class="input_check" name="remove_group[', $group, ']" value="', $group, '"', in_array($group, $context['remove_group']) ? ' checked="checked"' : '', ' />
								', $context['album_owner']['group_details'][$group]['color_name'], empty($context['album_owner']['group_details'][$group]['stars']) ? '' : ' ' . $context['album_owner']['group_details'][$group]['stars'], '
							</label>
							<br />';
	}
	echo '
						</dd>
					</dl>
					<hr class="clear ownership_group" />';
}

function template_change_type()
{
	global $txt, $context, $settings;

	echo '
					<dl class="lgal_settings">
						<dt>
							', $txt['levgal_album_ownership'], '
							<div>';

	template_current_owners();

	echo '
							</div>
						</dt>
						<dd>
							<select id="ownership" name="ownership" onchange="updateOwnership();">';
	foreach ($context['ownership_opts'] as $opt)
	{
		echo '
								<option value="', $opt, '"', $context['ownership'] == $opt ? ' selected="selected"' : '', '>', $txt['levgal_album_edit_ownership_' . $opt], $opt !== 'site' && $context['ownership'] == $opt ? ' ' . $txt['levgal_ownership_no_change'] : '', '</option>';
	}
	echo '
							</select>
							<fieldset id="configure_owner_member">
								<input id="ownership_member" type="text" name="ownership_member" value="', empty($context['ownership_member_display']) ? '' : '&quot;' . implode('&quot;, &quot;', $context['ownership_member_display']) . '&quot;', '" size="20" class="input_text" />
								<div id="ownership_member_container"></div>
							</fieldset>
							<fieldset id="configure_owner_group">';
	foreach ($context['group_list'] as $id_group => $group)
	{
		echo '
								<label>
									<input type="checkbox" name="ownership_group[', $id_group, ']" value="', $id_group, '"', in_array($id_group, $context['ownership_data']) ? ' checked="checked"' : '', ' class="input_check" /> ', $group['color_name'], empty($group['stars']) ? '' : ' ' . $group['stars'], '
								</label><br />';
	}
	echo '
							</fieldset>
						</dd>
					</dl>
					<hr class="clear" />';

	// We need some fancy JS for this.
	if (empty($context['loaded_autosuggest']))
	{
		$context['loaded_autosuggest'] = true;
		echo '
	<script src="', $settings['default_theme_url'], '/scripts/suggest.js"></script>';
	}

	echo '
	<script>
	var oOwnershipMemberSuggest = new smc_AutoSuggest({
		sSelf: \'oOwnershipMemberSuggest\',
		sSessionId: \'', $context['session_id'], '\',
		sSessionVar: \'', $context['session_var'], '\',
		sSuggestId: \'ownership_member\',
		sControlId: \'ownership_member\',
		sSearchType: \'member\',
		bItemList: true,
		sPostName: \'ownership_member_list\',
		sURLMask: \'action=profile;u=%item_id%\',
		sTextDeleteItem: \'', $txt['autosuggest_delete_item'], '\',
		sItemListContainerId: \'ownership_member_container\',
		aListItems: [';

	if (!empty($context['ownership_member_display']))
	{
		$i = 0;
		$count = count($context['ownership_member_display']);
		foreach ($context['ownership_member_display'] as $id_member => $member_name)
		{
			$i++;
			echo '
					{
						sItemId: ', JavaScriptEscape($id_member), ',
						sItemName: ', JavaScriptEscape($member_name), '
					}', $i == $count ? '' : ',';
		}
	}

	echo '
		]
	});
	</script>';
}
