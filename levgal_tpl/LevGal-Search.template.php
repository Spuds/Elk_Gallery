<?php
// Version: 1.0.4; Levertine Gallery search template

/**
 * This file handles displaying the search functionality.
 *
 * @package levgal
 * @since 1.0
 */

function template_search()
{
	global $txt, $context, $settings;

	// Kick off the form behaviour
	echo '
		<form action="', $context['form_url'], '" method="post" accept-charset="UTF-8" name="searchform" id="searchform">
			<h3 class="category_header">', $txt['levgal_search'], '</h3>';

	// Errors if we had any.
	if (!empty($context['errors']))
	{
		template_lgal_error_list($txt['lgal_error_search'], $context['errors']);
	}

	// Kicking off the upper form area.
	echo '
			<fieldset id="advanced_search">
				<div class="well">';

	// The actual search text bit
	echo '
					<span class="enhanced">
						<strong>', $txt['lgal_search_for'], '</strong>
						<input type="text" name="search"', empty($context['search_text']) ? '' : ' value="' . $context['search_text'] . '"', ' maxlength="100" size="50" class="input_text" />
					</span>
					<br /><br />';

	// Left panel of options
	echo '
					<div>
						<ul class="ignoreboards floatleft">
							<li class="category">
								', $txt['lgal_search_by_member'], '
								<input id="search_member" type="text" name="search_member" value="', empty($context['search_member_display']) ? '' : '&quot;' . implode('&quot;, &quot;', $context['search_member_display']) . '&quot;', '" size="20" class="input_text" />
								<div id="member_container"></div>
							</li>
							<li class="category lefttext">';

	foreach (array('search_album_names', 'search_item_names', 'search_item_descs') as $type)
	{
		echo '
								<label>
									<input type="checkbox" name="', $type, '"', empty($context[$type]) ? '' : ' checked="checked"', ' class="input_check" /> ', $txt['lgal_' . $type], '
								</label><br />';
	}

	echo '
							</li>';

	if (!empty($context['searchable_fields']))
	{
		echo '
							<li class="category lefttext">';
		foreach ($context['searchable_fields'] as $field)
		{
			echo '
								<label><input type="checkbox" name="search_field_', $field['id_field'], '"', in_array($field['id_field'], $context['selected_fields']) ? ' checked="checked"' : '', ' class="input_check" /> ', sprintf($txt['lgal_search_in_field'], $field['field_name']), '</label><br />';
		}
		echo '
							</li>';
	}

	echo '
						</ul>';

	// Right panel of options
	echo '
						<ul class="ignoreboards floatright">
							<li class="category lefttext">';

	foreach ($context['search_types'] as $type)
	{
		echo '
								<label>
									<input type="checkbox" name="search_', $type, '"', in_array($type, $context['selected_search_types']) ? ' checked="checked"' : '', ' class="input_check" />
									', $txt['lgal_search_type_' . $type], '
								</label><br />';
	}
	echo '
							</li>
						</ul>
					</div>';

	echo '
					<br class="clear" />';

	// Finishing the upper search area.
	echo '
				</div>
			</fieldset>';

	// Beginning the select-album area
	echo '
			<fieldset class="flow_hidden">
				<div class="well">';

	// Selecting an album with collapse joy.
	echo '
					<h4 class="secondary_header">
						<span id="search_toggle" class="toggle_down"></span>';

	echo ' <a href="#" id="search_toggle_link">', $txt['lgal_search_by_album'], '</a>
					</h4>
					<div class="flow_auto" id="search_albums">
						<ul class="ignoreboards floatleft">';

	if (!empty($context['hierarchies']['site']))
	{
		template_display_subalbum(sprintf($txt['lgal_albums_owned_site'], $context['forum_name']), $context['hierarchies']['site'], 'site');
	}

	if (!empty($context['hierarchies']['member']))
	{
		foreach ($context['hierarchies']['member'] as $id_member => $member)
		{
			template_display_subalbum(sprintf($txt['lgal_albums_owned_someone'], $member['member_name']), $member['albums'], 'member_' . $id_member);
		}
	}

	if (!empty($context['hierarchies']['group']))
	{
		foreach ($context['hierarchies']['group'] as $id_group => $group)
		{
			template_display_subalbum(sprintf($txt['lgal_albums_owned_someone'], $group['group_name']), $group['albums'], 'group_' . $id_group);
		}
	}

	echo '
						</ul>
					</div>';

	// Ending the select-album area
	echo '
					<br class="clear" />
					<div class="padding">
						<input type="checkbox" name="all" id="check_all" value=""', $context['all_albums'] ? ' checked="checked"' : '', ' onclick="invertAll(this, this.form, \'alb\');" class="input_check floatleft" />
						<label for="check_all" class="floatleft">', $txt['check_all'], '</label>
						<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
						<input type="submit" name="submit" value="Search" class="button_submit floatright">
					</div>
					<br class="clear" />
				</div>
			</fieldset>';

	// And finishing off the form plus our JS.
	echo '
		</form>
		<script src="', $settings['default_theme_url'], '/scripts/suggest.js"></script>
		<script>
	var oMemberSuggest = new smc_AutoSuggest({
		sSelf: \'oMemberSuggest\',
		sSessionId: \'', $context['session_id'], '\',
		sSessionVar: \'', $context['session_var'], '\',
		sSuggestId: \'search_member\',
		sControlId: \'search_member\',
		sSearchType: \'member\',
		bItemList: true,
		sPostName: \'search_member_list\',
		sURLMask: \'action=profile;u=%item_id%\',
		sTextDeleteItem: \'', $txt['autosuggest_delete_item'], '\',
		sItemListContainerId: \'member_container\',
		aListItems: [';

	if (!empty($context['search_member_display']))
	{
		$i = 0;
		$count = count($context['search_member_display']);
		foreach ($context['search_member_display'] as $id_member => $member_name)
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
		var oMetaToggle = new elk_Toggle({
			bToggleEnabled: true,
			bCurrentlyCollapsed: ', $context['all_albums'] ? 'true' : 'false', ',
			aSwappableContainers: [\'search_albums\'],
			aSwapClasses: [
				{
					sId: \'search_toggle\',
					classExpanded: \'chevricon i-chevron-up\',
					titleExpanded: ' . JavaScriptEscape($txt['hide']) . ',
					classCollapsed: \'chevricon i-chevron-down\',
					titleCollapsed: ' . JavaScriptEscape($txt['show']) . '
				}
			],
			aSwapLinks: [
				{
					sId: \'search_toggle_link\',
					msgExpanded: ', JavaScriptEscape($txt['lgal_search_by_album']), ',
					msgCollapsed: ', JavaScriptEscape($txt['lgal_search_by_album']), '
				}
			]
		});

		function setAlbums(album, value)
		{
			var elements = document.querySelectorAll("input.album_" + album);
			for (var i = 0, n = elements.length; i < n; i++)
			{
				elements[i].checked = value;
			}
		}

		function selectAlbums(identifier, children)
		{
			var toggle = true;

			var elements = document.querySelectorAll("input." + identifier);
			for (var i = 0, n = elements.length; i < n; i++)
			{
				toggle = toggle & elements[i].checked;
			}

			for (i = 0, n = children.length; i < n; i++)
			{
				setAlbums(children[i], !toggle);
			}
		}
		</script>';
}

function template_display_subalbum($title, $albumlist, $identifier)
{
	global $context;
	static $i = 0, $limit = null;

	if ($limit === null)
	{
		$limit = ceil($context['album_count'] / 2);
	}

	echo '					<li class="category">
								<a href="javascript:void(0);" onclick="selectAlbums(', JavaScriptEscape($identifier), ', [', implode(', ', array_keys($albumlist)), ']); return false;">', $title, '</a>
								<ul>';

	foreach ($albumlist as $album)
	{
		if ($i == $limit)
		{
			echo '
								</ul>
							</li>
						</ul>
						<ul class="ignoreboards floatright">
							<li class="category">
								<ul>';
		}

		echo '
								<li class="board">
									<label style="margin-', $context['right_to_left'] ? 'right' : 'left', ': ', $album['album_level'], 'em;" for="alb', $identifier, '_', $album['id_album'], '"><input type="checkbox" id="alb', $identifier, '_', $album['id_album'], '" name="alb[', $identifier, '_', $album['id_album'], ']" value="', $album['id_album'], '"', $context['all_albums'] || in_array($album['id_album'], $context['selected_albums']) ? ' checked="checked"' : '', ' class="input_check album ', $identifier, ' album_', $album['id_album'], '" onchange="setAlbums(', $album['id_album'], ', this.checked);" /> ', $album['album_name'], '</label>
								</li>';

		$i++;
	}

	echo '
								</ul>
							</li>';
}

function template_no_results()
{
	global $context, $txt;

	echo '
		<h3 class="category_header">', $txt['lgal_search_results'], '</h3>
		<div class="content">', sprintf($txt['lgal_search_no_results'], $context['refine_link']), '</div>
		<br class="clear" />';
}

function template_search_results()
{
	global $context, $txt;

	$refine = true;

	if ($context['show_albums'])
	{
		echo '
		<h3 class="category_header">', $txt['lgal_search_results_albums'], '</h3>';

		if (!empty($context['search_albums']))
		{
			template_display_album_list('search_albums');
		}
		else
		{
			echo '
		<div class="content">', sprintf($txt['lgal_search_results_items_none'], $context['refine_link']), '</div>
		<br class="clear" />';
			$refine = false;
		}
	}

	if ($context['show_items'])
	{
		echo '
		<h3 class="category_header">', $txt['lgal_search_results_items'], '</h3>';

		if (!empty($context['search_items']))
		{
			template_item_list('search_items');
		}
		else
		{
			echo '
		<div class="content">', sprintf($txt['lgal_search_results_items_none'], $context['refine_link']), '</div>
		<br class="clear" />';
			$refine = false;
		}
	}

	if ($refine)
	{
		echo '
		<div class="content">', sprintf($txt['lgal_search_refine'], $context['refine_link']), '</div>
		<br class="clear" />';
	}
}
